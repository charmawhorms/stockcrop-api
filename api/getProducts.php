<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// Use __DIR__ to ensure it finds the config file in the same folder
include(__DIR__ . "/databaseConn.php");

if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// 1. Get filter values from the URL (if they exist)
// Example: getProducts.php?categoryId=2  OR getProducts.php?search=tomato
$categoryId = $_GET['categoryId'] ?? null;
$search = $_GET['search'] ?? null;

// 2. Base SQL Query
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
";

// 3. Dynamically add filters to the SQL
if ($categoryId) {
    $sql .= " AND p.categoryId = " . (int)$categoryId;
}

if ($search) {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (p.productName LIKE '%$safeSearch%' OR p.description LIKE '%$safeSearch%')";
}

// 4. Final Sorting
$sql .= " ORDER BY p.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = [
        "id" => (int)$row["id"],
        "productName" => $row["productName"],
        "description" => $row["description"],
        "price" => (float)$row["price"],
        "unitOfSale" => $row["unitOfSale"],
        "stockQuantity" => (int)$row["stockQuantity"],
        // Provides the full URL so the mobile app can display the image immediately
        "imageUrl" => "https://stockcrop-api.onrender.com/assets/" . ($row["imagePath"] ?: "default_product.png"),
        "farmerName" => trim($row["firstName"] . " " . $row["lastName"]),
        "parish" => $row["parish"],
        "category" => $row["categoryName"]
    ];
}

echo json_encode($products);
