<?php
session_start();
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>StockCrop | Our Story</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">

    <style>
        :root {
            --primary-green: #2f8f3f;
            --dark-forest: #1b3921;
            --soft-bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
        }


        /* --- Hero Section --- */
        .about-hero {
            background: linear-gradient(rgba(27, 57, 33, 0.8), rgba(27, 57, 33, 0.8)), 
                        url('https://images.unsplash.com/photo-1500651230702-0e2d8a49d4ad?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            padding: 120px 0 80px;
            color: white;
            text-align: center;
        }

        .about-hero h1 { font-weight: 800; font-size: 3.5rem; margin-bottom: 20px; }

        /* --- Story Section --- */
        .story-section { padding: 100px 0; }
        .image-stack { position: relative; }
        .main-img { border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; }
        
        /* --- Values Grid --- */
        .values-section { background-color: var(--soft-bg); padding: 100px 0; }
        .value-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            height: 100%;
            transition: 0.3s;
            border: 1px solid var(--border);
        }
        .value-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.05); }
        .icon-box {
            width: 60px;
            height: 60px;
            background: rgba(47, 143, 63, 0.1);
            color: var(--primary-green);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        /* --- Team Section --- */
        .team-section { padding: 100px 0; }
        .member-card {
            text-align: center;
            margin-bottom: 40px;
        }
        .member-img-wrapper {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            position: relative;
        }
        .member-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid var(--soft-bg);
        }
        .member-card h4 { 
          font-weight: 700; 
          color: var(--dark-forest); 
          margin-bottom: 5px; 
        }

        .member-card p { 
          color: var(--primary-green); 
          font-weight: 600; 
          font-size: 0.9rem; 
          text-transform: uppercase; 
          letter-spacing: 1px; 
        }

        .leader-row { margin-bottom: 60px; }

        @media (max-width: 768px) {
            .about-hero h1 { font-size: 2.5rem; }
            .story-section { padding: 60px 0; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<section class="about-hero">
    <div class="container">
        <h1 class="display-3">Rooted in Community</h1>
        <p class="lead mx-auto opacity-75" style="max-width: 700px;">We are bridging the gap between Jamaica’s hard-working farmers and your dinner table through innovation and transparency.</p>
    </div>
</section>

<section class="story-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 image-stack">
                <img src="https://images.unsplash.com/photo-1592419044706-39796d40f98c?q=80&w=2000&auto=format&fit=crop" alt="Fresh Produce" class="main-img">
            </div>
            <div class="col-lg-6">
                <h6 class="text-success fw-bold text-uppercase mb-3">Who We Are</h6>
                <h2 class="fw-bold mb-4 display-6">Revolutionizing the Jamaican Food Chain</h2>
                <p class="mb-4">StockCrop is a proudly Jamaican platform born from a simple idea: <strong>Farmers should earn more, and customers should eat fresher.</strong></p>
                <p class="text-muted">By removing the unnecessary middle-men, we allow farmers to list their harvests directly. This ensures that the food on your plate hasn't spent days in a warehouse, but was likely in the soil just yesterday.</p>
                <div class="d-flex gap-4 mt-5">
                    <div>
                        <h3 class="fw-bold text-success mb-0">100%</h3>
                        <small class="text-muted">Local Produce</small>
                    </div>
                    <div class="vr"></div>
                    <div>
                        <h3 class="fw-bold text-success mb-0">14</h3>
                        <small class="text-muted">Parishes Served</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container text-center">
        <h6 class="text-success fw-bold text-uppercase mb-3">Our Mission</h6>
        <h2 class="fw-bold mb-5">Built on Core Principles</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="value-card">
                    <div class="icon-box mx-auto">
                        <span class="material-symbols-outlined fs-2">eco</span>
                    </div>
                    <h4>Sustainability</h4>
                    <p class="text-muted mb-0">Reducing food miles and supporting farming practices that keep Jamaica green.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="icon-box mx-auto">
                        <span class="material-symbols-outlined fs-2">handshake</span>
                    </div>
                    <h4>Fairness</h4>
                    <p class="text-muted mb-0">Ensuring farmers get a fair market price for their labor and dedication.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="icon-box mx-auto">
                        <span class="material-symbols-outlined fs-2">verified_user</span>
                    </div>
                    <h4>Transparency</h4>
                    <p class="text-muted mb-0">Know exactly where your food comes from and the farmer who grew it.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="team-section">
    <div class="container text-center">
        <h6 class="text-success fw-bold text-uppercase mb-3">Our Team</h6>
        <h2 class="fw-bold mb-5">The StockCrop Family</h2>

        <div class="row justify-content-center leader-row">
            <div class="col-md-4">
                <div class="member-card">
                    <div class="member-img-wrapper">
                        <img src="assets/ceo.jpg" alt="Christina Taylor">
                    </div>
                    <h4>Christina Taylor</h4>
                    <p>Founder & CEO</p>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-6 col-lg-2">
                <div class="member-card">
                    <div class="member-img-wrapper" style="width: 120px; height: 120px;">
                        <img src="assets/operations.jpg" alt="Darico Powell">
                    </div>
                    <h6 class="fw-bold mb-1">Darico Powell</h6>
                    <small class="text-success small fw-bold">Operations</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="member-card">
                    <div class="member-img-wrapper" style="width: 120px; height: 120px;">
                        <img src="assets/logistics.jpg" alt="David Martin">
                    </div>
                    <h6 class="fw-bold mb-1">David Martin</h6>
                    <small class="text-success small fw-bold">Logistics</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="member-card">
                    <div class="member-img-wrapper" style="width: 120px; height: 120px;">
                        <img src="assets/techlead.jpg" alt="Britania McGregor">
                    </div>
                    <h6 class="fw-bold mb-1">Britania McGregor</h6>
                    <small class="text-success small fw-bold">Tech Lead</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="member-card">
                    <div class="member-img-wrapper" style="width: 120px; height: 120px;">
                        <img src="assets/marketing.jpg" alt="Charlon McCarthy">
                    </div>
                    <h6 class="fw-bold mb-1">Charlon McCarthy</h6>
                    <small class="text-success small fw-bold">Marketing</small>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="member-card">
                    <div class="member-img-wrapper" style="width: 120px; height: 120px;">
                        <img src="assets/finance.jpg" alt="Charma Whorms">
                    </div>
                    <h6 class="fw-bold mb-1">Charma Whorms</h6>
                    <small class="text-success small fw-bold">Finance</small>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>