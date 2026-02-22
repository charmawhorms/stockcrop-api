<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'config.php';

// Redirect if not logged in or not a farmer
if (!isset($_SESSION['id']) || $_SESSION['roleId'] != 2) {
    header("Location: index.php");
    exit;
}

$is_logged_in = isset($_SESSION['id']);
$user_role_id = $_SESSION['roleId'];
$userId = $_SESSION['id'];

// Fetch farmer info
$farmerId = null;

$stmt = mysqli_prepare($conn, "SELECT id, firstName, lastName FROM farmers WHERE userId = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$farm_info = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($farm_info) {
    $farmerId = $farm_info['id'];
}

$newOrders = $newBids = [];

if ($farmerId) {
    // New orders
    $stmt = mysqli_prepare($conn, "
        SELECT DISTINCT 
            o.id AS orderId, 
            o.orderDate,
            c.firstName, 
            c.lastName
        FROM order_items oi
        JOIN orders o ON oi.orderId = o.id
        JOIN customers c ON o.customerId = c.id
        WHERE oi.farmerId = ? 
        AND oi.status = 'Pending'
        ORDER BY o.orderDate DESC
        LIMIT 5
    ");
    mysqli_stmt_bind_param($stmt, "i", $farmerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $newOrders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    // New bids
    $stmt = mysqli_prepare($conn, "
        SELECT 
            b.id AS bidId,
            b.bidAmount,
            b.bidTime,
            c.firstName,
            c.lastName,
            p.productName
        FROM bids b
        JOIN products p ON b.productId = p.id
        JOIN customers c ON b.userId = c.userId
        WHERE p.farmerId = ?
        AND b.bidStatus = 'pending'
        ORDER BY b.bidTime DESC
        LIMIT 5
    ");

    mysqli_stmt_bind_param($stmt, "i", $farmerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $newBids = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $newBidsCount = count($newBids);
    mysqli_stmt_close($stmt);
}

$newOrdersCount = count($newOrders);
$newBidsCount = count($newBids);
$totalNotifications = $newOrdersCount + $newBidsCount;
?>

<!-- Google Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

<!-- Sidebar and Navbar Styles -->
<style>
    :root {
        --sc-primary-green: #028037;
        --sc-dark-green: #01632c;
        --sc-hover-green: #146c43;
        --sc-background: #f5f7fa;
        --sc-light-text: #e0e0e0;
    }

    body { font-family: 'Roboto', sans-serif; background-color: var(--sc-background); margin:0; overflow-x: hidden; }

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

    .navbar-top .btn-logout {
        background: #ffc107;
        color: #212529;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        padding: 6px 15px;
    }

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

<!-- Top Navbar -->
<nav class="navbar navbar-top">
    <button class="navbar-toggler d-lg-none me-3" type="button" onclick="toggleSidebar()">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="d-flex align-items-center">
        <span class="fw-bold d-none d-sm-block">
            Hello, <?= htmlspecialchars($farm_info['firstName'] ?? 'Farmer'); ?>
        </span>
    </div>

    <!-- Notifications Dropdown (Styled & Aligned Right) -->
    <div class="d-flex align-items-center gap-3">
        <!-- Notification Bell -->
        <div class="dropdown">
            <button class="btn position-relative d-flex align-items-center justify-content-center p-0 bg-white border rounded-circle shadow-sm" 
                    type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
                    style="width: 42px; height: 42px;">
                <span class="material-icons-outlined text-success">notifications</span>
                <?php if(($newOrdersCount + $newBidsCount) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $newOrdersCount + $newBidsCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationsDropdown" style="min-width:280px; max-height:400px; overflow-y:auto;">
                <!-- Orders -->
                <li class="dropdown-header fw-bold text-dark">🔔 New Orders</li>
                <?php if(empty($newOrders)): ?>
                    <li class="dropdown-item text-muted">No new orders</li>
                <?php else: ?>
                    <?php foreach($newOrders as $order): ?>
                        <li class="dropdown-item">
                            <a href="viewOrders.php?orderId=<?= $order['orderId']; ?>" class="text-decoration-none text-dark">
                                Order #<?= $order['orderId']; ?> from <?= htmlspecialchars($order['firstName'].' '.$order['lastName']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>

                <li><hr class="dropdown-divider"></li>

                <!-- Bids -->
                <li class="dropdown-header fw-bold text-dark">💰 New Bids</li>
                <?php if(empty($newBids)): ?>
                    <li class="dropdown-item text-muted">No new bids</li>
                <?php else: ?>
                    <?php foreach($newBids as $bid): ?>
                        <li class="dropdown-item">
                            <a href="manageBids.php?bidId=<?= $bid['bidId']; ?>" class="text-decoration-none text-dark">
                                Bid #<?= $bid['bidId']; ?> from <?= htmlspecialchars($bid['firstName'].' '.$bid['lastName']); ?> - $<?= number_format($bid['bidAmount'], 2); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Logout Button -->
        <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo-container">
        <a href="farmerDashboard.php">
            <img src="assets/logo2.png" alt="StockCrop Logo">
        </a>
    </div>

    <a href="farmerDashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'farmerDashboard.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">dashboard</span> Dashboard
    </a>

    <a href="viewProducts.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'viewProducts.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">inventory_2</span> My Products
    </a>

    <a href="addProduct.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'addProduct.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">add_circle_outline</span> Add Product
    </a>

    <a href="viewOrders.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'viewOrders.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">receipt_long</span> Orders
    </a>

    <a href="manageBids.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'manageBids.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">gavel</span> Manage Bids
    </a>

    <a href="editProfile.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'editProfile.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">settings</span> Profile Settings
    </a>

    <a href="changePassword.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) == 'changePassword.php' ? 'active' : ''; ?>">
        <span class="material-icons-outlined">lock</span> Change Password
    </a>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show-sidebar');
    }
</script>