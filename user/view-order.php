<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$orderId = $_GET['id'] ?? 0;

// Get order details
$order = getOrderById($orderId);

// Check if order exists and belongs to user (or user is admin)
if (!$order || ($order['user_id'] != $userId && !Auth::isAdmin())) {
    $_SESSION['error'] = 'Order not found or access denied';
    redirect(SITE_URL . '/user/order-history.php');
}

$pageTitle = "Order Details";
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
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-status {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-dispatched {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .info-section {
            padding: 25px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 500;
        }
        
        .daily-breakdown {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
        }
        
        .day-card {
            background: #f7fafc;
            border-radius: 10px;
            padding: 15px;
            border: 2px solid #e2e8f0;
        }
        
        .day-name {
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .day-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .day-detail:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            font-weight: 700;
            color: var(--primary-color);
            font-size: 17px;
        }
        
        .day-label {
            color: #718096;
            font-weight: 500;
        }
        
        .day-value {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border-radius: 15px;
            padding: 25px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: bold;
            padding-top: 20px;
        }
        
        @media (max-width: 768px) {
            .daily-breakdown {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
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
            <a href="profile.php">
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
            <div>
                <h4 class="mb-0">Order Details</h4>
                <small class="text-muted">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></small>
            </div>
            <div>
                <a href="order-history.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to History
                </a>
            </div>
        </div>
        
        <div class="content-area">
            <div class="row">
                <div class="col-md-8">
                    <!-- Order Information -->
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <div>
                                <h5 class="mb-0">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h5>
                                <small>Week: <?php echo formatDate($order['week_start_date']); ?> to <?php echo formatDate($order['week_end_date']); ?></small>
                            </div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        
                        <!-- Anganwadi Details -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-building"></i> Anganwadi/School Details
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['anganwadi_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Code</span>
                                    <span class="info-value"><?php echo $order['aw_code']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Type</span>
                                    <span class="info-value"><?php echo ucfirst($order['anganwadi_type']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location</span>
                                    <span class="info-value">
                                        <?php echo $order['village_name']; ?>, <?php echo $order['taluka_name']; ?>, <?php echo $order['district_name']; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Total Children</span>
                                    <span class="info-value"><?php echo $order['total_children']; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Pregnant Women</span>
                                    <span class="info-value"><?php echo $order['pregnant_women']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daily Breakdown -->
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-calendar-week"></i> Daily Breakdown (Packets)
                            </div>
                            <div class="daily-breakdown">
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-primary"></i> Monday</div>
                                    <div class="day-detail">
                                        <span class="day-label">Children:</span>
                                        <span class="day-value"><?php echo floor($order['mon_qty'] * $order['children_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Pregnant:</span>
                                        <span class="day-value"><?php echo floor($order['mon_qty'] * $order['pregnant_women_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Total:</span>
                                        <span class="day-value"><?php echo $order['mon_qty']; ?></span>
                                    </div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-success"></i> Tuesday</div>
                                    <div class="day-detail">
                                        <span class="day-label">Children:</span>
                                        <span class="day-value"><?php echo floor($order['tue_qty'] * $order['children_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Pregnant:</span>
                                        <span class="day-value"><?php echo floor($order['tue_qty'] * $order['pregnant_women_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Total:</span>
                                        <span class="day-value"><?php echo $order['tue_qty']; ?></span>
                                    </div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-warning"></i> Wednesday</div>
                                    <div class="day-detail">
                                        <span class="day-label">Children:</span>
                                        <span class="day-value"><?php echo floor($order['wed_qty'] * $order['children_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Pregnant:</span>
                                        <span class="day-value"><?php echo floor($order['wed_qty'] * $order['pregnant_women_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Total:</span>
                                        <span class="day-value"><?php echo $order['wed_qty']; ?></span>
                                    </div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-info"></i> Thursday</div>
                                    <div class="day-detail">
                                        <span class="day-label">Children:</span>
                                        <span class="day-value"><?php echo floor($order['thu_qty'] * $order['children_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Pregnant:</span>
                                        <span class="day-value"><?php echo floor($order['thu_qty'] * $order['pregnant_women_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Total:</span>
                                        <span class="day-value"><?php echo $order['thu_qty']; ?></span>
                                    </div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-danger"></i> Friday</div>
                                    <div class="day-detail">
                                        <span class="day-label">Children:</span>
                                        <span class="day-value"><?php echo floor($order['fri_qty'] * $order['children_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Pregnant:</span>
                                        <span class="day-value"><?php echo floor($order['fri_qty'] * $order['pregnant_women_allocation'] / $order['total_qty']); ?></span>
                                    </div>
                                    <div class="day-detail">
                                        <span class="day-label">Total:</span>
                                        <span class="day-value"><?php echo $order['fri_qty']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Remarks -->
                        <?php if (!empty($order['remarks'])): ?>
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-comment"></i> Remarks
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['remarks'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Summary -->
                    <div class="summary-box mb-3">
                        <h5 class="mb-4"><i class="fas fa-calculator"></i> Order Summary</h5>
                        
                        <div class="summary-item">
                            <span>Total Packets:</span>
                            <strong><?php echo $order['total_qty']; ?></strong>
                        </div>
                        
                        <div class="summary-item">
                            <span>Children Packets:</span>
                            <strong><?php echo $order['children_allocation']; ?></strong>
                        </div>
                        
                        <div class="summary-item">
                            <span>Pregnant Packets:</span>
                            <strong><?php echo $order['pregnant_women_allocation']; ?></strong>
                        </div>
                        
                        <div class="summary-item">
                            <span>Status:</span>
                            <strong><?php echo ucfirst($order['status']); ?></strong>
                        </div>
                        
                        <div class="summary-item">
                            <span>Submitted On:</span>
                            <strong><?php echo formatDate($order['created_at']); ?></strong>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="card-custom">
                        <div class="card-body">
                            <h6 class="mb-3">Actions</h6>
                            <?php if (in_array($order['status'], ['approved', 'dispatched', 'completed'])): ?>
                                <a href="../reports/order-pdf.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-danger w-100 mb-2" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Download PDF
                                </a>
                            <?php endif; ?>
                            
                            <a href="order-history.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-list"></i> View All Orders
                            </a>
                        </div>
                    </div>
                    
                    <!-- Contact -->
                    <?php if (Auth::isAdmin()): ?>
                    <div class="card-custom mt-3">
                        <div class="card-body">
                            <h6 class="mb-3">Contact Details</h6>
                            <p class="mb-2">
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($order['user_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>Mobile:</strong><br>
                                <a href="tel:<?php echo $order['user_mobile']; ?>"><?php echo $order['user_mobile']; ?></a>
                            </p>
                            <p class="mb-0">
                                <strong>Anganwadi:</strong><br>
                                <?php echo htmlspecialchars($order['anganwadi_name']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>