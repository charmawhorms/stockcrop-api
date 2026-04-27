<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

// --- INITIAL AUTH CHECK ---
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}

$user_id = $_SESSION['id'];

// --- 1. SANITIZATION & VALIDATION ---
$fullName = mysqli_real_escape_string($conn, $_POST['fullName'] ?? '');
$phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? 'Customer Pickup'); 
$paymentMethod = mysqli_real_escape_string($conn, $_POST['paymentMethod'] ?? '');
$deliveryMethod = mysqli_real_escape_string($conn, $_POST['deliveryMethod'] ?? '');
$shippingFee = floatval($_POST['shippingFee'] ?? 0.00);

if (empty($fullName) || empty($phone) || empty($paymentMethod) || empty($deliveryMethod)) {
    echo json_encode(['success' => false, 'message' => 'Missing contact or payment details.']);
    exit();
}

// --- 2. GET CUSTOMER ID ---
$sql_customer = "SELECT id FROM customers WHERE userId = ?";
$stmt_customer = mysqli_prepare($conn, $sql_customer);
mysqli_stmt_bind_param($stmt_customer, "i", $user_id);
mysqli_stmt_execute($stmt_customer);
$result_customer = mysqli_stmt_get_result($stmt_customer);
$customer_row = mysqli_fetch_assoc($result_customer);
mysqli_stmt_close($stmt_customer);

if (!$customer_row) {
    echo json_encode(['success' => false, 'message' => 'Customer profile not found.']);
    exit();
}
$customerId = $customer_row['id'];

// --- 3. CART & BID LOGIC ---
$cart_items = [];
$order_subtotal = 0.0;
// Set status based on payment method
$order_status = ($paymentMethod === 'Online') ? 'Awaiting Payment' : 'Pending'; 

$sql = "SELECT ci.id AS cartItemId, ci.productId, ci.quantity, ci.price, 
               p.stockQuantity, p.farmerId, p.productName, p.imagePath
        FROM cartItems ci
        JOIN cart c ON ci.cartId = c.id
        JOIN products p ON ci.productId = p.id
        WHERE c.userId = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    // ... [KEEP YOUR EXISTING BID CHECK CODE HERE] ...

    // STOCK CHECK
    if ($row['quantity'] > $row['stockQuantity']) {
        echo json_encode(['success' => false, 'message' => "Stock exceeded for {$row['productName']}."]);
        exit();
    }

    $line_total = $row['quantity'] * $row['price'];
    $order_subtotal += $line_total;
    $row['lineTotal'] = $line_total;
    $cart_items[] = $row;
}
mysqli_stmt_close($stmt);

$order_total = $order_subtotal + $shippingFee;

// --- 4. DATABASE TRANSACTION ---
mysqli_begin_transaction($conn);

try {
    // Insert order
    $sql_order = "INSERT INTO orders (customerId, orderDate, totalAmount, shippingFee, deliveryMethod, deliveryAddress, recipientPhone, paymentMethod, status) 
                  VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
    $stmt_order = mysqli_prepare($conn, $sql_order);
    mysqli_stmt_bind_param($stmt_order, "iddsssss", 
        $customerId, $order_total, $shippingFee, $deliveryMethod, $address, $phone, $paymentMethod, $order_status
    );
    mysqli_stmt_execute($stmt_order);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_order);

    // Insert items & Update Stock
    foreach ($cart_items as $item) {
        $sql_item = "INSERT INTO order_items (orderId, productId, farmerId, quantity, priceAtPurchase, lineTotal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_item = mysqli_prepare($conn, $sql_item);
        mysqli_stmt_bind_param($stmt_item, "iiiidd", $order_id, $item['productId'], $item['farmerId'], $item['quantity'], $item['price'], $item['lineTotal']);
        mysqli_stmt_execute($stmt_item);

        $sql_stock = "UPDATE products SET stockQuantity = stockQuantity - ? WHERE id = ?";
        $stmt_stock = mysqli_prepare($conn, $sql_stock);
        mysqli_stmt_bind_param($stmt_stock, "ii", $item['quantity'], $item['productId']);
        mysqli_stmt_execute($stmt_stock);

        // Get farmer userId and create notification for the farmer
        $sql_farmer = "SELECT userId FROM farmers WHERE id = ?";
        $stmt_farmer = mysqli_prepare($conn, $sql_farmer);
        mysqli_stmt_bind_param($stmt_farmer, "i", $item['farmerId']);
        mysqli_stmt_execute($stmt_farmer);
        $farmer_result = mysqli_stmt_get_result($stmt_farmer);
        $farmer_row = mysqli_fetch_assoc($farmer_result);
        mysqli_stmt_close($stmt_farmer);

        if ($farmer_row) {
            $farmer_user_id = $farmer_row['userId'];
            $notification_message = "New order #$order_id for {$item['productName']} (Qty: {$item['quantity']})";
            $sql_notif = "INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'order', ?, 0, NOW())";
            $stmt_notif = mysqli_prepare($conn, $sql_notif);
            mysqli_stmt_bind_param($stmt_notif, "is", $farmer_user_id, $notification_message);
            mysqli_stmt_execute($stmt_notif);
            mysqli_stmt_close($stmt_notif);
        }
    }

    // ONLY clear cart if it's COD. 
    // If Online, clear it on orderConfirmation.php after payment success.
    if ($paymentMethod === 'COD') {
        mysqli_query($conn, "DELETE FROM cartItems WHERE cartId = (SELECT id FROM cart WHERE userId = $user_id)");
    }

    mysqli_commit($conn);

    // Customer notification record
    $customerMessage = "Your order #$order_id has been placed successfully and is now $order_status.";
    $stmt_notif_cust = mysqli_prepare($conn, "INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'order', ?, 0, NOW())");
    mysqli_stmt_bind_param($stmt_notif_cust, "is", $user_id, $customerMessage);
    mysqli_stmt_execute($stmt_notif_cust);
    mysqli_stmt_close($stmt_notif_cust);

    // Send customer email alert
    $customerEmail = $_SESSION['email'] ?? null;
    if (!$customerEmail) {
        $stmt_email = mysqli_prepare($conn, "SELECT email FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt_email, "i", $user_id);
        mysqli_stmt_execute($stmt_email);
        $result_email = mysqli_stmt_get_result($stmt_email);
        $rowEmail = mysqli_fetch_assoc($result_email);
        $customerEmail = $rowEmail['email'] ?? null;
        mysqli_stmt_close($stmt_email);
    }

    if ($customerEmail) {
        require_once 'notificationMailer.php';
        $emailBody = "<div style='font-family: sans-serif; color: #333; line-height: 1.6;'>"
            . "<h2 style='color: #2f8f3f;'>Order Confirmed</h2>"
            . "<p>Hi " . htmlspecialchars($fullName) . ",</p>"
            . "<p>Your order <strong>#$order_id</strong> has been placed successfully and is now <strong>$order_status</strong>.</p>"
            . "<p>Thank you for shopping with StockCrop Jamaica. You can track your order status in your dashboard.</p>"
            . "<br><p>Best regards,<br>StockCrop Jamaica Team</p></div>";
        sendNotificationEmail($customerEmail, $fullName, "StockCrop Order #$order_id Confirmed", $emailBody);
    }

    echo json_encode(['success' => true, 'orderId' => $order_id]);
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}