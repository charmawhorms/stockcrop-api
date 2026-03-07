<?php
    session_start();
    include 'config.php';
    include 'session.php';

    // Only allow ADMIN (roleId = 1)
    redirectIfNotLoggedIn();
    if ($_SESSION['roleId'] != 1) {
        header("Location: login.php");
        exit();
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: farmers.php");
        exit();
    }

    $farmerId = (int)$_GET['id'];
    $farmer = null;

    // UPDATED QUERY: Added verification_status
    $stmt = mysqli_prepare($conn, "
    SELECT 
        id AS farmerId,
        userId,
        firstName,
        lastName,
        email,
        phoneNumber,
        radaIdNumber,
        address1,
        address2,
        parish,
        farmerType,
        verification_status, 
        govtIdNumber,
        govtIdFile,
        trn,
        trnFile,
        created_at
    FROM farmers
    WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $farmerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $farmer = $row;
    } else {
        header("Location: farmers.php");
        exit();
    }
    mysqli_stmt_close($stmt);

    $fullAddress = trim(
        (empty($farmer['address1']) ? '' : $farmer['address1']) . 
        (empty($farmer['address2']) ? '' : ', ' . $farmer['address2'])
    );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?> | Admin Review</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-green: #008751; /* Jamaican Green */
            --sidebar-width: 250px;
        }

        body { background: #f4f7f6; display: flex; }

        .content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
        }

        .card-modern { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .bg-verified { background-color: #e6f6ec !important; border: 1px solid #badbcc; }
        .bg-pending { background-color: #fff3cd !important; border: 1px solid #ffeeba; }
        
        .status-pill { padding: 6px 16px; border-radius: 50px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; }
        .btn-verify { background: var(--primary-green); color: white; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
        .btn-verify:hover { background: #006b40; transform: translateY(-1px); color: white; }
        
        code { background: #f1f1f1; padding: 2px 6px; border-radius: 4px; color: #d63384; }
    </style>
</head>
<body>

<?php include 'adminSidePanel.php'; ?>

<div class="content mt-5">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <a href="farmManagement.php" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left"></i></a> 
                <?= htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?>
            </h2>
            <p class="text-muted mb-0">Reviewing Profile & Documents</p>
        </div>
        <div>
            <button class="btn btn-outline-danger me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete</button>
            <a href="editFarmer.php?id=<?= $farmer['farmerId']; ?>" class="btn btn-dark shadow-sm px-4">Edit Profile</a>
        </div>
    </div>

    <div class="card card-modern mb-4 overflow-hidden shadow-sm">
        <div class="d-flex align-items-center p-4 <?= ($farmer['verification_status'] === 'verified') ? 'bg-verified' : 'bg-pending' ?>">
            <div class="rounded-circle bg-white p-3 shadow-sm me-4">
                <span class="material-symbols-outlined <?= ($farmer['verification_status'] === 'verified') ? 'text-success' : 'text-warning' ?> fs-1">
                    <?= ($farmer['verification_status'] === 'verified') ? 'verified' : 'pending_actions' ?>
                </span>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-1 fw-bold">Verification: <?= ucfirst(htmlspecialchars($farmer['verification_status'] ?? 'Pending')); ?></h5>
                <p class="mb-0 text-muted">Farmer Type: <span class="badge bg-dark"><?= ucfirst(htmlspecialchars($farmer['farmerType'])); ?></span></p>
            </div>
            <?php if ($farmer['verification_status'] !== 'verified'): ?>
                <form action="verifyGuestFarmer.php" method="POST">
                    <input type="hidden" name="farmer_id" value="<?= $farmer['farmerId']; ?>">
                    <button type="submit" name="action" value="verify_guest" class="btn btn-verify px-4 py-2">
                        <i class="fas fa-check-double me-2"></i>Verify Guest Documents
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card card-modern p-4 h-100 bg-white">
                <h6 class="text-muted fw-bold text-uppercase mb-4 small">Primary Information</h6>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="text-muted small d-block mb-1">Phone</label>
                        <a href="tel:<?= $farmer['phoneNumber']; ?>" class="h5 fw-bold text-dark text-decoration-none"><?= htmlspecialchars($farmer['phoneNumber']); ?></a>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="text-muted small d-block mb-1">Email Address</label>
                        <a href="mailto:<?= $farmer['email']; ?>" class="h5 fw-bold text-primary text-decoration-none"><?= htmlspecialchars($farmer['email']); ?></a>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small d-block mb-1">Location (<?= htmlspecialchars($farmer['parish']); ?>)</label>
                        <p class="h5 text-dark fw-medium"><?= $fullAddress ?: 'No specific address provided'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-modern p-4 h-100 bg-light">
                <h6 class="text-muted fw-bold text-uppercase mb-4 small">System Metadata</h6>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Farmer ID</span>
                    <span class="fw-bold">#<?= $farmer['farmerId']; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Joined</span>
                    <span class="fw-bold text-dark"><?= date("M d, Y", strtotime($farmer['created_at'])); ?></span>
                </div>
                <div class="mb-0 pt-2 border-top">
                    <label class="text-muted small d-block mb-1 text-uppercase">RADA ID</label>
                    <span class="badge bg-success w-100 py-2 fs-6"><?= htmlspecialchars($farmer['radaIdNumber'] ?? 'NOT REGISTERED'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($farmer['farmerType'] === 'guest'): ?>
    <div class="card card-modern p-4 bg-white">
        <h6 class="text-muted fw-bold text-uppercase mb-4 small"><i class="fas fa-file-contract me-2"></i>Legal Documents</h6>
        <div class="row g-4">
            
            <div class="col-md-6">
                <div class="p-3 border rounded-4 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark">Gov. ID Number</span>
                        <code class="fs-6"><?= '•••• ' . substr(htmlspecialchars($farmer['govtIdNumber'] ?? '0000'), -4); ?></code>
                    </div>
                    <?php if (!empty($farmer['govtIdFile'])): ?>
                        <div class="rounded-3 overflow-hidden position-relative group" style="height: 150px;">
                            <img src="<?= htmlspecialchars($farmer['govtIdFile']); ?>" class="w-100 h-100" style="object-fit: cover; filter: brightness(0.8);">
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <button class="btn btn-sm btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#idModal">View Large</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4 border border-dashed rounded-3">No File Attached</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 border rounded-4 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark">TRN Number</span>
                        <code class="fs-6"><?= '•••• ' . substr(htmlspecialchars($farmer['trn'] ?? '0000'), -4); ?></code>
                    </div>
                    <?php if (!empty($farmer['trnFile'])): ?>
                        <div class="rounded-3 overflow-hidden position-relative" style="height: 150px;">
                            <img src="<?= htmlspecialchars($farmer['trnFile']); ?>" class="w-100 h-100" style="object-fit: cover; filter: brightness(0.8);">
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <button class="btn btn-sm btn-light fw-bold" data-bs-toggle="modal" data-bs-target="#trnModal">View Large</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4 border border-dashed rounded-3">No File Attached</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

</div>

<?php foreach (['id' => 'govtIdFile', 'trn' => 'trnFile'] as $prefix => $key): ?>
    <?php if (!empty($farmer[$key])): ?>
    <div class="modal fade" id="<?= $prefix ?>Modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-header border-0 text-white">
                    <h5 class="modal-title fw-bold"><?= strtoupper($prefix) ?> Verification Document</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 shadow-lg">
                    <img src="<?= htmlspecialchars($farmer[$key]); ?>" class="img-fluid rounded-4 w-100">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Permanently delete <strong><?= htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?></strong>? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="deleteFarmer.php?id=<?= $farmer['farmerId']; ?>" class="btn btn-danger">Delete Permanently</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>