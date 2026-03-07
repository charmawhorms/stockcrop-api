<?php
session_start();
include 'config.php';
include 'session.php';

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
        // 3. Update the verification_status column
        // We keep farmerType as 'guest' but update status to 'verified'
        $stmt = mysqli_prepare($conn, "UPDATE farmers SET verification_status = 'verified' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $farmerId);
        
        if (mysqli_stmt_execute($stmt)) {
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