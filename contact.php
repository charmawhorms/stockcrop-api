<?php
session_start();
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>StockCrop | Contact Us</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="assets/icon.png">

    <style>
        :root {
            --primary-green: #2f8f3f;
            --dark-forest: #1b3921;
            --soft-bg: #f8fafc;
            --transition: 0.3s ease;
        }

        /* Hero Section */
        .contact-hero {
            background: linear-gradient(rgba(27, 57, 33, 0.85), rgba(27, 57, 33, 0.85)), 
                        url('https://images.unsplash.com/photo-1595841696677-6489ff3f8cd1?q=80&w=2000&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            color: white;
            text-align: center;
        }

        /* Info Cards */
        .info-box {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 12px;
            background: var(--soft-bg);
            border: 1px solid #e2e8f0;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-green);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Modern Form Styles */
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }
        
        .floating-group { position: relative; 
            margin-bottom: 25px; 
        }

        .floating-group input, .floating-group textarea {
            width: 100%; 
            padding: 15px; 
            border: 1px solid #cbd5e1; 
            border-radius: 12px;
            background: transparent; 
            outline: none; 
            transition: var(--transition);
        }

        .floating-group label {
            position: absolute; 
            left: 15px; 
            top: 15px; 
            color: #64748b;
            pointer-events: none; 
            transition: 0.2s ease all; 
            background: white; 
            padding: 0 5px;
        }

        .floating-group input:focus, .floating-group textarea:focus { 
            border-color: var(--primary-green); 
            box-shadow: 0 0 0 4px rgba(47, 143, 63, 0.1); 
        }

        .floating-group input:focus ~ label, .floating-group input:not(:placeholder-shown) ~ label,
        .floating-group textarea:focus ~ label, .floating-group textarea:not(:placeholder-shown) ~ label {
            top: -10px; 
            font-size: 12px; 
            color: var(--primary-green); 
            font-weight: 600;
        }

        .btn-submit {
            background: var(--primary-green); 
            color: white; 
            width: 100%; 
            padding: 15px;
            border-radius: 12px; 
            border: none; 
            font-weight: 700; 
            transition: var(--transition);
        }

        .btn-submit:hover { 
            background: var(--dark-forest); 
            transform: translateY(-2px); 
        }

        /* Map */
        .map-container { 
            border-radius: 20px; 
            overflow: hidden; 
            height: 350px;
            border: 1px solid #e2e8f0; 
            margin-top: 20px;
            position: relative;
            z-index: 1; 
        }

        .contact-main-wrapper {
            margin-bottom: 80px; 
        }

        @media (max-width: 768px) {
            .map-container {
                height: 250px;
                margin-bottom: 40px;
            }
        }
        </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<section class="contact-hero">
    <div class="container">
        <h1 class="display-4 fw-bold">Let's Connect</h1>
        <p class="lead opacity-75">Have a question about the products or want to join as a farmer? We're here to chat.</p>
    </div>
</section>

<div class="container my-5 py-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <h2 class="fw-bold mb-4">Contact Information</h2>
            <p class="text-muted mb-5">Our team is available Monday through Friday to assist with your orders or farmer registrations.</p>

            <div class="info-box">
                <div class="info-icon"><span class="material-symbols-outlined">mail</span></div>
                <div>
                    <h6 class="fw-bold mb-0">Email Us</h6>
                    <small class="text-muted">support@stockcrop.com</small>
                </div>
            </div>

            <div class="info-box">
                <div class="info-icon"><span class="material-symbols-outlined">call</span></div>
                <div>
                    <h6 class="fw-bold mb-0">Call Us</h6>
                    <small class="text-muted">+1 (876) 555-1234</small>
                </div>
            </div>

            <div class="info-box">
                <div class="info-icon"><span class="material-symbols-outlined">location_on</span></div>
                <div>
                    <h6 class="fw-bold mb-0">Headquarters</h6>
                    <small class="text-muted">Kingston, Jamaica</small>
                </div>
            </div>

            <div class="map-container mt-4">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15147.222288015337!2d-76.7554!3d18.0193!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8ebd6d0000000000%3A0x0!2zMTjCsDAxJzA5LjUiTiA3NsKwNDUnMTkuNCJX!5e0!3m2!1sen!2sjm!4v1625000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="form-card">
                <h3 class="fw-bold mb-4">Send us a Message</h3>
                <form action="processContact.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="floating-group">
                                <input type="text" name="name" id="name" placeholder=" " required>
                                <label for="name">Full Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="floating-group">
                                <input type="email" name="email" id="email" placeholder=" " required>
                                <label for="email">Email Address</label>
                            </div>
                        </div>
                    </div>
                    <div class="floating-group">
                        <input type="text" name="subject" id="subject" placeholder=" " required>
                        <label for="subject">Subject</label>
                    </div>
                    <div class="floating-group">
                        <textarea name="message" id="message" rows="5" placeholder=" " required></textarea>
                        <label for="message">How can we help you?</label>
                    </div>
                    <button type="submit" class="btn-submit">
                        <span class="d-flex align-items-center justify-content-center gap-2">
                            Send Message <span class="material-symbols-outlined">send</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>