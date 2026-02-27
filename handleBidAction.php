<?php
include 'session.php';
include 'config.php';

if (!isset($_SESSION['id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bidId'], $_POST['action'])) {
    $bidId = (int)$_POST['bidId'];
    $action = $_POST['action'];

    // 1. Fetch bid info along with customer id
    $stmt = $conn->prepare("
        SELECT b.id, b.bidStatus, b.bidAmount, b.productId, c.id AS customerId, u.id AS userId
        FROM bids b
        JOIN users u ON b.userId = u.id
        JOIN customers c ON u.id = c.userId
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $bidId);
    $stmt->execute();
    $bid = $stmt->get_result()->fetch_assoc();

    if (!$bid) {
        die("Bid not found");
    }

    $customerId = $bid['customerId'];
    $bidStatus = $bid['bidStatus'];

    if ($bidStatus !== 'Pending') {
        die("Bid is already processed");
    }

    // 2. Handle actions
    switch ($action) {
        case 'accept':
            $update = $conn->prepare("UPDATE bids SET bidStatus='Accepted' WHERE id=?");
            $update->bind_param("i", $bidId);
            $update->execute();

            // Notification
            $msg = "Your bid has been accepted!";
            $notif = $conn->prepare("INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'accept', ?, 0, NOW())");
            $notif->bind_param("is", $customerId, $msg);
            $notif->execute();
            break;

        case 'reject':
            $update = $conn->prepare("UPDATE bids SET bidStatus='Rejected' WHERE id=?");
            $update->bind_param("i", $bidId);
            $update->execute();

            $msg = "Your bid has been rejected.";
            $notif = $conn->prepare("INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'reject', ?, 0, NOW())");
            $notif->bind_param("is", $customerId, $msg);
            $notif->execute();
            break;

        case 'counter':
    if (!isset($_POST['counterAmount']) || !is_numeric($_POST['counterAmount'])) {
        die("Invalid counter amount.");
    }

    $counterAmount = floatval($_POST['counterAmount']);

    // Set 1 hour expiration
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Update bid
    $update = $conn->prepare("
        UPDATE bids 
        SET counterAmount = ?, 
            bidStatus = 'Countered',
            expiresAt = ?
        WHERE id = ?
    ");
    $update->bind_param("dsi", $counterAmount, $expiresAt, $bidId);
    $update->execute();

    // ✅ Use customerId (NOT users.id)
    $msg = "The farmer countered your bid with $" . number_format($counterAmount, 2) . ". You have 1 hour to respond.";

    $notif = $conn->prepare("
        INSERT INTO notifications (userId, type, message, isRead, created_at)
        VALUES (?, 'Bid Countered', ?, 0, NOW())
    ");
    $notif->bind_param("is", $customerId, $msg);
    $notif->execute();

    header("Location: manageBids.php");
    exit();

        default:
            die("Invalid action");
    }

    // Redirect back to manage bids page
    header("Location: manageBids.php");
    exit();
} else {
    die("Invalid request");
}
?>