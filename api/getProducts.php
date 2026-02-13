<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

include("api/databaseConn.php");

if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$sql = "
    SELECT 
        p.id,
        p.productName,
        p.description,
        p.price,
        p.unitOfSale,
        p.stockQuantity,
        p.imagePath,
        f.firstName,
        f.lastName,
        f.parish,
        c.categoryName
    FROM products p
    LEFT JOIN farmers f ON p.farmerId = f.id
    LEFT JOIN categories c ON p.categoryId = c.id
    WHERE p.isAvailable = 1
      AND p.stockQuantity > 0
    ORDER BY p.id DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = [
        "id" => (int)$row["id"],
        "productName" => $row["productName"],
        "description" => $row["description"],
        "price" => (float)$row["price"],
        "unitOfSale" => $row["unitOfSale"],
        "stockQuantity" => (int)$row["stockQuantity"],
        "imageUrl" => $row["imagePath"] ?: "assets/default_product.png",
        "farmerName" => trim($row["firstName"] . " " . $row["lastName"]),
        "parish" => $row["parish"],
        "category" => $row["categoryName"]
    ];
}

echo json_encode($products);





