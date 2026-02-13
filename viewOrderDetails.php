<?php
    session_start();
    include 'config.php';

    if (!isset($_SESSION['id'])) {
        header('Location: login.php');
        exit;
    }

    $userId = $_SESSION['id'];

    // Get orderId from GET
    $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($orderId <= 0) {
        // If no ID is provided, redirect back to the dashboard
        header('Location: customerDashboard.php');
        exit;
    }

    // Function to format the currency
    function formatCurrency($amount) {
        return '$' . number_format($amount, 2) . ' JMD';
    }

    // Function to determine the badge color based on status
    function getStatusBadgeClass($status) {
        switch ($status) {
            case 'Delivered':
            case 'Completed':
                return 'bg-success';
            case 'Shipped':
            case 'Processing':
                return 'bg-warning text-dark';
            case 'Pending':
                return 'bg-primary';
            case 'Canceled':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    // --- Fetch main order details and customer info ---
    // Note: o.customerId links to the customer's ID in the 'customers' table.
    $orderQuery = mysqli_prepare($conn, "
        SELECT 
            o.id, o.orderDate, o.totalAmount, o.status, o.shippingFee, o.deliveryMethod,
            o.deliveryAddress, o.recipientPhone, o.paymentMethod,
            c.firstName, c.lastName
        FROM orders o
        JOIN customers c ON o.customerId = c.id
        WHERE o.id = ? AND o.customerId = (SELECT id FROM customers WHERE userId = ?)
    ");
    mysqli_stmt_bind_param($orderQuery, "ii", $orderId, $userId);
    mysqli_stmt_execute($orderQuery);
    $orderResult = mysqli_stmt_get_result($orderQuery);
    $order = mysqli_fetch_assoc($orderResult);
    mysqli_stmt_close($orderQuery);

    // Double-check to ensure the order belongs to the currently logged-in user
    if (!$order) {
        echo "<div class='alert alert-danger text-center'>Order not found or access denied.</div>";
        exit;
    }


    // --- Fetch order items with product names ---
    $itemsQuery = mysqli_prepare($conn, "
        SELECT p.productName, oi.quantity, oi.priceAtPurchase, oi.lineTotal
        FROM order_items oi
        JOIN products p ON oi.productId = p.id
        WHERE oi.orderId = ?
    ");
    mysqli_stmt_bind_param($itemsQuery, "i", $orderId);
    mysqli_stmt_execute($itemsQuery);
    $itemsResult = mysqli_stmt_get_result($itemsQuery);
    $items = mysqli_fetch_all($itemsResult, MYSQLI_ASSOC);
    mysqli_stmt_close($itemsQuery);

    // Calculate subtotal (Total amount minus shipping fee)
    $subtotal = $order['totalAmount'] - $order['shippingFee'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StockCrop | Order #<?= $order['id'] ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link rel="stylesheet" href="styles.css">
<link rel="icon" type="image/png" href="assets/icon.png">
<style>
    :root {
        --stockcrop-green: #388E3C; 
        --stockcrop-orange: #FF8F00; 
        --bs-background-light: #f4f6f9;
        --bs-card-border: #e0e4eb;
    }
    body { 
        background-color: var(--bs-background-light); 
    }

    .dashboard-header { 
        background-image: linear-gradient(to right, var(--stockcrop-green), var(--stockcrop-orange)); 
        padding: 1.5rem 0; 
        border: none; 
    }

    .dashboard-header h1, .dashboard-header p.lead { 
        color: #fff !important; 
    }

    .card-detail {
        border-radius: 0.5rem;
        border: 1px solid var(--bs-card-border);
        background-color: white;
    }

    .stat-icon { 
        color: #fff; 
    }

    .status-badge {
        font-size: 0.9em;
        font-weight: 600;
        padding: 0.4em 0.8em;
        border-radius: 0.35rem;
    }

    .order-summary-box {
        background-color: #f8f9fa; /* Light grey background for summary */
        border: 1px solid var(--bs-card-border);
        border-radius: 0.5rem;
    }
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="dashboard-header">
    <div class="container d-flex align-items-center justify-content-between">
        <div>
            <h1 class="display-6 fw-bold mb-1">Order #<?= $order['id'] ?></h1>
            <p class="lead mb-0">Details for your order placed on <?= date('F j, Y', strtotime($order['orderDate'])) ?></p>
        </div>
        <div class="d-none d-sm-flex align-items-center">
            <button class="btn btn-outline-light btn-sm" onclick="printInvoice()">
                <span class="material-symbols-outlined me-1" style="font-size: 18px;">print</span> Print Order
            </button>
        </div>

        <div id="printableInvoice" style="display:none;">
            <div style="font-family: Arial, sans-serif; padding: 20px; width: 800px; margin: auto;">
                <h2 style="text-align:center;">StockCrop Order Receipt</h2>
                <p><strong>Order #:</strong> <?= $order['id'] ?><br>
                <strong>Date:</strong> <?= date('F j, Y', strtotime($order['orderDate'])) ?><br>
                <strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>

                <h4>Customer Details</h4>
                <p><?= htmlspecialchars($order['firstName'] . ' ' . $order['lastName']) ?><br>
                <?= htmlspecialchars($order['deliveryAddress'] ?: 'Pickup') ?><br>
                <?= htmlspecialchars($order['recipientPhone']) ?></p>

                <h4>Order Items</h4>
                <table style="width:100%; border-collapse: collapse;" border="1">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding:8px; text-align:left;">Product</th>
                            <th style="padding:8px; text-align:right;">Qty</th>
                            <th style="padding:8px; text-align:right;">Price</th>
                            <th style="padding:8px; text-align:right;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td style="padding:8px;"><?= htmlspecialchars($item['productName']) ?></td>
                            <td style="padding:8px; text-align:right;"><?= $item['quantity'] ?></td>
                            <td style="padding:8px; text-align:right;"><?= formatCurrency($item['priceAtPurchase']) ?></td>
                            <td style="padding:8px; text-align:right;"><?= formatCurrency($item['lineTotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h4 style="margin-top:20px;">Totals</h4>
                <p>Subtotal: <?= formatCurrency($subtotal) ?><br>
                Shipping Fee: <?= formatCurrency($order['shippingFee']) ?><br>
                <strong>Grand Total: <?= formatCurrency($order['totalAmount']) ?></strong></p>

                <p style="text-align:center; margin-top:30px;">Thank you for shopping with StockCrop!</p>
            </div>
        </div>


    </div>
</div>

<div class="container py-5">
    
    <div class="row g-4 mb-4">
        <!-- Status and Total Summary -->
        <div class="col-lg-4 col-md-6">
            <div class="card p-4 shadow-sm card-detail h-100">
                <h5 class="fw-bold mb-3 text-dark">Order Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Status:</span>
                    <span class="status-badge <?= getStatusBadgeClass($order['status']) ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between pt-2 border-top">
                    <span class="fw-bold text-dark">Order Total:</span>
                    <span class="fw-bold text-success"><?= formatCurrency($order['totalAmount']) ?></span>
                </div>
            </div>
        </div>

        <!-- Customer & Billing Details -->
        <div class="col-lg-4 col-md-6">
            <div class="card p-4 shadow-sm card-detail h-100">
                <h5 class="fw-bold mb-3 text-dark">Customer & Payment</h5>
                <p class="mb-1 text-muted"><span class="fw-semibold text-dark">Customer:</span> <?= htmlspecialchars($order['firstName'] . ' ' . $order['lastName']) ?></p>
                <p class="mb-1 text-muted"><span class="fw-semibold text-dark">Order Date:</span> <?= date('M d, Y', strtotime($order['orderDate'])) ?></p>
                <p class="mb-0 text-muted"><span class="fw-semibold text-dark">Payment Method:</span> <?= htmlspecialchars($order['paymentMethod']) ?></p>
            </div>
        </div>

        <!-- Shipping Details -->
        <div class="col-lg-4 col-md-12">
            <div class="card p-4 shadow-sm card-detail h-100">
                <h5 class="fw-bold mb-3 text-dark">Shipping & Delivery</h5>
                <p class="mb-1 text-muted"><span class="fw-semibold text-dark">Method:</span> <?= htmlspecialchars($order['deliveryMethod']) ?></p>
                <?php if ($order['deliveryAddress']): ?>
                    <p class="mb-1 text-muted"><span class="fw-semibold text-dark">Address:</span> <?= htmlspecialchars($order['deliveryAddress']) ?></p>
                <?php else: ?>
                    <p class="mb-1 text-muted"><span class="fw-semibold text-dark">Address:</span> Pickup (No address needed)</p>
                <?php endif; ?>
                <p class="mb-0 text-muted"><span class="fw-semibold text-dark">Contact:</span> <?= htmlspecialchars($order['recipientPhone']) ?></p>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="card p-4 shadow-sm card-detail">
        <h5 class="fw-bold mb-3 text-dark">Order Items</h5>
        <?php if (empty($items)): ?>
            <div class="alert alert-info">No items found for this order.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col" class="text-end">Price/Unit</th>
                            <th scope="col" class="text-end">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['productName']) ?></td>
                            <td class="text-end"><?= $item['quantity'] ?></td>
                            <td class="text-end"><?= formatCurrency($item['priceAtPurchase']) ?></td>
                            <td class="text-end fw-semibold"><?= formatCurrency($item['lineTotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Financial Breakdown -->
            <div class="row justify-content-end mt-4">
                <div class="col-md-6 col-lg-4">
                    <div class="order-summary-box p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal:</span>
                            <span><?= formatCurrency($subtotal) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fee:</span>
                            <span><?= formatCurrency($order['shippingFee']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold pt-2 border-top">
                            <span>Grand Total:</span>
                            <span class="text-success"><?= formatCurrency($order['totalAmount']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4">
        <a href="customerDashboard.php" class="btn btn-outline-secondary">
            <span class="material-symbols-outlined align-middle me-1" style="font-size: 18px;">arrow_back</span> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function printInvoice() {
        const printContents = document.getElementById('printableInvoice').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload(); // optional to reload page after printing
    }
</script>

</body>
</html>
