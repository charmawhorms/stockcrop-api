<?php
require_once 'config.php';

$farmer_id = intval($_GET['farmer_id'] ?? 0);
if(!$farmer_id){
    echo json_encode(['error'=>'Invalid farmer ID']);
    exit;
}

// Farmer info
$fRes = mysqli_query($conn, "SELECT id, firstName, lastName, verification_status FROM farmers WHERE id=$farmer_id");
$farmer = mysqli_fetch_assoc($fRes);

// Products
$pRes = mysqli_query($conn, "SELECT id, productName, price, imagePath FROM products WHERE farmerId=$farmer_id");
$products = [];
while($p = mysqli_fetch_assoc($pRes)) $products[] = $p;

// QR code
$qrPath = "qr_codes/farmer_$farmer_id.png"; // make sure QR exists

echo json_encode(['farmer'=>$farmer, 'products'=>$products, 'qr'=>file_exists($qrPath)?$qrPath:'qr_codes/default.png']);