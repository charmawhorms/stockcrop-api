<?php
session_start();
include 'config.php';

// --- INITIALIZATION ---
$cartItems = [];
$subtotal = 0.0;
$totalItems = 0;
$defaultImagePath = 'assets/default_product.png'; // Fallback image path

// --- LOGIC: DETERMINE CART SOURCE AND FETCH ITEMS ---

// Logged-in User Logic (Using the correct two-table structure)
if (isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];
    
    // p.imagePath added to the SELECT list to display images
    $sql = "SELECT ci.productId, ci.quantity, ci.price AS cartPrice, 
                   p.productName, p.unitOfSale, p.stockQuantity, p.imagePath, f.parish 
            FROM cartItems ci
            JOIN cart c ON ci.cartId = c.id
            JOIN products p ON ci.productId = p.id
            LEFT JOIN farmers f ON p.farmerId = f.id 
            WHERE c.userId = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $price = floatval($row['cartPrice']); 
            $quantity = intval($row['quantity']);
            $lineTotal = $quantity * $price;
            
            $subtotal += $lineTotal;
            $totalItems += $quantity;
            
            $cartItems[] = [
                'id' => intval($row['productId']),
                'productName' => htmlspecialchars($row['productName']),
                'price' => $price,
                'quantity' => $quantity,
                'unitOfSale' => htmlspecialchars($row['unitOfSale']),
                'imagePath' => htmlspecialchars($row['imagePath'] ?? $defaultImagePath), // Safely use default
                'parish' => htmlspecialchars($row['parish'] ?? 'N/A'), 
                'lineTotal' => $lineTotal,
                'stock' => intval($row['stockQuantity'])
            ];
        }
        mysqli_stmt_close($stmt);
    }

// Guest User Logic (Fetching product details for session cart)
} elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = implode(',', array_keys($_SESSION['cart']));
    
    if (!empty($productIds)) {
        $sql = "SELECT p.id, p.productName, p.price, p.unitOfSale, p.imagePath, p.stockQuantity, f.parish 
                FROM products p
                LEFT JOIN farmers f ON p.farmerId = f.id
                WHERE p.id IN ($productIds)";
        
        $result = mysqli_query($conn, $sql);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $quantity = $_SESSION['cart'][$row['id']];
            $price = floatval($row['price']);
            $lineTotal = $quantity * $price;
            
            $subtotal += $lineTotal;
            $totalItems += $quantity;
            
            $cartItems[] = [
                'id' => intval($row['id']),
                'productName' => htmlspecialchars($row['productName']),
                'price' => $price,
                'quantity' => intval($quantity),
                'unitOfSale' => htmlspecialchars($row['unitOfSale']),
                'imagePath' => htmlspecialchars($row['imagePath'] ?? $defaultImagePath),
                'parish' => htmlspecialchars($row['parish'] ?? 'N/A'),
                'lineTotal' => $lineTotal,
                'stock' => intval($row['stockQuantity'])
            ];
        }
    }
}

$response = [
    'itemsHtml' => '',
    'footerHtml' => '',
    'newCount' => $totalItems
];


ob_start(); // Start output buffering
if (empty($cartItems)): ?>
    <div class="text-center p-5">
        <span class="material-symbols-outlined display-3 text-muted">shopping_cart_off</span>
        <p class="lead mt-3">Your cart is empty.</p>
        <a href="shop.php" class="btn btn-success">Go to Shop</a>
    </div>
<?php else: ?>
    <ul class="list-group list-group-flush border-bottom">
        <?php foreach ($cartItems as $item): 
            $maxQty = $item['stock'] > 0 ? $item['stock'] : 99; 
            $priceFormatted = number_format($item['price'], 2);
            $totalFormatted = number_format($item['lineTotal'], 2);
        ?>
            <li class="list-group-item d-flex align-items-center justify-content-between p-3">
                <div class="d-flex align-items-start flex-grow-1 me-2">
                    <img src="<?= $item['imagePath'] ?>" 
                         class="rounded me-2 flex-shrink-0" 
                         style="width: 50px; height: 50px; object-fit: cover;" 
                         alt="<?= $item['productName'] ?>">
                    <div>
                        <h6 class="mb-1 fw-semibold text-truncate" style="max-width: 150px;"><?= $item['productName'] ?></h6>
                        
                        <small class="text-success fw-bold">$<?= $priceFormatted ?> / <?= $item['unitOfSale'] ?></small>
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="d-flex align-items-center justify-content-end mb-1">
                        <button class="btn btn-sm btn-outline-success p-0 px-1" 
                                onclick="updateCartPanelQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                        <span class="mx-2 fw-bold" style="min-width: 15px;"><?= $item['quantity'] ?></span>
                        <button class="btn btn-sm btn-outline-success p-0 px-1" 
                                onclick="updateCartPanelQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>, <?= $maxQty ?>)"
                                <?= $item['quantity'] >= $maxQty ? 'disabled' : '' ?>>+</button>
                    </div>
                    
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif;

$response['itemsHtml'] = ob_get_clean(); // Capture items

// --- BUILD FOOTER HTML ---
if (!empty($cartItems)):
    ob_start(); ?>
    <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
        <span>Subtotal (<?= $totalItems ?> items)</span>
        <span class="text-success">$<?= number_format($subtotal, 2) ?></span>
    </div>
    <a href="checkout.php" class="btn btn-success btn-lg w-100 mb-2">Proceed to Checkout</a>
    <a href="cart.php" class="btn btn-outline-success w-100 btn-sm">Manage Full Cart</a>
    <?php 
    $response['footerHtml'] = ob_get_clean();
endif;

// --- RETURN JSON ---
header('Content-Type: application/json');
echo json_encode($response);