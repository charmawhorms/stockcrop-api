<?php
    session_start();
    include 'config.php';

    // --- Ensure only admin can access ---
    if (!isset($_SESSION['id']) || $_SESSION['roleId'] != 1) {
        header("Location: login.php");
        exit();
    }

    $message = '';

    // --- Handle Add New User ---
    if (isset($_POST['addUser'])) {
        $email = trim($_POST['email'] ?? '');
        $roleId = intval($_POST['roleId'] ?? 3); // default to customer
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $message = "Email and password are required.";
        } else {
            // Check if email exists
            $stmtCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmtCheck, "s", $email);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            if (mysqli_num_rows($resCheck) > 0) {
                $message = "Email already exists.";
            } else {
                // Insert new user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = mysqli_prepare($conn, "INSERT INTO users (roleId, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmtInsert, "iss", $roleId, $email, $hash);
                if (mysqli_stmt_execute($stmtInsert)) {
                    $message = "New user added successfully.";
                } else {
                    $message = "Failed to add user.";
                }
                mysqli_stmt_close($stmtInsert);
            }
            mysqli_stmt_close($stmtCheck);
        }
    }

    // --- Handle Password Change ---
    if (isset($_POST['changePassword'])) {
        $userId = intval($_POST['userId'] ?? 0);
        $newPassword = $_POST['newPassword'] ?? '';

        if ($userId && $newPassword) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hash, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Password updated successfully.";
            } else {
                $message = "Failed to update password.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Select a user and enter a new password.";
        }
    }

    // --- Fetch all users ---
    $users = [];
    $res = mysqli_query($conn, "SELECT id, email, roleId, created_at FROM users ORDER BY id DESC");
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link rel="icon" type="image/png" href="assets/icon.png">

<style>
    :root {
        --primary-green: #2f8f3f;
        --dark-green: #1b5e20;
        --light-bg: #f8faf8;
        --sidebar-width: 250px;
    }

    body {
        display: flex;
        min-height: 100vh;
        background: var(--light-bg);
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .sidebar {
        width: var(--sidebar-width);
        background: var(--primary-green);
        color: white;
        position: fixed;
        height: 100vh;
        padding-top: 20px;
        box-shadow: 4px 0 10px rgba(0,0,0,0.15);
        z-index: 1000;
    }
    .sidebar .logo {
        font-size: 1.5rem;
        font-weight: bold;
        text-align: center;
        padding: 10px 20px 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    .sidebar a {
        color: white;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        text-decoration: none;
        font-size: 15px;
        transition: background 0.2s, border-left 0.2s;
    }
    .sidebar a:hover, .sidebar a.active {
        background: var(--dark-green);
        border-left: 4px solid white;
    }
    .sidebar a .material-symbols-outlined {
        margin-right: 10px;
        font-size: 1.2rem;
    }
    .content {
        margin-left: var(--sidebar-width);
        padding: 30px;
        width: calc(100% - var(--sidebar-width));
        flex-grow: 1;
    }

    @media(max-width:992px) {
        .sidebar { width: 100%; height: auto; position: relative; padding-top: 10px; }
        .content { margin-left: 0; width: 100%; padding-top: 20px; }
    }
</style>
</head>
<body>
    <?php include 'adminSidePanel.php'; ?>
    <div class="content">
    <h2 class="fw-bold mb-4 mt-5">Admin Settings</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Add New User -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">Add New User</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="roleId" class="form-label">Role</label>
                            <select class="form-select" id="roleId" name="roleId">
                                <option value="1">Admin</option>
                                <option value="2">Farmer</option>
                                <option value="3" selected>Customer</option>
                            </select>
                        </div>
                        <button type="submit" name="addUser" class="btn btn-success">Add User</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change User Password -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">Change User Password</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="userId" class="form-label">Select User</label>
                            <select class="form-select" id="userId" name="userId" required>
                                <option value="">-- Select a user --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>">
                                        <?= htmlspecialchars($u['email']) ?> (<?= $u['roleId'] == 1 ? 'Admin' : ($u['roleId'] == 2 ? 'Farmer' : 'Customer') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        </div>
                        <button type="submit" name="changePassword" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- List of Users -->
    <div class="card shadow-sm">
        <div class="card-header">All Users</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= $u['roleId'] == 1 ? 'Admin' : ($u['roleId'] == 2 ? 'Farmer' : 'Customer') ?></td>
                            <td><?= $u['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="4" class="text-center">No users found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
