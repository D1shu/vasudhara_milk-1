<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';
require_once '../includes/functions.php';

$db = getDB(); // assumes getDB() returns mysqli connection

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'user';

// --- Fetch unread notifications count to avoid undefined variable in sidebar ---
$unreadCount = 0;
if ($db) {
    $notifQuery = $db->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($notifQuery) {
        $notifQuery->bind_param("i", $userId);
        $notifQuery->execute();
        $res = $notifQuery->get_result();
        if ($row = $res->fetch_assoc()) {
            $unreadCount = (int)$row['cnt'];
        }
        $notifQuery->close();
    }
}

// Ensure we have anganwadi_id: prefer session, otherwise fetch from users table
$anganwadiId = $_SESSION['anganwadi_id'] ?? null;
if (!$anganwadiId) {
    $stmt = $db->prepare("SELECT anganwadi_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $anganwadiId = $row['anganwadi_id'] ? (int)$row['anganwadi_id'] : null;
        if ($anganwadiId) $_SESSION['anganwadi_id'] = $anganwadiId;
    }
    $stmt->close();
}

// fetch anganwadi details if available (also fetch location names)
$anganwadi = null;
if ($anganwadiId) {
    $stmt = $db->prepare("SELECT a.*, v.name as village_name, t.name as taluka_name, d.name as district_name
                          FROM anganwadi a
                          LEFT JOIN villages v ON a.village_id = v.id
                          LEFT JOIN talukas t ON v.taluka_id = t.id
                          LEFT JOIN districts d ON t.district_id = d.id
                          WHERE a.id = ?");
    $stmt->bind_param("i", $anganwadiId);
    $stmt->execute();
    $res = $stmt->get_result();
    $anganwadi = $res->fetch_assoc() ?: null;
    $stmt->close();

    if ($anganwadi) {
        $_SESSION['anganwadi_name'] = $anganwadi['name'];
        $_SESSION['anganwadi_code'] = $anganwadi['aw_code'];
    }
}

// Helper: compute next Monday default for date input if not provided
function getNextMonday() {
    $today = new DateTime('today');
    $dow = (int)$today->format('N'); // 1 (Mon) - 7 (Sun)
    if ($dow === 1) {
        return $today->format('Y-m-d');
    } else {
        $days = 8 - $dow;
        $today->modify("+{$days} days");
        return $today->format('Y-m-d');
    }
}

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(16));
$_SESSION['csrf_token'] = $csrfToken;
$nextMonday = getNextMonday();

// Initialize messages
$error = '';
$success = '';
$warning = ''; // for non-blocking warnings shown after server-side validation (if needed)


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF basic check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request (CSRF). Please refresh the page and try again.';
    } else {
        // Week start date - validate and ensure Monday
        $weekStartDate = $_POST['week_start_date'] ?? '';
        if (empty($weekStartDate)) {
            $error = 'Please select week start date.';
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $weekStartDate);
            if (!$d) {
                $error = 'Invalid date format.';
            } elseif ((int)$d->format('N') !== 1) { // 1 = Monday
                $error = 'Week start date must be a Monday.';
            }
        }

        // collect per-day children + pregnant numbers (mon..fri)
        $days = ['mon','tue','wed','thu','fri'];
        $day_qty = [];
        $day_children = [];
        $day_pregnant = [];
        $total_children = 0;
        $total_pregnant = 0;
        foreach ($days as $day) {
            $children = isset($_POST["{$day}_children"]) ? (int)$_POST["{$day}_children"] : 0;
            $pregnant = isset($_POST["{$day}_pregnant"]) ? (int)$_POST["{$day}_pregnant"] : 0;
            if ($children < 0) $children = 0;
            if ($pregnant < 0) $pregnant = 0;
            $day_children[$day] = $children;
            $day_pregnant[$day] = $pregnant;
            $day_qty[$day] = $children + $pregnant; // total packets that day
            $total_children += $children;
            $total_pregnant += $pregnant;
        }

        $total_qty = array_sum($day_qty);

        if (empty($anganwadiId)) {
            $error = 'No Anganwadi linked to your account. Contact admin.';
        } elseif ($total_qty <= 0) {
            $error = 'Please enter quantity for at least one day.';
        }

        // remarks optional
        $remarks = trim($_POST['remarks'] ?? '');

        // Confirmation flags sent by JS when user confirms overflow prompts
        $confirm_children = isset($_POST['confirm_overflow_children']) && $_POST['confirm_overflow_children'] === '1';
        $confirm_pregnant = isset($_POST['confirm_overflow_pregnant']) && $_POST['confirm_overflow_pregnant'] === '1';

        // Server-side overflow checks (prevent silent bypass)
        if (empty($error) && $anganwadi) {
            $reg_children = (int)($anganwadi['total_children'] ?? 0);
            $reg_pregnant = (int)($anganwadi['pregnant_women'] ?? 0);

            if ($reg_children > 0 && $total_children > $reg_children && !$confirm_children) {
                // user ordered more child packets than registered children, require explicit confirmation
                $error = "You ordered more child packets ({$total_children}) than registered Total Children ({$reg_children}). Please confirm to proceed.";
            }

            // For pregnant: allowed to be zero; but if user orders more than registered pregnant, ask confirm
            if (empty($error) && $reg_pregnant > 0 && $total_pregnant > $reg_pregnant && !$confirm_pregnant) {
                $error = "You ordered more pregnant-women packets ({$total_pregnant}) than registered Pregnant Women ({$reg_pregnant}). Please confirm to proceed.";
            }
        }

        if (empty($error)) {
            // Insert into weekly_orders using mon_qty..fri_qty and per-day children/pregnant and totals
            $stmt = $db->prepare("
                INSERT INTO weekly_orders 
                    (user_id, anganwadi_id, week_start_date, week_end_date, 
                     mon_qty, tue_qty, wed_qty, thu_qty, fri_qty,
                     mon_children, mon_pregnant, tue_children, tue_pregnant,
                     wed_children, wed_pregnant, thu_children, thu_pregnant,
                     fri_children, fri_pregnant,
                     total_qty, children_allocation, pregnant_women_allocation, total_bags,
                     remarks, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            // compute week_end_date as +4 days (Mon - Fri)
            $weekEnd = (new DateTime($weekStartDate))->modify('+4 days')->format('Y-m-d');

            // Map day_qty to variables (int)
            $mon = (int)$day_qty['mon'];
            $tue = (int)$day_qty['tue'];
            $wed = (int)$day_qty['wed'];
            $thu = (int)$day_qty['thu'];
            $fri = (int)$day_qty['fri'];

            // per-day children/pregnant
            $mon_children = (int)$day_children['mon'];
            $mon_pregnant = (int)$day_pregnant['mon'];
            $tue_children = (int)$day_children['tue'];
            $tue_pregnant = (int)$day_pregnant['tue'];
            $wed_children = (int)$day_children['wed'];
            $wed_pregnant = (int)$day_pregnant['wed'];
            $thu_children = (int)$day_children['thu'];
            $thu_pregnant = (int)$day_pregnant['thu'];
            $fri_children = (int)$day_children['fri'];
            $fri_pregnant = (int)$day_pregnant['fri'];

            // total_bags — as integer (currently equal to total_qty)
            $total_bags = (int)$total_qty;

            // children_allocation and pregnant_women_allocation are weekly totals
            $children_allocation = (int)$total_children;
            $pregnant_allocation = (int)$total_pregnant;

            // build types string (24 params)
            $types = "iiss" . str_repeat("i", 19) . "s"; // i i s s + 19 i's + s = 24 params

            $bindParams = [
                $userId, $anganwadiId, $weekStartDate, $weekEnd,
                $mon, $tue, $wed, $thu, $fri,
                $mon_children, $mon_pregnant, $tue_children, $tue_pregnant,
                $wed_children, $wed_pregnant, $thu_children, $thu_pregnant,
                $fri_children, $fri_pregnant,
                $total_qty, $children_allocation, $pregnant_allocation, $total_bags,
                $remarks
            ];

            // Use call_user_func_array to bind dynamic params
            $refs = [];
            $refs[] = &$types;
            for ($i = 0; $i < count($bindParams); $i++) {
                $refs[] = &$bindParams[$i];
            }
            // bind
            call_user_func_array([$stmt, 'bind_param'], $refs);

            if ($stmt->execute()) {
                $success = 'Order submitted successfully!';
                // Optionally clear CSRF so double POST is less likely
                $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
            } else {
                $error = 'Failed to submit order: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$pageTitle = "Submit Order";
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
        .sidebar{
            width:250px; min-height:100vh;
            background: linear-gradient(135deg,var(--primary-500),var(--secondary-500));
            color:#fff; padding:18px 12px; position:fixed; left:0; top:0; z-index:1100;
            transition: transform .22s ease-in-out;
        }
        .sidebar.collapsed{ transform: translateX(-260px); }
        .brand{ text-align:center; padding:12px 6px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .brand h4{ margin:8px 0 0; font-weight:700; }
        .brand small{ opacity:.9; font-weight:500; }

        .nav-links{ margin-top:14px; display:flex; flex-direction:column; gap:8px; padding:8px; }
        .nav-links a{ display:flex; gap:12px; align-items:center; color:rgba(255,255,255,0.95); text-decoration:none; padding:10px 12px; border-radius:10px; font-weight:600; transition:all .12s ease; }
        .nav-links a i{ width:20px; text-align:center; }
        .nav-links a:hover{ transform: translateX(4px); background: rgba(255,255,255,0.06); }
        .nav-links a.active{ background: rgba(0,0,0,0.12); border-left:4px solid rgba(255,255,255,0.14); }

        .sidebar .small-badge{ background: rgba(255,255,255,0.12); padding:4px 8px; border-radius:999px; font-weight:700; font-size:12px; }

        /* Main */
        .main{ margin-left:250px; padding:0; width: calc(100% - 250px); transition: margin-left .22s ease-in-out, width .22s ease-in-out; }
        .main.full{ margin-left:0; width:100%; }

        .topbar{ background:#fff; padding:14px 18px; box-shadow: 0 1px 4px rgba(15,23,42,0.06); display:flex; align-items:center; justify-content:space-between; gap:12px; position:sticky; top:0; z-index:100; }
        .topbar h5{ margin:0; font-weight:600; font-size:16px; }
        .topbar .left{ display:flex; align-items:center; gap:12px; }

        .avatar{ width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); box-shadow: 0 6px 18px rgba(102,126,234,0.12); }

        .content-area{ padding:28px; max-width:var(--max-width); margin:0 auto; }

        /* Cards & summary */
        .card-custom{ background:var(--card-bg); border-radius:var(--radius-md); box-shadow: 0 8px 22px rgba(15,23,42,0.06); overflow:hidden; margin-bottom:18px; }
        .card-header-custom{ background: linear-gradient(135deg,var(--primary-500),var(--secondary-500)); color:#fff; padding:18px; font-weight:700; font-size:16px; }
        .card-body{ padding:18px; }

        .info-box{ background:#ebf8ff; border-left:4px solid #4299e1; padding:12px 14px; border-radius:8px; margin-bottom:18px; color:#0f172a; }

        /* form */
        .form-label{ font-weight:700; color:var(--muted); margin-bottom:8px; }
        .form-control, .form-select{ border:2px solid #eef2ff; border-radius:10px; padding:8px 12px; transition: all .18s; font-size:14px; }
        .form-control:focus, .form-select:focus{ border-color:var(--primary-500); box-shadow: 0 8px 20px rgba(102,126,234,0.08); outline: none; }

        .day-input-group{ background:#fff; border-radius:10px; padding:12px; margin-bottom:12px; border:1px solid #f1f5f9; display:block; }
        .day-label{ font-weight:700; margin-bottom:0; font-size:15px; display:flex; align-items:center; gap:8px; }

        .input-group-compact{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .input-group-compact label{ font-size:13px; color:var(--muted); margin:0; white-space:nowrap; }
        .input-group-compact input[type="number"]{ width:90px; padding:6px 8px; border-radius:8px; border:1px solid #e6eefb; }

        .day-total{ font-weight:700; color:var(--primary-500); font-size:15px; }

        .summary-box{ background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; border-radius:12px; padding:20px; position:sticky; top:20px; box-shadow: 0 10px 30px rgba(102,126,234,0.12); }
        .summary-item{ display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.12); }
        .summary-item:last-child{ border-bottom:none; font-size:18px; font-weight:700; padding-top:12px; }

        .btn-submit{ background: linear-gradient(135deg,#48bb78,#38a169); color:#fff; border:none; padding:12px 18px; border-radius:10px; font-weight:700; width:100%; box-shadow: 0 8px 20px rgba(56,161,105,0.16); }
        .btn-submit:hover{ transform: translateY(-3px); }

        .table-empty{ padding:28px; text-align:center; color:var(--muted); }

        /* small helpers */
        .text-muted{ color: var(--muted) !important; }
        .gap-3{ gap:1rem; }

        /* responsive */
        @media (max-width: 992px) {
            .content-area{ padding:18px; }
        }
        @media (max-width:720px) {
            .sidebar{ transform: translateX(-260px); }
            .main{ margin-left:0; width:100%; }
            .summary-box{ position:relative; top:auto; margin-top:12px; }
            .input-group-compact input[type="number"]{ width:72px; }
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
                <a href="submit-order.php" class="active"><i class="fas fa-plus-circle"></i> Submit Order</a>
                <a href="order-history.php"><i class="fas fa-history"></i> Order History</a>
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
                <div class="left">
                    <button id="sidebarToggle" class="btn btn-sm btn-outline-secondary" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
                    <div>
                        <h5 class="mb-0">Submit Weekly Order</h5>
                        <small class="text-muted">Place weekly milk orders for your Anganwadi</small>
                    </div>
                </div>

                <div class="right d-flex align-items-center gap-3">
                    <div class="text-muted"><?php echo htmlspecialchars($anganwadi['name'] ?? 'N/A'); ?></div>
                    <div class="avatar" title="<?php echo htmlspecialchars($userName); ?>">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                </div>
            </header>

            <section class="content-area">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        <a href="order-history.php" class="alert-link"> View Order History</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($warning): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($warning); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Anganwadi Info -->
                <div class="info-box d-flex flex-column">
                    <div><strong><i class="fas fa-building me-2"></i> <?php echo htmlspecialchars($anganwadi['name'] ?? 'N/A'); ?></strong></div>
                    <div class="text-muted mt-1">
                        Code: <?php echo htmlspecialchars($anganwadi['aw_code'] ?? 'N/A'); ?> |
                        Children: <?php echo (int)($anganwadi['total_children'] ?? 0); ?> |
                        Pregnant Women: <?php echo (int)($anganwadi['pregnant_women'] ?? 0); ?>
                    </div>
                    <div class="text-muted mt-1">
                        Location: 
                        <?php
                            if ($anganwadi) {
                                $loc = [];
                                if (!empty($anganwadi['village_name'])) $loc[] = $anganwadi['village_name'];
                                if (!empty($anganwadi['taluka_name'])) $loc[] = $anganwadi['taluka_name'];
                                if (!empty($anganwadi['district_name'])) $loc[] = $anganwadi['district_name'];
                                echo htmlspecialchars(implode(', ', $loc));
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </div>
                </div>

                <form method="POST" id="orderForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="confirm_overflow_children" id="confirm_overflow_children" value="0">
                    <input type="hidden" name="confirm_overflow_pregnant" id="confirm_overflow_pregnant" value="0">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card-custom">
                                <div class="card-header-custom">
                                    <i class="fas fa-calendar-week me-2"></i> Weekly Milk Order (in Packets)
                                </div>
                                <div class="card-body">
                                    <!-- Week Selection -->
                                    <div class="mb-4">
                                        <label class="form-label" style="font-weight:700; font-size:15px;">Select Week Start Date (Monday)</label>
                                        <input type="date" class="form-control" name="week_start_date" id="weekStartDate" value="<?php echo htmlspecialchars($nextMonday); ?>" required>
                                        <small class="text-muted">Select the Monday of the week for which you want to place order</small>
                                    </div>

                                    <hr class="my-4">

                                    <h6 class="mb-3" style="font-weight:700">Daily Quantities (in Packets)</h6>

                                    <!-- Monday -->
                                    <div class="day-input-group" aria-labelledby="mon-label">
                                        <div class="row align-items-center">
                                            <div class="col-12 col-md-2">
                                                <label id="mon-label" class="day-label"><i class="fas fa-calendar-day text-primary"></i> Monday</label>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="input-group-compact">
                                                    <label>Children:</label>
                                                    <input type="number" class="form-control children-qty" name="mon_children" min="0" value="0" required data-day="mon">
                                                    <label>Pregnant:</label>
                                                    <input type="number" class="form-control pregnant-qty" name="mon_pregnant" min="0" value="0" data-day="mon">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 text-end">
                                                <span class="day-total" id="mon-total">0 packets</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tuesday -->
                                    <div class="day-input-group" aria-labelledby="tue-label">
                                        <div class="row align-items-center">
                                            <div class="col-12 col-md-2">
                                                <label id="tue-label" class="day-label"><i class="fas fa-calendar-day text-success"></i> Tuesday</label>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="input-group-compact">
                                                    <label>Children:</label>
                                                    <input type="number" class="form-control children-qty" name="tue_children" min="0" value="0" required data-day="tue">
                                                    <label>Pregnant:</label>
                                                    <input type="number" class="form-control pregnant-qty" name="tue_pregnant" min="0" value="0" data-day="tue">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 text-end">
                                                <span class="day-total" id="tue-total">0 packets</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Wednesday -->
                                    <div class="day-input-group" aria-labelledby="wed-label">
                                        <div class="row align-items-center">
                                            <div class="col-12 col-md-2">
                                                <label id="wed-label" class="day-label"><i class="fas fa-calendar-day text-warning"></i> Wednesday</label>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="input-group-compact">
                                                    <label>Children:</label>
                                                    <input type="number" class="form-control children-qty" name="wed_children" min="0" value="0" required data-day="wed">
                                                    <label>Pregnant:</label>
                                                    <input type="number" class="form-control pregnant-qty" name="wed_pregnant" min="0" value="0" data-day="wed">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 text-end">
                                                <span class="day-total" id="wed-total">0 packets</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Thursday -->
                                    <div class="day-input-group" aria-labelledby="thu-label">
                                        <div class="row align-items-center">
                                            <div class="col-12 col-md-2">
                                                <label id="thu-label" class="day-label"><i class="fas fa-calendar-day text-info"></i> Thursday</label>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="input-group-compact">
                                                    <label>Children:</label>
                                                    <input type="number" class="form-control children-qty" name="thu_children" min="0" value="0" required data-day="thu">
                                                    <label>Pregnant:</label>
                                                    <input type="number" class="form-control pregnant-qty" name="thu_pregnant" min="0" value="0" data-day="thu">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 text-end">
                                                <span class="day-total" id="thu-total">0 packets</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Friday -->
                                    <div class="day-input-group" aria-labelledby="fri-label">
                                        <div class="row align-items-center">
                                            <div class="col-12 col-md-2">
                                                <label id="fri-label" class="day-label"><i class="fas fa-calendar-day text-danger"></i> Friday</label>
                                            </div>
                                            <div class="col-12 col-md-8">
                                                <div class="input-group-compact">
                                                    <label>Children:</label>
                                                    <input type="number" class="form-control children-qty" name="fri_children" min="0" value="0" required data-day="fri">
                                                    <label>Pregnant:</label>
                                                    <input type="number" class="form-control pregnant-qty" name="fri_pregnant" min="0" value="0" data-day="fri">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 text-end">
                                                <span class="day-total" id="fri-total">0 packets</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="mb-3 mt-4">
                                        <label class="form-label">Remarks (Optional)</label>
                                        <textarea class="form-control" name="remarks" rows="3" placeholder="Any special instructions or remarks"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Sidebar -->
                        <div class="col-md-4 mt-3 mt-md-0">
                            <div class="summary-box">
                                <h5 class="mb-3"><i class="fas fa-calculator me-2"></i> Order Summary</h5>

                                <div class="summary-item">
                                    <span>Total Packets:</span>
                                    <strong id="summaryTotal">0</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Children Packets:</span>
                                    <strong id="summaryChildren">0</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Pregnant Women Packets:</span>
                                    <strong id="summaryPregnant">0</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Week Period:</span>
                                    <strong id="summaryWeek">-</strong>
                                </div>

                                <button type="submit" class="btn-submit mt-4">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Order
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function(){
            // Sidebar toggle behavior (non-intrusive)
            const sidebar = document.getElementById('appSidebar');
            const main = document.getElementById('mainContent');
            const toggle = document.getElementById('sidebarToggle');

            toggle.addEventListener('click', function(){
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('full');
            });

            // Close sidebar on small screens when clicking outside
            document.addEventListener('click', function(e){
                const isInside = sidebar.contains(e.target) || toggle.contains(e.target);
                if (window.innerWidth <= 720 && !isInside) {
                    sidebar.classList.add('collapsed');
                    main.classList.add('full');
                }
            });

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

        // Calculate day total
        function updateDayTotal(day) {
            const children = parseInt(document.querySelector(`input[name="${day}_children"]`).value) || 0;
            const pregnant = parseInt(document.querySelector(`input[name="${day}_pregnant"]`).value) || 0;
            const total = children + pregnant;
            const el = document.getElementById(`${day}-total`);
            if (el) el.textContent = total + ' packets';
        }

        // Calculate summary
        function updateSummary() {
            let totalChildren = 0;
            let totalPregnant = 0;

            document.querySelectorAll('.children-qty').forEach(input => {
                totalChildren += parseInt(input.value) || 0;
            });

            document.querySelectorAll('.pregnant-qty').forEach(input => {
                totalPregnant += parseInt(input.value) || 0;
            });

            const total = totalChildren + totalPregnant;

            document.getElementById('summaryTotal').textContent = total;
            document.getElementById('summaryChildren').textContent = totalChildren;
            document.getElementById('summaryPregnant').textContent = totalPregnant;

            // Update week period
            const startDate = document.getElementById('weekStartDate').value;
            if (startDate) {
                const start = new Date(startDate);
                const end = new Date(start);
                end.setDate(start.getDate() + 4);
                document.getElementById('summaryWeek').textContent =
                    start.toLocaleDateString('en-GB') + ' to ' + end.toLocaleDateString('en-GB');
            } else {
                document.getElementById('summaryWeek').textContent = '-';
            }
        }

        // Attach event listeners
        document.querySelectorAll('.children-qty, .pregnant-qty').forEach(input => {
            input.addEventListener('input', function() {
                const day = this.dataset.day;
                updateDayTotal(day);
                updateSummary();
            });
        });

        const weekInput = document.getElementById('weekStartDate');
        if (weekInput) {
            weekInput.addEventListener('change', function() {
                updateSummary();
                // Ensure selected date is Monday (JS check)
                const date = new Date(this.value);
                if (this.value && date.getDay() !== 1) {
                    alert('Please select a Monday as week start date');
                    this.value = '';
                    document.getElementById('summaryWeek').textContent = '-';
                }
            });
        }

        // Form validation + overflow confirmations (keeps your logic)
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            let totalChildren = 0;
            let totalPregnant = 0;
            document.querySelectorAll('.children-qty').forEach(input => { totalChildren += parseInt(input.value) || 0; });
            document.querySelectorAll('.pregnant-qty').forEach(input => { totalPregnant += parseInt(input.value) || 0; });
            const total = totalChildren + totalPregnant;

            if (total === 0) {
                e.preventDefault();
                alert('Please enter quantity for at least one day');
                return false;
            }

            // Registered values from server-side anganwadi (injected safely)
            const regChildren = <?php echo (int)($anganwadi['total_children'] ?? 0); ?>;
            const regPregnant = <?php echo (int)($anganwadi['pregnant_women'] ?? 0); ?>;

            // Reset hidden confirms
            document.getElementById('confirm_overflow_children').value = '0';
            document.getElementById('confirm_overflow_pregnant').value = '0';

            // If children overflow, show confirm
            if (regChildren > 0 && totalChildren > regChildren) {
                const ok = confirm('You ordered ' + totalChildren + ' child packets which is more than your Total Children (' + regChildren + '). Do you want to continue?');
                if (!ok) {
                    e.preventDefault();
                    return false;
                } else {
                    document.getElementById('confirm_overflow_children').value = '1';
                }
            }

            // If pregnant overflow and registered pregnant > 0, show confirm
            if (regPregnant > 0 && totalPregnant > regPregnant) {
                const ok2 = confirm('You ordered ' + totalPregnant + ' pregnant-women packets which is more than registered Pregnant Women (' + regPregnant + '). Do you want to continue?');
                if (!ok2) {
                    e.preventDefault();
                    return false;
                } else {
                    document.getElementById('confirm_overflow_pregnant').value = '1';
                }
            } else if (regPregnant === 0 && totalPregnant > 0) {
                // If registered pregnant = 0 but user orders pregnant packets, show informational confirm
                const ok3 = confirm('Registered Pregnant Women is 0 for this Anganwadi, but you entered ' + totalPregnant + ' pregnant packets. Do you want to continue?');
                if (!ok3) {
                    e.preventDefault();
                    return false;
                } else {
                    document.getElementById('confirm_overflow_pregnant').value = '1';
                }
            }

            // allow submit to proceed if all good (server will re-check)
        });

        // Initialize totals
        ['mon','tue','wed','thu','fri'].forEach(d => updateDayTotal(d));
        updateSummary();
    </script>
</body>
</html>
