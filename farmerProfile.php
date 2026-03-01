<?php
include 'config.php';

if (!isset($_GET['id'])) {
    die("Farmer not found.");
}

$farmerId = intval($_GET['id']);

// Get farmer info
$stmt = $conn->prepare("
    SELECT id, firstName, lastName, verification_status
    FROM farmers
    WHERE id = ?
");
$stmt->bind_param("i", $farmerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Farmer not found.");
}

$farmer = $result->fetch_assoc();

// Get farmer products
$productStmt = $conn->prepare("
    SELECT id, productName, price, unitOfSale, imagePath
    FROM products
    WHERE farmerId = ? AND isAvailable = 1
");
$productStmt->bind_param("i", $farmerId);
$productStmt->execute();
$products = $productStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StockCrop | Farmer Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
<body class="bg-light">
<?php include 'navbar.php'; ?>
<div class="container py-5">

    <!-- Farmer Info -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body text-center">

            <h3 class="fw-bold">
                <?php echo htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?>

                <?php if ($farmer['verification_status'] === 'verified'): ?>
                    <span class="material-symbols-outlined text-primary align-middle ms-1"
                          style="font-size:20px;"
                          title="RADA Verified Farmer">
                        verified
                    </span>
                <?php endif; ?>
            </h3>
        </div>
    </div>

    <!-- Products -->
    <h4 class="mb-3">Available Products</h4>

    <div class="row">
        <?php if ($products->num_rows > 0): ?>
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($product['imagePath'])): ?>
                            <img src="<?php echo htmlspecialchars($product['imagePath']); ?>" 
                                 class="card-img-top"
                                 style="height:200px; object-fit:cover;">
                        <?php endif; ?>

                        <div class="card-body text-center">
                            <h6 class="fw-bold">
                                <?php echo htmlspecialchars($product['productName']); ?>
                            </h6>
                            <p class="text-success fw-semibold">
                                $<?php echo number_format($product['price'], 2); ?>
                                / <?php echo htmlspecialchars($product['unitOfSale']); ?>
                            </p>
                        </div>
                        <a href="productDetails.php?id=<?= $product['id'] ?>" class="btn btn-outline-success flex-grow-1 btn-sm text-truncate">
                            View Product
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted">No products available at the moment.</p>
        <?php endif; ?>
    </div>

</div>
<?php include 'footer.php'; ?>
</body>
</html>