<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$userId = Auth::getUserId();
$userName = $_SESSION['user_name'];

// Get dashboard statistics
$stats = getDashboardStats(null, 'admin');

// Get chart data
$weeklyTrends = getWeeklyOrderTrends();
$districtData = getDistrictWiseDistribution();
$statusData = getStatusDistribution();

// Get recent pending orders
$pendingOrders = getOrdersByStatus('pending');

// Get current rate
$currentRate = getDB()->query("SELECT rate_per_packet, effective_from_date FROM rates WHERE status = 'active' ORDER BY effective_from_date DESC LIMIT 1");
$currentRateData = $currentRate->fetch_assoc();

$pageTitle = "Admin Dashboard";
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
        
        /* Scrollbar styling for sidebar */
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
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .sidebar-header .badge {
            background: var(--danger-color);
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 10px;
            margin-left: 5px;
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
        
        .sidebar-menu a:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), transparent);
            color: white;
            padding-left: 25px;
            border-left: 3px  solid var(--primary-color);
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white !important;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-left: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .logout-btn:hover {
            background-color: #e53e3e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(245, 101, 101, 0.3);
            text-decoration: none;
            color: white;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card.primary::before {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .stat-card.success::before {
            background: var(--success-color);
        }
        
        .stat-card.warning::before {
            background: var(--warning-color);
        }
        
        .stat-card.info::before {
            background: var(--info-color);
        }
        
        .stat-card.danger::before {
            background: var(--danger-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
            color: #2d3748;
        }
        
        .stat-card p {
            color: #718096;
            margin: 0;
            font-size: 14px;
        }
        
        .stat-trend {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .stat-trend.up {
            color: var(--success-color);
        }
        
        .stat-trend.down {
            color: var(--danger-color);
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
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 20px;
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
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 11px;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .quick-action-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .quick-action-card i {
            font-size: 40px;
            margin-bottom: 15px;
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
            <a href="dashboard.php" class="active">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            
            <div class="menu-section">Orders</div>
            <a href="orders.php">
                <i class="fas fa-clipboard-list"></i> All Orders
                <?php if ($stats['pending_orders'] > 0): ?>
                    <span class="badge bg-danger ms-auto"><?php echo $stats['pending_orders']; ?></span>
                <?php endif; ?>
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
            
            <div class="menu-section">Reports</div>
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
                <h4 class="mb-0">Dashboard Overview</h4>
                <small class="text-muted">Welcome back, <?php echo htmlspecialchars($userName); ?>!</small>
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
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="orders.php" class="stat-card primary" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Orders</p>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="orders.php?status=pending" class="stat-card warning" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: var(--warning-color);">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                        <p>Pending Approvals</p>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="orders.php?status=approved" class="stat-card success" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: var(--success-color);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $stats['approved_this_week']; ?></h3>
                        <p>Approved This Week</p>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="anganwadi.php" class="stat-card info" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: var(--info-color);">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3><?php echo $stats['total_anganwadis']; ?></h3>
                        <p>Anganwadis</p>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="routes.php" class="stat-card danger" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: var(--danger-color);">
                            <i class="fas fa-route"></i>
                        </div>
                        <h3><?php echo $stats['total_routes']; ?></h3>
                        <p>Routes</p>
                    </a>
                </div>
                
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="users.php" class="stat-card primary" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: #805ad5;">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Active Users</p>
                    </a>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="rate_management.php" class="stat-card info" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: var(--info-color);">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3>₹<?php echo number_format($currentRateData['rate_per_packet'], 2); ?></h3>
                        <p>Current Rate</p>
                        <small class="text-muted">Effective: <?php echo formatDate($currentRateData['effective_from_date']); ?></small>
                    </a>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="rate_management.php" class="stat-card primary" style="text-decoration: none; color: inherit; display: block; cursor: pointer;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3>Manage</h3>
                        <p>Rates</p>
                        <small class="text-muted">Update packet rates</small>
                    </a>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <a href="orders.php?status=pending" class="quick-action-card">
                        <i class="fas fa-tasks"></i>
                        <h5>Approve Orders</h5>
                        <p class="mb-0"><?php echo $stats['pending_orders']; ?> pending</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="anganwadi.php" class="quick-action-card" style="background: linear-gradient(135deg, #48bb78, #38a169);">
                        <i class="fas fa-plus-circle"></i>
                        <h5>Add Anganwadi</h5>
                        <p class="mb-0">Register new center</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="reports.php" class="quick-action-card" style="background: linear-gradient(135deg, #ed8936, #dd6b20);">
                        <i class="fas fa-file-pdf"></i>
                        <h5>Generate Reports</h5>
                        <p class="mb-0">PDF & Excel</p>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="users.php" class="quick-action-card" style="background: linear-gradient(135deg, #4299e1, #3182ce);">
                        <i class="fas fa-user-plus"></i>
                        <h5>Add User</h5>
                        <p class="mb-0">Create new account</p>
                    </a>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class="fas fa-chart-line"></i> Weekly Order Trends</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="weeklyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class="fas fa-chart-pie"></i> Order Status</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Orders Table -->
            <?php if (!empty($pendingOrders)): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <span><i class="fas fa-clock"></i> Pending Approvals (<?php echo count($pendingOrders); ?>)</span>
                            <a href="orders.php?status=pending" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Anganwadi</th>
                                            <th>Week Period</th>
                                            <th>Quantity</th>
                                            <th>Bags</th>
                                            <th>Contact</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($pendingOrders, 0, 5) as $order): ?>
                                            <tr>
                                                <td><strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['anganwadi_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo $order['aw_code']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo formatDate($order['week_start_date']); ?><br>
                                                    <small class="text-muted">to <?php echo formatDate($order['week_end_date']); ?></small>
                                                </td>
                                                <td><?php echo number_format($order['total_qty'], 2); ?> L</td>
                                                <td><?php echo $order['total_bags']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['user_name']); ?><br>
                                                    <small class="text-muted"><?php echo $order['mobile']; ?></small>
                                                </td>
                                                <td>
                                                    <a href="approve-order.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-success btn-action">
                                                        <i class="fas fa-check"></i> Review
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Weekly Trend Chart
        const weeklyCtx = document.getElementById('weeklyTrendChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weeklyTrends['labels']); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($weeklyTrends['orders']); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Quantity (L)',
                    data: <?php echo json_encode($weeklyTrends['quantity']); ?>,
                    borderColor: '#48bb78',
                    backgroundColor: 'rgba(72, 187, 120, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statusData['labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusData['data']); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#48bb78',
                        '#ed8936',
                        '#f56565',
                        '#4299e1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>