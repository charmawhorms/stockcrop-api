<?php
    include 'session.php';
    include 'config.php';
    header('Content-Type: application/json');

    redirectIfNotLoggedIn();

    // Ensure only farmers can access
    if ($_SESSION['roleId'] != 2) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }

    // Must be POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    $itemId = intval($_POST['itemId'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $orderId = intval($_POST['orderId'] ?? 0);

    $allowedStatuses = ['Pending', 'Processed', 'Shipped'];
    if (!$itemId || !in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid item or status.']);
        exit;
    }

    // Ensure the item belongs to the logged-in farmer
    $farmerUserId = $_SESSION['id'];
    $checkStmt = $conn->prepare("
        SELECT oi.id, oi.orderId
        FROM order_items oi
        JOIN farmers f ON oi.farmerId = f.id
        WHERE oi.id = ? AND f.userId = ?
    ");
    $checkStmt->bind_param("ii", $itemId, $farmerUserId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to update this item.']);
        exit;
    }
    $checkStmt->close();

    // Update item status
    $updateStmt = $conn->prepare("UPDATE order_items SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $status, $itemId);
    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update item status.']);
        exit;
    }
    $updateStmt->close();

    // Recalculate order status based on all its items
    $query = $conn->prepare("
        SELECT 
            SUM(status = 'Pending') AS pending,
            SUM(status = 'Processed') AS processed,
            SUM(status = 'Shipped') AS shipped
        FROM order_items
        WHERE orderId = ?
    ");
    $query->bind_param('i', $orderId);
    $query->execute();
    $counts = $query->get_result()->fetch_assoc();
    $query->close();

    $orderStatus = 'Pending';
    if ($counts['pending'] == 0 && $counts['processed'] > 0 && $counts['shipped'] == 0)
        $orderStatus = 'Processed';
    elseif ($counts['pending'] == 0 && $counts['shipped'] > 0)
        $orderStatus = 'Shipped';

    // Update overall order status
    $updateOrder = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $updateOrder->bind_param('si', $orderStatus, $orderId);
    $updateOrder->execute();
    $updateOrder->close();

    echo json_encode([
        'success' => true,
        'message' => 'Item status updated successfully.',
        'orderStatus' => $orderStatus
    ]);
    exit;
?>
