
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

$cart_count = 0;

if (isset($_SESSION['id']) && isset($conn)) {

    $checkCart = mysqli_query($conn, "SHOW TABLES LIKE 'cart'");

    if ($checkCart && mysqli_num_rows($checkCart) > 0) {

        $stmt = mysqli_prepare($conn,"
            SELECT SUM(ci.quantity) AS total
            FROM cartItems ci
            JOIN cart c ON ci.cartId = c.id
            WHERE c.userId = ?
        ");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $cart_count = intval($row['total'] ?? 0);
        }
    }

} elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    $cart_count = array_sum($_SESSION['cart']);
}


// Check database connection
if (!isset($conn) || !$conn) {
    die("Database connection failed. Check config.php");
}

$is_logged_in = isset($_SESSION['id']);
$user_name = $_SESSION['firstName'] ?? 'User';
$user_role_id = $_SESSION['roleId'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);

function getParishes($conn) {
    $result = mysqli_query($conn, "SELECT * FROM parishes ORDER BY name");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    return $data;
}

function getMarkets($conn, $filters = []) {

    $data = [];

    // Update only the SQL string in your function
$sql = "
    SELECT 
        m.id, 
        m.name, 
        m.latitude,          -- Fixes 'latitude' warning
        m.longitude,         -- Fixes 'longitude' warning
        m.type,              -- Fixes 'type' warning
        m.operating_hours, 
        m.established,
        p.name AS parish_name, -- Fixes 'parish_name' warning

        v.id AS vendor_id,
        v.farmer_name,       -- Fixes 'farmer_name' warning
        v.latitude AS vendor_lat, -- Fixes 'vendor_lat' warning
        v.longitude AS vendor_lng, -- Fixes 'vendor_lng' warning

        vp.productName, 
        vp.price
    FROM markets m
    LEFT JOIN parishes p ON m.parish_id = p.id
    LEFT JOIN vendors v ON v.market_id = m.id
    LEFT JOIN products vp ON vp.farmerId = v.id 
    WHERE m.is_active = 1
";

    // PARISH FILTER
    if (!empty($filters['parish'])) {
        $sql .= " AND m.parish_id = " . intval($filters['parish']);
    }

    // TYPE FILTER
    if (!empty($filters['type'])) {
        $type = mysqli_real_escape_string($conn, $filters['type']);
        $sql .= " AND m.type = '$type'";
    }

    // SEARCH FILTER
    if (!empty($filters['search'])) {
        $search = mysqli_real_escape_string($conn, $filters['search']);
        $sql .= " AND (
            m.name LIKE '%$search%' OR
            p.name LIKE '%$search%' OR
            v.farmer_name LIKE '%$search%' OR
            vp.product_name LIKE '%$search%'
        )";
    }

    $sql .= " ORDER BY m.name ASC";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("SQL Error: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result)) {

        $marketId = $row['id'];

        // CREATE MARKET
        if (!isset($data[$marketId])) {
            $data[$marketId] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'type' => $row['type'],
                'operating_hours' => $row['operating_hours'],
                'established' => $row['established'],
                'parish_name' => $row['parish_name'],
                'vendors' => []
            ];
        }

        // ADD VENDOR
        if (!empty($row['vendor_id'])) {

            if (!isset($data[$marketId]['vendors'][$row['vendor_id']])) {
                $data[$marketId]['vendors'][$row['vendor_id']] = [
                    'farmer_name' => $row['farmer_name'],
                    'vendor_lat' => $row['vendor_lat'],
                    'vendor_lng' => $row['vendor_lng'],
                    'products' => []
                ];
            }

            // ADD PRODUCTS
            if (!empty($row['product_name'])) {
                $data[$marketId]['vendors'][$row['vendor_id']]['products'][] =
                    $row['product_name'] . " - $" . $row['price'];
            }
        }
    }

    return array_values($data);
}

if (isset($_GET['ajax'])) {

    header('Content-Type: application/json');

    $markets = getMarkets($conn, [
        'parish' => $_GET['parish'] ?? '',
        'type'   => $_GET['type'] ?? '',
        'search' => $_GET['search'] ?? ''
    ]);

    echo json_encode([
        'markets' => $markets,
        'count'   => count($markets)
    ]);

    exit;
}

$parishes = getParishes($conn);
$markets = getMarkets($conn);
$marketTypes = ['Municipal', 'Farmers', 'Fishing', 'Craft', 'Wholesale'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Market - StockCrop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .jamaica-font { font-family: 'Playfair Display', serif; }
        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
        .market-card { transition: all 0.3s ease; }
        .market-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .map-container { height: 500px; border-radius: 1rem; overflow: hidden; }
        .filter-chip.active { background: #009b3a; color: white; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .jamaica-pattern { background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23009b3a' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E"); }
        
        .btn-cart { background-color: #FFEB3B; width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative; }
        .btn-user { background-color: #028037; color: white !important; border: none; }
        .btn-signup { background-color: #E57373; color: white !important; border: none; }
        .btn-login { background-color: #F4A261; color: white !important; border: none; }
        .search-form { width: 280px; }
        @media (max-width: 992px) { 
            .search-form { width: 100%; margin: 10px 0; } 
            .navbar-collapse { background: white; padding: 15px; border-radius: 10px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        }
    </style>
</head>
<body class="jamaica-pattern">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top" style="z-index: 1030;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img src="assets/logo.png" alt="Logo" height="40" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline'">
                <span class="fw-bold text-success fs-4" style="display: none;">StockCrop</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="navbar-collapse" id="mainNavbar">

                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="shop.php">Marketplace</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3 active text-success" href="farmersMarket.php">Farmers Market</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold px-3" href="contact.php">Contact Us</a></li>
                </ul>
                
                <form class="d-flex search-form me-3" action="shop.php" method="GET">
                    <div class="input-group bg-light rounded-pill">
                        <span class="input-group-text bg-light border-0 rounded-pill ps-3">
                            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">search</span>
                        </span>
                        <input type="search" name="search" class="form-control bg-light border-0 rounded-pill" placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                </form>
                
                <div class="d-flex align-items-center gap-2">
                    <a href="#" data-bs-toggle="offcanvas" data-bs-target="#cartCanvas" class="btn-cart text-decoration-none">
                        <span class="material-symbols-outlined text-dark">shopping_cart</span>
                        <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if ($is_logged_in): ?>
                        <div class="dropdown">
                            <button class="btn btn-user dropdown-toggle d-flex align-items-center gap-1 rounded-pill px-3 py-2" type="button" data-bs-toggle="dropdown">
                                <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
                                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($user_name); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <?php if ($user_role_id == 2): ?>
                                    <li><a class="dropdown-item" href="farmerDashboard.php"><i class="fas fa-tachometer-alt me-2 text-success"></i>Dashboard</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="customerDashboard.php"><i class="fas fa-user me-2 text-success"></i>My Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Log Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="dropdown">
                            <button class="btn btn-signup dropdown-toggle d-flex align-items-center gap-1 rounded-pill px-3 py-2" type="button" data-bs-toggle="dropdown">
                                <span class="material-symbols-outlined" style="font-size: 20px;">person_add</span>
                                <span class="d-none d-sm-inline">Sign Up</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="registerFarmer.php">Register as Farmer</a></li>
                                <li><a class="dropdown-item" href="registerCustomer.php">Register as Customer</a></li>
                            </ul>
                        </div>
                        <a href="login.php?redirect=<?php echo urlencode($current_page); ?>" class="btn btn-login rounded-pill px-3 py-2 d-flex align-items-center gap-1">
   
                        <span class="material-symbols-outlined" style="font-size: 20px;">login</span>
                            <span class="d-none d-sm-inline">Log In</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Cart Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartCanvas">
        <div class="offcanvas-header bg-success text-white">
            <h5 class="offcanvas-title fw-bold"><i class="fas fa-shopping-cart me-2"></i>Your Cart</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <p class="text-muted text-center mt-5">Cart items will appear here</p>
        </div>
        <div class="offcanvas-footer p-3 border-top">
            <a href="cart.php" class="btn btn-success w-100">View Full Cart</a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container py-5">
        
        <!-- Header -->
        <div class="glass-panel rounded-4 p-4 p-md-5 mb-5 shadow-lg">
            <div class="row align-items-center mb-4">
                <div class="col-md-8 mb-3 mb-md-0">
                    <h1 class="display-5 fw-bold text-gray-900 jamaica-font mb-2">
                        <i class="fas fa-store text-success me-2"></i>Farmers Markets
                    </h1>
                    <p class="lead text-gray-600 mb-0">Explore local markets across all 14 parishes</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button onclick="resetFilters()" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-undo me-1"></i>Reset
                    </button>
                    <span class="badge bg-success fs-6 px-3 py-2 rounded-pill">
                        <span id="market-count"><?php echo count($markets); ?></span> Markets
                    </span>
                </div>
            </div>

            <!-- Filters -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="search-input" class="form-control border-start-0 ps-0" placeholder="Search markets..." oninput="debounceFilter()">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="parish-filter" class="form-select" onchange="filterMarkets()">
                        <option value="">All Parishes</option>
                        <?php foreach ($parishes as $parish): ?>
                            <option value="<?php echo $parish['id']; ?>"><?php echo htmlspecialchars($parish['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select id="type-filter" class="form-select" onchange="filterMarkets()">
                        <option value="">All Types</option>
                        <?php foreach ($marketTypes as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?> Market</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Parish Chips -->
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($parishes as $parish): ?>
                    <button class="filter-chip btn btn-outline-secondary rounded-pill btn-sm" onclick="toggleParishChip(this, <?php echo $parish['id']; ?>)">
                        <?php echo htmlspecialchars($parish['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Markets List -->
            <div class="col-lg-4">
                <div class="glass-panel rounded-4 p-3 shadow" style="max-height: 600px; overflow-y: auto;">
                    <div id="markets-list" class="d-flex flex-column gap-3">
                        <?php if (empty($markets)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                                <p>No markets found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($markets as $index => $market): 
                                $typeColors = ['Municipal' => 'bg-primary', 'Farmers' => 'bg-success', 'Fishing' => 'bg-info', 'Craft' => 'bg-warning', 'Wholesale' => 'bg-danger'];
                                $badgeClass = $typeColors[$market['type']] ?? 'bg-secondary';
                            ?>
                                <div class="market-card glass-panel rounded-3 p-3 cursor-pointer border-start border-4 border-success" 
                                     style="animation: slideIn 0.5s ease <?php echo $index * 0.05; ?>s both;"
                                     onclick="selectMarket(<?php echo $market['latitude']; ?>, <?php echo $market['longitude']; ?>, '<?php echo addslashes($market['name']); ?>', this)">
                                
                                      <div class="d-flex justify-content-between align-items-start mb-2">
                                      <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($market['name']); ?></h5>
                                      <?php
    
                                     $vendorCount = (!empty($market['vendors']) && is_array($market['vendors'])) 
                                       ? count($market['vendors']) 
                                      : 0;
                                      ?>
                                     <span class="badge bg-success"><?php echo $vendorCount; ?> Vendors</span>
                                     </div>
                                     <div class="d-flex align-items-center text-muted small mb-2">
                                     <i class="fas fa-map-marker-alt text-success me-2"></i>
                                     <?php echo htmlspecialchars($market['parish_name']); ?>
                                     </div>
                                    <div class="d-flex align-items-center text-muted small mb-2">
                                    <i class="far fa-clock me-2"></i>
                                    <?php echo htmlspecialchars($market['operating_hours']); ?>
                                    </div>
<?php if (!empty($market['vendors'])): ?>
    <div class="mt-2 small">
        <strong>Farmers & Produce:</strong>
        <?php foreach ($market['vendors'] as $vendor): ?>
            <?php if (!empty($vendor['farmer_name'])): ?>
                <div><?php echo htmlspecialchars($vendor['farmer_name']); ?></div>
                <?php if (!empty($vendor['products'])): ?>
                    <div class="text-muted">
                        <?php echo htmlspecialchars(implode(", ", $vendor['products'])); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <small class="text-muted">Est. <?php echo $market['established']; ?></small>
                                        <button class="btn btn-link btn-sm text-success text-decoration-none p-0" onclick="event.stopPropagation(); showOnMap(<?php echo $market['latitude']; ?>, <?php echo $market['longitude']; ?>, '<?php echo addslashes($market['name']); ?>')">
                                            View Map <i class="fas fa-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="col-lg-8">
                <div class="glass-panel rounded-4 p-3 shadow h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0"><i class="fas fa-map-marked-alt text-success me-2"></i>Market Locations</h4>
                        <button onclick="locateNearest()" class="btn btn-success btn-sm rounded-pill">
                            <i class="fas fa-location-arrow me-1"></i>Find Nearest
                        </button>
                    </div>
                    <div id="map" class="map-container bg-light d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <i class="fas fa-map-marker-alt fa-3x text-success mb-2"></i>
                            <p>Select a market to view on map</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const parishes = <?php echo json_encode($parishes); ?>;
        let currentFilter = { search: '', parish: '', type: '' };
        let debounceTimer;

        function debounceFilter() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(filterMarkets, 300);
        }

        function toggleParishChip(chip, parishId) {
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active', 'btn-success'));
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.add('btn-outline-secondary'));
            
            const select = document.getElementById('parish-filter');
            if (chip.classList.contains('active')) {
                chip.classList.remove('active', 'btn-success');
                chip.classList.add('btn-outline-secondary');
                select.value = '';
            } else {
                chip.classList.remove('btn-outline-secondary');
                chip.classList.add('active', 'btn-success');
                select.value = parishId;
            }
            filterMarkets();
        }

        function filterMarkets() {
            currentFilter.search = document.getElementById('search-input').value;
            currentFilter.parish = document.getElementById('parish-filter').value;
            currentFilter.type = document.getElementById('type-filter').value;
            
            const container = document.getElementById('markets-list');
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success"></div></div>';
            
            fetch(`?ajax=1&search=${encodeURIComponent(currentFilter.search)}&parish=${currentFilter.parish}&type=${encodeURIComponent(currentFilter.type)}`)
                .then(r => r.json())
                .then(data => {
                    renderMarkets(data.markets);
                    document.getElementById('market-count').textContent = data.count;
                })
                .catch(() => container.innerHTML = '<div class="alert alert-danger">Error loading markets</div>');
        }

 function renderMarkets(markets) {
    const container = document.getElementById('markets-list');
    const typeColors = { 'Municipal': 'bg-primary', 'Farmers': 'bg-success', 'Fishing': 'bg-info', 'Craft': 'bg-warning', 'Wholesale': 'bg-danger' };

    if (!markets.length) {
        container.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-search fa-3x mb-3 opacity-25"></i><p>No markets found</p></div>';
        return;
    }
    
    container.innerHTML = markets.map((m, i) => {
        // Build vendors HTML
        let vendorsHtml = '';
        if (m.vendors && Object.keys(m.vendors).length > 0) {
            vendorsHtml = `
                <div class="mt-2 small">
                    <strong>Farmers & Produce:</strong>
                    ${Object.values(m.vendors).map(v => `
                        <div>${escapeHtml(v.farmer_name)}</div>
                        <div class="text-muted">${(v.products || []).join(", ")}</div>
                    `).join('')}
                </div>
            `;
        }

        return `
            <div class="market-card glass-panel rounded-3 p-3 cursor-pointer border-start border-4 border-success" 
                 style="animation: slideIn 0.5s ease ${i * 0.05}s both"
                 onclick='selectMarket(${m.latitude}, ${m.longitude}, ${JSON.stringify(m.name)}, this, ${JSON.stringify(m)})'>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="fw-bold mb-0">${escapeHtml(m.name)}</h5>
                    <span class="badge ${typeColors[m.type] || 'bg-secondary'} bg-opacity-75">${m.type}</span>
                </div>
                <div class="d-flex align-items-center text-muted small mb-2">
                    <i class="fas fa-map-marker-alt text-success me-2"></i>${escapeHtml(m.parish_name)}
                </div>
                <div class="d-flex align-items-center text-muted small mb-2">
                    <i class="far fa-clock me-2"></i>${escapeHtml(m.operating_hours)}
                </div>
                ${vendorsHtml}
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <small class="text-muted">Est. ${m.established}</small>
                    <button class="btn btn-link btn-sm text-success text-decoration-none p-0" onclick="event.stopPropagation(); showOnMap(${m.latitude}, ${m.longitude}, '${m.name.replace(/'/g, "\\'")}')">
                        View Map <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}


        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

       function showOnMap(lat, lng, name, market = null) {

    let markers = '';

    if (market && market.vendors) {
        market.vendors.forEach(v => {
            if (v.vendor_lat && v.vendor_lng) {
                markers += `&markers=color:green%7Clabel:F%7C${v.vendor_lat},${v.vendor_lng}`;
            }
        });
    }

    document.getElementById('map').innerHTML = `
        <iframe width="100%" height="100%" style="border:0"
        src="https://maps.google.com/maps?q=${lat},${lng}${markers}&output=embed">
        </iframe>
    `;
}
      

        function locateNearest() {
            if (!navigator.geolocation) return alert('Geolocation not supported');
            navigator.geolocation.getCurrentPosition(pos => {
                const uLat = pos.coords.latitude, uLng = pos.coords.longitude;
                fetch('?ajax=1').then(r => r.json()).then(data => {
                    let nearest = null, minD = Infinity;
                    data.markets.forEach(m => {
                        const d = Math.sqrt(Math.pow(uLat - m.latitude, 2) + Math.pow(uLng - m.longitude, 2));
                        if (d < minD) { minD = d; nearest = m; }
                    });
                    if (nearest) {
                        showOnMap(parseFloat(nearest.latitude), parseFloat(nearest.longitude), nearest.name);
                        renderMarkets(data.markets);
                        setTimeout(() => {
                            document.querySelectorAll('.market-card').forEach(c => {
                                if (c.textContent.includes(nearest.name)) {
                                    c.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    c.style.borderLeft = '4px solid #198754';
                                }
                            });
                        }, 100);
                    }
                });
            }, () => alert('Unable to get location'));
        }

        function resetFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('parish-filter').value = '';
            document.getElementById('type-filter').value = '';
            document.querySelectorAll('.filter-chip').forEach(c => { c.classList.remove('active', 'btn-success'); c.classList.add('btn-outline-secondary'); });
            filterMarkets();
            document.getElementById('map').innerHTML = '<div class="text-center text-muted"><i class="fas fa-map-marker-alt fa-3x text-success mb-2"></i><p>Select a market to view on map</p></div>';
        }
    </script>
</body>
</html>
