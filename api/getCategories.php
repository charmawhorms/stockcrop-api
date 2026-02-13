<?php
header("Content-Type: application/json");
include(__DIR__ . "/databaseConn.php");

if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Fetch all categories sorted alphabetically
$sql = "SELECT id, categoryName FROM categories ORDER BY categoryName ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

$categories = [];

while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
        "id" => (int)$row["id"],
        "name" => $row["categoryName"]
    ];
}

echo json_encode($categories);
