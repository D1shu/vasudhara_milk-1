<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details (assumes getOrderById returns new columns too)
$order = getOrderById($orderId);

if (!$order || ($order['user_id'] != $userId && !Auth::isAdmin())) {
    $_SESSION['error'] = 'Order not found or access denied';
    redirect(SITE_URL . '/user/order-history.php');
}

// unread notifications count (prevent undefined variable)
$unreadCount = 0;
$db = getDB();
if ($db) {
    $notifStmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($notifStmt) {
        $notifStmt->bind_param("i", $userId);
        $notifStmt->execute();
        $res = $notifStmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $unreadCount = (int)$row['cnt'];
        }
        $notifStmt->close();
    }
}

// Ensure expected per-day fields exist (fallback to zero)
$order = array_merge([
    'mon_qty' => 0, 'tue_qty' => 0, 'wed_qty' => 0, 'thu_qty' => 0, 'fri_qty' => 0,
    'mon_children' => 0, 'mon_pregnant' => 0, 'tue_children' => 0, 'tue_pregnant' => 0,
    'wed_children' => 0, 'wed_pregnant' => 0, 'thu_children' => 0, 'thu_pregnant' => 0,
    'fri_children' => 0, 'fri_pregnant' => 0
], $order);

$pageTitle = "Order Details";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . (defined('SITE_NAME') ? SITE_NAME : 'Vasudhara')); ?></title>

    <!-- Fonts & icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root{
            --primary-500: #667eea;
            --secondary-500: #764ba2;
            --muted: #64748b;
            --card-bg: #ffffff;
            --page-bg: #f8fafc;
            --radius-md: 12px;
            --gap: 1rem;
            --max-width: 1100px;
        }

        *{box-sizing:border-box}
        body{
            margin:0;
            font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--page-bg);
            color:#0f172a;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
            font-size:15px;
            line-height:1.45;
        }

        .app{ display:flex; min-height:100vh; align-items:stretch; }

        /* Sidebar */
        .sidebar{ width:250px; min-height:100vh; background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); color:#fff; padding:18px 12px; position:fixed; left:0; top:0; z-index:1100; transition: transform .22s ease-in-out; }
        .brand{ text-align:center; padding:12px 6px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .brand h4{ margin:8px 0 0; font-weight:700; }
        .nav-links{ margin-top:14px; display:flex; flex-direction:column; gap:8px; padding:8px; }
        .nav-links a{ display:flex; gap:12px; align-items:center; color:rgba(255,255,255,0.95); text-decoration:none; padding:10px 12px; border-radius:10px; font-weight:600; transition:all .12s ease; }
        .nav-links a i{ width:20px; text-align:center; }
        .nav-links a:hover{ transform: translateX(4px); background: rgba(255,255,255,0.06); }
        .nav-links a.active{ background: rgba(0,0,0,0.12); border-left:4px solid rgba(255,255,255,0.14); }
        .small-badge{ background: rgba(255,255,255,0.12); padding:4px 8px; border-radius:999px; font-weight:700; font-size:12px; }

        /* Main */
        .main{ margin-left:250px; padding:0; width: calc(100% - 250px); transition: margin-left .22s ease-in-out, width .22s ease-in-out; }
        .main.full{ margin-left:0; width:100%; }

        .topbar{ background:#fff; padding:14px 18px; box-shadow: 0 1px 4px rgba(15,23,42,0.06); display:flex; align-items:center; justify-content:space-between; gap:12px; position:sticky; top:0; z-index:100; }
        .topbar h4{ margin:0; font-weight:700; }

        .content-area{ padding:28px; max-width:var(--max-width); margin:0 auto; }

        .card-custom{ background:var(--card-bg); border-radius:var(--radius-md); box-shadow: 0 8px 22px rgba(15,23,42,0.06); overflow:hidden; margin-bottom:18px; }
        .card-header-custom{ background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); color:#fff; padding:18px; display:flex; justify-content:space-between; align-items:center; }

        .order-status{ padding:8px 16px; border-radius:999px; font-weight:700; font-size:14px; }
        .status-pending{ background:#fef3c7; color:#92400e; }
        .status-approved{ background:#d1fae5; color:#065f46; }
        .status-dispatched{ background:#dbeafe; color:#1e40af; }
        .status-rejected{ background:#fee2e2; color:#991b1b; }

        .info-section{ padding:20px; border-bottom:1px solid #eef2ff; }
        .section-title{ font-size:16px; font-weight:700; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; gap:10px; }
        .section-title i{ color:var(--primary-500); }

        .info-grid{ display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
        .info-item{ display:flex; flex-direction:column; }
        .info-label{ font-size:12px; color:var(--muted); text-transform:uppercase; margin-bottom:6px; font-weight:700; }
        .info-value{ font-size:15px; font-weight:700; color:#0f172a; }

        .daily-breakdown{ display:grid; grid-template-columns: repeat(5,1fr); gap:12px; }
        .day-card{ background:#fff; border-radius:10px; padding:12px; border:1px solid #eef2ff; box-shadow: 0 6px 18px rgba(15,23,42,0.04); }
        .day-name{ font-size:14px; font-weight:700; color:#4a5568; margin-bottom:10px; text-align:center; }
        .day-detail{ display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:14px; }
        .day-detail.total{ margin-top:8px; padding-top:8px; border-top:1px solid #eef2ff; font-weight:800; color:var(--primary-500); }

        .summary-box{ background: linear-gradient(135deg,#48bb78,#38a169); color: white; border-radius:12px; padding:18px; }
        .summary-item{ display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.12); }
        .summary-item:last-child{ border-bottom:none; font-size:18px; font-weight:800; padding-top:12px; }

        .btn-ghost{ background: transparent; border:1px solid rgba(255,255,255,0.12); color:#fff; }

        @media (max-width: 992px) { .daily-breakdown{ grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 720px) { .daily-breakdown { grid-template-columns: 1fr; } .sidebar{ transform: translateX(-260px); } .main{ margin-left:0; width:100%; }
            .card-header-custom{ flex-direction:column; align-items:flex-start; gap:10px; }
        }
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

            <nav class="nav-links" aria-label="Sidebar links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="submit-order.php"><i class="fas fa-plus-circle"></i> Submit Order</a>
                <a href="order-history.php" class="active"><i class="fas fa-history"></i> Order History</a>
                <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>

                <a href="notifications.php" style="display:flex;align-items:center;justify-content:space-between;">
                    <span><i class="fas fa-bell"></i> Notifications</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="small-badge"><?php echo (int)$unreadCount; ?></span>
                    <?php endif; ?>
                </a>

                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="main" id="mainContent">
            <header class="topbar">
                <div>
                    <h4>Order Details</h4>
                    <small class="text-muted">Order #<?php echo str_pad((int)$order['id'], 5, '0', STR_PAD_LEFT); ?></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="order-history.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
                    <div class="avatar" title="<?php echo htmlspecialchars($order['user_name'] ?? ''); ?>"><?php echo strtoupper(substr($order['user_name'] ?? '',0,1)); ?></div>
                </div>
            </header>

            <section class="content-area">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <div>
                                    <h5 class="mb-0">Order #<?php echo str_pad((int)$order['id'], 5, '0', STR_PAD_LEFT); ?></h5>
                                    <small>Week: <?php echo formatDate($order['week_start_date']); ?> to <?php echo formatDate($order['week_end_date']); ?></small>
                                </div>
                                <div>
                                    <span class="order-status status-<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                                </div>
                            </div>

                            <!-- Anganwadi Details -->
                            <div class="info-section">
                                <div class="section-title"><i class="fas fa-building"></i> Anganwadi / School Details</div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-label">Name</span>
                                        <span class="info-value"><?php echo htmlspecialchars($order['anganwadi_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Code</span>
                                        <span class="info-value"><?php echo htmlspecialchars($order['aw_code']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Type</span>
                                        <span class="info-value"><?php echo htmlspecialchars(ucfirst($order['anganwadi_type'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Location</span>
                                        <span class="info-value"><?php echo htmlspecialchars($order['village_name']); ?>, <?php echo htmlspecialchars($order['taluka_name']); ?>, <?php echo htmlspecialchars($order['district_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Children</span>
                                        <span class="info-value"><?php echo (int)$order['total_children']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Pregnant Women</span>
                                        <span class="info-value"><?php echo (int)$order['pregnant_women']; ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Daily Breakdown -->
                            <div class="info-section">
                                <div class="section-title"><i class="fas fa-calendar-week"></i> Daily Breakdown (Packets)</div>
                                <div class="daily-breakdown">
                                    <?php
                                    $days = [
                                        ['label'=>'Monday','key'=>'mon_qty','children_field'=>'mon_children','preg_field'=>'mon_pregnant','icon'=>'text-primary'],
                                        ['label'=>'Tuesday','key'=>'tue_qty','children_field'=>'tue_children','preg_field'=>'tue_pregnant','icon'=>'text-success'],
                                        ['label'=>'Wednesday','key'=>'wed_qty','children_field'=>'wed_children','preg_field'=>'wed_pregnant','icon'=>'text-warning'],
                                        ['label'=>'Thursday','key'=>'thu_qty','children_field'=>'thu_children','preg_field'=>'thu_pregnant','icon'=>'text-info'],
                                        ['label'=>'Friday','key'=>'fri_qty','children_field'=>'fri_children','preg_field'=>'fri_pregnant','icon'=>'text-danger'],
                                    ];

                                    foreach ($days as $d) {
                                        $day_total = (int)($order[$d['key']] ?? 0);
                                        $day_children = isset($order[$d['children_field']]) ? (int)$order[$d['children_field']] : 0;
                                        $day_preg = isset($order[$d['preg_field']]) ? (int)$order[$d['preg_field']] : 0;
                                        ?>
                                        <div class="day-card">
                                            <div class="day-name"><i class="fas fa-calendar-day <?php echo $d['icon']; ?>"></i> <?php echo $d['label']; ?></div>

                                            <div class="day-detail"><span class="day-label">Children</span><span class="day-value"><?php echo $day_children; ?></span></div>
                                            <div class="day-detail"><span class="day-label">Pregnant</span><span class="day-value"><?php echo $day_preg; ?></span></div>
                                            <div class="day-detail total"><span class="day-label">Total</span><span class="day-value"><?php echo $day_total; ?></span></div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <?php if (!empty($order['remarks'])): ?>
                                <div class="info-section">
                                    <div class="section-title"><i class="fas fa-comment"></i> Remarks</div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['remarks'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-md-4">
                        <!-- Summary -->
                        <div class="summary-box mb-3">
                            <h5 class="mb-3"><i class="fas fa-calculator"></i> Order Summary</h5>

                            <div class="summary-item"><span>Total Packets:</span><strong><?php echo (int)$order['total_qty']; ?></strong></div>
                            <div class="summary-item"><span>Children Packets:</span><strong><?php echo (int)$order['children_allocation']; ?></strong></div>
                            <div class="summary-item"><span>Pregnant Packets:</span><strong><?php echo (int)$order['pregnant_women_allocation']; ?></strong></div>
                            <div class="summary-item"><span>Status:</span><strong><?php echo ucfirst(htmlspecialchars($order['status'])); ?></strong></div>
                            <div class="summary-item"><span>Submitted On:</span><strong><?php echo formatDateTime($order['created_at']); ?></strong></div>
                        </div>

                        <!-- Actions -->
                        <div class="card-custom mb-3">
                            <div class="card-body">
                                <h6 class="mb-3">Actions</h6>
                                <?php if (in_array($order['status'], ['approved', 'dispatched', 'completed'])): ?>
                                    <a href="../reports/order-pdf.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-danger w-100 mb-2" target="_blank"><i class="fas fa-file-pdf me-2"></i> Download PDF</a>
                                <?php endif; ?>

                                <a href="order-history.php" class="btn btn-outline-secondary w-100"><i class="fas fa-list me-2"></i> View All Orders</a>
                            </div>
                        </div>

                        <!-- Contact (only for admins) -->
                        <?php if (Auth::isAdmin()): ?>
                            <div class="card-custom">
                                <div class="card-body">
                                    <h6 class="mb-3">Contact Details</h6>
                                    <p class="mb-2"><strong>Name:</strong><br><?php echo htmlspecialchars($order['user_name']); ?></p>
                                    <p class="mb-2"><strong>Mobile:</strong><br><a href="tel:<?php echo htmlspecialchars($order['user_mobile']); ?>"><?php echo htmlspecialchars($order['user_mobile']); ?></a></p>
                                    <p class="mb-0"><strong>Anganwadi:</strong><br><?php echo htmlspecialchars($order['anganwadi_name']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            const sidebar = document.getElementById('appSidebar');
            const main = document.getElementById('mainContent');
            const toggle = document.getElementById('sidebarToggle');
            if (toggle) {
                toggle.addEventListener('click', function(){
                    sidebar.classList.toggle('collapsed');
                    main.classList.toggle('full');
                });
            }

            function handleResize(){
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
