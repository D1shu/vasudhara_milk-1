<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$userName = $_SESSION['user_name'] ?? 'User';

// Get filters
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// helper to bind params dynamically (works with references)
function refValues($arr) {
    $refs = [];
    foreach ($arr as $k => $v) {
        $refs[$k] = &$arr[$k];
    }
    return $refs;
}

$db = getDB();

// === unread notifications count (prevent undefined variable) ===
$unreadCount = 0;
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

// Build dynamic WHERE
$whereConditions = ["wo.user_id = ?"];
$params = [$userId];
$types = "i";

if ($statusFilter !== 'all') {
    $whereConditions[] = "wo.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($searchQuery !== '') {
    // Search on order id (integer) or aw_code (string)
    $whereConditions[] = "(wo.id = ? OR a.aw_code LIKE ?)";
    $idParam = is_numeric($searchQuery) ? (int)$searchQuery : 0;
    $likeParam = "%$searchQuery%";
    $params[] = $idParam;
    $params[] = $likeParam;
    $types .= "is"; // integer for id, string for LIKE
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM weekly_orders wo 
             JOIN anganwadi a ON wo.anganwadi_id = a.id
             $whereClause";

$countStmt = $db->prepare($countSql);
if (!$countStmt) {
    die("Prepare failed: " . $db->error);
}

// bind params to count stmt
if (count($params) > 0) {
    $bindParams = array_merge([$types], $params);
    call_user_func_array([$countStmt, 'bind_param'], refValues($bindParams));
}

$countStmt->execute();
$totalRecords = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

// Get orders
$sql = "SELECT wo.*, a.name as anganwadi_name, a.aw_code
        FROM weekly_orders wo
        JOIN anganwadi a ON wo.anganwadi_id = a.id
        $whereClause
        ORDER BY wo.created_at DESC
        LIMIT ? OFFSET ?";

// Add pagination params (limit, offset)
$params_with_limit = $params;
$types_with_limit = $types . "ii";
$params_with_limit[] = $limit;
$params_with_limit[] = $offset;

$stmt = $db->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $db->error);
}

// bind all params
$bindParams = array_merge([$types_with_limit], $params_with_limit);
call_user_func_array([$stmt, 'bind_param'], refValues($bindParams));

$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

$pageTitle = "Order History";
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

        .main{ margin-left:250px; padding:0; width: calc(100% - 250px); transition: margin-left .22s ease-in-out, width .22s ease-in-out; }
        .main.full{ margin-left:0; width:100%; }

        .topbar{ background:#fff; padding:14px 18px; box-shadow: 0 1px 4px rgba(15,23,42,0.06); display:flex; align-items:center; justify-content:space-between; gap:12px; position:sticky; top:0; z-index:100; }
        .topbar h4{ margin:0; font-weight:700; }

        .content-area{ padding:28px; max-width:var(--max-width); margin:0 auto; }

        .filter-card{ background:var(--card-bg); border-radius:var(--radius-md); padding:14px; box-shadow: 0 8px 22px rgba(15,23,42,0.06); margin-bottom:18px; }

        .order-card{ background:var(--card-bg); border-radius:var(--radius-md); padding:18px; margin-bottom:14px; box-shadow: 0 8px 22px rgba(15,23,42,0.06); transition: transform .18s ease, box-shadow .18s ease; }
        .order-card:hover{ transform: translateY(-6px); box-shadow: 0 14px 36px rgba(15,23,42,0.08); }

        .order-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #eef2ff; }
        .order-id{ font-size:18px; font-weight:700; color:var(--primary-500); }

        .badge-pending { background: #fef3c7; color: #92400e; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:700; }
        .badge-approved { background: #d1fae5; color: #065f46; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:700; }
        .badge-dispatched { background: #dbeafe; color: #1e40af; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:700; }
        .badge-rejected { background: #fee2e2; color: #991b1b; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:700; }
        .badge-completed { background: #e5e7eb; color: #374151; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:700; }

        .order-details{ display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:12px; }
        .detail-item{ display:flex; flex-direction:column; }
        .detail-label{ font-size:12px; color:var(--muted); text-transform:uppercase; margin-bottom:6px; }
        .detail-value{ font-size:15px; font-weight:700; color:#0f172a; }

        .order-actions{ margin-top:12px; padding-top:12px; border-top:1px solid #eef2ff; display:flex; gap:10px; flex-wrap:wrap; }
        .btn-view{ background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); color:#fff; border:none; padding:8px 16px; border-radius:10px; font-weight:700; }
        .btn-view:hover{ transform: translateY(-3px); }

        .empty-state{ text-align:center; padding:44px 18px; }
        .empty-state i{ font-size:56px; color:#cbd5e0; margin-bottom:12px; }

        .pagination { margin-top:18px; }

        /* small helpers */
        .text-muted{ color: var(--muted) !important; }

        @media (max-width: 720px) {
            .content-area{ padding:16px; }
            .order-details{ grid-template-columns: 1fr; }
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
                    <h4>My Order History</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="avatar" title="<?php echo htmlspecialchars($userName); ?>"><?php echo strtoupper(substr($userName,0,1)); ?></div>
                </div>
            </header>

            <section class="content-area">
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="dispatched" <?php echo $statusFilter === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Search by Order ID</label>
                            <input type="text" name="search" class="form-control" placeholder="Enter order ID..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>

                        <div class="col-md-3 d-grid">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-view w-100"><i class="fas fa-search me-2"></i> Search</button>
                        </div>
                    </form>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="card-custom">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>No Orders Found</h4>
                            <p class="text-muted">You haven't submitted any orders yet.</p>
                            <a href="submit-order.php" class="btn btn-view mt-3"><i class="fas fa-plus me-2"></i> Submit Your First Order</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-3 text-muted">Showing <?php echo count($orders); ?> of <?php echo $totalRecords; ?> orders</div>

                    <?php foreach ($orders as $order): ?>
                        <article class="order-card">
                            <div class="order-header">
                                <div class="order-id">Order #<?php echo str_pad($order['id'],5,'0',STR_PAD_LEFT); ?></div>
                                <div>
                                    <span class="badge-<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                                </div>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">Week Period</span>
                                    <span class="detail-value"><?php echo formatDate($order['week_start_date']); ?> - <?php echo formatDate($order['week_end_date']); ?></span>
                                </div>

                                <div class="detail-item">
                                    <span class="detail-label">Total Packets</span>
                                    <span class="detail-value"><?php echo (int)$order['total_qty']; ?> packets</span>
                                </div>

                                <div class="detail-item">
                                    <span class="detail-label">Children Packets</span>
                                    <span class="detail-value"><?php echo (int)$order['children_allocation']; ?> packets</span>
                                </div>

                                <div class="detail-item">
                                    <span class="detail-label">Pregnant Women Packets</span>
                                    <span class="detail-value"><?php echo (int)$order['pregnant_women_allocation']; ?> packets</span>
                                </div>

                                <div class="detail-item">
                                    <span class="detail-label">Submitted On</span>
                                    <span class="detail-value"><?php echo formatDateTime($order['created_at']); ?></span>
                                </div>
                            </div>

                            <div class="order-actions">
                                <a href="view-order.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-view"><i class="fas fa-eye me-2"></i> View Details</a>

                                
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>

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

            // responsive
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
