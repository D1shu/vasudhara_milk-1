<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$pageTitle = "Notifications";

// Get all notifications
$db = getDB();
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
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
            margin-left: 260px;
            padding: 0;
        }
        
        .top-navbar {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .notification-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .notification-item.unread {
            background: #f0f4ff;
        }
        
        .notification-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        
        .notification-item.order {
            border-left-color: var(--primary-color);
        }
        
        .notification-item.approval {
            border-left-color: var(--success-color);
        }
        
        .notification-item.dispatch {
            border-left-color: var(--info-color);
        }
        
        .notification-item.system {
            border-left-color: var(--warning-color);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .notification-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
            margin: 0;
        }
        
        .notification-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-order {
            background: #e0e7ff;
            color: #667eea;
        }
        
        .badge-approval {
            background: #dcfce7;
            color: #22863a;
        }
        
        .badge-dispatch {
            background: #cffafe;
            color: #0369a1;
        }
        
        .badge-system {
            background: #fef3c7;
            color: #92400e;
        }
        
        .notification-message {
            color: #4a5568;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .notification-time {
            color: #a0aec0;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 30px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #a0aec0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .badge-unread {
            background: #f56565;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 10px;
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
            <a href="dashboard.php">
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
            <a href="notifications.php" class="active">
                <i class="fas fa-bell"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="badge-unread"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">Notifications</h4>
            <div>
                <?php if ($unreadCount > 0): ?>
                    <a href="?action=mark_all_read" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-area">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>No Notifications</h4>
                    <p>You're all caught up! Check back later for updates.</p>
                </div>
            <?php else: ?>
                <div class="action-buttons">
                    <a href="dashboard.php" class="btn btn-custom btn-primary-custom">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
                
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['type']; ?> <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                        <div class="notification-header">
                            <h5 class="notification-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                            </h5>
                            <span class="notification-badge badge-<?php echo $notif['type']; ?>">
                                <?php echo ucfirst($notif['type']); ?>
                            </span>
                        </div>
                        
                        <p class="notification-message">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="notification-time">
                                <i class="fas fa-clock"></i> 
                                <?php echo formatDateTime($notif['created_at']); ?>
                            </span>
                            
                            <?php if (!$notif['is_read']): ?>
                                <a href="?mark_read=1&id=<?php echo $notif['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-check"></i> Mark as Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>