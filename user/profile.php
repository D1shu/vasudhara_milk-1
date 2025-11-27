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
                $user = array_merge($user, $stmt->get_result()->fetch_assoc());
                $stmt->close();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
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
            width: 250px;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.1);
            color: white;
            text-align: center;
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
        }
        
        .top-navbar {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .content-area {
            padding: 30px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            font-weight: bold;
            color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
            padding: 20px 25px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            flex: 0 0 200px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .info-value {
            flex: 1;
            color: #2d3748;
        }
        
        .stat-box {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 2px solid #e2e8f0;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-glass-whiskey fa-2x mb-2"></i>
            <h4>Vasudhara Milk</h4>
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
            <a href="profile.php" class="active">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">My Profile</h4>
        </div>
        
        <div class="content-area">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="mb-0"><?php echo htmlspecialchars($user['anganwadi_name']); ?></p>
                <small><?php echo $user['aw_code']; ?></small>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Personal Information -->
                <div class="col-md-8">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <i class="fas fa-user-circle"></i> Personal Information
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $user['mobile']; ?>" disabled>
                                    <small class="text-muted">Contact admin to change mobile number</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                    <small class="text-muted">Optional</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ucfirst($user['role']); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Last Login</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?>" disabled>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Anganwadi Details -->
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <i class="fas fa-building"></i> Anganwadi/School Details
                        </div>
                        <div class="card-body p-4">
                            <div class="info-row">
                                <div class="info-label">Name:</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['anganwadi_name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Code:</div>
                                <div class="info-value"><?php echo $user['aw_code']; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Type:</div>
                                <div class="info-value"><?php echo ucfirst($user['anganwadi_type']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Location:</div>
                                <div class="info-value">
                                    <?php echo $user['village_name']; ?>, 
                                    <?php echo $user['taluka_name']; ?>, 
                                    <?php echo $user['district_name']; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Total Children:</div>
                                <div class="info-value"><?php echo $user['total_children']; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Pregnant Women:</div>
                                <div class="info-value"><?php echo $user['pregnant_women']; ?></div>
                            </div>
                            <?php if ($user['address']): ?>
                            <div class="info-row">
                                <div class="info-label">Address:</div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($user['address'])); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="col-md-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <i class="fas fa-chart-bar"></i> My Statistics
                        </div>
                        <div class="card-body p-4">
                            <?php
                            $stats = getDashboardStats($userId, 'user');
                            ?>
                            
                            <div class="stat-box mb-3">
                                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            
                            <div class="stat-box mb-3">
                                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                                <div class="stat-label">Pending Orders</div>
                            </div>
                            
                            <div class="stat-box mb-3">
                                <div class="stat-number"><?php echo $stats['approved_orders']; ?></div>
                                <div class="stat-label">Approved Orders</div>
                            </div>
                            
                            <div class="stat-box">
                                <div class="stat-number"><?php echo $stats['dispatched_orders']; ?></div>
                                <div class="stat-label">Dispatched Orders</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </div>
                        <div class="card-body p-4">
                            <a href="submit-order.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-plus"></i> Submit New Order
                            </a>
                            <a href="order-history.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-history"></i> View Order History
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-home"></i> Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>