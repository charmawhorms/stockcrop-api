<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['roleId'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['id'];

// Fetch notifications (includes order notifications)
$stmt = mysqli_prepare($conn, "
    SELECT n.id, n.type, n.message, n.isRead, n.created_at
    FROM notifications n
    WHERE n.userId = ?
    ORDER BY n.created_at DESC
    LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
?>
