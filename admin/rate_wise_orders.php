<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

// Get filter parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$rate = isset($_GET['rate']) && $_GET['rate'] > 0 ? floatval($_GET['rate']) : 0;

$filterApplied = false;
$orders = [];
$totalOrders = 0;
$totalQuantity = 0;
$totalAmount = 0;

if ($startDate && $endDate && $rate > 0) {
    $filterApplied = true;

    $db = getDB();

    // Query to get orders within the date range with the specified rate
    $query = "
        SELECT
            wo.id,
            wo.week_start_date,
            wo.week_end_date,
            wo.total_qty,
            wo.children_allocation,
            wo.pregnant_women_allocation,
            wo.status,
            wo.created_at,
            a.id as anganwadi_id,
            a.aw_code,
            a.name as anganwadi_name,
            a.type as anganwadi_type,
            u.name as user_name,
            u.mobile as user_mobile,
            v.name as village_name,
            t.name as taluka_name,
            d.name as district_name,
            r.rate_per_packet
        FROM weekly_orders wo
        JOIN anganwadi a ON wo.anganwadi_id = a.id
        JOIN users u ON wo.user_id = u.id
        LEFT JOIN villages v ON a.village_id = v.id
        LEFT JOIN talukas t ON v.taluka_id = t.id
        LEFT JOIN districts d ON t.district_id = d.id
        LEFT JOIN (
            SELECT r1.*
            FROM rates r1
            WHERE r1.status = 'active'
            AND r1.effective_from_date = (
                SELECT MAX(r2.effective_from_date)
                FROM rates r2
                WHERE r2.status = 'active'
                AND r2.effective_from_date <= wo.week_start_date
            )
        ) r ON 1=1
        WHERE wo.week_start_date >= ?
        AND wo.week_start_date <= ?
        AND r.rate_per_packet = ?
        AND wo.status IN ('approved', 'dispatched', 'completed')
        ORDER BY wo.week_start_date DESC, a.name ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->bind_param("ssd", $startDate, $endDate, $rate);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
        $totalOrders++;
        $totalQuantity += $row['total_qty'];
        $totalAmount += $row['total_qty'] * $rate;
    }

    $stmt->close();
}

$pageTitle = "Rate-wise Order Report";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ed8936;
            --info-color: #4299e1;
        }

        body {
            background: linear-gradient(135deg, #f7fafc 0%, #eef2f7 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            min-height: 100vh;
            max-height: 100vh;
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            position: fixed;
            width: 260px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 100;
            padding: 0;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.2);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            border-bottom: 2px solid rgba(102, 126, 234, 0.3);
        }

        .sidebar-header i {
            color: var(--primary-color);
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), transparent);
            color: white;
            padding-left: 25px;
            border-left: 3px solid var(--primary-color);
        }

        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--primary-color), rgba(102, 126, 234, 0.1));
            color: white;
            border-left: 3px solid var(--primary-color);
            padding-left: 25px;
        }

        .sidebar-menu a[href="../logout.php"]:hover {
            background: linear-gradient(90deg, rgba(245, 101, 101, 0.2), transparent) !important;
            border-left-color: var(--danger-color) !important;
        }

        .sidebar-menu a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 260px;
            padding: 0;
        }

        .top-navbar {
            background: white;
            padding: 25px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }

        .content-area {
            padding: 30px;
        }

        .card-custom {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-approved {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-dispatched {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-completed {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .table-custom thead {
            background: linear-gradient(135deg, #f7fafc, #eef2f7);
        }

        .table-custom th {
            font-weight: 600;
            color: #2d3748;
            font-size: 13px;
            padding: 15px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .table-custom td {
            padding: 15px;
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .table-custom tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .filter-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .filter-item {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
        }

        .filter-item label {
            font-weight: 600;
            color: #495057;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .filter-item .value {
            background: white;
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-weight: 500;
            font-size: 14px;
        }

        .summary-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
        }

        .summary-card h3 {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }

        .summary-card p {
            margin: 0;
            opacity: 0.9;
        }

        .alert {
            border: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-info {
            background-color: rgba(66, 153, 225, 0.1);
            color: #2c5282;
            border-left: 4px solid var(--info-color);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <h5 class="mb-0">Admin Panel</h5>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i> Dashboard
            </a>
            <a href="orders.php">
                <i class="fas fa-clipboard-list me-2"></i> Orders
            </a>
            <a href="anganwadi.php">
                <i class="fas fa-building me-2"></i> Anganwadi
            </a>
            <a href="users.php">
                <i class="fas fa-users me-2"></i> Users
            </a>
            <a href="reports.php" class="active">
                <i class="fas fa-file-alt me-2"></i> Reports
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h4 class="mb-0">Rate-wise Order Report</h4>
                <small class="text-muted">Orders for specific time period and rate</small>
            </div>
            <div>
                <a href="reports.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Reports
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if (!$filterApplied): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Please provide start date, end date, and rate to view orders.
                </div>
            <?php elseif (empty($orders)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No orders found for the selected criteria.
                </div>
            <?php else: ?>
                <!-- Filter Info -->
                <div class="filter-info">
                    <div class="filter-item">
                        <label>Time Period:</label>
                        <div class="value">
                            <?php echo date('d-m-Y', strtotime($startDate)); ?> to <?php echo date('d-m-Y', strtotime($endDate)); ?>
                        </div>
                    </div>
                    <div class="filter-item">
                        <label>Rate per Packet:</label>
                        <div class="value">₹<?php echo number_format($rate, 2); ?></div>
                    </div>
                    <div class="filter-item">
                        <label>Total Orders:</label>
                        <div class="value"><?php echo $totalOrders; ?></div>
                    </div>
                    <div class="filter-item">
                        <label>Total Quantity:</label>
                        <div class="value"><?php echo number_format($totalQuantity, 2); ?> Packets</div>
                    </div>
                    <div class="filter-item">
                        <label>Total Amount:</label>
                        <div class="value">₹<?php echo number_format($totalAmount, 2); ?></div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="summary-card">
                            <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                            <h3><?php echo $totalOrders; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <i class="fas fa-boxes fa-2x mb-2"></i>
                            <h3><?php echo number_format($totalQuantity, 2); ?></h3>
                            <p>Total Packets</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <i class="fas fa-tags fa-2x mb-2"></i>
                            <h3>₹<?php echo number_format($rate, 2); ?></h3>
                            <p>Rate per Packet</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <i class="fas fa-rupee-sign fa-2x mb-2"></i>
                            <h3>₹<?php echo number_format($totalAmount, 2); ?></h3>
                            <p>Total Amount</p>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card-custom">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Anganwadi</th>
                                        <th>Location</th>
                                        <th>Week Period</th>
                                        <th>Packets</th>
                                        <th>Children</th>
                                        <th>Pregnant</th>
                                        <th>Rate (₹)</th>
                                        <th>Amount (₹)</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['anganwadi_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo $order['aw_code']; ?> |
                                                    <?php echo ucfirst($order['anganwadi_type']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo $order['village_name']; ?>,<br>
                                                    <?php echo $order['taluka_name']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo formatDate($order['week_start_date']); ?><br>
                                                <small class="text-muted">to <?php echo formatDate($order['week_end_date']); ?></small>
                                            </td>
                                            <td><strong><?php echo number_format($order['total_qty'], 2); ?></strong></td>
                                            <td><?php echo $order['children_allocation']; ?></td>
                                            <td><?php echo $order['pregnant_women_allocation']; ?></td>
                                            <td>₹<?php echo number_format($rate, 2); ?></td>
                                            <td>₹<?php echo number_format($order['total_qty'] * $rate, 2); ?></td>
                                            <td>
                                                <span class="badge-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo formatDate($order['created_at']); ?><br>
                                                <small class="text-muted"><?php echo $order['user_name']; ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
