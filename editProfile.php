<?php
session_start();
include 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['id'];

// 1. Get user role + email
$stmt = mysqli_prepare($conn, "SELECT roleId, email FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$roleId = $user['roleId'];
$email  = $user['email'];

$isFarmer   = $roleId == 2;
$isCustomer = $roleId == 3;

// 2. Fetch profile data based on role
if ($isFarmer) {
    $stmt = mysqli_prepare($conn, "
        SELECT firstName, lastName, phoneNumber, radaIdNumber, address1, address2, parish
        FROM farmers WHERE userId=?
    ");
} else {
    $stmt = mysqli_prepare($conn, "
        SELECT firstName, lastName, phoneNumber, address1, address2, parish
        FROM customers WHERE userId=?
    ");
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// 3. Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['firstName']);
    $lastName  = trim($_POST['lastName']);
    $phone     = trim($_POST['phoneNumber']);
    $address1  = trim($_POST['address1']);
    $address2  = trim($_POST['address2']);
    $parish    = trim($_POST['parish']);
    $newEmail  = trim($_POST['email']);

    // Update email in users table
    $stmt = mysqli_prepare($conn, "UPDATE users SET email=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "si", $newEmail, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($isFarmer) {
        $radaId = trim($_POST['radaIdNumber']);
        $stmt = mysqli_prepare($conn, "
            UPDATE farmers 
            SET firstName=?, lastName=?, phoneNumber=?, radaIdNumber=?, address1=?, address2=?, parish=?
            WHERE userId=?
        ");
        mysqli_stmt_bind_param($stmt, "sssssssi",
            $firstName, $lastName, $phone, $radaId, $address1, $address2, $parish, $userId
        );
    } else {
        $stmt = mysqli_prepare($conn, "
            UPDATE customers 
            SET firstName=?, lastName=?, phoneNumber=?, address1=?, address2=?, parish=?
            WHERE userId=?
        ");
        mysqli_stmt_bind_param($stmt, "ssssssi",
            $firstName, $lastName, $phone, $address1, $address2, $parish, $userId
        );
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['firstName'] = $firstName;

    header("Location: editProfile.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Profile | StockCrop</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
<link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Edit Profile</h3>

                    <?php if(isset($_GET['success'])): ?>
                        <div class="alert alert-success">Profile updated successfully.</div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" name="firstName" class="form-control" required
                                       value="<?= htmlspecialchars($profile['firstName'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="lastName" class="form-control" required
                                       value="<?= htmlspecialchars($profile['lastName'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($email) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phoneNumber" class="form-control"
                                       value="<?= htmlspecialchars($profile['phoneNumber'] ?? '') ?>">
                            </div>

                            <?php if($isFarmer): ?>
                                <div class="col-md-6">
                                    <label class="form-label">RADA ID</label>
                                    <input type="text" name="radaIdNumber" class="form-control"
                                           value="<?= htmlspecialchars($profile['radaIdNumber'] ?? '') ?>">
                                </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="form-label">Parish</label>
                                <input type="text" name="parish" class="form-control"
                                       value="<?= htmlspecialchars($profile['parish'] ?? '') ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Address Line 1</label>
                                <input type="text" name="address1" class="form-control"
                                       value="<?= htmlspecialchars($profile['address1'] ?? '') ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Address Line 2</label>
                                <input type="text" name="address2" class="form-control"
                                       value="<?= htmlspecialchars($profile['address2'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= $isFarmer ? 'farmerDashboard.php' : 'customerDashboard.php#profile' ?>"
                               class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button class="btn btn-success px-4">Save Changes</button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
