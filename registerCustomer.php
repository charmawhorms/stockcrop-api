<?php
    session_start();
    include 'config.php'; 

    // Check if a redirection parameter exists (e.g., from checkout)
    $redirect_url = htmlspecialchars($_GET['redirect'] ?? 'login.php');

    $registration_successful = false;
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and Collect Input
        $firstName = mysqli_real_escape_string($conn, $_POST['firstName'] ?? '');
        $lastName = mysqli_real_escape_string($conn, $_POST['lastName'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber'] ?? '');
        $address1 = mysqli_real_escape_string($conn, $_POST['address1'] ?? '');
        $parish = mysqli_real_escape_string($conn, $_POST['parish'] ?? '');
        // address2 is optional
        $address2 = mysqli_real_escape_string($conn, $_POST['address2'] ?? '');

        // Server-side Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($phoneNumber) || empty($address1) || empty($parish)) {
            $error_message = "All required fields must be filled out.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } else {
            // Check for existing email
            $sql_check = "SELECT id FROM users WHERE email = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "s", $email);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $error_message = "This email is already registered. Please log in.";
            } else {
                // Secure Password Hashing
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $roleId = 3; 

                // Start Transaction for Atomic Inserts
                mysqli_begin_transaction($conn);
                
                try {
                    // A. Insert into users table
                    $sql_user = "INSERT INTO users (roleId, email, password_hash, created_at) VALUES (?, ?, ?, NOW())";
                    $stmt_user = mysqli_prepare($conn, $sql_user);
                    mysqli_stmt_bind_param($stmt_user, "iss", $roleId, $email, $password_hash);
                    mysqli_stmt_execute($stmt_user);
                    $new_user_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt_user);

                    // B. Insert into customers table
                    $sql_customer = "INSERT INTO customers (userId, firstName, lastName, phoneNumber, address1, address2, parish) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_customer = mysqli_prepare($conn, $sql_customer);
                    mysqli_stmt_bind_param($stmt_customer, "issssss", 
                        $new_user_id, 
                        $firstName, 
                        $lastName, 
                        $phoneNumber, 
                        $address1, 
                        $address2, 
                        $parish
                    );
                    mysqli_stmt_execute($stmt_customer);
                    mysqli_stmt_close($stmt_customer);
                    
                    // C. Commit the transaction
                    mysqli_commit($conn);
                    
                    $registration_successful = true;
                    
                    // Automatically log the user in
                    $_SESSION['id'] = $new_user_id;
                    $_SESSION['roleId'] = $roleId;
                    $_SESSION['email'] = $email;
                    $_SESSION['firstName'] = $firstName;

                    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {

                    // Check if the user already has a cart
                    $cartQuery = mysqli_prepare($conn, "SELECT id FROM cart WHERE userId = ?");
                    mysqli_stmt_bind_param($cartQuery, "i", $new_user_id);
                    mysqli_stmt_execute($cartQuery);
                    $cartResult = mysqli_stmt_get_result($cartQuery);

                    if ($cartRow = mysqli_fetch_assoc($cartResult)) {
                        $cartId = $cartRow['id'];
                    } else {
                        // Create new cart for the user
                        $insertCart = mysqli_prepare($conn, "INSERT INTO cart (userId) VALUES (?)");
                        mysqli_stmt_bind_param($insertCart, "i", $new_user_id);
                        mysqli_stmt_execute($insertCart);
                        $cartId = mysqli_insert_id($conn);
                        mysqli_stmt_close($insertCart);
                    }
                    mysqli_stmt_close($cartQuery);

                    // Add session cart items into database
                    foreach ($_SESSION['cart'] as $productId => $qty) {

                        // Get product price
                        $priceQuery = mysqli_prepare($conn, "SELECT price FROM products WHERE id = ?");
                        mysqli_stmt_bind_param($priceQuery, "i", $productId);
                        mysqli_stmt_execute($priceQuery);
                        $priceResult = mysqli_stmt_get_result($priceQuery);
                        $priceRow = mysqli_fetch_assoc($priceResult);
                        mysqli_stmt_close($priceQuery);

                        if (!$priceRow) continue; // product removed

                        $price = $priceRow['price'];

                        // Check if item already exists in cart
                        $itemQuery = mysqli_prepare($conn, "SELECT quantity FROM cartItems WHERE cartId = ? AND productId = ?");
                        mysqli_stmt_bind_param($itemQuery, "ii", $cartId, $productId);
                        mysqli_stmt_execute($itemQuery);
                        $itemResult = mysqli_stmt_get_result($itemQuery);

                        if ($itemRow = mysqli_fetch_assoc($itemResult)) {
                            $newQty = $itemRow['quantity'] + $qty;
                            $updateItem = mysqli_prepare($conn, "UPDATE cartItems SET quantity = ? WHERE cartId = ? AND productId = ?");
                            mysqli_stmt_bind_param($updateItem, "iii", $newQty, $cartId, $productId);
                            mysqli_stmt_execute($updateItem);
                            mysqli_stmt_close($updateItem);
                        } else {
                            $insertItem = mysqli_prepare($conn, "INSERT INTO cartItems (cartId, productId, quantity, price) VALUES (?, ?, ?, ?)");
                            mysqli_stmt_bind_param($insertItem, "iiid", $cartId, $productId, $qty, $price);
                            mysqli_stmt_execute($insertItem);
                            mysqli_stmt_close($insertItem);
                        }
                        mysqli_stmt_close($itemQuery);
                    }

                    // Clear guest session cart
                    unset($_SESSION['cart']);
                }

                    
                    header("Location: " . $redirect_url);
                    exit();

                } catch (Exception $e) {
                    // D. Rollback on failure
                    mysqli_rollback($conn);
                    error_log("Customer registration failed: " . $e->getMessage());
                    $error_message = "Registration failed due to a system error. Please try again.";
                }
            }
            mysqli_stmt_close($stmt_check);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>StockCrop | Join the Community</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <style>
        :root {
            --primary-green: #2f8f3f;
            --dark-forest: #1b3921;
            --soft-gray: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            margin: 0;
        }

        .register-wrapper { display: flex; min-height: 100vh; }

        /* --- Left Side: Freshness Visual --- */
        .register-visual {
            flex: 1;
            background: linear-gradient(rgba(27, 57, 33, 0.7), rgba(27, 57, 33, 0.7)), 
                        url('https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1974&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        /* --- Right Side: Form --- */
        .register-form-container {
            flex: 1.2;
            background: white;
            padding: 60px;
            overflow-y: auto;
        }

        .form-max-width { max-width: 600px; margin: 0 auto; }

        .back-home {
            text-decoration: none;
            color: var(--dark-forest);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            transition: 0.3s;
        }
        .back-home:hover { color: var(--primary-green); transform: translateX(-5px); }

        .section-header {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--primary-green);
            margin: 30px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .section-header::after { content: ""; flex: 1; height: 1px; background: var(--border-color); }

        /* --- Modern Form Elements --- */
        .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
            background: var(--soft-gray);
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary-green);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(47, 143, 63, 0.1);
        }

        .btn-submit {
            background: var(--dark-forest);
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            transition: 0.3s;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background: var(--primary-green);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(27, 57, 33, 0.15);
        }

        .alert-modern {
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @media (max-width: 992px) {
            .register-visual { display: none; }
            .register-form-container { padding: 40px 20px; }
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-visual">
        <div class="visual-content">
            <img src="assets/logo2.png" alt="StockCrop" height="45" class="mb-5">
            <h1 class="display-4 fw-bold mb-4">The freshest path <br>to your plate.</h1>
            <p class="lead opacity-75 mb-5">Skip the supermarket lines. Get authentic Jamaican produce delivered directly from the farm to your door.</p>
            
            <div class="d-flex flex-column gap-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="material-symbols-outlined text-warning">local_shipping</span>
                    <span>Direct home delivery across 14 parishes</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="material-symbols-outlined text-warning">eco</span>
                    <span>Support local farm families directly</span>
                </div>
            </div>
        </div>
    </div>

    <div class="register-form-container">
        <div class="form-max-width">
            <a href="index.php" class="back-home">
                <span class="material-symbols-outlined">arrow_back</span> Back to Home
            </a>

            <h2 class="fw-bold text-dark mb-1">Create Account</h2>
            <p class="text-muted mb-4">Start shopping for fresh local produce today.</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-modern mb-4 shadow-sm" role="alert">
                    <span class="material-symbols-outlined">error</span>
                    <div><?= $error_message ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="registerCustomer.php?redirect=<?= $redirect_url ?>">
                
                <div class="section-header">Account Security</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" placeholder="example@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="section-header">Personal Details</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="firstName" placeholder="Jane" required value="<?= htmlspecialchars($_POST['firstName'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="lastName" placeholder="Doe" required value="<?= htmlspecialchars($_POST['lastName'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phoneNumber" placeholder="876-000-0000" required value="<?= htmlspecialchars($_POST['phoneNumber'] ?? '') ?>">
                    </div>
                </div>

                <div class="section-header">Delivery Address</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Street Address</label>
                        <input type="text" class="form-control" name="address1" placeholder="House number and street name" required value="<?= htmlspecialchars($_POST['address1'] ?? '') ?>">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Apt/Suite (Optional)</label>
                        <input type="text" class="form-control" name="address2" placeholder="e.g. Apt 4B" value="<?= htmlspecialchars($_POST['address2'] ?? '') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Parish</label>
                        <select class="form-select" name="parish" required>
                            <option value="">Select...</option>
                            <?php 
                                $parishes = ["Kingston", "St. Andrew", "St. Thomas", "Portland", "St. Mary", "St. Ann", "Trelawny", "St. James", "Hanover", "Westmoreland", "St. Elizabeth", "Manchester", "Clarendon", "St. Catherine"];
                                $selected_parish = $_POST['parish'] ?? '';
                                foreach ($parishes as $p) {
                                    $selected = ($p === $selected_parish) ? 'selected' : '';
                                    echo "<option value=\"$p\" $selected>$p</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit w-100 mt-5">
                    Create Customer Account
                </button>

                <p class="text-center mt-4 text-muted small">
                    Already have an account? <a href="login.php?redirect=<?= $redirect_url ?>" class="fw-bold text-success text-decoration-none">Sign In</a>
                </p>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>