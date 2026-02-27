<?php
session_start();
include 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bidId'])) {
    die("Invalid request");
}

$bidId = (int)$_POST['bidId'];
$userId = $_SESSION['id'];

// Get bid details
$stmt = $conn->prepare("
    SELECT b.*, p.price AS originalPrice
    FROM bids b
    JOIN products p ON b.productId = p.id
    WHERE b.id = ? AND b.userId = ?
");
$stmt->bind_param("ii", $bidId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$bid = $result->fetch_assoc();
$stmt->close();

if (!$bid) {
    die("Bid not found");
}

// Check status
if ($bid['bidStatus'] !== 'Countered') {
    die("This bid cannot be accepted.");
}

// Check expiration
if (!$bid['expiresAt'] || strtotime($bid['expiresAt']) <= time()) {
    // Expire bid
    $expireStmt = $conn->prepare("UPDATE bids SET bidStatus='Expired' WHERE id=?");
    $expireStmt->bind_param("i", $bidId);
    $expireStmt->execute();
    $expireStmt->close();

    die("This bid has expired.");
}

// Use counterAmount as price
$price = $bid['counterAmount'];
$productId = $bid['productId'];
$quantity = $bid['quantity'];

// Get or create cart
$stmt = $conn->prepare("SELECT id FROM cart WHERE userId=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$cart = $result->fetch_assoc();
$stmt->close();

if ($cart) {
    $cartId = $cart['id'];
} else {
    $stmt = $conn->prepare("INSERT INTO cart (userId, created_at, updated_at) VALUES (?, NOW(), NOW())");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $cartId = $stmt->insert_id;
    $stmt->close();
}

// Add item to cart
$stmt = $conn->prepare("
    INSERT INTO cartitems (cartId, productId, quantity, price)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiid", $cartId, $productId, $quantity, $price);
$stmt->execute();
$stmt->close();

// Update bid status
$stmt = $conn->prepare("UPDATE bids SET bidStatus='Accepted' WHERE id=?");
$stmt->bind_param("i", $bidId);
$stmt->execute();
$stmt->close();

// Notify farmer
$stmt = $conn->prepare("
    INSERT INTO notifications (userId, type, message, isRead, created_at)
    SELECT f.id, 'Bid Accepted',
           'A customer accepted your counter offer.',
           0, NOW()
    FROM bids b
    JOIN products p ON b.productId = p.id
    JOIN farmers f ON p.farmerId = f.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $bidId);
$stmt->execute();
$stmt->close();

// Redirect back to dashboard
header("Location: customerDashboard.php#bids");
exit();
?>