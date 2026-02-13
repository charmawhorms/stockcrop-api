<?php
session_start();
include 'config.php';

// --- Check if user is logged in ---
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// --- Get user info from DB ---
$user_id = $_SESSION['id'];
$sql = "SELECT id, roleId, email, password_hash FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- Handle form submission ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Check all fields filled
    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        $message = "All fields are required.";
    }
    // Check new passwords match
    elseif ($newPassword !== $confirmPassword) {
        $message = "New password and confirmation do not match.";
    }
    // Verify current password
    elseif (!password_verify($currentPassword, $user['password_hash'])) {
        $message = "Current password is incorrect.";
    }
    else {
        // Hash the new password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update in DB
        $sqlUpdate = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, "si", $newHash, $user_id);
        $success = mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);

        if ($success) {
            $message = "Password updated successfully.";

            // Redirect to appropriate dashboard after short delay
            if ($user['roleId'] == 1) {           // Admin
                $redirect = 'adminDashboard.php';
            } elseif ($user['roleId'] == 2) {     // Farmer
                $redirect = 'farmerDashboard.php';
            } else {                              // Customer
                $redirect = 'customerDashboard.php';
            }

            header("Refresh:2; url=$redirect");
        } else {
            $message = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
</head>
<body>
    <?php include 'navbar.php'; ?>
<div class="container py-5">
    <h2 class="mb-4">Change Password</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="currentPassword" class="form-label">Current Password</label>
            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
        </div>

        <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
        </div>

        <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Password</button>
        <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
