<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$error = '';
$success = '';

// Get user details
$db = getDB();
$stmt = $db->prepare("
    SELECT u.*, a.name as anganwadi_name, a.aw_code, a.type as anganwadi_type,
           a.total_children, a.pregnant_women, a.address,
           v.name as village_name, t.name as taluka_name, d.name as district_name
    FROM users u
    LEFT JOIN anganwadi a ON u.anganwadi_id = a.id
    LEFT JOIN villages v ON a.village_id = v.id
    LEFT JOIN talukas t ON v.taluka_id = t.id
    LEFT JOIN districts d ON t.district_id = d.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If user fetch failed, provide safe defaults to avoid undefined index notices
if (!$user) {
    $user = [
        'id' => $userId,
        'name' => 'Unknown',
        'mobile' => '',
        'email' => '',
        'role' => 'user',
        'last_login' => null,
        'anganwadi_name' => '',
        'aw_code' => '',
        'anganwadi_type' => '',
        'total_children' => 0,
        'pregnant_women' => 0,
        'address' => '',
        'village_name' => '',
        'taluka_name' => '',
        'district_name' => ''
    ];
}

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        
        if (empty($name)) {
            $error = 'Name is required';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } else {
            $updateStmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $name, $email, $userId);
            
            if ($updateStmt->execute()) {
                $_SESSION['user_name'] = $name;
                logActivity($userId, 'UPDATE_PROFILE', 'users', $userId);
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $fresh = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($fresh) {
                    // merge fresh data into $user while preserving computed fields
                    $user = array_merge($user, $fresh);
                }
            } else {
                $error = 'Failed to update profile';
            }
            $updateStmt->close();
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

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

        .profile-header{ background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); color:#fff; border-radius:var(--radius-md); padding:26px; text-align:center; margin-bottom:20px; box-shadow: 0 10px 30px rgba(102,126,234,0.08); }
        .profile-avatar{ width:96px; height:96px; border-radius:999px; background:#fff; color:var(--primary-500); font-weight:700; display:inline-flex; align-items:center; justify-content:center; font-size:34px; margin-bottom:8px; }
        .profile-header h3{ margin:8px 0 4px; font-weight:700; }
        .profile-header small{ opacity:0.9; }

        .card-custom{ background:var(--card-bg); border-radius:var(--radius-md); box-shadow: 0 8px 22px rgba(15,23,42,0.06); overflow:hidden; margin-bottom:18px; }
        .card-header-custom{ background: transparent; border-bottom:1px solid #eef2ff; padding:14px 18px; font-weight:700; }
        .card-body{ padding:18px; }

        .form-label{ font-weight:700; color:var(--muted); margin-bottom:8px; }
        .form-control{ border:1px solid #eef2ff; border-radius:10px; padding:8px 12px; }

        .info-row{ display:flex; padding:12px 0; border-bottom:1px solid #eef2ff; }
        .info-row:last-child{ border-bottom:none; }
        .info-label{ flex:0 0 160px; font-weight:700; color:var(--muted); }
        .info-value{ flex:1; color:#0f172a; }

        .stat-box{ background:#fff; border-radius:10px; padding:16px; text-align:center; border:1px solid #eef2ff; }
        .stat-number{ font-size:28px; font-weight:800; color:var(--primary-500); }
        .stat-label{ font-size:13px; color:var(--muted); margin-top:6px; }

        .btn-primary{ background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); border:none; }

        @media (max-width: 992px) { .content-area{ padding:18px; } }
        @media (max-width: 720px) {
            .sidebar{ transform: translateX(-260px); }
            .main{ margin-left:0; width:100%; }
            .info-row{ flex-direction:column; gap:6px; }
            .info-label{ flex:0 0 auto; }
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
                <a href="order-history.php"><i class="fas fa-history"></i> Order History</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a>

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
                    <h4>My Profile</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="avatar" style="background:linear-gradient(135deg,var(--primary-500),var(--secondary-500)); color:#fff; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:700;">
                        <?php echo strtoupper(substr($user['name'],0,1)); ?>
                    </div>
                </div>
            </header>

            <section class="content-area">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <div class="text-muted"><?php echo htmlspecialchars($user['anganwadi_name']); ?> <small class="d-block"><?php echo htmlspecialchars($user['aw_code']); ?></small></div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-md-8">
                        <div class="card-custom">
                            <div class="card-header-custom"><i class="fas fa-user-circle me-2"></i> Personal Information</div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mobile Number</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['mobile']); ?>" disabled>
                                        <small class="text-muted">Contact admin to change mobile number</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                        <small class="text-muted">Optional</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Last Login</label>
                                        <input type="text" class="form-control" value="<?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?>" disabled>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Update Profile</button>
                                </form>
                            </div>
                        </div>

                        <!-- Anganwadi Details -->
                        <div class="card-custom">
                            <div class="card-header-custom"><i class="fas fa-building me-2"></i> Anganwadi/School Details</div>
                            <div class="card-body">
                                <div class="info-row"><div class="info-label">Name:</div><div class="info-value"><?php echo htmlspecialchars($user['anganwadi_name']); ?></div></div>
                                <div class="info-row"><div class="info-label">Code:</div><div class="info-value"><?php echo htmlspecialchars($user['aw_code']); ?></div></div>
                                <div class="info-row"><div class="info-label">Type:</div><div class="info-value"><?php echo htmlspecialchars(ucfirst($user['anganwadi_type'])); ?></div></div>
                                <div class="info-row"><div class="info-label">Location:</div><div class="info-value"><?php echo htmlspecialchars($user['village_name']); ?>, <?php echo htmlspecialchars($user['taluka_name']); ?>, <?php echo htmlspecialchars($user['district_name']); ?></div></div>
                                <div class="info-row"><div class="info-label">Total Children</div><div class="info-value"><?php echo (int)$user['total_children']; ?></div></div>
                                <div class="info-row"><div class="info-label">Pregnant Women</div><div class="info-value"><?php echo (int)$user['pregnant_women']; ?></div></div>
                                <?php if ($user['address']): ?><div class="info-row"><div class="info-label">Address:</div><div class="info-value"><?php echo nl2br(htmlspecialchars($user['address'])); ?></div></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="col-md-4">
                        <div class="card-custom">
                            <div class="card-header-custom"><i class="fas fa-chart-bar me-2"></i> My Statistics</div>
                            <div class="card-body">
                                <?php $stats = getDashboardStats($userId, 'user'); ?>
                                <div class="stat-box mb-3"><div class="stat-number"><?php echo (int)$stats['total_orders']; ?></div><div class="stat-label">Total Orders</div></div>
                                <div class="stat-box mb-3"><div class="stat-number"><?php echo (int)$stats['pending_orders']; ?></div><div class="stat-label">Pending Orders</div></div>
                                <div class="stat-box mb-3"><div class="stat-number"><?php echo (int)$stats['approved_orders']; ?></div><div class="stat-label">Approved Orders</div></div>
                                <div class="stat-box"><div class="stat-number"><?php echo (int)$stats['dispatched_orders']; ?></div><div class="stat-label">Dispatched Orders</div></div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card-custom">
                            <div class="card-header-custom"><i class="fas fa-bolt me-2"></i> Quick Actions</div>
                            <div class="card-body">
                                <a href="submit-order.php" class="btn btn-primary w-100 mb-2"><i class="fas fa-plus me-2"></i> Submit New Order</a>
                                <a href="order-history.php" class="btn btn-outline-primary w-100 mb-2"><i class="fas fa-history me-2"></i> View Order History</a>
                                <a href="dashboard.php" class="btn btn-outline-secondary w-100"><i class="fas fa-home me-2"></i> Go to Dashboard</a>
                            </div>
                        </div>
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
