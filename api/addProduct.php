<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}

// Required fields
$required = [
    'farmerId',
    'categoryId',
    'productName',
    'price',
    'unitOfSale',
    'stockQuantity'
];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        echo json_encode(["error" => "$field is required"]);
        exit;
    }
}

// Sanitize inputs
$farmerId      = intval($_POST['farmerId']);
$categoryId    = intval($_POST['categoryId']);
$productName   = trim($_POST['productName']);
$description   = trim($_POST['description'] ?? '');
$price         = floatval($_POST['price']);
$unitOfSale    = trim($_POST['unitOfSale']);
$stockQuantity = intval($_POST['stockQuantity']);
$isAvailable   = 1;

// Image upload
$imagePath = "assets/default_product.png";

if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES['image']['type'], $allowedTypes)) {
        echo json_encode(["error" => "Invalid image type"]);
        exit;
    }

    $uploadDir = "../uploads/products/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid("product_") . "_" . basename($_FILES['image']['name']);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        echo json_encode(["error" => "Image upload failed"]);
        exit;
    }

    $imagePath = "uploads/products/" . $fileName;
}

// Insert product (MATCHES TABLE)
$sql = "
    INSERT INTO products
    (farmerId, categoryId, productName, description, price, unitOfSale, stockQuantity, imagePath, isAvailable)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(["error" => mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "iissdsisi",
    $farmerId,
    $categoryId,
    $productName,
    $description,
    $price,
    $unitOfSale,
    $stockQuantity,
    $imagePath,
    $isAvailable
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => true,
        "productId" => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode(["error" => "Failed to add product"]);
}
