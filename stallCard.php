<?php
include 'session.php';
include 'config.php';

$farmerId = intval($_GET['farmer_id'] ?? 0);
if (!$farmerId) die("Farmer not specified.");

$query = mysqli_prepare($conn, "SELECT firstName AS first_name, lastName AS last_name, verification_status FROM farmers WHERE id=?");
mysqli_stmt_bind_param($query, "i", $farmerId);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$farmer = mysqli_fetch_assoc($result) ?: ['first_name'=>'John','last_name'=>'Brown','verification_status'=>'verified'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Premium Stall Card</title>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@700;900&family=Public+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<style>
    :root {
        --jamaica-green: #006847;
        --vibrant-orange: #ff6b35;
        --deep-blue: #004e92;
        --soft-white: #f8f9fa;
    }

    body {
        font-family: 'Public Sans', sans-serif;
        margin: 0;
        padding: 0;
        background: #ccc;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .card-container {
        width: 148mm;
        height: 210mm;
        background: var(--soft-white);
        border-radius: 25px;
        box-shadow: 0 30px 60px rgba(0,0,0,0.4);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 4px solid white;
    }

    /* Top Visual Header */
    .hero-header {
        background: var(--jamaica-green);
        height: 150px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
    }

    /* "Fresh" Ribbon */
    .ribbon {
        position: absolute;
        top: 20px;
        left: -10px;
        background: var(--vibrant-orange);
        padding: 8px 25px;
        font-weight: 900;
        transform: rotate(-5deg);
        box-shadow: 5px 5px 15px rgba(0,0,0,0.2);
        font-size: 18px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .brand-logo {
        font-family: 'Unbounded', sans-serif;
        font-size: 32px;
        text-transform: uppercase;
        line-height: 1;
    }

    .brand-logo span {
        color: var(--vibrant-orange);
    }

    /* Farmer Section */
    .farmer-profile {
        padding: 30px 20px;
        text-align: center;
    }

    .farmer-name {
        font-family: 'Unbounded', sans-serif;
        font-size: 48px;
        color: var(--jamaica-green);
        margin: 0;
        letter-spacing: -2px;
        word-wrap: break-word;
    }

    .rada-seal {
        display: inline-flex;
        align-items: center;
        margin-top: 15px;
        background: #fff;
        border: 2px solid var(--jamaica-green);
        padding: 5px 20px;
        border-radius: 50px;
        color: var(--jamaica-green);
        font-weight: 800;
        font-size: 14px;
        text-transform: uppercase;
    }

    /* QR Impact Zone */
    .qr-impact-zone {
        flex-grow: 1;
        background: white;
        margin: 0 20px 20px;
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px dashed #ccc;
    }

    .qr-wrapper {
        padding: 15px;
        background: white;
        border: 5px solid var(--soft-white);
        border-radius: 15px;
        margin-bottom: 15px;
    }

    .qr-wrapper img {
        width: 250px;
        height: 250px;
        display: block;
    }

    .scan-instruction {
        font-size: 24px;
        font-weight: 800;
        color: var(--deep-blue);
        text-align: center;
        max-width: 80%;
    }

    /* Bottom Action Bar */
    .action-bar {
        background: var(--vibrant-orange);
        color: white;
        padding: 15px;
        text-align: center;
        font-weight: 900;
        font-size: 18px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>
</head>
<body>

<div class="card-container">
    <div class="hero-header">
        <div class="ribbon">100% Jamaican</div>
        <img src="assets/logo2.png" alt="StockCrop Logo" class="brand-logo">
    </div>

    <div class="farmer-profile">
        <h1 class="farmer-name">
            <?php echo htmlspecialchars(($farmer['first_name'] ?? 'John') . ' ' . ($farmer['last_name'] ?? 'Brown')); ?>
        </h1>
        
        <?php if(($farmer['verification_status'] ?? 'verified') == 'verified'): ?>
        <div class="rada-seal">
            <span style="margin-right:8px">✓</span> RADA VERIFIED FARMER
        </div>
        <?php endif; ?>
    </div>

    <div class="qr-impact-zone">
        <div class="qr-wrapper">
            <?php 
                $farmerId = $farmerId ?? 1;
                $qrUrl = "https://stockcrop.onrender.com/farmerProfile.php?id=" . $farmerId; 
            ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qrUrl); ?>" alt="Farmer Profile">
        </div>
        <div class="scan-instruction">SCAN TO ORDER FRESH PRODUCE</div>
    </div>

    <div class="action-bar">
        Skip the line &bull; Pay with StockCrop
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
window.onload = function() {
    // Check if the URL has ?download=true
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('download') === 'true') {
        
        // Target the card container
        const element = document.querySelector(".card-container");
        
        html2canvas(element, {
            scale: 3, // Higher quality for printing
            useCORS: true // Allows loading the QR code image from the external API
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'My_StockCrop_Stall_Card.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
            
            // Optional: Close the tab after download
            // window.close();
        });
    }
};
</script>
</body>
</html>