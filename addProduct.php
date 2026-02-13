<?php
include 'session.php';
include 'config.php';

// Only allow farmers
redirectIfNotLoggedIn();
if ($_SESSION['roleId'] != 2) {
    header("Location: index.php");
    exit();
}

// Get farmerId
$userId = $_SESSION['id'];
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

$successMessage = '';
$errorMessage = '';

// Fetch categories
$categories = [];
$catQuery = mysqli_query($conn, "SELECT id, categoryName FROM categories ORDER BY categoryName ASC");
while ($row = mysqli_fetch_assoc($catQuery)) {
    $categories[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $productName = htmlspecialchars(trim($_POST["productName"]));
    $description = htmlspecialchars(trim($_POST["description"]));
    $categoryId = intval($_POST["category"]);
    $price = floatval($_POST["price"]);
    $unitOfSale = htmlspecialchars(trim($_POST["unitOfSale"]));
    $stockQuantity = intval($_POST["stockQuantity"]);
    $isAvailable = isset($_POST["isAvailable"]) ? 1 : 0;

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES["image"]["type"], $allowedTypes)) {
            $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $imagePath = "uploads/" . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
        } else {
            $errorMessage = "Only JPG, PNG, and GIF files are allowed.";
        }
    }

    if ($errorMessage == '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (farmerId, categoryId, productName, description, price, unitOfSale, stockQuantity, imagePath, isAvailable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
            $successMessage = "Product added successfully!";
        } else {
            $errorMessage = "Error adding product: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product | StockCrop</title>
<link rel="icon" type="image/png" href="assets/icon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">

<style>
    .content {
        margin-left: 250px;
        padding: 20px 30px;
        padding-top: 80px;
        min-height: 100vh;
    }

    @media (max-width: 992px) {
        .content {
            margin-left: 0;
            padding: 20px;
        }
        .product-image {
            width: 60px;
            height: 60px;
        }
        td.description-cell {
            max-width: 100px;
        }
        
        .sidebar {
            position: relative;
            width: 100%;
        }
        .content {
            margin-left: 0;
            padding-top: 20px; /* reduce padding for mobile */
            padding-left: 10px;
            padding-right: 10px;
        }
    }

</style>
</head>
<body>

<?php include 'sidePanel.php'; ?>

<div class="content">
    <h2 class="fw-bold text-success">Add New Product</h2>
    <p class="lead">Fill in the details below to add a new product.</p>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form action="addProduct.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="productName" class="form-label">Product Name:</label>
            <input type="text" id="productName" name="productName" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category:</label>
            <select id="category" name="category" class="form-select" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" data-category-name="<?php echo htmlspecialchars($cat['categoryName']); ?>"><?php echo htmlspecialchars($cat['categoryName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description:</label>
            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
            <button type="button" id="generateDescriptionBtn" class="btn btn-secondary mt-2">Generate AI Description</button>
            <span id="descriptionStatus" class="ms-2 text-muted"></span>
        </div>

        <div class="mb-3">
            <label for="price" class="form-label">Price (JMD):</label>
            <input type="number" step="0.01" id="price" name="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="unitOfSale" class="form-label">Unit of Sale:</label>
            <input type="text" id="unitOfSale" name="unitOfSale" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="stockQuantity" class="form-label">Stock Quantity:</label>
            <input type="number" id="stockQuantity" name="stockQuantity" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Product Image:</label>
            <input type="file" id="image" name="image" class="form-control">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" id="isAvailable" name="isAvailable" class="form-check-input" checked>
            <label for="isAvailable" class="form-check-label">Available for Sale</label>
        </div>

        <button type="submit" name="submit" class="btn btn-success">Add Product</button>
    </form>
</div>

<script>
document.getElementById('generateDescriptionBtn').addEventListener('click', function() {
    const productName = document.getElementById('productName').value.trim();
    const categorySelect = document.getElementById('category');
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryName = selectedOption.getAttribute('data-category-name');
    const statusSpan = document.getElementById('descriptionStatus');

    if (!productName || !categorySelect.value) {
        alert('Please enter a product name and select a category first.');
        return;
    }

    statusSpan.textContent = 'Generating description...';

    fetch('generateDescription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `productName=${encodeURIComponent(productName)}&category=${encodeURIComponent(categoryName)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.description) {
            document.getElementById('description').value = data.description;
            statusSpan.textContent = 'Description generated successfully!';
        } else if (data.error) {
            statusSpan.textContent = '';
            alert('Error generating description: ' + data.error);
        } else {
            statusSpan.textContent = '';
            alert('Unknown error generating description.');
        }
    })
    .catch(err => {
        statusSpan.textContent = '';
        alert('Fetch error: The AJAX request failed (check console/network tab).');
        console.error(err);
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
