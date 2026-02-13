<?php
    session_start();
    include 'config.php';

    $orderId = $_GET['orderId'] ?? null;

    if (!$orderId) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid order.'];
        header("Location: shop.php");
        exit();
    }

    // Fetch order details
    $sql_order = "SELECT o.*, c.firstName, c.lastName 
                FROM orders o 
                JOIN customers c ON o.customerId = c.id
                WHERE o.id = ?";
    $stmt = mysqli_prepare($conn, $sql_order);
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$order) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Order not found.'];
        header("Location: shop.php");
        exit();
    }

    // Fetch order items
    $sql_items = "SELECT oi.*, p.productName, p.imagePath 
                FROM order_items oi 
                JOIN products p ON oi.productId = p.id 
                WHERE oi.orderId = ?";
    $stmt_items = mysqli_prepare($conn, $sql_items);
    mysqli_stmt_bind_param($stmt_items, "i", $orderId);
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
    $order_items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_items);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>StockCrop | Order Confirmation</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> 
    <link rel="icon" type="image/png" href="assets/icon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-5">
    <div class="alert alert-success text-center">
        <h3 class="fw-bold">Thank you, <?= htmlspecialchars($order['firstName']) ?>!</h3>
        <p>Your order <strong>#<?= $orderId ?></strong> has been placed successfully.</p>
        <p>Status: <span class="badge bg-warning text-dark"><?= $order['status'] ?></span></p>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white fw-bold">Order Summary</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($order_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <img src="<?= $item['imagePath'] ?>" alt="<?= htmlspecialchars($item['productName']) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px;" class="me-2">
                                <div>
                                    <small class="fw-bold"><?= htmlspecialchars($item['productName']) ?></small><br>
                                    <small class="text-muted">Qty: <?= $item['quantity'] ?> x $<?= number_format($item['priceAtPurchase'], 2) ?></small>
                                </div>
                            </div>
                            <span class="fw-bold">$<?= number_format($item['lineTotal'], 2) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <span>$<?= number_format($order['totalAmount'] - $order['shippingFee'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Shipping (<?= htmlspecialchars($order['deliveryMethod']) ?>)</span>
                        <span>$<?= number_format($order['shippingFee'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3 fw-bold fs-4">
                        <span>Total</span>
                        <span>$<?= number_format($order['totalAmount'], 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Delivery Details</div>
                <div class="card-body">
                    <p><strong>Recipient:</strong> <?= htmlspecialchars($order['firstName'] . ' ' . $order['lastName']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['recipientPhone']) ?></p>
                    <p><strong>Delivery Method:</strong> <?= htmlspecialchars($order['deliveryMethod']) ?></p>
                    <?php if ($order['deliveryMethod'] === 'Delivery'): ?>
                        <p><strong>Address:</strong> <?= htmlspecialchars($order['deliveryAddress']) ?></p>
                    <?php else: ?>
                        <p><em>Pickup location and time slot will be provided shortly.</em></p>
                    <?php endif; ?>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['paymentMethod']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="alert alert-info shadow-sm">
                <h5 class="fw-bold">Next Steps:</h5>
                <ul>
                    <li>For delivery: Expect your order within the next 2–3 business days.</li>
                    <li>For pickup: You will receive an email with pickup location and time.</li>
                    <li>Keep your order ID handy for any inquiries.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
