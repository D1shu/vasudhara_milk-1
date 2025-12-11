<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$userId = Auth::getUserId();
$userName = $_SESSION['user_name'];

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_rate'])) {
        $ratePerPacket = floatval($_POST['rate_per_packet']);
        $effectiveFrom = $_POST['effective_from_date'];

        if ($ratePerPacket <= 0) {
            $message = 'Rate per packet must be greater than 0';
            $messageType = 'danger';
        } elseif (empty($effectiveFrom)) {
            $message = 'Effective from date is required';
            $messageType = 'danger';
        } else {
            $db = getDB();

            // First, set all existing active rates to inactive
            $db->query("UPDATE rates SET status = 'inactive' WHERE status = 'active'");

            // Insert new rate
            $stmt = $db->prepare("INSERT INTO rates (rate_per_packet, effective_from_date, status, created_by) VALUES (?, ?, 'active', ?)");
            $stmt->bind_param("dsi", $ratePerPacket, $effectiveFrom, $userId);

            if ($stmt->execute()) {
                $message = 'Rate updated successfully!';
                $messageType = 'success';

                // Log activity
                logActivity($userId, 'UPDATE_RATE', 'rates', $stmt->insert_id, null, [
                    'rate_per_packet' => $ratePerPacket,
                    'effective_from_date' => $effectiveFrom
                ]);
            } else {
                $message = 'Error updating rate: ' . $db->error;
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// Get current active rate
$currentRate = getDB()->query("SELECT * FROM rates WHERE status = 'active' ORDER BY effective_from_date DESC LIMIT 1");
$currentRateData = $currentRate->fetch_assoc();

// Get rate history
$rateHistory = getDB()->query("SELECT r.*, u.name as created_by_name FROM rates r LEFT JOIN users u ON r.created_by = u.id ORDER BY r.effective_from_date DESC LIMIT 10");

$pageTitle = "Rate Management";
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
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 100;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu .menu-section {
            padding: 15px 20px 5px;
            color: #a0aec0;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--primary-color), rgba(102, 126, 234, 0.1));
            color: white;
            border-left: 3px solid var(--primary-color);
            padding-left: 25px;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .top-navbar {
            background: white;
            padding: 25px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .card-custom {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(102, 126, 234, 0.1);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .card-custom:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f7fafc, #eef2f7);
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
            padding: 20px 25px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #2d3748;
        }

        .current-rate-card {
            background: linear-gradient(135deg, var(--info-color), #3182ce);
            color: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 25px;
        }

        .current-rate-card .rate-amount {
            font-size: 48px;
            font-weight: bold;
            margin: 15px 0;
        }

        .current-rate-card .rate-label {
            font-size: 16px;
            opacity: 0.9;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .table-custom {
            margin-bottom: 0;
            font-size: 14px;
        }

        .table-custom thead {
            background: linear-gradient(135deg, #f7fafc, #eef2f7);
        }

        .table-custom th {
            font-weight: 600;
            color: #2d3748;
            border: 1px solid rgba(102, 126, 234, 0.1);
            padding: 15px;
            font-size: 13px;
            text-transform: uppercase;
        }

        .table-custom td {
            padding: 12px 15px;
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .table-custom tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }

        .badge-active {
            background: var(--success-color);
            color: white;
        }

        .badge-inactive {
            background: var(--danger-color);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <h5 class="mb-0">Admin Panel</h5>
            <small>Vasudhara Milk Distribution</small>
        </div>

        <div class="sidebar-menu">
            <div class="menu-section">Dashboard</div>
            <a href="dashboard.php">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>

            <div class="menu-section">Orders</div>
            <a href="orders.php">
                <i class="fas fa-clipboard-list"></i> All Orders
            </a>
            <a href="orders.php?status=pending">
                <i class="fas fa-clock"></i> Pending Approvals
            </a>

            <div class="menu-section">Master Data</div>
            <a href="anganwadi.php">
                <i class="fas fa-building"></i> Anganwadi/Schools
            </a>
            <a href="districts.php">
                <i class="fas fa-map-marked-alt"></i> Districts
            </a>
            <a href="talukas.php">
                <i class="fas fa-map-marker-alt"></i> Talukas
            </a>
            <a href="villages.php">
                <i class="fas fa-home"></i> Villages
            </a>
            <a href="routes.php">
                <i class="fas fa-route"></i> Routes
            </a>
            <a href="users.php">
                <i class="fas fa-users"></i> Users
            </a>

            <div class="menu-section">Rates & Reports</div>
            <a href="rate_management.php" class="active">
                <i class="fas fa-tags"></i> Rate Management
            </a>
            <a href="reports.php">
                <i class="fas fa-file-alt"></i> Reports
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h4 class="mb-0">Rate Management</h4>
                <small class="text-muted">Set and manage milk packet rates</small>
            </div>
            <div class="user-info">
                <div>
                    <strong><?php echo htmlspecialchars($userName); ?></strong><br>
                    <small class="text-muted">Administrator</small>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Current Rate Display -->
            <?php if ($currentRateData): ?>
            <div class="current-rate-card">
                <i class="fas fa-tags fa-3x mb-3"></i>
                <div class="rate-amount">₹<?php echo number_format($currentRateData['rate_per_packet'], 2); ?></div>
                <div class="rate-label">Current Rate per Packet</div>
                <small class="d-block mt-2">Effective from: <?php echo formatDate($currentRateData['effective_from_date']); ?></small>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Update Rate Form -->
                <div class="col-md-6">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class="fas fa-edit"></i> Update Rate</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="rate_per_packet" class="form-label">Rate per Packet (₹)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="rate_per_packet" name="rate_per_packet"
                                           value="<?php echo $currentRateData ? $currentRateData['rate_per_packet'] : ''; ?>" required>
                                    <div class="form-text">Enter the rate per milk packet in rupees</div>
                                </div>

                                <div class="mb-3">
                                    <label for="effective_from_date" class="form-label">Effective From Date</label>
                                    <input type="date" class="form-control" id="effective_from_date" name="effective_from_date"
                                           value="<?php echo $currentRateData ? $currentRateData['effective_from_date'] : date('Y-m-d'); ?>" required>
                                    <div class="form-text">Date when this rate becomes effective</div>
                                </div>

                                <button type="submit" name="add_rate" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Rate
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Rate History -->
                <div class="col-md-6">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class="fas fa-history"></i> Rate History</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Rate (₹)</th>
                                            <th>Effective Date</th>
                                            <th>Status</th>
                                            <th>Updated By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($rate = $rateHistory->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?php echo number_format($rate['rate_per_packet'], 2); ?></strong></td>
                                                <td><?php echo formatDate($rate['effective_from_date']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $rate['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                        <?php echo ucfirst($rate['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($rate['created_by_name'] ?? 'System'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
