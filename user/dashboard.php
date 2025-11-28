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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

    <!-- Fonts & icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>

    <style>
        :root{
            --primary-50: #eef2ff;
            --primary-100: #e0e7ff;
            --primary-500: #667eea;
            --primary-600: #5b65d9;
            --secondary-500: #764ba2;
            --muted: #64748b;
            --card-bg: #ffffff;
            --page-bg: #f8fafc;
            --radius-md: 12px;
            --gap: 1rem;
            --max-width: 1200px;
        }

        /* Base */
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--page-bg);
            color: #0f172a;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
            font-size: 15px;
            line-height: 1.45;
        }

        /* Layout */
        .app {
            display: flex;
            min-height: 100vh;
            align-items: stretch;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
            color: #fff;
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1100;
            transition: transform .22s ease-in-out;
        }

        .sidebar.collapsed {
            transform: translateX(-260px);
        }

        .sidebar .brand {
            text-align: center;
            padding: 18px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar .brand h4 {
            margin: 6px 0 0;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .sidebar .brand small { opacity: .9; font-weight: 500; }

        .sidebar .nav {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 10px;
        }

        .sidebar .nav a {
            display: flex;
            gap: 12px;
            align-items: center;
            color: rgba(255,255,255,0.95);
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 10px;
            font-weight: 600;
            transition: all .16s ease;
        }

        .sidebar .nav a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar .nav a:hover { transform: translateX(4px); background: rgba(255,255,255,0.06); }
        .sidebar .nav a.active { background: rgba(0,0,0,0.12); border-left: 4px solid rgba(255,255,255,0.14); }

        .sidebar .badge {
            background: rgba(255,255,255,0.12);
            padding: 4px 8px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
        }

        /* Main content */
        .main {
            margin-left: 250px;
            padding: 0;
            width: calc(100% - 250px);
            transition: margin-left .22s ease-in-out, width .22s ease-in-out;
        }

        .main.full {
            margin-left: 0;
            width: 100%;
        }

        .topbar {
            background: #fff;
            padding: 14px 20px;
            box-shadow: 0 1px 4px rgba(15,23,42,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar .left { display:flex; align-items:center; gap:12px; }
        .topbar h5 { margin:0; font-weight:600; font-size:16px; }
        .topbar small { color: var(--muted); font-weight:500; }

        .topbar .user-info { display:flex; align-items:center; gap:12px; }

        .avatar {
            width:44px; height:44px; border-radius:12px;
            background: linear-gradient(135deg,var(--primary-500),var(--secondary-500));
            display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;
            box-shadow: 0 4px 12px rgba(102,126,234,0.12);
        }

        .notification {
            position: relative;
            font-size:18px;
            color:var(--muted);
        }

        .notification .badge {
            position: absolute;
            top: -8px; right: -10px;
            background: #ef4444; color:#fff; padding:4px 7px; border-radius:999px;
            font-size:12px; font-weight:700;
        }

        /* Content area */
        .content-area {
            padding: 28px;
            max-width: var(--max-width);
            margin: 0 auto;
        }

        /* Stat cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4,1fr);
            gap: var(--gap);
        }

        .stat {
            background: var(--card-bg);
            padding: 20px;
            border-radius: var(--radius-md);
            box-shadow: 0 6px 18px rgba(15,23,42,0.06);
            display:flex;
            gap:14px;
            align-items:center;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .stat:hover { transform: translateY(-6px); box-shadow: 0 12px 32px rgba(15,23,42,0.08); }

        .stat .icon {
            width:64px; height:64px; border-radius:12px;
            display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px;
        }
        .stat h3 { margin:0; font-size:24px; font-weight:700; }
        .stat p { margin:0; color: var(--muted); font-weight:600; font-size:13px; }

        /* Cards */
        .card-custom {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            box-shadow: 0 6px 18px rgba(15,23,42,0.06);
            overflow: hidden;
            margin-bottom: 18px;
        }

        .card-custom .card-header {
            padding: 16px 20px;
            background: transparent;
            border-bottom: 1px solid #eef2ff;
            font-weight:700;
            font-size:15px;
        }

        .card-custom .card-body {
            padding: 18px 20px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg,var(--primary-500),var(--secondary-500));
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(102,126,234,0.14);
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .btn-primary-custom:hover { transform: translateY(-3px); }

        /* Table */
        .table-custom {
            border-collapse: collapse;
            width: 100%;
            font-size: 14px;
        }
        .table-custom thead th {
            text-align:left; padding:12px 14px; color: #334155; font-weight:700; font-size:13px;
            border-bottom: 2px solid #eef2ff;
        }
        .table-custom tbody td { padding:13px 14px; vertical-align: middle; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
        .table-empty {
            padding: 36px; text-align:center; color: var(--muted);
        }

        /* Status badges */
        .badge-status {
            display:inline-block; padding:6px 10px; border-radius:999px; font-weight:700; font-size:12px;
        }
        .badge-pending { background: #fef3c7; color:#92400e; }
        .badge-approved { background: #d1fae5; color:#065f46; }
        .badge-dispatched { background: #dbeafe; color:#1e40af; }
        .badge-rejected { background: #fee2e2; color:#991b1b; }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .sidebar { transform: translateX(-0); position: fixed; }
        }
        @media (max-width: 720px) {
            .stats-grid { grid-template-columns: 1fr; }
            .content-area { padding: 16px; }
            .sidebar { transform: translateX(-260px); }
            .main { margin-left: 0; width: 100%; }
        }

        /* small helpers */
        .text-muted { color: var(--muted) !important; }
        .gap-3 { gap: 1rem; }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar" id="appSidebar" role="navigation" aria-label="Main navigation">
            <div class="brand">
                <i class="fas fa-glass-whiskey fa-2x"></i>
                <h4>Vasudhara Milk</h4>
                <small>Distribution System</small>
            </div>

            <nav class="nav" aria-label="Sidebar links">
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="submit-order.php"><i class="fas fa-plus-circle"></i> Submit Order</a>
                <a href="order-history.php"><i class="fas fa-history"></i> Order History</a>
                <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>

                <a href="notifications.php" style="display:flex;align-items:center;justify-content:space-between;">
                    <span><i class="fas fa-bell"></i> Notifications</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>

                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="main" id="mainContent">
            <!-- Topbar -->
            <header class="topbar">
                <div class="left">
                    <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h5 class="mb-0">Welcome, <?php echo htmlspecialchars($userName); ?>!</h5>
                        <small class="text-muted"><?php echo htmlspecialchars($anganwadiName); ?></small>
                    </div>
                </div>

                <div class="user-info">
                    <div class="notification" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="avatar" title="<?php echo htmlspecialchars($userName); ?>">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Content area -->
            <section class="content-area">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat">
                        <div class="icon" style="background: linear-gradient(135deg,#667eea,#764ba2);">
                            <i class="fas fa-clipboard-list fa-lg"></i>
                        </div>
                        <div>
                            <h3><?php echo $stats['total_orders']; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>

                    <div class="stat">
                        <div class="icon" style="background: linear-gradient(135deg,#f6d365,#fda085);">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                        <div>
                            <h3><?php echo $stats['pending_orders']; ?></h3>
                            <p>Pending Orders</p>
                        </div>
                    </div>

                    <div class="stat">
                        <div class="icon" style="background: linear-gradient(135deg,#48bb78,#38a169);">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <div>
                            <h3><?php echo $stats['approved_orders']; ?></h3>
                            <p>Approved Orders</p>
                        </div>
                    </div>

                    <div class="stat">
                        <div class="icon" style="background: linear-gradient(135deg,#4299e1,#3182ce);">
                            <i class="fas fa-truck fa-lg"></i>
                        </div>
                        <div>
                            <h3><?php echo $stats['dispatched_orders']; ?></h3>
                            <p>Dispatched Orders</p>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="card-custom mt-4">
                    <div class="card-header">
                        <i class="fas fa-bolt text-warning"></i> Quick Actions
                    </div>
                    <div class="card-body d-flex gap-3 flex-wrap align-items-center">
                        <a href="submit-order.php" class="btn btn-primary-custom">
                            <i class="fas fa-plus me-2"></i> Submit New Order
                        </a>
                        <a href="order-history.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i> View All Orders
                        </a>
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-edit me-2"></i> Update Profile
                        </a>
                    </div>
                </div>

                <!-- Recent orders -->
                <div class="card-custom mt-4">
                    <div class="card-header">
                        <i class="fas fa-history"></i> Recent Orders
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table-custom" role="table" aria-label="Recent orders">
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
                                            <td colspan="7" class="table-empty">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                <div style="margin-top:12px;">
                                                    <div class="text-muted">No orders found. Submit your first order!</div>
                                                    <div style="margin-top:12px;">
                                                        <a href="submit-order.php" class="btn btn-primary-custom btn-sm">
                                                            <i class="fas fa-plus me-1"></i> Submit Order
                                                        </a>
                                                    </div>
                                                </div>
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

                        <?php if (!empty($recentOrders)): ?>
                            <div class="card-body text-center" style="border-top: 1px solid #f1f5f9;">
                                <a href="order-history.php" class="btn btn-outline-primary">
                                    View All Orders <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            const sidebar = document.getElementById('appSidebar');
            const main = document.getElementById('mainContent');
            const toggle = document.getElementById('sidebarToggle');

            // Toggle sidebar
            toggle.addEventListener('click', function(){
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('full');
            });

            // Close sidebar on small screens when clicking outside
            document.addEventListener('click', function(e){
                const isClickInside = sidebar.contains(e.target) || toggle.contains(e.target);
                if (window.innerWidth <= 720 && !isClickInside) {
                    sidebar.classList.add('collapsed');
                    main.classList.add('full');
                }
            });

            // Start collapsed on small screens
            function handleResize() {
                if (window.innerWidth <= 720) {
                    sidebar.classList.add('collapsed');
                    main.classList.add('full');
                } else {
                    sidebar.classList.remove('collapsed');
                    main.classList.remove('full');
                }
            }
            window.addEventListener('resize', handleResize);
            handleResize();
        })();
    </script>
</body>
</html>
