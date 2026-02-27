<?php
include 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userId'])) {
    $userId = (int)$_POST['userId'];
    $stmt = $conn->prepare("UPDATE notifications SET isRead=1 WHERE userId=? AND isRead=0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}