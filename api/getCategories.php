<?php
header("Content-Type: application/json");
include(__DIR__ . "/databaseConn.php");

$sql = "SELECT id, categoryName FROM categories ORDER BY categoryName ASC";
$result = mysqli_query($conn, $sql);
$categories = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode($categories);
