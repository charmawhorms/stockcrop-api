<?php
    // Ensure session and DB connection
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    include_once 'config.php';

    // Redirect if not logged in
    if (!isset($_SESSION['id']) || $_SESSION['roleId'] != 2) {
        header("Location: index.php");
        exit;
    }

    // Fetch farmer info if not already set
    if (!isset($farm_info)) {
    $userId = $_SESSION['id'];
    $stmt = mysqli_prepare($conn, "SELECT firstName, lastName FROM farmers WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $farmResult = mysqli_stmt_get_result($stmt); // <-- rename
    if ($farmResult && mysqli_num_rows($farmResult) > 0) {
        $farm_info = mysqli_fetch_assoc($farmResult);
    } else {
        $farm_info = ['firstName' => 'Farmer', 'lastName' => ''];
    }
}

?>

<style>
    :root {
        --sc-primary-green: #028037;
        --sc-dark-green: #01632c;
        --sc-hover-green: #146c43;
        --sc-background: #f5f7fa;
        --sc-light-text: #e0e0e0;
    }

    body { 
        font-family: 'Roboto', sans-serif; 
        background-color: var(--sc-background); 
        margin:0; 
        overflow-x: hidden;
    }

    /* --- Sidebar --- */
    .sidebar {
        width: 250px; 
        background-color: var(--sc-primary-green);
        color: #fff;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        padding: 1rem 0;
        position: fixed;
        top: 0;
        bottom: 0;
        z-index: 1030;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .sidebar .logo-container {
        padding: 10px 20px 30px;
        text-align: center;
    }

    .sidebar .logo-container img { max-height: 40px; }

    .sidebar-link {
        color: var(--sc-light-text);
        text-decoration: none;
        padding: 12px 25px;
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 0 10px;
        border-radius: 8px;
        transition: background-color 0.3s, color 0.3s;
    }

    .sidebar-link i { font-size: 1.2rem; }

    .sidebar-link:hover, .sidebar-link.active { 
        background-color: var(--sc-dark-green); 
        color: #fff; 
    }

    .sidebar-link.active {
        font-weight: 700;
        border-left: 5px solid #ffc107;
        padding-left: 20px;
    }

    /* --- Top Navbar --- */
    .navbar-top {
        background-color: #fff;
        border-bottom: 1px solid #ddd;
        color: #333;
        z-index: 1020;
        position: fixed;
        left: 250px; 
        right: 0;
        height: 60px; 
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1.5rem;
    }

    .navbar-top .navbar-brand { 
        color: var(--sc-primary-green); 
        font-weight: 700; 
        font-size: 1.2rem; 
    }

    .navbar-top .btn-logout { 
        background: #ffc107; 
        color: #212529; 
        font-weight: 600; 
        border: none;
        border-radius: 6px;
        padding: 6px 15px;
    }

    /* --- Responsive Layout (Mobile) --- */
    @media(max-width: 992px){
        .sidebar { 
            position: fixed; 
            left: -250px;
            transition: left 0.3s;
        }
        .show-sidebar { left: 0; }
        .navbar-top { left: 0; }
    }
</style>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

<!-- Top Navbar -->
<nav class="navbar navbar-top">
    <button class="navbar-toggler d-lg-none me-3" type="button" onclick="toggleSidebar()">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="d-flex align-items-center">
        <span class="fw-bold d-none d-sm-block">
            Hello, <?php echo htmlspecialchars($farm_info['firstName'] ?? 'Farmer'); ?>
        </span>
    </div>
    <a href="logout.php" class="btn btn-logout">Logout</a>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo-container">
        <a href="farmerDashboard.php">
            <img src="assets/logo2.png" alt="StockCrop Logo">
        </a>
    </div>

    <a href="farmerDashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'farmerDashboard.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">dashboard</span> Dashboard
    </a>

    <a href="viewProducts.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'viewProducts.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">inventory_2</span> My Products
    </a>

    <a href="addProduct.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'addProduct.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">add_circle_outline</span> Add Product
    </a>

    <a href="viewOrders.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'viewOrders.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">receipt_long</span> Orders
    </a>

    <a href="editProfile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'editProfile.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">settings</span> Profile Settings
    </a>

    <a href="changePassword.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'changePassword.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">lock</span> Change Password
    </a>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('show-sidebar');
    }
</script>
