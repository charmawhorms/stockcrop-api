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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    
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
            transition: all 0.3s;
        }

        .profile-card {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--sc-primary), var(--sc-dark));
            padding: 3rem 2rem;
            color: white;
            text-align: center;
        }

        .avatar-placeholder {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 40px;
        }

        .section-header {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 1.5rem;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #f1f5f9;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
        }

        .input-group-text {
            background: transparent;
            border-right: none;
            color: #94a3b8;
        }

        .form-control {
            border-radius: 10px;
            padding: 0.65rem 1rem;
            border: 1.5px solid #e2e8f0;
        }

        .form-control:focus {
            border-color: var(--sc-primary);
            box-shadow: 0 0 0 4px rgba(2, 128, 55, 0.1);
        }

        .btn-save {
            background: var(--sc-primary);
            border: none;
            padding: 0.8rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-save:hover {
            background: var(--sc-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 128, 55, 0.2);
        }

        @media (max-width: 992px) {
            .content { margin-left: 0; padding-top: 110px; }
        }
    </style>
</head>
<body>

<?php include 'sidePanel.php'; ?>

<div class="content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="avatar-placeholder">
                            <span class="material-symbols-outlined" style="font-size: 50px;">person</span>
                        </div>
                        <h3 class="fw-bold mb-0"><?= htmlspecialchars($profile['firstName'] . ' ' . $profile['lastName']) ?></h3>
                        <p class="opacity-75 mb-0 small text-uppercase fw-bold mt-1">
                            <?= $isFarmer ? 'Registered Farmer' : 'Customer Account' ?>
                        </p>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <?php if(isset($_GET['success'])): ?>
                            <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-4">
                                <span class="material-symbols-outlined me-2">check_circle</span>
                                <div>Your profile has been successfully updated!</div>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="section-header">Personal Information</div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="firstName" class="form-control" required value="<?= htmlspecialchars($profile['firstName'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="lastName" class="form-control" required value="<?= htmlspecialchars($profile['lastName'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phoneNumber" class="form-control" placeholder="876-000-0000" value="<?= htmlspecialchars($profile['phoneNumber'] ?? '') ?>">
                                </div>
                                <?php if($isFarmer): ?>
                                <div class="col-md-6">
                                    <label class="form-label">RADA ID Number</label>
                                    <input type="text" name="radaIdNumber" class="form-control fw-bold text-success" style="background-color: #f0fdf4" value="<?= htmlspecialchars($profile['radaIdNumber'] ?? '') ?>">
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="section-header">Location & Logistics</div>
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label class="form-label">Street Address</label>
                                    <input type="text" name="address1" class="form-control mb-3" placeholder="Line 1" value="<?= htmlspecialchars($profile['address1'] ?? '') ?>">
                                    <input type="text" name="address2" class="form-control" placeholder="Line 2 (Optional)" value="<?= htmlspecialchars($profile['address2'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parish</label>
                                    <select name="parish" class="form-select form-control">
                                        <?php 
                                        $parishes = ["Kingston", "St. Andrew", "St. Catherine", "Clarendon", "Manchester", "St. Elizabeth", "Westmoreland", "Hanover", "St. James", "Trelawny", "St. Ann", "St. Mary", "Portland", "St. Thomas"];
                                        foreach($parishes as $p): 
                                            $selected = (isset($profile['parish']) && $profile['parish'] == $p) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $p ?>" <?= $selected ?>><?= $p ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-top d-flex justify-content-between align-items-center">
                                <a href="<?= $isFarmer ? 'farmerDashboard.php' : 'customerDashboard.php' ?>" class="text-muted text-decoration-none fw-medium d-flex align-items-center">
                                    <span class="material-symbols-outlined me-1" style="font-size: 18px;">arrow_back</span>
                                    Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-success btn-save">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>