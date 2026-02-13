<?php
session_start();

include 'config.php';
include 'session.php'; 

// --- Redirection Logic ---
// Get the redirect URL from the query string (e.g., login.php?redirect=checkout.php).
// If no redirect is specified, default to 'customerDashboard.php'.
$redirect_url = htmlspecialchars($_GET['redirect'] ?? 'customerDashboard.php');

// If the user is already logged in, redirect them away from the login page.
redirectIfLoggedIn();

// Initialize error message to avoid undefined variable warning
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Query to find user by email using a prepared statement (security)
    $query = mysqli_prepare($conn, "SELECT id, roleId, password_hash FROM users WHERE email = ?");
    mysqli_stmt_bind_param($query, "s", $email);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Verify password against the stored hash
        if (password_verify($password, $user["password_hash"])) {
            session_regenerate_id(true); // Prevent session fixation

            // Set core session variables
            $_SESSION["id"] = $user["id"];
            $_SESSION["roleId"] = $user["roleId"];


            // 3. Redirect based on role
            if ($user["roleId"] == 2) {
                // Farmer login always goes to the dashboard
                header("Location: farmerDashboard.php");
                exit();
            } elseif ($user["roleId"] == 1) {
                // Admin login always goes to the dashboard
                header("Location: adminDashboard.php");
                exit();
            } elseif ($user["roleId"] == 3) { 
                // CUSTOMER LOGIN FLOW

                // Fetch Customer data (firstName) for the session
                $customer_query = mysqli_prepare($conn, "SELECT firstName, lastName, address1, address2, parish FROM customers WHERE userId = ?");
                mysqli_stmt_bind_param($customer_query, "i", $user["id"]);
                mysqli_stmt_execute($customer_query);
                $customer_result = mysqli_stmt_get_result($customer_query);
                
                if ($customer_row = mysqli_fetch_assoc($customer_result)) {
                    $_SESSION['firstName'] = $customer_row['firstName'];
                    $_SESSION['lastName']  = $customer_row['lastName'];
                    $_SESSION['address1']  = $customer_row['address1'];
                    $_SESSION['address2']  = $customer_row['address2'];
                    $_SESSION['parish']  = $customer_row['parish'];
                }
                mysqli_stmt_close($customer_query);

                // === MERGE GUEST CART INTO DATABASE CART ===
                if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {

                    // Find or create user's cart
                    $cartQuery = mysqli_prepare($conn, "SELECT id FROM cart WHERE userId = ?");
                    mysqli_stmt_bind_param($cartQuery, "i", $user["id"]);
                    mysqli_stmt_execute($cartQuery);
                    $cartResult = mysqli_stmt_get_result($cartQuery);

                    if ($cartRow = mysqli_fetch_assoc($cartResult)) {
                        $cartId = $cartRow['id'];
                    } else {
                        $insertCart = mysqli_prepare($conn, "INSERT INTO cart (userId) VALUES (?)");
                        mysqli_stmt_bind_param($insertCart, "i", $user["id"]);
                        mysqli_stmt_execute($insertCart);
                        $cartId = mysqli_insert_id($conn);
                        mysqli_stmt_close($insertCart);
                    }
                    mysqli_stmt_close($cartQuery);

                    // Loop through session cart items
                    foreach ($_SESSION['cart'] as $productId => $qty) {

                        // Get product price
                        $priceQuery = mysqli_prepare($conn, "SELECT price FROM products WHERE id = ?");
                        mysqli_stmt_bind_param($priceQuery, "i", $productId);
                        mysqli_stmt_execute($priceQuery);
                        $priceResult = mysqli_stmt_get_result($priceQuery);
                        $priceRow = mysqli_fetch_assoc($priceResult);
                        mysqli_stmt_close($priceQuery);

                        if (!$priceRow) continue; // product no longer exists

                        $price = $priceRow['price'];

                        // Check if product already in cart
                        $itemQuery = mysqli_prepare($conn,
                            "SELECT quantity FROM cartItems WHERE cartId = ? AND productId = ?"
                        );
                        mysqli_stmt_bind_param($itemQuery, "ii", $cartId, $productId);
                        mysqli_stmt_execute($itemQuery);
                        $itemResult = mysqli_stmt_get_result($itemQuery);

                        if ($itemRow = mysqli_fetch_assoc($itemResult)) {
                            // Update quantity
                            $newQty = $itemRow['quantity'] + $qty;
                            $updateItem = mysqli_prepare($conn,
                                "UPDATE cartItems SET quantity = ? WHERE cartId = ? AND productId = ?"
                            );
                            mysqli_stmt_bind_param($updateItem, "iii", $newQty, $cartId, $productId);
                            mysqli_stmt_execute($updateItem);
                            mysqli_stmt_close($updateItem);
                        } else {
                            // Insert new item
                            $insertItem = mysqli_prepare($conn,
                                "INSERT INTO cartItems (cartId, productId, quantity, price)
                                VALUES (?, ?, ?, ?)"
                            );
                            mysqli_stmt_bind_param($insertItem, "iiid", $cartId, $productId, $qty, $price);
                            mysqli_stmt_execute($insertItem);
                            mysqli_stmt_close($insertItem);
                        }

                        mysqli_stmt_close($itemQuery);
                    }

                    // Clear session cart
                    unset($_SESSION['cart']);
                }


                // Redirect to the originally requested page ($redirect_url)
                header("Location: " . $redirect_url);
                exit();

            } else {
                $errorMessage = "Invalid account role. Please contact support.";
            }
        } else {
            $errorMessage = "Invalid login credentials. Please try again.";
        }
    } else {
        $errorMessage = "Invalid email or password.";
    }

    mysqli_stmt_close($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StockCrop | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-green: #2f8f3f;
            --dark-forest: #1b3921;
            --harvest-gold: #ffeb3b;
            --soft-gray: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        /* --- Split Layout --- */
        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* --- Left Side: Branding & Visual --- */
        .login-visual {
            flex: 1.2;
            background: linear-gradient(rgba(27, 57, 33, 0.8), rgba(27, 57, 33, 0.8)), 
                        url('https://images.unsplash.com/photo-1601004890684-d8cbf643f5f2?auto=format&fit=crop&w=1400&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            color: white;
        }

        .visual-content { max-width: 500px; }
        .visual-content h1 { font-weight: 800; font-size: 3rem; margin-bottom: 20px; line-height: 1.1; }
        
        /* --- Right Side: Form --- */
        .login-form-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            padding: 60px;
            justify-content: center;
            position: relative;
        }

        .back-home {
            position: absolute;
            top: 40px;
            left: 60px;
            text-decoration: none;
            color: var(--dark-forest);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .back-home:hover { color: var(--primary-green); transform: translateX(-5px); }

        .form-card { max-width: 400px; width: 100%; margin: 0 auto; }
        
        .login-form-container h3 { font-weight: 800; font-size: 2rem; color: var(--dark-forest); }

        /* --- Modern Inputs --- */
        .form-label { 
            font-size: 0.85rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            color: #64748b; 
            margin-bottom: 8px; 
        }

        .form-control {
            padding: 14px 18px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: var(--soft-gray);
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary-green);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(47, 143, 63, 0.1);
        }

        .btn-login {
            background: var(--dark-forest);
            color: white;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(27, 57, 33, 0.2);
        }
        .btn-login:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 30px 0;
            color: #cbd5e1;
        }
        .divider::before, .divider::after { 
            content: ''; 
            flex: 1; 
            border-bottom: 1px solid #e2e8f0; 
        }

        .divider:not(:empty)::before { 
            margin-right: .5em; 
        }

        .divider:not(:empty)::after { 
            margin-left: .5em; 
        }

        .reg-link {
            text-decoration: none;
            color: var(--primary-green);
            font-weight: 700;
        }

        /* --- Responsive --- */
        @media (max-width: 1024px) {
            .login-visual { display: none; }
            .login-form-container { padding: 40px 20px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-visual">
            <div class="visual-content">
                <img src="assets/logo2.png" alt="StockCrop" height="50" class="mb-5">
                <h1>Freshly Picked <br>Just for You.</h1>
                <p class="lead opacity-75">Join Jamaica's most efficient farm-to-door network. Your pantry is waiting.</p>
                
                <div class="mt-5 d-flex gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined">verified</span>
                        <small>RADA Verified</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined">local_shipping</span>
                        <small>Island-wide Delivery</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-form-container">
            <a href="index.php" class="back-home">
                <span class="material-symbols-outlined">arrow_back</span> Back to Home
            </a>

            <div class="form-card">
                <h3>Welcome back</h3>
                <p class="text-muted mb-4">Enter your credentials to access your account</p>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger border-0 rounded-4 py-3" role="alert">
                        <small class="fw-bold"><?php echo $errorMessage; ?></small>
                    </div>
                <?php endif; ?>

                <form action="login.php?redirect=<?= urlencode($redirect_url) ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="example@gmail.com">
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label">Password</label>
                            <a href="#" class="text-muted small text-decoration-none">Forgot?</a>
                        </div>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <button type="submit" name="submit" class="btn btn-login w-100 mb-3">
                        Sign In to Account
                    </button>

                    <div class="divider small fw-bold">OR JOIN THE MOVEMENT</div>

                    <div class="text-center small">
                        <p class="mb-2">Are you a farmer? <a href="registerFarmer.php" class="reg-link">Register Here</a></p>
                        <p class="mb-0">Hungry for fresh? <a href="registerCustomer.php?redirect=<?= urlencode($redirect_url) ?>" class="reg-link">Sign Up as a Customer</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>