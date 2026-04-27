<?php
    include 'config.php';
    require_once 'notificationMailer.php';

    if (isset($_POST['orderId'], $_POST['status'])) {
        $orderId = $_POST['orderId'];
        $status = $_POST['status'];

        // Update order status
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $orderId);
        mysqli_stmt_execute($stmt);

        // Get the customer user ID and email
        $stmt2 = mysqli_prepare($conn, "SELECT u.id, u.email FROM orders o JOIN customers c ON o.customerId = c.id JOIN users u ON c.userId = u.id WHERE o.id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $orderId);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_bind_result($stmt2, $customerUserId, $customerEmail);
        mysqli_stmt_fetch($stmt2);
        mysqli_stmt_close($stmt2);

        // If delivered, create a notification and send an email
        if ($status === "Delivered" && !empty($customerUserId)) {
            $message = "Your order #$orderId has been delivered! Thank you for shopping with us.";

            $stmt3 = mysqli_prepare($conn, "INSERT INTO notifications (userId, type, message, isRead, created_at) VALUES (?, 'order', ?, 0, NOW())");
            mysqli_stmt_bind_param($stmt3, "is", $customerUserId, $message);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            if (!empty($customerEmail)) {
                $emailBody = "<div style='font-family: sans-serif; color: #333; line-height: 1.6;'>"
                    . "<h2 style='color: #2f8f3f;'>Order Delivered</h2>"
                    . "<p>Your order <strong>#$orderId</strong> has been delivered successfully.</p>"
                    . "<p>Thank you for shopping with StockCrop Jamaica. We hope you enjoy your purchase.</p>"
                    . "<br><p>Best regards,<br>StockCrop Jamaica Team</p></div>";
                sendNotificationEmail($customerEmail, 'Customer', "StockCrop Order #$orderId Delivered", $emailBody);
            }
        }

        echo "Status updated successfully.";
    }
?>
