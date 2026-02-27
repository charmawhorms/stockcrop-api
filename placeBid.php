<?php
session_start();
header('Content-Type: application/json');
include 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to place a bid.'
    ]);
    exit;
}

// Validate POST data
$productId = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
$bidAmount = isset($_POST['bidAmount']) ? floatval($_POST['bidAmount']) : 0;
$quantity  = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($productId <= 0 || $bidAmount <= 0 || $quantity <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid bid data.'
    ]);
    exit;
}

// Fetch product info
$stmt = $conn->prepare("
    SELECT allowBidding, minPrice, stockQuantity 
    FROM products 
    WHERE id = ?
");

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error.'
    ]);
    exit;
}

$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product not found.'
    ]);
    exit;
}

// Check if bidding is allowed
if ($product['allowBidding'] != 1) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Bidding not allowed for this product.'
    ]);
    exit;
}

// Check stock availability
if ($quantity > $product['stockQuantity']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Requested quantity exceeds available stock.'
    ]);
    exit;
}

// Validate per-unit minimum price
$minPrice = floatval($product['minPrice']);
$perUnitBid = $bidAmount / $quantity;

if ($perUnitBid < $minPrice) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Bid too low per unit.'
    ]);
    exit;
}

// Insert bid into bids table
$userId = $_SESSION['id'];

$insertStmt = $conn->prepare("
    INSERT INTO bids 
    (productId, userId, quantity, bidAmount, bidStatus, bidTime)
    VALUES (?, ?, ?, ?, 'Pending', NOW())
");

if (!$insertStmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database prepare failed.'
    ]);
    exit;
}

$insertStmt->bind_param("iiid", $productId, $userId, $quantity, $bidAmount);

if ($insertStmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Bid placed successfully!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database insert failed.'
    ]);
}
