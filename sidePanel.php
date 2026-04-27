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

// Fetch the latest 5 notifications for the logged-in farmer
$stmt = $conn->prepare("
    SELECT n.id, n.type, n.message, n.isRead, n.created_at
    FROM notifications n
    WHERE n.userId = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$notifications = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) AS unreadCount FROM notifications WHERE userId=? AND isRead=0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$unreadCount = $row['unreadCount'] ?? 0;
$stmt->close();

// Fetch farmer info
$farmerId = null;
$farmerType = null;
$verificationStatus = null;

$stmt = mysqli_prepare($conn, "SELECT id, firstName, lastName, farmerType, verification_status FROM farmers WHERE userId = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$farm_info = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($farm_info) {
    $farmerId = $farm_info['id'];
    $farmerType = $farm_info['farmerType'];
    $verificationStatus = $farm_info['verification_status'];
}

$newOrders = $newBidsCount = $newOrdersCount = [];

if ($farmerId) {
    // New bids (keeping for reference, but won't display separately)
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

$newBidsCount = count($newBids);
$totalNotifications = $unreadCount;
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
        <?php if($unreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $unreadCount ?>
            </span>
        <?php endif; ?>
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:350px; max-height:400px; overflow-y:auto;" id="notificationsDropdownMenu">
    <li class="dropdown-header fw-bold">Notifications</li>
    <?php if(empty($notifications)): ?>
        <li class="dropdown-item text-muted text-center py-3">No notifications yet</li>
    <?php else: ?>
        <?php foreach($notifications as $note): ?>
            <li class="dropdown-item <?= $note['isRead'] == 0 ? 'fw-bold bg-light' : '' ?>" style="white-space: normal; padding: 12px 16px; border-bottom: 1px solid #f0f0f0;">
                <div style="display: flex; gap: 8px;">
                    <span style="font-size: 18px; flex-shrink: 0;">
                        <?= $note['type'] === 'order' ? '📦' : '🔔' ?>
                    </span>
                    <div style="flex-grow: 1;">
                        <?= htmlspecialchars($note['message']) ?>
                        <br>
                        <small class="text-muted"><?= date('M d, H:i', strtotime($note['created_at'])) ?></small>
                    </div>
                </div>
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
        <span class="material-icons-outlined">add_circle</span> Add Product
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

    // Refresh notification dropdown content
    function refreshNotifications() {
        fetch('refreshNotifications.php')
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    let html = '<li class="dropdown-header fw-bold">Notifications</li>';
                    
                    if(data.notifications && data.notifications.length > 0){
                        data.notifications.forEach(note => {
                            const isUnread = note.isRead == 0 ? 'fw-bold bg-light' : '';
                            const icon = note.type === 'order' ? '📦' : '🔔';
                            const created = new Date(note.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
                            html += `<li class="dropdown-item ${isUnread}" style="white-space: normal; padding: 12px 16px; border-bottom: 1px solid #f0f0f0;">
                                <div style="display: flex; gap: 8px;">
                                    <span style="font-size: 18px; flex-shrink: 0;">${icon}</span>
                                    <div style="flex-grow: 1;">
                                        ${note.message}
                                        <br>
                                        <small class="text-muted">${created}</small>
                                    </div>
                                </div>
                            </li>`;
                        });
                    } else {
                        html += '<li class="dropdown-item text-muted text-center py-3">No notifications yet</li>';
                    }
                    
                    document.getElementById('notificationsDropdownMenu').innerHTML = html;
                }
            });
    }

    document.getElementById('notificationsDropdown').addEventListener('click', () => {
        refreshNotifications();
        fetch('markRead.php', { method: 'POST', body: new URLSearchParams({ userId: <?= $userId ?> }) })
            .then(res => res.text())
            .then(console.log);
    });

// Poll for unread notifications every 5 seconds
setInterval(() => {
    fetch('fetchUnreadCount.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.querySelector('#notificationsDropdown .badge');
            if(data.unread > 0){
                if(!badge){
                    // Create badge if it doesn't exist
                    const newBadge = document.createElement('span');
                    newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    newBadge.textContent = data.unread;
                    document.getElementById('notificationsDropdown').appendChild(newBadge);
                } else {
                    // Update existing badge
                    badge.textContent = data.unread;
                }
            } else if(badge){
                // Hide badge if no unread notifications
                badge.remove();
            }
        });
}, 5000);
</script>
