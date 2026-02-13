<?php
    session_start();
    include 'config.php';

    // Get product ID
    $productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($productId <= 0) {
        echo "<p class='text-center mt-5'>Invalid product ID.</p>";
        exit;
    }

    // Fetch product info
    $sql = "
        SELECT p.*, f.firstName, f.lastName, f.parish, c.categoryName
        FROM products p
        JOIN farmers f ON p.farmerId = f.id
        JOIN categories c ON p.categoryId = c.id
        WHERE p.id = $productId
        LIMIT 1
    ";
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "<p class='text-center mt-5'>Product not found.</p>";
        exit;
    }
    $product = mysqli_fetch_assoc($result);

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
                    <form id="addToCartForm" class="mb-5">
                        <input type="hidden" name="productId" value="<?= $product['id'] ?>">
                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-auto">
                                <label for="quantity-input" class="fw-semibold">Quantity:</label>
                            </div>
                            <div class="col-4 col-md-3 col-lg-4">
                                <input type="number" id="quantity-input" name="quantity" class="form-control form-control-lg text-center" value="1" min="1" max="99">
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
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-outline-success flex-grow-1"><i class="bi bi-heart"></i> Add to Wishlist</button>
                            <button class="btn btn-outline-secondary flex-grow-1"><i class="bi bi-share"></i> Share</button>
                        </div>
                        <a href="shop.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
                    </form>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Product Description</h5>
                    <p class="text-muted description-text"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

                    <div class="card p-3 mt-4 border-success">
                        <p class="mb-1 fw-bold text-success"><span class="material-symbols-outlined me-1 small align-middle">verified_user</span> Sold By Local Farmer</p>
                        <p class="mb-1"><span class="fw-semibold">Farmer:</span> <?= htmlspecialchars($product['firstName'] . ' ' . $product['lastName']) ?></p>
                        <p class="mb-0"><span class="fw-semibold">Location:</span> <?= htmlspecialchars($product['parish']) ?></p>
                    </div>
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- AJAX Add to Cart & Toast -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('addToCartForm');
    if(!form) return;

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
            if(data.status === 'success'){
                showToast('Product added to cart!');
                updateCartBadge(data.newCount);
            } else {
                showToast('Failed to add product', true);
            }
        })
        .catch(()=>showToast('Network error', true));
    });

    function showToast(message, isError=false){
        let container = document.getElementById('toastContainer');
        if(!container){
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${isError?'danger':'success'} border-0 show mb-2`;
        toast.role = "alert";
        toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        container.appendChild(toast);
        setTimeout(()=>toast.remove(), 3000);
    }

    function updateCartBadge(count){
        const cartLink = document.querySelector('a[href="cart.php"]');
        if(!cartLink) return;
        let badge = document.getElementById('cart-item-count');
        if(count > 0){
            if(!badge){
                badge = document.createElement('span');
                badge.id = 'cart-item-count';
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                cartLink.appendChild(badge);
            }
            badge.textContent = count;
        } else if(badge){
            badge.remove();
        }
    }
});
</script>

</body>
</html>
