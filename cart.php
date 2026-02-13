<?php
session_start();
include 'config.php';

// --- ADD TO CART (AJAX OR DIRECT) ---
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] == 1;
$productId = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($productId > 0 && $quantity > 0) {
    if (isset($_SESSION['id'])) {
        $userId = $_SESSION['id'];

        // Find or create cart
        $cartQuery = mysqli_prepare($conn, "SELECT id FROM cart WHERE userId = ?");
        mysqli_stmt_bind_param($cartQuery, "i", $userId);
        mysqli_stmt_execute($cartQuery);
        $cartResult = mysqli_stmt_get_result($cartQuery);

        if ($cartRow = mysqli_fetch_assoc($cartResult)) {
            $cartId = $cartRow['id'];
        } else {
            $insertCart = mysqli_prepare($conn, "INSERT INTO cart (userId) VALUES (?)");
            mysqli_stmt_bind_param($insertCart, "i", $userId);
            mysqli_stmt_execute($insertCart);
            $cartId = mysqli_insert_id($conn);
            mysqli_stmt_close($insertCart);
        }
        mysqli_stmt_close($cartQuery);

        // Get product price
        $priceQuery = mysqli_prepare($conn, "SELECT price FROM products WHERE id = ?");
        mysqli_stmt_bind_param($priceQuery, "i", $productId);
        mysqli_stmt_execute($priceQuery);
        $productPrice = mysqli_fetch_assoc(mysqli_stmt_get_result($priceQuery))['price'];
        mysqli_stmt_close($priceQuery);

        // Check if item exists
        $itemQuery = mysqli_prepare($conn, "SELECT quantity FROM cartItems WHERE cartId = ? AND productId = ?");
        mysqli_stmt_bind_param($itemQuery, "ii", $cartId, $productId);
        mysqli_stmt_execute($itemQuery);
        $itemResult = mysqli_stmt_get_result($itemQuery);

        if ($itemRow = mysqli_fetch_assoc($itemResult)) {
            $newQty = $itemRow['quantity'] + $quantity;
            $updateItem = mysqli_prepare($conn, "UPDATE cartItems SET quantity = ? WHERE cartId = ? AND productId = ?");
            mysqli_stmt_bind_param($updateItem, "iii", $newQty, $cartId, $productId);
            mysqli_stmt_execute($updateItem);
            mysqli_stmt_close($updateItem);
        } else {
            $insertItem = mysqli_prepare($conn, "INSERT INTO cartItems (cartId, productId, quantity, price) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($insertItem, "iiid", $cartId, $productId, $quantity, $productPrice);
            mysqli_stmt_execute($insertItem);
            mysqli_stmt_close($insertItem);
        }
        mysqli_stmt_close($itemQuery);

        // Get total item count
        $countQuery = mysqli_prepare($conn, "SELECT SUM(quantity) as total FROM cartItems WHERE cartId = ?");
        mysqli_stmt_bind_param($countQuery, "i", $cartId);
        mysqli_stmt_execute($countQuery);
        $countResult = mysqli_stmt_get_result($countQuery);
        $newCount = intval(mysqli_fetch_assoc($countResult)['total'] ?? 0);
        mysqli_stmt_close($countQuery);

    } else {
        // Guest cart (session)
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        $newCount = array_sum($_SESSION['cart']);
    }

    $response = ['status' => 'success', 'message' => 'Added to cart', 'newCount' => $newCount];
}

// Return JSON if AJAX
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>StockCrop | Your Cart</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .cart-image { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-5">
    <h1 class="display-6 mb-4 text-success fw-bold">Your Shopping Cart</h1>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th>Product</th>
                                    <th>Price / Unit</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Loading cart items...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 80px;">
                <div class="card-header bg-success text-white fw-bold">Order Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<span id="itemCount">0</span> items)</span>
                        <span id="cartSubtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span>Shipping</span>
                        <span>$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3 fw-bold fs-5">
                        <span>Order Total</span>
                        <span id="cartTotal">$0.00</span>
                    </div>
                    <a href="checkout.php" id="checkoutBtn" class="btn btn-success btn-lg w-100 mt-4">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </div>

    <div id="emptyCartMessage" class="alert alert-info text-center d-none mt-4">
        Your cart is currently empty. <a href="shop.php" class="alert-link">Start shopping now!</a>
    </div>
</div>
<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Fetch & Render Cart Items ---
document.addEventListener('DOMContentLoaded', fetchCartItems);

function updateNavbarCartCount(newCount) {
    const badge = document.querySelector('#cart-count');
    if (badge) badge.textContent = newCount;
}

function fetchCartItems() {
    fetch('getCartItems.php')
        .then(res => res.json())
        .then(data => renderCart(data.items))
        .catch(() => {
            document.getElementById('cartTableBody').innerHTML =
                '<tr><td colspan="5" class="text-center text-danger py-5">Error loading cart.</td></tr>';
        });
}

function renderCart(items) {
    const body = document.getElementById('cartTableBody');
    const subtotalEl = document.getElementById('cartSubtotal');
    const totalEl = document.getElementById('cartTotal');
    const countEl = document.getElementById('itemCount');
    const emptyMsg = document.getElementById('emptyCartMessage');

    body.innerHTML = '';
    if (items.length === 0) {
        emptyMsg.classList.remove('d-none');
        subtotalEl.textContent = totalEl.textContent = '$0.00';
        countEl.textContent = '0';
        return;
    }
    emptyMsg.classList.add('d-none');

    let subtotal = 0, totalItems = 0;
    items.forEach(item => {
        subtotal += item.lineTotal;
        totalItems += item.quantity;
        body.innerHTML += `
            <tr>
                <td><img src="${item.imagePath}" class="cart-image me-2">${item.productName}</td>
                <td>$${item.price.toFixed(2)}</td>
                <td class="text-center">
                    <div class="qty-container">
                        <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})">−</button>
                        <span id="qty_${item.id}">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                    </div>
                </td>
                <td class="text-end" id="lineTotal_${item.id}">$${item.lineTotal.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.id})">
                    <span class="material-symbols-outlined">delete</span></button></td>
            </tr>`;
    });

    subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    totalEl.textContent = `$${subtotal.toFixed(2)}`;
    countEl.textContent = totalItems;
    updateNavbarCartCount(totalItems);
}

// --- Send new absolute quantity to server ---
function updateQuantity(productId, newQty) {
    if (newQty < 1) {
        if (!confirm("Quantity is 0. Remove this item?")) return;
        removeItem(productId);
        return;
    }

    fetch('updateCartQuantity.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `productId=${productId}&quantity=${newQty}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            fetchCartItems(); // refresh quantities & totals
            updateNavbarCartCount(data.newCount);
        } else {
            alert("Error updating quantity");
        }
    });
}


function removeItem(productId) {
    fetch('removeItemAjax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'productId=' + productId
    }).then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
            updateNavbarCartCount(data.newCount);
            fetchCartItems();
        }
    });
}

</script>
</body>
</html>
