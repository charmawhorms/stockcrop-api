<?php
session_start();
include 'config.php';
include 'session.php';
redirectIfNotLoggedIn();

// Only farmers can access this page
if ($_SESSION['roleId'] != 2) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: viewProducts.php");
    exit();
}

$productId = intval($_GET['id']);
$userId = $_SESSION['id'];

// Get farmerId
$farmerQuery = mysqli_prepare($conn, "SELECT id FROM farmers WHERE userId = ?");
mysqli_stmt_bind_param($farmerQuery, "i", $userId);
mysqli_stmt_execute($farmerQuery);
$farmerResult = mysqli_stmt_get_result($farmerQuery);

if ($farmerResult && mysqli_num_rows($farmerResult) > 0) {
    $farmerRow = mysqli_fetch_assoc($farmerResult);
    $farmerId = $farmerRow['id'];
} else {
    die("Error: Farmer record not found.");
}

// Fetch product
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND farmerId = ?");
mysqli_stmt_bind_param($stmt, "ii", $productId, $farmerId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: viewProducts.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

// Fetch categories
$catStmt = mysqli_prepare($conn, "SELECT id, categoryName FROM categories ORDER BY categoryName ASC");
mysqli_stmt_execute($catStmt);
$catResult = mysqli_stmt_get_result($catStmt);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $productName = htmlspecialchars(trim($_POST['productName']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = floatval($_POST['price']);
    $unitOfSale = htmlspecialchars(trim($_POST['unitOfSale']));
    $stockQuantity = intval($_POST['stockQuantity']);
    $categoryId = intval($_POST['categoryId']);
    $isAvailable = isset($_POST['isAvailable']) ? 1 : 0;

    // Image upload handling
    $imagePath = $product['imagePath']; // Keep existing image by default
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('prod_', true) . "." . $ext;
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $targetFile = $targetDir . $newFileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile; // Update image only if new one uploaded
        }
    }

    // Update product
    $updateStmt = mysqli_prepare($conn, "UPDATE products SET categoryId=?, productName=?, description=?, price=?, unitOfSale=?, stockQuantity=?, imagePath=?, isAvailable=? WHERE id=? AND farmerId=?");
    mysqli_stmt_bind_param($updateStmt, "issdsisiii", $categoryId, $productName, $description, $price, $unitOfSale, $stockQuantity, $imagePath, $isAvailable, $productId, $farmerId);

    if (mysqli_stmt_execute($updateStmt)) {
        header("Location: viewProducts.php?msg=Product+updated+successfully");
        exit();
    } else {
        $errorMessage = "Failed to update product. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product | StockCrop</title>
<link rel="icon" type="image/png" href="assets/icon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body { 
        font-family: 'Roboto', sans-serif; 
        margin:0; 
        overflow-x: hidden; 
    }

    .sidebar { 
        width: 250px; 
        background-color: var(--sc-primary-green); 
        color: #fff; 
        flex-shrink:0; 
        display:flex; 
        flex-direction: column; 
        padding: 1rem 0; 
        position: fixed; 
        top:0; 
        bottom:0; 
        z-index:1030; 
        box-shadow:2px 0 5px rgba(0,0,0,0.1);
    }

    .sidebar .logo-container {
        padding:10px 20px 30px; 
        text-align:center;
    }

    .sidebar .logo-container img {
        max-height:40px;
    }

    .sidebar-link {
        color: var(--sc-light-text); 
        text-decoration:none; 
        padding:12px 25px; 
        display:flex; 
        align-items:center; 
        gap:15px; 
        margin:0 10px; 
        border-radius:8px; 
        transition: background-color 0.3s, color 0.3s;
    }

    .sidebar-link i { 
        font-size:1.2rem; 
    }

    .sidebar-link:hover, .sidebar-link.active { 
        background-color: var(--sc-dark-green); 
        color:#fff;
    }

    .sidebar-link.active { 
        font-weight:700; 
        border-left:5px solid #ffc107; 
        padding-left:20px; 
    }

    .navbar-top { 
        background-color:#fff; 
        border-bottom:1px solid #ddd; 
        color:#333; 
        z-index:1020; 
        position:fixed; 
        left:250px; 
        right:0; 
        height:60px;
    }

    .navbar-top .navbar-brand { 
        color: var(--sc-primary-green); 
        font-weight:700; 
        font-size:1.2rem; 
    }

    .navbar-top .btn-logout { 
        background:#ffc107; 
        color:#212529; 
        font-weight:600; 
        border:none; 
        border-radius:6px; 
        padding:6px 15px; 

    }

    .content { 
        margin-left:250px; 
        padding:20px 30px; 
        padding-top:80px; 
        min-height:100vh;
    }

    img.product-image { 
        width: 80px; 
        height: auto; 
    }
</style>
</head>
<body>

<?php
    include 'sidePanel.php';
?>

<div class="content">
    <h2 class="fw-bold text-success">Edit Product</h2>
    <?php if(isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    <form action="editProduct.php?id=<?php echo $productId; ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="productName" class="form-label">Product Name:</label>
            <input type="text" id="productName" name="productName" class="form-control" value="<?php echo htmlspecialchars($product['productName']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description:</label>
            <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price (JMD):</label>
            <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="unitOfSale" class="form-label">Unit of Sale:</label>
            <input type="text" id="unitOfSale" name="unitOfSale" class="form-control" value="<?php echo htmlspecialchars($product['unitOfSale']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="stockQuantity" class="form-label">Stock Quantity:</label>
            <input type="number" id="stockQuantity" name="stockQuantity" class="form-control" value="<?php echo $product['stockQuantity']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="categoryId" class="form-label">Category:</label>
            <select class="form-select" id="categoryId" name="categoryId" required>
                <option value="">-- Select Category --</option>
                <?php while ($cat = mysqli_fetch_assoc($catResult)): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $product['categoryId']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['categoryName']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Product Image (optional):</label>
            <input type="file" id="image" name="image" class="form-control">
            <?php if($product['imagePath']): ?>
                <img src="<?php echo $product['imagePath']; ?>" alt="Current Image" width="150" class="mt-2">
            <?php endif; ?>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="isAvailable" name="isAvailable" <?php echo ($product['isAvailable']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="isAvailable">Available</label>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Update Product</button>
        <a href="viewProducts.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
