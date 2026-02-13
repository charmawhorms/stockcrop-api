<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$cartData = [];
$productIds = [];

// --- Determine Cart Source ---
if (isset($_SESSION['id'])) {
    // 1. LOGGED-IN USER: Get cart items from the database
    $userId = $_SESSION['id'];
    
    // Join cart header with cartItems
    $stmt = mysqli_prepare($conn, "
        SELECT ci.productId, ci.quantity, ci.price 
        FROM cartItems ci
        JOIN cart c ON ci.cartId = c.id
        WHERE c.userId = ?
    ");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $productIds[] = $row['productId'];
            // Store the quantity and the *price stored in the cartItems table*
            $cartData[$row['productId']] = [
                'quantity' => $row['quantity'],
                'price' => floatval($row['price'])
            ];
        }
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    // 2. GUEST USER: Get cart items from the session array
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $productIds[] = $productId;
        // GUEST: Only quantity is stored in session. Price must be fetched later.
        $cartData[$productId] = ['quantity' => $quantity]; 
    }
}

// --- Fetch Product Details for all items in the cart ---
if (!empty($productIds)) {
    // Prepare product ID string for IN clause
    $idString = implode(',', array_map('intval', $productIds));

    $sql = "
        SELECT p.id, p.productName, p.price, p.unitOfSale, p.imagePath, p.stockQuantity, f.parish
        FROM products p
        JOIN farmers f ON p.farmerId = f.id
        WHERE p.id IN ($idString)
    ";
    
    $productResult = mysqli_query($conn, $sql);

    while ($product = mysqli_fetch_assoc($productResult)) {
        $id = $product['id'];
        
        // Use price from products table ONLY if the cartData doesn't have it (Guest user)
        $itemPrice = $cartData[$id]['price'] ?? floatval($product['price']);
        
        // Merge product details with cart quantity
        $cartData[$id] = array_merge($cartData[$id], [
            'id' => intval($product['id']), 
            'productName' => htmlspecialchars($product['productName']),
            'price' => $itemPrice, // Use item price (cartItems or products)
            'unitOfSale' => htmlspecialchars($product['unitOfSale']),
            'imagePath' => htmlspecialchars($product['imagePath']),
            'stockQuantity' => intval($product['stockQuantity']),
            'parish' => htmlspecialchars($product['parish']),
            'lineTotal' => round($cartData[$id]['quantity'] * $itemPrice, 2)
        ]);
    }
}

// Convert the associative array (key=productId) into an indexed array for easier JavaScript iteration
echo json_encode(['items' => array_values($cartData)]);
?>