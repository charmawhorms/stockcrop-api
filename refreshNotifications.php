<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['id'];

$stmt = mysqli_prepare($conn, "SELECT id, type, message, isRead, created_at FROM notifications WHERE userId = ? ORDER BY created_at DESC LIMIT 10");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
?>
