<?php
    include 'config.php';

    $farmerRoleId = 2;
    $showSuccess = false;
    $showError = false;
    $errorMessage = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
        $firstName = htmlspecialchars(trim($_POST["firstName"]));
        $lastName = htmlspecialchars(trim($_POST["lastName"]));
        $email = htmlspecialchars(trim($_POST["email"]));
        $phoneNumber = htmlspecialchars(trim($_POST["phoneNumber"]));
        $farmerType = $_POST["farmerType"]; // verified | guest
        $radaIdNumber = htmlspecialchars(trim($_POST["radaIdNumber"] ?? ''));
        $address1 = htmlspecialchars(trim($_POST["address1"]));
        $address2 = htmlspecialchars(trim($_POST["address2"]));
        $parish = htmlspecialchars(trim($_POST["parish"]));
        $govtIdNumber = htmlspecialchars(trim($_POST["govtIdNumber"] ?? ''));
        $trn = htmlspecialchars(trim($_POST["trn"] ?? ''));
        $password = $_POST["password"];
        $confirmPassword = $_POST["confirmPassword"];

        // Password check
        if ($password !== $confirmPassword) {
            $showError = true;
            $errorMessage = "Passwords do not match. Please re-enter your password.";
            goto end;
        }

        // Conditional validation
        if ($farmerType === "verified") {
            // RADA verified - RADA ID required
            if (empty($radaIdNumber)) {
                $showError = true;
                $errorMessage = "RADA ID is required for RADA verified farmers.";
                goto end;
            }
        } else if ($farmerType === "guest") {
            // Guest - Govt ID and TRN required
            if (empty($govtIdNumber) || !isset($_FILES['govtIdFile']) || $_FILES['govtIdFile']['error'] !== 0) {
                $showError = true;
                $errorMessage = "Government ID number and document upload are required for guest farmers.";
                goto end;
            }
            if (empty($trn) || !isset($_FILES['trnFile']) || $_FILES['trnFile']['error'] !== 0) {
                $showError = true;
                $errorMessage = "TRN number and document upload are required for guest farmers.";
                goto end;
            }
            // TRN format check
            if (!preg_match("/^\d{9}$/", $trn)) {
                $showError = true;
                $errorMessage = "Please enter a valid 9-digit TRN.";
                goto end;
            }
        }

        // Check email uniqueness
        $query = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($query, "s", $email);
        mysqli_stmt_execute($query);
        $result = mysqli_stmt_get_result($query);
        if (mysqli_num_rows($result) > 0) {
            $showError = true;
            $errorMessage = "Email already exists. Please use a different email address.";
            goto end;
        }

        // Check TRN uniqueness for guests
        if ($farmerType === "guest") {
            $checkTRN = mysqli_prepare($conn, "SELECT id FROM farmers WHERE trn = ?");
            mysqli_stmt_bind_param($checkTRN, "s", $trn);
            mysqli_stmt_execute($checkTRN);
            $resultTRN = mysqli_stmt_get_result($checkTRN);
            if (mysqli_num_rows($resultTRN) > 0) {
                $showError = true;
                $errorMessage = "This TRN is already registered.";
                goto end;
            }
        }

        // Upload files if guest
        $allowedTypes = ['image/jpeg','image/png','application/pdf'];
        $govtIdFileName = null;
        $trnFileName = null;
        if ($farmerType === "guest") {
            // Govt ID
            $govtIdFileName = "uploads/govtId_".time()."_".basename($_FILES['govtIdFile']['name']);
            if (!in_array($_FILES['govtIdFile']['type'], $allowedTypes)) {
                $showError = true;
                $errorMessage = "Government ID must be JPG, PNG, or PDF.";
                goto end;
            }
            move_uploaded_file($_FILES['govtIdFile']['tmp_name'], $govtIdFileName);

            // TRN
            $trnFileName = "uploads/trn_".time()."_".basename($_FILES['trnFile']['name']);
            if (!in_array($_FILES['trnFile']['type'], $allowedTypes)) {
                $showError = true;
                $errorMessage = "TRN document must be JPG, PNG, or PDF.";
                goto end;
            }
            move_uploaded_file($_FILES['trnFile']['tmp_name'], $trnFileName);
        }

        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query2 = mysqli_prepare($conn, "INSERT INTO users (roleId,email,password_hash,created_at) VALUES (?,?,?,NOW())");
        mysqli_stmt_bind_param($query2, "iss", $farmerRoleId, $email, $hashedPassword);
        if (!mysqli_stmt_execute($query2)) {
            $showError = true;
            $errorMessage = "Unable to create user account.";
            goto end;
        }
        $userId = mysqli_insert_id($conn);

        // RADA farmers are instantly verified; Guests remain as guest status
        $verificationStatus = ($farmerType === "verified") ? "verified" : "guest";
        if ($farmerType === "guest") $radaIdNumber = null;

        // Insert farmer
        $insertFarmer = mysqli_prepare($conn, "
            INSERT INTO farmers 
            (userId,firstName,lastName,email,phoneNumber,radaIdNumber,address1,address2,parish,farmerType,verification_status,govtIdNumber,govtIdFile,trn,trnFile,created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
        ");
        mysqli_stmt_bind_param(
            $insertFarmer,
            "issssssssssssss",
            $userId, $firstName, $lastName, $email, $phoneNumber, $radaIdNumber, $address1, $address2, $parish, $farmerType, $verificationStatus, $govtIdNumber, $govtIdFileName, $trn, $trnFileName
        );

        if ($insertFarmer && mysqli_stmt_execute($insertFarmer)) {
            $showSuccess = true;
        } else {
            $showError = true;
            $errorMessage = "Unable to create farmer details.";
        }

    } end:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>StockCrop | Join as a Farmer</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* Same styling as before */
:root{--primary-green:#2f8f3f;--dark-forest:#1b3921;--soft-gray:#f8fafc;--border-color:#e2e8f0}
body{font-family:'Inter',sans-serif;background-color:#fff;margin:0}
.register-wrapper{display:flex;min-height:100vh}
.register-visual{flex:1;background:linear-gradient(rgba(27,57,33,0.8),rgba(27,57,33,0.8)),url('https://images.unsplash.com/photo-1595841696677-6489ff3f8cd1?auto=format&fit=crop&w=1200&q=80');background-size:cover;background-position:center;display:flex;flex-direction:column;justify-content:center;padding:60px;color:white;position:sticky;top:0;height:100vh}
.visual-content h1{font-weight:800;font-size:2.8rem;margin-bottom:20px}
.register-form-container{flex:1.5;background:white;padding:80px 60px;overflow-y:auto}
.form-max-width{max-width:700px;margin:0 auto}
.back-home{text-decoration:none;color:var(--dark-forest);font-weight:600;display:inline-flex;align-items:center;gap:8px;margin-bottom:40px;transition:.3s}
.back-home:hover{color:var(--primary-green);transform:translateX(-5px)}
.section-title{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:var(--primary-green);margin-bottom:25px;display:flex;align-items:center;gap:10px}
.section-title::after{content:"";flex:1;height:1px;background:var(--border-color)}
.form-label{font-size:.85rem;font-weight:600;color:#475569;margin-bottom:8px}
.form-control,.form-select{padding:12px 16px;border-radius:10px;border:2px solid var(--border-color);background:var(--soft-gray);font-size:.95rem;transition:.3s}
.form-control:focus,.form-select:focus{border-color:var(--primary-green);background:#fff;box-shadow:0 0 0 4px rgba(47,143,63,0.1)}
.btn-register{background:var(--dark-forest);color:white;padding:16px;border-radius:12px;font-weight:700;border:none;transition:.3s;margin-top:20px}
.btn-register:hover{background:var(--primary-green);transform:translateY(-2px);box-shadow:0 10px 20px rgba(27,57,33,0.2)}
@media(max-width:992px){.register-visual{display:none}.register-form-container{padding:40px 20px}}
</style>
</head>
<body>

<div class="register-wrapper">
<div class="register-visual">
<div class="visual-content">
<img src="assets/logo2.png" alt="StockCrop" height="40" class="mb-5">
<h1>Grow your <br>business with us.</h1>
<p class="lead opacity-75">Connect directly with thousands of customers across Jamaica.</p>
<div class="mt-5">
<div class="d-flex align-items-center gap-3 mb-3"><span class="material-symbols-outlined text-warning">verified_user</span><span>Exclusive RADA Farmer Network</span></div>
<div class="d-flex align-items-center gap-3"><span class="material-symbols-outlined text-warning">payments</span><span>Faster Payments & Direct Deposits</span></div>
</div>
</div>
</div>

<div class="register-form-container">
<div class="form-max-width">

<?php if($showSuccess): ?>
<script>
Swal.fire({icon:'success',title:'Registration Successful!',text:'Your farmer account has been created.',confirmButtonText:'Go to Login'}).then(()=>{window.location.href='login.php';});
</script>
<?php elseif($showError): ?>
<script>
Swal.fire({icon:'error',title:'Error',text:'<?= $errorMessage ?>'});
</script>
<?php endif; ?>

<a href="index.php" class="back-home"><span class="material-symbols-outlined">arrow_back</span> Back to Home</a>
<h2 class="fw-bold mb-2">Create Farmer Account</h2>
<p class="text-muted mb-5">Join the digital marketplace for Jamaican agriculture.</p>

<form action="registerFarmer.php" method="POST" enctype="multipart/form-data">

<!-- Personal Details -->
<div class="section-title">Personal Details</div>
<div class="row g-3 mb-4">
<div class="col-md-6"><label class="form-label">First Name</label><input type="text" name="firstName" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Last Name</label><input type="text" name="lastName" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" name="phoneNumber" class="form-control" required></div>
</div>

<!-- Farmer Type -->
<div class="section-title">Farmer Type</div>
<div class="mb-4">
<label class="form-label">Are you RADA registered?</label>
<select name="farmerType" id="farmerType" class="form-select" required>
<option value="">Select...</option>
<option value="verified">Yes, I am RADA registered</option>
<option value="guest">No, I am not RADA registered</option>
</select>
</div>

<!-- RADA ID -->
<div class="section-title" id="radaSection">Farm Verification</div>
<div class="mb-4" id="radaDiv">
<label class="form-label">RADA ID Number</label>
<input type="text" name="radaIdNumber" id="radaIdNumber" class="form-control" placeholder="Required if RADA verified">
</div>

<!-- Guest Govt ID/TRN -->
<div class="section-title" id="guestDocsSection">Identification Documents</div>
<div id="guestDocsDiv">
<div class="row g-3 mb-4">
<div class="col-md-6"><label class="form-label">Government ID Number</label><input type="text" name="govtIdNumber" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Upload Government ID Document</label><input type="file" name="govtIdFile" class="form-control"></div>
</div>
<div class="row g-3 mb-4">
<div class="col-md-6"><label class="form-label">TRN Number</label><input type="text" name="trn" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Upload TRN Document</label><input type="file" name="trnFile" class="form-control"></div>
</div>
</div>

<!-- Location -->
<div class="section-title">Location</div>
<div class="row g-3 mb-4">
<div class="col-12"><label class="form-label">Address Line 1</label><input type="text" name="address1" class="form-control" required></div>
<div class="col-md-8"><label class="form-label">Address Line 2</label><input type="text" name="address2" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Parish</label>
<select name="parish" class="form-select" required>
<option value="">Select...</option>
<option>Kingston</option><option>St. Andrew</option><option>St. Catherine</option><option>Clarendon</option><option>Manchester</option><option>St. Elizabeth</option><option>Westmoreland</option><option>Hanover</option><option>St. James</option><option>Trelawny</option><option>St. Ann</option><option>St. Mary</option><option>Portland</option><option>St. Thomas</option>
</select></div>
</div>

<!-- Security -->
<div class="section-title">Security</div>
<div class="row g-3 mb-5">
<div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="confirmPassword" class="form-control" required></div>
</div>

<button type="submit" name="submit" class="btn btn-register w-100">Complete Registration</button>
<p class="text-center mt-4 text-muted">Already part of the network? <a href="login.php" class="fw-bold text-success text-decoration-none">Sign In</a></p>

</form>
</div>
</div>
</div>

<script>
// Dynamically show/hide fields based on farmer type
const farmerTypeSelect = document.getElementById('farmerType');
const radaDiv = document.getElementById('radaDiv');
const guestDocsDiv = document.getElementById('guestDocsDiv');
const radaInput = document.getElementById('radaIdNumber');

function toggleFields() {
    if (farmerTypeSelect.value === 'verified') {
        radaDiv.style.display = 'block';
        radaInput.required = true;
        guestDocsDiv.style.display = 'none';
        guestDocsDiv.querySelectorAll('input').forEach(i=>i.required=false);
    } else if (farmerTypeSelect.value === 'guest') {
        radaDiv.style.display = 'none';
        radaInput.required = false;
        guestDocsDiv.style.display = 'block';
        guestDocsDiv.querySelectorAll('input').forEach(i=>i.required=true);
    } else {
        radaDiv.style.display = 'none';
        guestDocsDiv.style.display = 'none';
        radaInput.required = false;
        guestDocsDiv.querySelectorAll('input').forEach(i=>i.required=false);
    }
}

farmerTypeSelect.addEventListener('change', toggleFields);
window.addEventListener('load', toggleFields);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
