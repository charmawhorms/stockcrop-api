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
mysqli_stmt_bind_result($stmt, $id, $productName, $description, $price, $unitOfSale, $stockQuantity, $isAvailable, $imagePath, $categoryName);

$products = [];
while (mysqli_stmt_fetch($stmt)) {
    $products[] = [
        'id' => $id,
        'productName' => $productName,
        'description' => $description,
        'price' => $price,
        'unitOfSale' => $unitOfSale,
        'stockQuantity' => $stockQuantity,
        'isAvailable' => $isAvailable,
        'imagePath' => $imagePath,
        'categoryName' => $categoryName
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products | StockCrop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sc-primary: #028037;
            --sc-primary-light: #e6f2ea;
            --sc-dark: #014d21;
            --sc-bg: #f8fafc;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--sc-bg); 
            color: var(--text-main);
        }

        .content { 
            margin-left: 250px; 
            padding: 100px 3rem 3rem 3rem; 
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Header Styling */
        .page-header h2 { 
            font-weight: 700; 
            color: var(--sc-dark); 
            letter-spacing: -0.5px;
        }

        /* Card Container */
        .main-card {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        /* Table Aesthetics */
        .table thead th {
            background: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            padding: 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .product-image {
            width: 54px;
            height: 54px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        /* Badge Styling */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .bg-low-stock { background-color: #fff7ed; color: #9a3412; } /* Warning Orange */
        .bg-in-stock { background-color: #f0fdf4; color: #166534; } /* Success Green */
        .bg-out-stock { background-color: #fef2f2; color: #991b1b; } /* Danger Red */

        /* Action Buttons */
        .btn-action {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: var(--text-muted);
        }

        .btn-view:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .btn-edit:hover { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .btn-delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

        @media (max-width: 992px) {
            .content { margin-left: 0; padding: 110px 1.5rem 2rem 1.5rem; }
        }
    </style>
</head>
<body>
<?php include 'sidePanel.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="page-header">
            <h2>My Products</h2>
            <p class="text-muted mb-0">Manage your products: view details, edit, or remove items.</p>
        </div>
        <a href="addProduct.php" class="btn btn-success d-flex align-items-center gap-2 px-4 py-2 shadow-sm">
            <i class="bi bi-plus-lg"></i> <span class="fw-semibold">Add New Product</span>
        </a>
    </div>

    <?php if (count($products) === 0): ?>
        <div class="main-card text-center py-5">
            <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3">No products found</h5>
            <p class="text-muted">You haven't added anything to your store yet.</p>
            <a href="addProduct.php" class="btn btn-outline-success btn-sm px-4">Get Started</a>
        </div>
    <?php else: ?>
        <div class="main-card">
            <div class="table-responsive">
                <table class="table align-middle" id="productsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Stock Level</th>
                            <th>Price (JMD)</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $row): ?>
                        <?php
                            if ($row['stockQuantity'] > 20) {
                                $stockLabel = 'In Stock';
                                $stockClass = 'bg-in-stock';
                            } elseif ($row['stockQuantity'] > 0) {
                                $stockLabel = 'Low Stock';
                                $stockClass = 'bg-low-stock';
                            } else {
                                $stockLabel = 'Out of Stock';
                                $stockClass = 'bg-out-stock';
                            }
                        ?>
                        <tr id="product-<?= $row['id']; ?>">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= !empty($row['imagePath']) && file_exists($row['imagePath']) ? htmlspecialchars($row['imagePath']) : 'assets/placeholder.png'; ?>" 
                                         class="product-image" alt="Product">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($row['productName']); ?></div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 150px;"><?= htmlspecialchars($row['description']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-muted"><?= htmlspecialchars($row['categoryName']); ?></span></td>
                            <td>
                                <span class="badge <?= $stockClass; ?>"><?= $row['stockQuantity']; ?></span>
                                <div class="small mt-1 <?= strpos($stockClass, 'out') !== false ? 'text-danger' : 'text-muted'; ?>" style="font-size: 0.7rem; font-weight: 600;">
                                    <?= $stockLabel ?>
                                </div>
                            </td>
                            <td class="fw-bold text-dark">$<?= number_format($row['price'], 2); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= $row['isAvailable'] ? '#22c55e' : '#94a3b8'; ?>;"></div>
                                    <span class="small fw-medium <?= $row['isAvailable'] ? 'text-success' : 'text-muted'; ?>">
                                        <?= $row['isAvailable'] ? 'Active' : 'Hidden'; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn-action btn-view" title="View" onclick='viewDetails(<?= json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="editProduct.php?id=<?= $row['id']; ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn-action btn-delete" title="Delete" onclick="confirmDelete(<?= $row['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .modal-content { border: none; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .modal-header { border-bottom: 1px solid #f1f5f9; padding: 1.5rem; }
    .modal-title { font-weight: 700; color: var(--sc-dark); }
    .detail-label { font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; display: block; margin-bottom: 2px; }
    .detail-value { font-weight: 600; color: var(--text-main); font-size: 1rem; }
</style>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Overview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <img id="modalImage" class="rounded-4 w-100 mb-4 shadow-sm" style="height: 200px; object-fit: cover;" src="" alt="">
                <div class="row g-3">
                    <div class="col-6">
                        <span class="detail-label">Category</span>
                        <span class="detail-value" id="modalCategory"></span>
                    </div>
                    <div class="col-6">
                        <span class="detail-label">Price</span>
                        <span class="detail-value text-success">$<span id="modalPrice"></span></span>
                    </div>
                    <div class="col-12">
                        <span class="detail-label">Description</span>
                        <p class="text-muted small" id="modalDescription"></p>
                    </div>
                    <div class="col-6">
                        <span class="detail-label">Stock Status</span>
                        <span id="modalStock" class="fw-bold"></span> <span id="modalUnit" class="text-muted"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Dismiss</button>
            </div>
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
        "ordering": true,
        "language": {
            "search": "",
            "searchPlaceholder": "Filter products..."
        },
        "columnDefs": [{ "orderable": false, "targets": [0,5] }]
    });
    // Style DataTables Search input
    $('.dataTables_filter input').addClass('form-control shadow-sm border-0 bg-light px-3 py-2 rounded-3 mb-3');
});

function viewDetails(product) {
    document.getElementById('modalImage').src = product.imagePath && product.imagePath.length ? product.imagePath : 'assets/placeholder.png';
    document.getElementById('modalCategory').textContent = product.categoryName;
    document.getElementById('modalDescription').textContent = product.description;
    document.getElementById('modalPrice').textContent = parseFloat(product.price).toFixed(2);
    document.getElementById('modalUnit').textContent = product.unitOfSale;
    document.getElementById('modalStock').textContent = product.stockQuantity;
    
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
            showToast("Product removed from inventory.", true);
        } else {
            showToast("Error: " + data, false);
        }
    })
    .catch(err => showToast("Error connecting to server.", false));
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