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
    <title>Change Password | StockCrop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/icon.png">

    <style>
        :root {
            --sc-primary: #028037;
            --sc-dark: #014d21;
            --sc-bg: #f8fafc;
            --white: #ffffff;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--sc-bg); 
            color: #1e293b;
        }

        .content { 
            margin-left: 250px; 
            padding: 100px 2rem 3rem 2rem; 
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .security-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }

        .security-header {
            background: #fff;
            padding: 2rem 2rem 1rem 2rem;
            text-align: center;
        }

        .shield-icon {
            width: 60px;
            height: 60px;
            background: #f0fdf4;
            color: var(--sc-primary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
            font-size: 32px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
        }

        .input-group-custom {
            position: relative;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--sc-primary);
            box-shadow: 0 0 0 4px rgba(2, 128, 55, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
        }

        .btn-update {
            background: var(--sc-primary);
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            margin-top: 1rem;
            transition: 0.3s;
        }

        .btn-update:hover {
            background: var(--sc-dark);
            box-shadow: 0 4px 12px rgba(2, 128, 55, 0.2);
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        @media (max-width: 992px) {
            .content { margin-left: 0; padding-top: 110px; }
        }
    </style>
</head>
<body>

<?php include 'sidePanel.php'; ?>

<div class="content">
    <div class="security-card">
        <div class="security-header">
            <div class="shield-icon">
                <span class="material-symbols-outlined" style="font-size: 35px;">lock_reset</span>
            </div>
            <h4 class="fw-bold mb-1">Update Password</h4>
            <p class="text-muted small">Ensure your account stays secure with a strong password.</p>
        </div>

        <div class="card-body p-4 pt-0">
            <?php if ($message): ?>
                <?php 
                    $isSuccess = strpos($message, 'successfully') !== false;
                    $alertClass = $isSuccess ? 'alert-success' : 'alert-danger';
                ?>
                <div class="alert <?= $alertClass ?> alert-custom shadow-sm mb-4 d-flex align-items-center">
                    <span class="material-symbols-outlined me-2" style="font-size: 20px;">
                        <?= $isSuccess ? 'check_circle' : 'error' ?>
                    </span>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Current Password</label>
                    <div class="input-group-custom">
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePass('currentPassword')">
                            <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                        </button>
                    </div>
                </div>

                <hr class="my-4 opacity-25">

                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password</label>
                    <div class="input-group-custom">
                        <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Minimum 8 characters" required>
                        <button type="button" class="password-toggle" onclick="togglePass('newPassword')">
                            <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                    <div class="input-group-custom">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Match new password" required>
                        <button type="button" class="password-toggle" onclick="togglePass('confirmPassword')">
                            <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-update">
                    Update Security Key
                </button>
                
                <div class="text-center mt-3">
                    <a href="javascript:history.back()" class="text-muted small text-decoration-none fw-medium">
                        Cancel and return
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePass(id) {
        const input = document.getElementById(id);
        const btn = input.nextElementSibling.querySelector('span');
        if (input.type === "password") {
            input.type = "text";
            btn.textContent = "visibility_off";
        } else {
            input.type = "password";
            btn.textContent = "visibility";
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>