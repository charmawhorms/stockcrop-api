<?php
    // 1. Start the session
    session_start();

    include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>StockCrop | Home</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="styles.css">
        <link rel="icon" type="image/png" href="assets/icon.png">
        <meta name="keywords" content="farmers market, fresh produce, organic fruits, vegetables, local products, sustainable farming, healthy eating, farm-to-table, seasonal produce, community market">
        <meta name="description" content="Discover fresh, organic produce directly from local farmers. Shop seasonal fruits and vegetables, support sustainable farming, and enjoy healthy eating with our farm-to-table market.">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    </head>
    <body>
        <?php
            include 'navbar.php';
        ?>
        
        <section class="market-hero">
            <div class="container">
                <div class="row g-4 align-items-stretch">
                    
                    <div class="col-lg-7">
                        <div class="hero-main-card">
                            <div class="trust-badge">
                                <span class="material-symbols-outlined">verified</span>
                                RADA Verified Farmers
                            </div>
                            <h1 class="hero-title">
                                Fresh From Jamaican Farms, <br>
                                <span class="accent-text">Delivered to Your Door.</span>
                            </h1>
                            <p class="hero-subtitle">
                                StockCrop bridges the gap between verified local farmers and your home. 
                                Ensuring food security and economic growth through every harvest.
                            </p>
                            <div class="hero-actions">
                                <a href="shop.php" class="btn-primary-custom">
                                    Start Shopping <span class="material-symbols-outlined">trending_flat</span>
                                </a>
                                <div class="user-proof">
                                    <div class="user-avatars">
                                        <span class="material-symbols-outlined">group</span>
                                    </div>
                                    <span>Join 1,000+ satisfied customers</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="hero-visual-stack">
                            <div class="image-box">
                                <img src="assets/man-holding-vegetables.png" alt="RADA Farmer" class="farmer-photo">
                            </div>
                            <div class="feature-strip">
                                <div class="strip-item">
                                    <span class="material-symbols-outlined">payments</span>
                                    Direct to Farmer
                                </div>
                                <div class="strip-item">
                                    <span class="material-symbols-outlined">local_shipping</span>
                                    Islandwide Reach
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Features + Categories Section -->
        <section class="impact-section py-5">
            <div class="container mb-5 text-center">
                <span class="sub-heading">Marketplace Pillars</span>
                <h2 class="section-title text-dark-green">Our Direct-to-Consumer Model</h2>
                <div class="title-divider mx-auto"></div>
            </div>

            <div class="container">
                <div class="row g-4 justify-content-center">
                    
                    <div class="col-lg-4">
                        <div class="impact-card">
                            <div class="icon-header produce-bg">
                                <span class="material-symbols-outlined">nutrition</span>
                            </div>
                            <div class="card-body-custom">
                                <h4 class="pillar-title">Premium Produce</h4>
                                <p class="pillar-text">Get the freshest fruits, vegetables and ground provisions harvested daily and delivered with care. No long storage, no middlemen just direct freshness.</p>
                                <div class="pillar-tag"><span class="material-symbols-outlined">check_circle</span> Ground Provisions & Veg</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="impact-card featured-card">
                            <div class="icon-header livestock-bg">
                                <span class="material-symbols-outlined">agriculture</span>
                            </div>
                            <div class="card-body-custom">
                                <h4 class="pillar-title">RADA-Verified Livestock</h4>
                                <p class="pillar-text">Ensuring food security through healthy, ethically-raised animals. Every farmer is RADA-registered for complete consumer trust.</p>
                                <div class="pillar-tag gold"><span class="material-symbols-outlined">verified</span> Certified Health Tracking</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="impact-card">
                            <div class="icon-header fair-bg">
                                <span class="material-symbols-outlined">handshake</span>
                            </div>
                            <div class="card-body-custom">
                                <h4 class="pillar-title">Fair-Trade Ecosystem</h4>
                                <p class="pillar-text">By removing intermediaries, we ensure farmers earn more while customers pay less for higher quality. A sustainable win for Jamaica.</p>
                                <div class="pillar-tag green"><span class="material-symbols-outlined">trending_up</span> Economic Empowerment</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <h4 class="text-center categories-title">Categories</h4>

        <div class="categories-row mt-4">
            <div class="category-item">
                <img src="assets/catFruits.png" alt="Fruits">
                <p class="cat-label">Fruits</p>
            </div>

            <div class="category-item">
                <img src="assets/catVegetables.png" alt="Vegetables">
                <p class="cat-label">Vegetables</p>
            </div>

            <div class="category-item">
                <img src="assets/catHerbs.png" alt="Herbs">
                <p class="cat-label">Herbs</p>
            </div>

            <div class="category-item">
                <img src="assets/catGrains.png" alt="Grains">
                <p class="cat-label">Grains</p>
            </div>

            <div class="category-item">
                <img src="assets/catGroundProvision.png" alt="Ground Provision">
                <p class="cat-label">Ground Provision</p>
            </div>

            <div class="category-item">
                <img src="assets/catLivestock.png" alt="Livestock">
                <p class="cat-label">Livestock</p>
            </div>
        </div>
        </div>
        </section>

        <!-- Featured Products Section -->
        <section class="featured-products py-5 position-relative">
            <!-- Background Image -->
            <div class="featured-bg"></div>
            <div class="overlay"></div>

            <div class="container position-relative text-white">
                <h2 class="text-center mb-5 fw-bold text-white">This Week’s Fresh Picks</h2>

                <div class="row g-4">
                    <?php
                        // Fetch 4 products
                        $query = "SELECT id, productName, price, imagePath, unitOfSale FROM products ORDER BY productName DESC LIMIT 4";
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0):
                            while ($row = mysqli_fetch_assoc($result)):
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="card product-card h-100 border-0 shadow-lg">
                            <div class="card-img-container">
                                <img src="<?= htmlspecialchars($row['imagePath']) ?>"  
                                    class="card-img-top" 
                                    alt="<?= htmlspecialchars($row['productName']); ?>">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title text-dark"><?= htmlspecialchars($row['productName']); ?></h5>
                                <p class="text-success fw-bold mb-3">$<?= number_format($row['price'], 2); ?> / <?= htmlspecialchars($row['unitOfSale']); ?></p>
                                <a href="productDetails.php?id=<?= $row['id']; ?>" class="btn btn-success w-100 fw-semibold">View Product</a>
                            </div>
                        </div>
                    </div>
                    <?php 
                            endwhile;
                        else:
                            echo "<p class='text-center text-light'>No featured products available right now.</p>";
                        endif;
                    ?>
                </div>

                <div class="text-center mt-5">
                    <a href="shop.php" class="btn btn-lg btn-warning fw-bold shadow">Browse All Products</a>
                </div>
            </div>
        </section>
        
        <section class="testimonials py-5">
            <div class="container">
                <h2 class="text-center mb-2 fw-bold">Real Stories From Farmers and Shoppers</h2>
                <p class="text-center lead text-muted mb-5">
                    See how StockCrop is helping Jamaicans buy and sell fresh produce directly from the source.
                </p>

                <div class="row g-4">
                    <!-- Shopper -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 p-4 shadow-sm border-0">
                            <div class="card-body text-center"> 
                                <img src="assets/customerProfilePicture.png" 
                                    alt="Lia G. Avatar" 
                                    class="testimonial-avatar mb-3 rounded-circle" height="80" width="80"> 
                                
                                <div class="text-warning mb-3">★★★★★</div> 
                                <p class="card-text fst-italic">
                                    "I love being able to order directly from local farmers. The produce is fresher, and I actually know where my food comes from. StockCrop makes it so convenient!"
                                </p>
                                <footer class="blockquote-footer mt-3">
                                    <cite title="Shopper" class="fw-bold text-dark">Lia G.</cite>, St. Ann
                                </footer>
                            </div>
                        </div>
                    </div>

                    <!-- Farmer -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 p-4 shadow-sm border-0">
                            <div class="card-body text-center"> 
                                <img src="assets/customerProfilePicture.png"
                                    alt="Michael B. Avatar" 
                                    class="testimonial-avatar mb-3 rounded-circle" height="80" width="80"> 

                                <div class="text-warning mb-3">★★★★★</div>
                                <p class="card-text fst-italic">
                                    "As a farmer, StockCrop has helped me reach new customers across the island without needing a middleman. It’s simple, fair, and boosts my earnings."
                                </p>
                                <footer class="blockquote-footer mt-3">
                                    <cite title="Farmer" class="fw-bold text-dark">Michael B.</cite>, Clarendon Farmer
                                </footer>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shopper -->
                    <div class="col-lg-4 col-md-12">
                        <div class="card h-100 p-4 shadow-sm border-0">
                            <div class="card-body text-center"> 
                                <img src="assets/customerProfilePicture.png" 
                                    alt="Sophia R. Avatar" 
                                    class="testimonial-avatar mb-3 rounded-circle" height="80" width="80"> 

                                <div class="text-warning mb-3">★★★★★</div>
                                <p class="card-text fst-italic">
                                    "My family now gets farm-fresh ground provisions delivered weekly. Supporting local farmers while getting quality produce—it’s a win-win!"
                                </p>
                                <footer class="blockquote-footer mt-3">
                                    <cite title="Shopper" class="fw-bold text-dark">Sophia R.</cite>, St. Catherine
                                </footer>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <section class="final-cta-modern">
            <div class="cta-background-image"></div>
            <div class="cta-overlay"></div>
    
            <div class="container position-relative" style="z-index: 3;">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="cta-glass-card text-center">
                            <div class="brand-tag mb-4">StockCrop: Jamaica's Digital Harvest</div>
                            <h2 class="display-4 fw-black text-white mb-4">
                                Taste the Freshness. <br>
                                <span class="text-warning-accent">Get Started Today.</span>
                            </h2>
                            <p class="lead text-white-50 mb-5 mx-auto" style="max-width: 700px;">
                                Join the movement towards a sustainable, transparent, and direct farm-to-table ecosystem. Whether you’re a family looking for quality or a farmer looking for a fair market - your journey starts here.
                            </p>
                            
                            <div class="cta-button-wrapper d-flex flex-column flex-md-row justify-content-center gap-3">
                                <a href="shop.php" class="btn btn-primary-cta">
                                    <span class="material-symbols-outlined">shopping_cart</span>
                                    Start Shopping Now
                                </a>
                                <a href="register.php" class="btn btn-outline-cta">
                                    <span class="material-symbols-outlined">agriculture</span>
                                    Register as a Farmer
                                </a>
                            </div>
                            
                            <div class="mt-5 d-flex justify-content-center align-items-center gap-4 text-white-50 fs-small">
                                <span class="d-flex align-items-center gap-2"><span class="material-symbols-outlined fs-6">verified</span> RADA Verified</span>
                                <span class="d-flex align-items-center gap-2"><span class="material-symbols-outlined fs-6">local_shipping</span> Islandwide Delivery</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
            include 'footer.php';
        ?>
    </body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</html>
