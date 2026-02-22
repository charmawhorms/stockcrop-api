<?php
session_start();
include 'config.php';

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($productId <= 0) {
    echo "<p class='text-center mt-5'>Invalid product ID.</p>";
    exit;
}

// Fetch product info securely
$stmt = $conn->prepare("
    SELECT p.*, f.firstName, f.lastName, f.parish, f.verification_status, c.categoryName, p.allowBidding, p.minPrice
    FROM products p
    JOIN farmers f ON p.farmerId = f.id
    JOIN categories c ON p.categoryId = c.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "<p class='text-center mt-5'>Product not found.</p>";
    exit;
}

// Normalize allowBidding
$allowBidding = !empty($product['allowBidding']) && $product['allowBidding'] == 1;
$minPrice = $allowBidding ? floatval($product['minPrice']) : null;

// Fetch related products
$relatedQuery = "
    SELECT id, productName, price, imagePath, unitOfSale
    FROM products
    WHERE farmerId = {$product['farmerId']}
    AND id != $productId
    ORDER BY RAND()
    LIMIT 4
";
$relatedResult = mysqli_query($conn, $relatedQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>StockCrop | Product Details</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link rel="icon" type="image/png" href="assets/icon.png">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body>
<?php include 'navbar.php'; ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="custom-breadcrumb-nav text-sm py-3 mb-4">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-success-dark">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php" class="text-success-dark">Shop</a></li>
            <li class="breadcrumb-item">
                <a href="shop.php?category=<?= $product['categoryId'] ?>" class="text-success-dark"><?= htmlspecialchars($product['categoryName']) ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['productName']) ?></li>
        </ol>
    </div>
</nav>

<section class="product-view py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Product Image -->
            <div class="col-lg-6">
                <div class="product-img-container shadow-lg">
                    <img src="<?= htmlspecialchars($product['imagePath']) ?>" alt="<?= htmlspecialchars($product['productName']) ?>" class="img-fluid">
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-lg-6">
                <div class="product-details p-lg-4 p-3">
                    <p class="text-success fw-bold text-uppercase small mb-1"><?= htmlspecialchars($product['categoryName']) ?></p>
                    <h1 class="product-title display-6 fw-bold mb-3"><?= htmlspecialchars($product['productName']) ?></h1>
                    <div class="d-flex align-items-baseline mb-4">
                        <span class="price display-5 fw-bold text-success me-2">$<?= number_format($product['price'],2) ?></span>
                        <span class="text-muted fs-5">/ <?= htmlspecialchars($product['unitOfSale']) ?></span>
                    </div>

                    <!-- AJAX Add to Cart Form -->
                    <form id="addToCartForm" class="mb-4">
                        <input type="hidden" name="productId" value="<?= $product['id'] ?>">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-auto">
                                <label for="quantity-input" class="fw-semibold">Quantity:</label>
                            </div>
                            <div class="col-4 col-md-3 col-lg-4">
                                <input type="number" id="quantity-input" name="quantity" class="form-control form-control-lg text-center" value="1" min="1" max="<?= $product['stockQuantity'] ?>" <?= $product['stockQuantity'] == 0 ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-12 col-md-5 col-lg-5">
                                <?php if ($product['stockQuantity'] > 0): ?>
                                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold d-flex align-items-center justify-content-center">
                                        <span class="material-symbols-outlined me-2">shopping_cart</span> Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-lg w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <!-- Product Description -->
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Product Description</h5>
                    <p class="text-muted description-text"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

                    <!-- Farmer Info -->
                    <div class="card p-3 mt-4 border-success shadow-sm">
                        <p class="mb-1 fw-bold text-success d-flex align-items-center">
                            <span class="material-symbols-outlined me-1 small">verified_user</span> 
                            Sold By Local Farmer
                        </p>
                        <p class="mb-1 d-flex align-items-center">
                            <span class="fw-semibold me-1">Farmer:</span> 
                            <?= htmlspecialchars($product['firstName'] . ' ' . $product['lastName']) ?>
                            <?php if ($product['verification_status'] === 'verified'): ?>
                                <span class="material-symbols-outlined text-primary ms-1" style="font-size: 20px;" title="RADA Verified Farmer">verified</span>
                            <?php endif; ?>
                        </p>
                        <p class="mb-0"><span class="fw-semibold">Location:</span> <?= htmlspecialchars($product['parish']) ?></p>
                    </div>

                    <!-- Place Bid Section -->
                    <?php if ($allowBidding): ?>
                    <div class="mb-3 mt-4">
                        <label class="fw-bold text-warning">Place Your Bid</label>
                        <input type="number" step="0.01" id="bidAmount" name="bidAmount" class="form-control mb-2" placeholder="Enter total bid amount">
                        <button id="placeBidBtn" class="btn btn-warning w-100 fw-bold">
                            <span class="material-symbols-outlined me-2">gavel</span> Place Bid
                        </button>
                        <small class="text-muted">Your bid will be applied for the selected quantity. Must meet per-unit minimum price.</small>
                        <div id="bidFeedback" class="mt-2"></div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (mysqli_num_rows($relatedResult) > 0): ?>
<section class="related-products py-5 bg-light-green">
    <div class="container">
        <h3 class="fw-bold text-center mb-5 display-5 text-dark">More Products From <?= htmlspecialchars($product['firstName']) ?>'s Farm</h3>
        <div class="row g-4">
            <?php while ($related = mysqli_fetch_assoc($relatedResult)): ?>
            <div class="col-lg-3 col-md-6">
                <a href="productDetails.php?id=<?= $related['id'] ?>" class="text-decoration-none d-block">
                    <div class="card related-card h-100 border-0 shadow-sm">
                        <div class="related-img-wrap">
                            <img src="<?= htmlspecialchars($related['imagePath']) ?>" loading="lazy" alt="<?= htmlspecialchars($related['productName']) ?>" class="img-fluid">
                        </div>
                        <div class="card-body text-center">
                            <h6 class="fw-bold text-dark"><?= htmlspecialchars($related['productName']) ?></h6>
                            <p class="text-success fw-semibold mb-2 fs-5">$<?= number_format($related['price'],2) ?> / <?= htmlspecialchars($related['unitOfSale']) ?></p>
                            <span class="btn btn-outline-success w-100">View Product</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Add to Cart
    const form = document.getElementById('addToCartForm');
    if(form){
        form.addEventListener('submit', e => {
            e.preventDefault();
            const productId = form.querySelector('input[name="productId"]').value;
            const quantity = form.querySelector('input[name="quantity"]').value;
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `productId=${productId}&quantity=${quantity}&ajax=1`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.status === 'success' ? 'Added to cart!' : 'Failed to add.');
            })
            .catch(()=>alert('Network error.'));
        });
    }

    // Place Bid
    const bidBtn = document.getElementById('placeBidBtn');
    if(bidBtn){
        bidBtn.addEventListener('click', e => {
            e.preventDefault();
            const bidInput = document.getElementById('bidAmount');
            const quantityInput = document.getElementById('quantity-input');
            const feedback = document.getElementById('bidFeedback');

            const quantity = parseInt(quantityInput.value) || 1;
            const totalBid = parseFloat(bidInput.value);
            const minPrice = <?= $minPrice ?>;

            if(isNaN(totalBid) || totalBid <= 0){
                feedback.textContent = 'Please enter a valid bid amount.';
                feedback.className = 'mt-2 text-danger';
                return;
            }

            const perUnitBid = totalBid / quantity;

            if(perUnitBid < minPrice){
                feedback.textContent = `Bid too low per unit!.`;
                feedback.className = 'mt-2 text-danger';
                return;
            }

            // AJAX request to placeBid.php
            fetch('placeBid.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `productId=<?= $product['id'] ?>&bidAmount=${totalBid}&quantity=${quantity}`
            })
            .then(res => res.json())
            .then(data => {
                feedback.className = data.status === 'success' ? 'mt-2 text-success' : 'mt-2 text-danger';
                feedback.textContent = data.message || (data.status === 'success' ? 'Bid placed successfully!' : 'Bid could not be placed.');
            })
            .catch(() => {
                feedback.className = 'mt-2 text-danger';
                feedback.textContent = 'Network error.';
            });
        });
    }
});
</script>
</body>
</html>