<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$userId = Auth::getUserId();
$orderId = $_GET['id'] ?? 0;

// Get order details
$order = getOrderById($orderId);

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    redirect(SITE_URL . '/admin/orders.php');
}

$error = '';
$success = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        if ($action === 'approve') {
            if (updateOrderStatus($orderId, 'approved', $userId, $remarks)) {
                $_SESSION['success'] = 'Order approved successfully!';
                redirect(SITE_URL . '/admin/orders.php');
            } else {
                $error = 'Failed to approve order';
            }
        } elseif ($action === 'reject') {
            if (empty($remarks)) {
                $error = 'Please provide reason for rejection';
            } else {
                if (updateOrderStatus($orderId, 'rejected', $userId, $remarks)) {
                    $_SESSION['success'] = 'Order rejected';
                    redirect(SITE_URL . '/admin/orders.php');
                } else {
                    $error = 'Failed to reject order';
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = "Review Order";
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
        body {
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            position: fixed;
            width: 260px;
            padding: 0;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a.active {
            background: linear-gradient(90deg, #667eea, transparent);
            color: white;
        }
        
        .main-content {
            margin-left: 260px;
        }
        
        .top-navbar {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
        
        .order-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .order-id {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .section-header {
            background: #f7fafc;
            padding: 15px 25px;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            font-size: 16px;
        }
        
        .info-section {
            padding: 25px;
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
            padding: 20px;
            text-align: center;
            border: 2px solid #e2e8f0;
        }
        
        .day-name {
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .day-qty {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        .day-unit {
            font-size: 12px;
            color: #718096;
        }
        
        .action-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 30px;
            position: sticky;
            top: 20px;
        }
        
        .btn-approve {
            background: #48bb78;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-approve:hover {
            background: #38a169;
            color: white;
        }
        
        .btn-reject {
            background: #f56565;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-reject:hover {
            background: #e53e3e;
            color: white;
        }
        
        .summary-box {
            background: #f7fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .daily-breakdown {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <h5 class="mb-0">Admin Panel</h5>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i> Dashboard
            </a>
            <a href="orders.php" class="active">
                <i class="fas fa-clipboard-list me-2"></i> Orders
            </a>
            <a href="anganwadi.php">
                <i class="fas fa-building me-2"></i> Anganwadi
            </a>
            <a href="reports.php">
                <i class="fas fa-file-alt me-2"></i> Reports
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">Review Order</h4>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        
        <div class="content-area">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card-custom">
                        <div class="order-header">
                            <div class="order-id">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                            <p class="mb-0">Week: <?php echo formatDate($order['week_start_date']); ?> to <?php echo formatDate($order['week_end_date']); ?></p>
                        </div>
                        
                        <!-- Anganwadi Details -->
                        <div class="section-header">
                            <i class="fas fa-building me-2"></i> Anganwadi/School Information
                        </div>
                        <div class="info-section">
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
                        <div class="section-header">
                            <i class="fas fa-calendar-week me-2"></i> Daily Breakdown
                        </div>
                        <div class="info-section">
                            <div class="daily-breakdown">
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-primary"></i> MON</div>
                                    <div class="day-qty"><?php echo number_format($order['mon_qty'], 1); ?></div>
                                    <div class="day-unit">Liters</div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-success"></i> TUE</div>
                                    <div class="day-qty"><?php echo number_format($order['tue_qty'], 1); ?></div>
                                    <div class="day-unit">Liters</div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-warning"></i> WED</div>
                                    <div class="day-qty"><?php echo number_format($order['wed_qty'], 1); ?></div>
                                    <div class="day-unit">Liters</div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-info"></i> THU</div>
                                    <div class="day-qty"><?php echo number_format($order['thu_qty'], 1); ?></div>
                                    <div class="day-unit">Liters</div>
                                </div>
                                <div class="day-card">
                                    <div class="day-name"><i class="fas fa-calendar-day text-danger"></i> FRI</div>
                                    <div class="day-qty"><?php echo number_format($order['fri_qty'], 1); ?></div>
                                    <div class="day-unit">Liters</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Allocation -->
                        <div class="section-header">
                            <i class="fas fa-users me-2"></i> Allocation Details
                        </div>
                        <div class="info-section">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Children Allocation</span>
                                    <span class="info-value"><?php echo number_format($order['children_allocation'], 2); ?> Liters</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Pregnant Women Allocation</span>
                                    <span class="info-value"><?php echo number_format($order['pregnant_women_allocation'], 2); ?> Liters</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Details -->
                        <div class="section-header">
                            <i class="fas fa-phone me-2"></i> Contact Information
                        </div>
                        <div class="info-section">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Submitted By</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['user_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Mobile</span>
                                    <span class="info-value">
                                        <a href="tel:<?php echo $order['user_mobile']; ?>"><?php echo $order['user_mobile']; ?></a>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Submitted On</span>
                                    <span class="info-value"><?php echo formatDateTime($order['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Remarks -->
                        <?php if (!empty($order['remarks'])): ?>
                        <div class="section-header">
                            <i class="fas fa-comment me-2"></i> Remarks
                        </div>
                        <div class="info-section">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['remarks'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions Sidebar -->
                <div class="col-md-4">
                    <div class="action-card">
                        <h5 class="mb-4">Order Summary</h5>
                        
                        <div class="summary-box">
                            <div class="summary-item">
                                <span>Total Quantity:</span>
                                <strong><?php echo number_format($order['total_qty'], 2); ?> L</strong>
                            </div>
                            <div class="summary-item">
                                <span>Total Bags:</span>
                                <strong><?php echo $order['total_bags']; ?></strong>
                            </div>
                            <div class="summary-item">
                                <span>Status:</span>
                                <strong><?php echo ucfirst($order['status']); ?></strong>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'pending'): ?>
                        <form method="POST" id="approvalForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Admin Remarks</label>
                                <textarea class="form-control" name="remarks" rows="3" 
                                          placeholder="Add your comments (optional for approval, required for rejection)"></textarea>
                            </div>
                            
                            <button type="submit" name="action" value="approve" class="btn btn-approve">
                                <i class="fas fa-check-circle"></i> Approve Order
                            </button>
                            
                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                <i class="fas fa-times-circle"></i> Reject Order
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info">
                            This order has already been <?php echo $order['status']; ?>.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('approvalForm')?.addEventListener('submit', function(e) {
            const action = e.submitter.value;
            const remarks = this.remarks.value.trim();
            
            if (action === 'reject' && !remarks) {
                e.preventDefault();
                alert('Please provide reason for rejection');
                return false;
            }
            
            const message = action === 'approve' 
                ? 'Are you sure you want to approve this order?' 
                : 'Are you sure you want to reject this order?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>