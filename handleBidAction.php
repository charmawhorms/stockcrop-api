<?php
include 'session.php';
include 'config.php';

if (!isset($_SESSION['id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bidId'], $_POST['action'])) {
    $bidId = (int)$_POST['bidId'];
    $action = $_POST['action'];

    // 1. Fetch bid info along with customer user id and product details
    $stmt = $conn->prepare("
        SELECT b.id, b.bidStatus, b.bidAmount, b.productId, u.id AS customerUserId, p.productName
        FROM bids b
        JOIN users u ON b.userId = u.id
        JOIN products p ON b.productId = p.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $bidId);
    $stmt->execute();
    $bid = $stmt->get_result()->fetch_assoc();

    if (!$bid) {
        die("Bid not found");
    }

    $customerUserId = $bid['customerUserId'];
    $productName = $bid['productName'];
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

            $msg = "Your bid for {$productName} has been accepted.";
            $notif = $conn->prepare("INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'bid', ?, 0, NOW())");
            $notif->bind_param("is", $customerUserId, $msg);
            $notif->execute();
            break;

        case 'reject':
            $update = $conn->prepare("UPDATE bids SET bidStatus='Rejected' WHERE id=?");
            $update->bind_param("i", $bidId);
            $update->execute();

            $msg = "Your bid for {$productName} has been rejected.";
            $notif = $conn->prepare("INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'bid', ?, 0, NOW())");
            $notif->bind_param("is", $customerUserId, $msg);
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

            $msg = "The farmer countered your bid on {$productName} with $" . number_format($counterAmount, 2) . ". You have 1 hour to respond.";
            $notif = $conn->prepare("INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'bid', ?, 0, NOW())");
            $notif->bind_param("is", $customerUserId, $msg);
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