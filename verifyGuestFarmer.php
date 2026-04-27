<?php
session_start();
include 'config.php';
include 'session.php';
require_once 'notificationMailer.php';

// 1. Security Check: Only admins can verify
redirectIfNotLoggedIn();
if ($_SESSION['roleId'] != 1) {
    die("Unauthorized access.");
}

// 2. Process the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['farmer_id'])) {
    $farmerId = (int)$_POST['farmer_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'verify_guest') {
        // 3. Load the farmer's email and name for notification
        $fetchStmt = mysqli_prepare($conn, "SELECT firstName, lastName, email FROM farmers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($fetchStmt, "i", $farmerId);
        mysqli_stmt_execute($fetchStmt);
        mysqli_stmt_bind_result($fetchStmt, $firstName, $lastName, $email);
        mysqli_stmt_fetch($fetchStmt);
        mysqli_stmt_close($fetchStmt);

        // 4. Update the verification_status column
        // We keep farmerType as 'guest' but update status to 'verified'
        $stmt = mysqli_prepare($conn, "UPDATE farmers SET verification_status = 'verified' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $farmerId);
        
        if (mysqli_stmt_execute($stmt)) {
            // Send approval email if we have a valid address
            if (!empty($email)) {
                $fullName = trim($firstName . ' ' . $lastName);
                $subject = 'Your StockCrop Farm Account Has Been Approved';
                $body = "<p>Hi " . htmlspecialchars($firstName) . ",</p>" .
                        "<p>Your farmer account has been reviewed and approved by our admin team. You can now list products on StockCrop and manage your farm profile.</p>" .
                        "<p>If you want the RADA verified badge on your products, please update your account with your RADA ID in your profile.</p>" .
                        "<p>Thank you for joining StockCrop!</p>";
                sendNotificationEmail($email, $fullName ?: 'Farmer', $subject, $body);
            }

            // Success: Redirect back with a success flag
            header("Location: viewFarmer.php?id=$farmerId&msg=verified");
        } else {
            // Error handling
            header("Location: viewFarmer.php?id=$farmerId&msg=error");
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Redirect if someone tries to access the file directly
    header("Location: farmManagement.php");
}
exit();