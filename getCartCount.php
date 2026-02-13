<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$count = 0;

if (isset($_SESSION['id'])) {
    // LOGGED-IN USER
    $userId = $_SESSION['id'];
    
    // Join cart header with cartItems to SUM quantity
    $stmt = mysqli_prepare($conn, "
        SELECT SUM(ci.quantity) as total 
        FROM cartItems ci
        JOIN cart c ON ci.cartId = c.id
        WHERE c.userId = ?
    ");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $count = intval($row['total'] ?? 0);
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    // GUEST USER
    $count = array_sum($_SESSION['cart']);
}

echo json_encode(['count' => $count]);
?>