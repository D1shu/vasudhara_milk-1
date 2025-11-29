<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$pageTitle = "Notifications";

// Get all notifications
$db = getDB();
$stmt = $db->prepare("\n    SELECT * FROM notifications \n    WHERE user_id = ? \n    ORDER BY created_at DESC \n    LIMIT 50\n");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Mark all as read if requested
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    redirect(SITE_URL . '/user/notifications.php');
}

// Mark single notification as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notifId = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notifId, $userId);
    $stmt->execute();
    $stmt->close();
}

$unreadCount = Auth::getUnreadNotificationsCount($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root{
            --primary:#667eea;
            --secondary:#764ba2;
            --muted:#62748b;
            --bg:#f8fafc;
            --card:#ffffff;
            --success:#48bb78;
            --info:#38bdf8;
            --warning:#f59e0b;
            --danger:#f56565;
            --radius:12px;
            --maxw:1100px;
        }

        *{box-sizing:border-box}
        body{font-family:'Poppins',system-ui,-apple-system,'Segoe UI',Roboto,Arial;background:var(--bg);color:#0f172a;margin:0}
        .app{display:flex;min-height:100vh}

        /* Sidebar */
        .sidebar{width:250px;min-height:100vh;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;padding:18px;position:fixed;left:0;top:0}
        .brand{text-align:center;padding:8px 4px;border-bottom:1px solid rgba(255,255,255,0.06)}
        .brand h4{margin:8px 0 0}
        .nav-links{margin-top:12px;display:flex;flex-direction:column;gap:8px}
        .nav-links a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.95);text-decoration:none;padding:10px;border-radius:10px;font-weight:600}
        .nav-links a.active{background:rgba(0,0,0,0.12);border-left:4px solid rgba(255,255,255,0.14)}
        .small-badge{background:rgba(255,255,255,0.12);padding:4px 8px;border-radius:999px;font-weight:700;font-size:12px}

        /* Main area */
        .main{margin-left:250px;width:calc(100% - 250px)}
        .topbar{background:#fff;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(15,23,42,0.04);position:sticky;top:0;z-index:90}
        .content{padding:26px;max-width:var(--maxw);margin:0 auto}

        .actions{display:flex;gap:10px;align-items:center;margin-bottom:18px}
        .btn-ghost{background:transparent;border:1px solid rgba(15,23,42,0.06);padding:8px 12px;border-radius:8px}

        .notification-item{background:var(--card);border-radius:10px;padding:16px;margin-bottom:12px;border-left:4px solid var(--primary);transition:all .18s ease}
        .notification-item.unread{background:#f0f4ff}
        .notification-item:hover{transform:translateX(6px);box-shadow:0 8px 26px rgba(15,23,42,0.06)}

        .notification-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
        .notification-title{font-weight:700;margin:0;color:#0f172a}
        .notification-badge{padding:6px 12px;border-radius:999px;font-weight:700;font-size:12px}
        .badge-order{background:#eef2ff;color:var(--primary)}
        .badge-approval{background:#dcfce7;color:var(--success)}
        .badge-dispatch{background:#ecfeff;color:var(--info)}
        .badge-system{background:#fff4e6;color:var(--warning)}

        .notification-message{color:#425063;margin:10px 0 6px;font-size:14px}
        .notification-meta{display:flex;justify-content:space-between;align-items:center;color:#9aa3b3;font-size:13px}

        .empty{padding:60px;text-align:center;color:#64748b}
        .empty i{font-size:56px;margin-bottom:12px;color:#cbd5e0}

        @media (max-width:900px){.daily-grid{grid-template-columns:repeat(2,1fr)}}
        @media (max-width:720px){.sidebar{transform:translateX(-260px)}.main{margin-left:0;width:100%}.content{padding:16px}}
    </style>
</head>
<body>
    <div class="app">
        <aside class="sidebar" aria-label="Main navigation">
            <div class="brand">
                <i class="fas fa-glass-whiskey fa-2x"></i>
                <h4>Vasudhara Milk</h4>
                <small>Distribution System</small>
            </div>

            <nav class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="submit-order.php"><i class="fas fa-plus-circle"></i> Submit Order</a>
                <a href="order-history.php"><i class="fas fa-history"></i> Order History</a>
                <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                <a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications <?php if ($unreadCount>0): ?> <span style="margin-left:auto" class="small-badge"><?php echo (int)$unreadCount; ?></span><?php endif; ?></a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main">
            <div class="topbar">
                <h4 style="margin:0">Notifications</h4>
                <div>
                    <?php if ($unreadCount > 0): ?>
                        <a href="?action=mark_all_read" class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double me-2"></i>Mark all as read</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content">
                <?php if (empty($notifications)): ?>
                    <div class="empty">
                        <i class="fas fa-inbox"></i>
                        <h4>No Notifications</h4>
                        <p>You're all caught up — check back later for updates.</p>
                        <a href="dashboard.php" class="btn btn-sm btn-ghost"><i class="fas fa-home me-2"></i> Back to dashboard</a>
                    </div>
                <?php else: ?>

                    <div class="actions">
                        <a href="dashboard.php" class="btn btn-ghost"><i class="fas fa-home me-2"></i> Dashboard</a>
                    </div>

                    <?php foreach ($notifications as $notif): ?>
                        <?php
                            $type = $notif['type'] ?? 'system';
                            $badgeClass = 'badge-system';
                            if ($type === 'order') $badgeClass = 'badge-order';
                            if ($type === 'approval') $badgeClass = 'badge-approval';
                            if ($type === 'dispatch') $badgeClass = 'badge-dispatch';
                        ?>

                        <a href="<?php echo !$notif['is_read'] ? '?mark_read=1&id=' . (int)$notif['id'] : '#'; ?>" style="text-decoration:none; color:inherit;">
                            <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?> <?php echo htmlspecialchars($type); ?>">
                                <div class="notification-header">
                                    <h5 class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></h5>
                                    <div class="notification-badge <?php echo $badgeClass; ?>"><?php echo ucfirst(htmlspecialchars($type)); ?></div>
                                </div>

                                <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>

                                <div class="notification-meta">
                                    <div><i class="fas fa-clock me-1"></i><?php echo formatDateTime($notif['created_at']); ?></div>
                                    <div>
                                        <?php if (!$notif['is_read']): ?>
                                            <a href="?mark_read=1&id=<?php echo (int)$notif['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-check me-1"></i>Mark</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
