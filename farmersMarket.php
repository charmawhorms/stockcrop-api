<?php
session_start();
require_once 'config.php';

// Logged-in info
$is_logged_in = isset($_SESSION['id']);
$user_name = $_SESSION['firstName'] ?? 'User';
$user_role_id = $_SESSION['roleId'] ?? null;

// Fetch parishes
$parishes = [];
$res = mysqli_query($conn, "SELECT * FROM parishes ORDER BY name");
while($row = mysqli_fetch_assoc($res)) $parishes[] = $row;

// Market types
$marketTypes = ['Farmers', 'Municipal', 'Craft', 'Fishing', 'Wholesale'];

// Fetch markets function
function getMarkets($conn, $filters = []) {
    $sql = "SELECT m.*, p.name AS parish_name,
            (SELECT COUNT(*) FROM market_farmers mf WHERE mf.market_id = m.id) AS farmer_count
            FROM markets m
            JOIN parishes p ON m.parish_id = p.id
            WHERE m.is_active = 1";
    if(!empty($filters['parish'])) $sql .= " AND m.parish_id=".intval($filters['parish']);
    if(!empty($filters['type'])) $sql .= " AND m.type='".mysqli_real_escape_string($conn,$filters['type'])."'";
    if(!empty($filters['search'])) $sql .= " AND m.name LIKE '%".mysqli_real_escape_string($conn,$filters['search'])."%'";
    $sql .= " ORDER BY m.name ASC";
    
    $markets = [];
    $res = mysqli_query($conn,$sql);
    while($row = mysqli_fetch_assoc($res)){
        $markets[] = $row;
    }
    return $markets;
}

// AJAX for filtering markets
if(isset($_GET['ajax'])){
    header('Content-Type: application/json');
    $markets = getMarkets($conn, [
        'parish'=>$_GET['parish'] ?? '',
        'type'=>$_GET['type'] ?? '',
        'search'=>$_GET['search'] ?? ''
    ]);
    echo json_encode(['markets'=>$markets,'count'=>count($markets)]);
    exit;
}

$markets = getMarkets($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>StockCrop | Farmers' Market</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
<style>
.market-card { cursor:pointer; transition:0.3s; }
.market-card:hover { transform: translateY(-5px); box-shadow:0 15px 20px rgba(0,0,0,0.1);}
.map-container { height:500px; border-radius:1rem; overflow:hidden;}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-5">

    <!-- Header & Filters -->
    <div class="bg-white p-4 rounded shadow mb-4">
        <h1 class="fw-bold mb-2"><i class="fas fa-store text-success me-2"></i>Farmers Markets</h1>
        <p class="text-muted mb-3">Explore local farmers markets across Jamaica</p>
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" id="search-input" class="form-control" placeholder="Search markets..." oninput="debounceFilter()">
            </div>
            <div class="col-md-4">
                <select id="parish-filter" class="form-select" onchange="filterMarkets()">
                    <option value="">All Parishes</option>
                    <?php foreach($parishes as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select id="type-filter" class="form-select" onchange="filterMarkets()">
                    <option value="">All Types</option>
                    <?php foreach($marketTypes as $t): ?>
                    <option value="<?php echo $t; ?>"><?php echo $t; ?> Market</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Markets & Map -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="bg-white p-3 rounded shadow" style="max-height:600px; overflow-y:auto;">
                <div id="markets-list">
                    <?php foreach($markets as $m): ?>
                    <div class="market-card p-3 mb-3 border-start border-4 border-success"
                        onclick="openMarketModal(<?php echo $m['id']; ?>, <?php echo $m['latitude']; ?>, <?php echo $m['longitude']; ?>, '<?php echo addslashes($m['name']); ?>')">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($m['name']); ?></h5>
                        <small class="text-muted"><?php echo htmlspecialchars($m['parish_name']); ?> | <?php echo htmlspecialchars($m['operating_hours']); ?></small>
                        <div class="mt-2 small"><strong>Farmers:</strong> <?php echo $m['farmer_count']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="bg-white p-3 rounded shadow h-100">
                <h4 class="fw-bold mb-3"><i class="fas fa-map-marked-alt text-success me-2"></i>Market Locations</h4>
                <div id="map" class="map-container d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="fas fa-map-marker-alt fa-3x text-success mb-2"></i>
                        <p>Select a market to view on map</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Market Modal -->
<div class="modal fade" id="marketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="marketModalLabel"><i class="fas fa-store me-2"></i>Market Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="market-details" class="mb-3"></div>
        <h6 class="fw-bold mt-3">Farmers in this Market:</h6>
        <div id="market-farmers-list" class="row g-3 mt-2"></div>
      </div>
    </div>
  </div>
</div>


</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let debounceTimer;
function debounceFilter(){ clearTimeout(debounceTimer); debounceTimer=setTimeout(filterMarkets,300); }

function filterMarkets(){
    const search = document.getElementById('search-input').value;
    const parish = document.getElementById('parish-filter').value;
    const type = document.getElementById('type-filter').value;
    const container = document.getElementById('markets-list');
    container.innerHTML='<div class="text-center py-5"><div class="spinner-border text-success"></div></div>';
    fetch(`?ajax=1&search=${encodeURIComponent(search)}&parish=${parish}&type=${encodeURIComponent(type)}`)
        .then(r=>r.json())
        .then(data=>{
            if(!data.markets.length){
                container.innerHTML='<div class="text-center py-5 text-muted"><i class="fas fa-search fa-3x mb-3 opacity-25"></i><p>No markets found</p></div>';
                return;
            }
            container.innerHTML = data.markets.map(m=>`
                <div class="market-card p-3 mb-3 border-start border-4 border-success" 
                    onclick="openMarketModal(${m.id}, ${m.latitude}, ${m.longitude}, '${m.name.replace(/'/g,"\\'")}')">
                    <h5 class="fw-bold">${m.name}</h5>
                    <small class="text-muted">${m.parish_name} | ${m.operating_hours}</small>
                    <div class="mt-2 small"><strong>Farmers:</strong> ${m.farmer_count}</div>
                </div>
            `).join('');
        });
}


function openMarketModal(marketId, lat=null, lng=null){
    const modal = new bootstrap.Modal(document.getElementById('marketModal'));
    const detailsDiv = document.getElementById('market-details');
    const farmersDiv = document.getElementById('market-farmers-list');

    detailsDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-success"></div></div>';
    farmersDiv.innerHTML = '';

    // Update map if coordinates provided
    if(lat && lng){
        document.getElementById('map').innerHTML = `<iframe width="100%" height="100%" style="border:0"
            src="https://maps.google.com/maps?q=${lat},${lng}&output=embed"></iframe>`;
    }

    fetch('get_market_farmers.php?market_id='+marketId)
    .then(r=>r.json())
    .then(data=>{
        if(data.error){ detailsDiv.innerHTML = `<p class="text-danger">${data.error}</p>`; return; }

        // Market info
        detailsDiv.innerHTML = `
            <h5 class="fw-bold">${data.market.name}</h5>
            <p class="mb-1"><strong>Parish:</strong> ${data.market.parish_name}</p>
            <p class="mb-1"><strong>Operating Hours:</strong> ${data.market.operating_hours}</p>
            <p class="text-muted">${data.market.description}</p>
        `;

        // Farmers list
        if(data.farmers.length){
            farmersDiv.innerHTML = data.farmers.map(f=>`
                <div class="col-md-4">
                    <a href="farmerProfile.php?id=${f.id}" 
                    class="btn btn-outline-success w-100">
                        ${f.first_name} ${f.last_name}
                    </a>
                </div>
            `).join('');
        } else {
            farmersDiv.innerHTML = '<p class="text-muted text-center">No farmers in this market.</p>';
        }
    });

    modal.show();
}

// Show market on map
function showOnMap(lat,lng,name){
    document.getElementById('map').innerHTML=`<iframe width="100%" height="100%" style="border:0"
        src="https://maps.google.com/maps?q=${lat},${lng}&output=embed"></iframe>`;
}
</script>
</body>
</html>