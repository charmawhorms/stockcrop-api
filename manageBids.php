<?php
    include 'session.php';
    include 'config.php';

    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit();
    }

    $uID = $_SESSION['id'];

    // Fetch Farmer ID
    $fQuery = $conn->prepare("SELECT id FROM farmers WHERE userId = ?");
    $fQuery->bind_param("i", $uID);
    $fQuery->execute();
    $fResult = $fQuery->get_result();
    $fData = $fResult->fetch_assoc();

    if (!$fData) {
        die("Error: No farmer profile linked to this account.");
    }
    $farmerID = $fData['id'];

    // Fetch Bids
    $bidQuery = "
        SELECT 
            b.id AS finalBidId,
            b.quantity AS finalQty, 
            b.bidAmount AS finalAmount,
            b.counterAmount,
            b.bidStatus AS finalStatus,
            b.bidTime AS finalTime,
            b.expiresAt,
            p.productName AS finalProdName,
            p.price AS originalPrice,
            u.email AS finalEmail,
            c.firstName,
            c.lastName
        FROM bids b
        JOIN products p ON b.productId = p.id
        JOIN users u ON b.userId = u.id
        LEFT JOIN customers c ON u.id = c.userId
        WHERE p.farmerId = ?
        ORDER BY b.bidTime DESC";

    $stmt = $conn->prepare($bidQuery);
    $stmt->bind_param("i", $farmerID);
    $stmt->execute();
    $finalResult = $stmt->get_result();
    $totalBids = $finalResult->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bids| StockCrop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --primary-green: #028037;
            --soft-green: #eef7f1;
            --dark-green: #014d21;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .content {
            margin-left: 250px;
            padding: 100px 3rem 3rem 3rem; 
            transition: all 0.3s;
        }

        .page-header { margin-bottom: 2.5rem; }
        .page-header h2 { font-weight: 700; color: var(--dark-green); margin-bottom: 0.5rem; }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.2rem;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .stat-icon {
            background: var(--soft-green);
            color: var(--primary-green);
            padding: 0.6rem;
            border-radius: 8px;
        }

        .main-card {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .table thead th {
            background: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            padding: 1.2rem 1.5rem;
            border: none;
        }

        .table tbody td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }

        .badge { 
            padding: 0.5rem 0.75rem; 
            border-radius: 6px; 
            font-weight: 600; 
            font-size: 0.75rem; 
        }

        .badge-pending { 
            background-color: #fef3c7; 
            color: #92400e; 
        }

        .badge-accepted { 
            background-color: #dcfce7; 
            color: #166534; 
        }

        .badge-rejected { 
            background-color: #fee2e2; 
            color: #991b1b; 
        }

        .badge-countered { 
            background-color: #e0f2fe; 
            color: #075985; 
        }

        .btn-action { 
            padding: 0.5rem 0.9rem; 
            font-size: 0.85rem; 
            font-weight: 600; 
            border-radius: 8px; 
            transition: 0.2s; 
            border: 1px solid transparent; 
        }

        .btn-accept { 
            background-color: var(--primary-green);
            color: white; 
        }

        .btn-accept:hover { 
            background-color: var(--dark-green); 
        }

        .btn-outline-custom { 
            border: 1px solid #e2e8f0; 
            color: #64748b; 
            background: white; 
        }

        .btn-outline-custom:hover { 
            border-color: #94a3b8; 
            color: var(--text-dark); 
        }
        
        .btn-counter { 
            background-color: #fff7ed; 
            color: #9a3412; 
            border: 1px solid #fdba74; 
        }

        .btn-counter:hover { 
            background-color: #ffedd5; 
        }

        .diff-tag {
            font-size: 0.75rem; 
            padding: 2px 6px; 
            border-radius: 4px; 
            background: #fff1f2; 
            color: #be123c; 
        }

        @media(max-width:992px)
        { 
            .content { 
                margin-left: 0; 
                padding-top: 110px; 
            } 
        }

        /* Container for the buttons */
        .action-button-group {
            display: inline-block;
            min-width: 150px;
        }

        .btn-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid transparent;
            background-color: #fff;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .btn-circle span {
            font-size: 22px;
        }

        .btn-circle-accept {
            color: #10b981; 
            border-color: #d1fae5;
        }
        .btn-circle-accept:hover {
            background-color: #10b981;
            color: #fff;
            transform: scale(1.15);
        }

        .btn-circle-counter {
            color: #f59e0b; 
            border-color: #fef3c7;
        }
        .btn-circle-counter:hover {
            background-color: #f59e0b;
            color: #fff;
            transform: scale(1.15);
        }

        .btn-circle-reject {
            color: #ef4444; 
            border-color: #fee2e2;
        }
        .btn-circle-reject:hover {
            background-color: #ef4444;
            color: #fff;
            transform: scale(1.15);
        }

        /* Tooltip text fix for active state */
        .btn-circle:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>

    <?php include 'sidePanel.php'; ?>

    <div class="content">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2>Negotiations & Bids</h2>
                <p>Compare customer offers against your asking price and decide your next move.</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><span class="material-icons-outlined">handshake</span></div>
                <div>
                    <div class="small text-muted">Active Offers</div>
                    <div class="h5 mb-0 fw-bold"><?= $totalBids ?></div>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Asking Price</th>
                            <th>Customer Offer</th>
                            <th>Status</th>
                            <th>Received</th>
                            <th class="text-end">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($finalResult->num_rows > 0): ?>
                        <?php while ($row = $finalResult->fetch_assoc()): ?>
                        <tr>
                            <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['finalProdName']) ?></span></td>
                            <td>
                                <?php 
                                    $name = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
                                    echo htmlspecialchars($name !== '' ? $name : 'Valued Customer');
                                ?>
                            </td>
                            <td class="fw-medium"><?= htmlspecialchars($row['finalQty']) ?></td>
                            <td class="text-muted">$<?= number_format($row['originalPrice'], 2) ?></td>
                            <td>
                                <div class="text-success fw-bold">$<?= number_format($row['finalAmount'], 2) ?></div>
                                <?php 
                                    $diff = $row['originalPrice'] - $row['finalAmount'];
                                    if($diff > 0): 
                                ?>
                                <span class="diff-tag">-$<?= number_format($diff, 2) ?> under</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $status = $row['finalStatus'];
                                    $badgeClass = match($status) {
                                        'Pending' => 'badge-pending',
                                        'Accepted' => 'badge-accepted',
                                        'Rejected', 'Expired' => 'badge-rejected',
                                        'Countered' => 'badge-countered',
                                        default => 'bg-secondary text-white'
                                    };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $status ?></span>
                            </td>
                            <td class="text-muted small"><?= date("M d, Y", strtotime($row['finalTime'])) ?></td>
                            <td class="text-end">
                                <?php if ($status === 'Pending'): ?>
                                <div class="action-button-group">
                                    <form method="POST" action="handleBidAction.php" class="d-flex gap-2 justify-content-end">
                                        <input type="hidden" name="bidId" value="<?= $row['finalBidId'] ?>">
                                        
                                        <button name="action" value="accept" class="btn-circle btn-circle-accept" title="Accept Offer">
                                            <span class="material-icons-outlined">check_circle</span>
                                        </button>
                                        
                                        <!-- Counter Button triggers modal -->
                                        <!--<button type="button" class="btn-circle btn-circle-counter" data-bs-toggle="modal" data-bs-target="#counterModal<?= $row['finalBidId'] ?>" title="Counter Offer">
                                            <span class="material-icons-outlined">payments</span>
                                        </button>-->

                                        <button class="btn-circle btn-circle-counter" data-bs-toggle="modal" data-bs-target="#counterModal<?= $row['finalBidId'] ?>" title="Counter Offer">
                                            <span class="material-icons-outlined">payments</span>
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="counterModal<?= $row['finalBidId'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form method="POST" action="handleBidAction.php">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                <h5 class="modal-title">Counter Bid</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                <input type="hidden" name="bidId" value="<?= $row['finalBidId'] ?>">
                                                <label>Counter Amount ($)</label>
                                                <input type="number" name="counterAmount" step="0.01" class="form-control" required>
                                                </div>
                                                <div class="modal-footer">
                                                <button type="submit" name="action" value="counter" class="btn btn-warning">Submit Counter</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                </div>
                                            </div>
                                            </form>
                                        </div>
                                        </div>
                                        
                                        <button name="action" value="reject" class="btn-circle btn-circle-reject" title="Reject Offer">
                                            <span class="material-icons-outlined">cancel</span>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                    <?php if ($status === 'Countered'): ?>
                                        <span class="badge bg-info text-white">Waiting on Customer</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Bid Closed</span>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    No current bids to display.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</html>