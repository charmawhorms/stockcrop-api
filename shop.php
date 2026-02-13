<?php
session_start();
include 'config.php';

// --- 1. Initialize filters ---
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$parishFilter = isset($_GET['parish']) ? trim($_GET['parish']) : '';

// --- 2. Build products query dynamically ---
$products = [];
$sql = "SELECT p.*, f.firstName, f.lastName, f.parish, c.categoryName
        FROM products p
        LEFT JOIN farmers f ON p.farmerId = f.id
        LEFT JOIN categories c ON p.categoryId = c.id
        WHERE p.isAvailable = 1 AND p.stockQuantity > 0";

$params = [];
$types = '';

// Search filter
if (!empty($searchQuery)) {
    $sql .= " AND p.productName LIKE ?";
    $params[] = "%$searchQuery%";
    $types .= 's';
}

// Category filter
if ($categoryFilter > 0) {
    $sql .= " AND p.categoryId = ?";
    $params[] = $categoryFilter;
    $types .= 'i';
}

// Parish filter
if (!empty($parishFilter)) {
    $sql .= " AND f.parish = ?";
    $params[] = $parishFilter;
    $types .= 's';
}

$sql .= " ORDER BY p.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

// Bind parameters only if filters exist
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = [
        'id' => intval($row['id']),
        'productName' => htmlspecialchars($row['productName']),
        'description' => htmlspecialchars($row['description']),
        'price' => floatval($row['price']),
        'unitOfSale' => htmlspecialchars($row['unitOfSale']),
        'stockQuantity' => intval($row['stockQuantity']),
        'imagePath' => htmlspecialchars($row['imagePath']),
        'farmerName' => htmlspecialchars($row['firstName'] . ' ' . $row['lastName']),
        'parish' => htmlspecialchars($row['parish'] ?? 'N/A'),
        'categoryName' => htmlspecialchars($row['categoryName'] ?? 'General')
    ];
}

mysqli_stmt_close($stmt);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StockCrop | Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .product-card .card-img-top {
            height: 200px;
            object-fit: cover;
            border-top-left-radius: var(--bs-card-border-radius);
            border-top-right-radius: var(--bs-card-border-radius);
        }

        .shop-banner {
            background: url('https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=2000&auto=format&fit=crop') no-repeat center center;
            background-size: cover;
            min-height: 300px;
            border-bottom: 5px solid #2f8f3f;
        }

        .shop-banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(27, 57, 33, 0.9) 0%, rgba(27, 57, 33, 0.4) 100%);
        }

        .z-1 { z-index: 1; }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255,255,255,0.5);
            content: "/";
        }

        @media (max-width: 768px) {
            .shop-banner { min-height: 250px; text-align: center; }
            .breadcrumb { justify-content: center; }
            .shop-banner-overlay {
                background: rgba(27, 57, 33, 0.7); /* Solid shade for mobile readability */
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section class="shop-banner position-relative d-flex align-items-center py-5">
        <div class="shop-banner-overlay"></div>
        <div class="container position-relative z-1 text-white">
            <div class="row">
                <div class="col-lg-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none small">Home</a></li>
                            <li class="breadcrumb-item active text-white small" aria-current="page">Marketplace</li>
                        </ol>
                    </nav>
                    
                    <h1 class="display-5 fw-bold mb-2">Jamaican <span class="text-warning">Marketplace</span></h1>
                    <p class="lead mb-0 opacity-90">Explore authentic Jamaican produce directly from our farmers - fresh, local, and delivered to your door.</p>
                </div>
            </div>
        </div>
    </section>


    <section class="shop-content py-5" id="products">
        <div class="container">
            <div class="row">
                <!-- Filters -->
                <aside class="col-lg-3 mb-4 mb-lg-0">
                    <div class="card p-3 shadow-sm border-0">
                        <h5 class="fw-bold mb-3">Filter Products</h5>
                        <form method="GET">
                            <input 
                                type="text" 
                                name="search" 
                                class="form-control mb-3" 
                                placeholder="Search..." 
                                value="<?= htmlspecialchars($searchQuery) ?>"
                            >

                            <label class="fw-semibold">Category</label>
                            <select name="category" class="form-select mb-3">
                                <option value="0">All</option>
                                <?php
                                    $catResult = mysqli_query($conn, "SELECT * FROM categories ORDER BY categoryName");
                                    while ($cat = mysqli_fetch_assoc($catResult)) {
                                        $selected = ($categoryFilter == $cat['id']) ? 'selected' : '';
                                        echo "<option value='{$cat['id']}' $selected>{$cat['categoryName']}</option>";
                                    }
                                ?>
                            </select>

                            <label class="fw-semibold">Parish</label>
                            <select name="parish" class="form-select mb-3">
                                <option value="">All</option>
                                <?php
                                    $parishQuery = mysqli_query($conn, "SELECT DISTINCT parish FROM farmers WHERE parish != '' ORDER BY parish ASC");
                                    while ($row = mysqli_fetch_assoc($parishQuery)) {
                                        $selected = ($parishFilter == $row['parish']) ? 'selected' : '';
                                        echo "<option value='{$row['parish']}' $selected>{$row['parish']}</option>";
                                    }
                                ?>
                            </select>

                            <button type="submit" class="btn btn-success w-100">Search</button>
                        </form>
                    </div>
                </aside>

                <!-- Products -->
                <div class="col-lg-9">
                    <div class="row g-4">
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card product-card h-100 text-center shadow-sm">
                                <img src="<?= $product['imagePath'] ?>" loading="lazy" class="card-img-top" style="height:200px; object-fit:cover;" alt="<?= $product['productName'] ?>">
                                <div class="card-body d-flex flex-column justify-content-between"><div>
                                <h6 class="card-title mb-1"><?= $product['productName'] ?></h6>
                                <p class="text-success fw-bold mb-1">$<?= number_format($product['price'], 2) ?> / <?= $product['unitOfSale'] ?></p>
                                <small class="text-muted d-block mb-2">
                                    <?= $product['categoryName'] ?> | <?= $product['parish'] ?></p>
                                </small>
                            </div>
           
                            <?php if ($product['stockQuantity'] > 0): ?>
                            <div class="d-flex mt-auto gap-2">
                                <a href="productDetails.php?id=<?= $product['id'] ?>" class="btn btn-outline-success flex-grow-1 btn-sm text-truncate">
                                    View Product
                                </a>
                                <form method="POST" action="cart.php" class="flex-grow-1 m-0 p-0">
                                    <input type="hidden" name="productId" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-success w-100 btn-sm text-truncate">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>


                            <?php else: ?>
                                <span class="badge bg-danger mt-auto">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!--<script src="shop.js"></script>  Optional: keep addToCart() JS here -->
</body>
</html>
