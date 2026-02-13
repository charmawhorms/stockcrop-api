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

    // Fetch products
    $stmt = mysqli_prepare($conn, "
        SELECT p.id, p.productName, p.description, p.price, p.unitOfSale, p.stockQuantity, p.isAvailable, p.imagePath, c.categoryName
        FROM products p
        JOIN categories c ON p.categoryId = c.id
        WHERE p.farmerId = ?
        ORDER BY p.id DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $farmerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Products | StockCrop</title>
<link rel="icon" type="image/png" href="assets/icon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<style>
    :root {
        --sc-primary-green: #028037;
        --sc-dark-green: #01632c;
        --sc-hover-green: #146c43;
        --sc-background: #f5f7fa;
    }

    body {
        font-family: 'Roboto', sans-serif;
        background-color: var(--sc-background);
        margin: 0;
        overflow-x: hidden;
    }

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
            padding-top: 20px; 
            padding-left: 10px;
            padding-right: 10px;
        }
    }


    h2 {
        font-weight: 700;
        color: #028037;
    }

    .table {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .table thead {
        background-color: #028037;
        color: white;
    }

    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
    }

    td.description-cell {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-icon {
        font-size: 1.2rem;
        padding: 4px 8px;
        margin-right: 4px;
    }

    .modal-img {
        width: 100%;
        height: auto;
        border-radius: 6px;
    }
</style>
</head>
<body>
<?php include 'sidePanel.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2>My Products</h2>
    <a href="addProduct.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Add Product
    </a></div>
    
    <p class="sss">Manage your products: view details, edit, or remove items.</p>

    <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="alert alert-info mt-4">
            You have not added any products yet. <a href="addProduct.php" class="text-success fw-bold">Add a product now</a>.
        </div>
    <?php else: ?>
        <div class="table-responsive mt-4">
            <table class="table table-bordered align-middle" id="productsTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Stock</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                        // Stock badge color
                        if ($row['stockQuantity'] > 20) $stockClass = 'bg-success';
                        elseif ($row['stockQuantity'] > 0) $stockClass = 'bg-warning';
                        else $stockClass = 'bg-danger';
                    ?>
                    <tr id="product-<?= $row['id']; ?>">
                        <td>
                            <img src="<?= !empty($row['imagePath']) && file_exists($row['imagePath']) ? htmlspecialchars($row['imagePath']) : 'assets/placeholder.png'; ?>" 
                                 class="product-image" alt="<?= htmlspecialchars($row['productName']); ?>" loading="lazy">
                        </td>
                        <td><?= htmlspecialchars($row['productName']); ?></td>
                        <td class="description-cell" title="<?= htmlspecialchars($row['description']); ?>"><?= htmlspecialchars($row['description']); ?></td>
                        <td><span class="badge <?= $stockClass; ?>"><?= $row['stockQuantity']; ?></span></td>
                        <td>
                            <?php $is_available = (bool)$row['isAvailable']; ?>
                            <span class="badge <?= $is_available ? 'bg-success' : 'bg-secondary'; ?>">
                                <?= $is_available ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success btn-icon" title="View Details"
                                    onclick="viewDetails(<?= htmlspecialchars(json_encode($row)); ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="editProduct.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-primary btn-icon" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-danger btn-icon" title="Delete" onclick="confirmDelete(<?= $row['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <img id="modalImage" class="modal-img mb-3" src="" alt="">
        <p><strong>Category:</strong> <span id="modalCategory"></span></p>
        <p><strong>Description:</strong> <span id="modalDescription"></span></p>
        <p><strong>Price:</strong> JMD <span id="modalPrice"></span></p>
        <p><strong>Unit:</strong> <span id="modalUnit"></span></p>
        <p><strong>Stock:</strong> <span id="modalStock"></span></p>
        <p><strong>Available:</strong> <span id="modalAvailable"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this product?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="deleteConfirmBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="toast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#productsTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50],
            "columnDefs": [{ "orderable": false, "targets": [0,5] }]
        });
    });

    function viewDetails(product) {
        document.getElementById('modalImage').src = product.imagePath && product.imagePath.length ? product.imagePath : 'assets/placeholder.png';
        document.getElementById('modalCategory').textContent = product.categoryName;
        document.getElementById('modalDescription').textContent = product.description;
        document.getElementById('modalPrice').textContent = parseFloat(product.price).toFixed(2);
        document.getElementById('modalUnit').textContent = product.unitOfSale;
        document.getElementById('modalStock').textContent = product.stockQuantity;
        document.getElementById('modalAvailable').textContent = product.isAvailable ? 'Yes' : 'No';
        
        const modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    }

    let deleteProductId = null;
    function confirmDelete(productId) {
        deleteProductId = productId;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }

    document.getElementById('deleteConfirmBtn').addEventListener('click', function() {
        if (!deleteProductId) return;
        fetch('deleteProductAjax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + deleteProductId
        })
        .then(res => res.text())
        .then(data => {
            const deleteModalEl = document.getElementById('deleteModal');
            const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
            deleteModal.hide();

            if(data.trim() === "success") {
                const row = document.getElementById('product-' + deleteProductId);
                if(row) row.remove();
                showToast("✅ Product deleted successfully.", true);
            } else {
                showToast("❌ Error: " + data, false);
            }
        })
        .catch(err => showToast("Error: " + err, false));
        deleteProductId = null;
    });

    function showToast(message, success=true){
        const toastEl = document.getElementById('toast');
        toastEl.classList.remove('text-bg-success','text-bg-danger');
        toastEl.classList.add(success ? 'text-bg-success' : 'text-bg-danger');
        document.getElementById('toastBody').textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
</script>
</body>
</html>
