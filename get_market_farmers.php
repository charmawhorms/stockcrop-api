<?php
require_once 'config.php';

$market_id = intval($_GET['market_id'] ?? 0);
if(!$market_id){
    echo json_encode(['error'=>'Invalid market ID']);
    exit;
}

// Fetch market info
$marketRes = mysqli_query($conn, "SELECT m.*, p.name AS parish_name FROM markets m JOIN parishes p ON m.parish_id=p.id WHERE m.id=$market_id");
$market = mysqli_fetch_assoc($marketRes);

// Fetch farmers
$farmersRes = mysqli_query($conn, "SELECT f.id, f.firstName AS first_name, f.lastName AS last_name FROM market_farmers mf JOIN farmers f ON mf.farmer_id=f.id WHERE mf.market_id=$market_id");
$farmers = [];
while($f = mysqli_fetch_assoc($farmersRes)) $farmers[] = $f;

echo json_encode(['market'=>$market, 'farmers'=>$farmers]);