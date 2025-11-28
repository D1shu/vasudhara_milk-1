<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'] ?? 'user';

// Safe anganwadi info check
$anganwadiId = $_SESSION['anganwadi_id'] ?? null;
$anganwadiName = $_SESSION['anganwadi_name'] ?? $_SESSION['user_name'];

// Get dashboard statistics
$stats = getDashboardStats($userId, $userRole);

// Get recent orders
$db = getDB();
$stmt = $db->prepare("
    SELECT wo.*, a.name as anganwadi_name, a.aw_code
    FROM weekly_orders wo
    LEFT JOIN anganwadi a ON wo.anganwadi_id = a.id
    WHERE wo.user_id = ?
    ORDER BY wo.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$recentOrders = [];
while ($row = $result->fetch_assoc()) {
    $recentOrders[] = $row;
}
$stmt->close();

// Get notifications
$unreadCount = Auth::getUnreadNotificationsCount($userId);

$pageTitle = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
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
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.1);
            color: white;
            text-align: center;
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 0;
        }
        
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card p {
            color: #718096;
            margin: 0;
        }
        
        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header-custom {
            background: transparent;
            border-bottom: 2px solid #e2e8f0;
            padding: 20px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-dispatched {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .table-custom {
            margin-bottom: 0;
        }
        
        .table-custom thead {
            background: #f7fafc;
        }
        
        .table-custom th {
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
            padding: 15px;
        }
        
        .table-custom td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        
        .notification-badge .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            padding: 4px 6px;
            border-radius: 10px;
            background: #f56565;
            color: white;
            font-size: 10px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content-area {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-glass-whiskey fa-2x mb-2"></i>
            <h4>Vasudhara Milk</h4>
            <small>Distribution System</small>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="submit-order.php">
                <i class="fas fa-plus-circle"></i> Submit Order
            </a>
            <a href="order-history.php">
                <i class="fas fa-history"></i> Order History
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="notifications.php" class="notification-badge">
                <i class="fas fa-bell"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h5 class="mb-0">Welcome, <?php echo htmlspecialchars($userName); ?>!</h5>
                <small class="text-muted"><?php echo htmlspecialchars($anganwadiName); ?></small>
            </div>
            <div class="user-info">
                <div class="notification-badge">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f6d365, #fda085);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #48bb78, #38a169);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3><?php echo $stats['approved_orders']; ?></h3>
                        <p>Approved Orders</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4299e1, #3182ce);">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h3><?php echo $stats['dispatched_orders']; ?></h3>
                        <p>Dispatched Orders</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card-custom">
                        <div class="card-body p-4">
                            <h5 class="mb-3"><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                            <div class="d-flex gap-3 flex-wrap">
                                <a href="submit-order.php" class="btn btn-primary-custom">
                                    <i class="fas fa-plus"></i> Submit New Order
                                </a>
                                <a href="order-history.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list"></i> View All Orders
                                </a>
                                <a href="profile.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-user-edit"></i> Update Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <i class="fas fa-history"></i> Recent Orders
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Week Period</th>
                                            <th>Total Quantity</th>
                                            <th>Total Bags</th>
                                            <th>Status</th>
                                            <th>Submitted On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentOrders)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No orders found. Submit your first order!</p>
                                                    <a href="submit-order.php" class="btn btn-primary-custom btn-sm">
                                                        <i class="fas fa-plus"></i> Submit Order
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td><strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                                    <td>
                                                        <?php echo formatDate($order['week_start_date']); ?> to<br>
                                                        <?php echo formatDate($order['week_end_date']); ?>
                                                    </td>
                                                    <td><?php echo number_format($order['total_qty'], 2); ?> L</td>
                                                    <td><?php echo $order['total_bags']; ?> bags</td>
                                                    <td>
                                                        <span class="badge-status badge-<?php echo $order['status']; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDateTime($order['created_at']); ?></td>
                                                    <td>
                                                        <a href="view-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if (!empty($recentOrders)): ?>
                            <div class="card-body text-center">
                                <a href="order-history.php" class="btn btn-outline-primary">
                                    View All Orders <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</body>
</html>