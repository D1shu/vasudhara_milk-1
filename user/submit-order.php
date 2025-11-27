<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireLogin();

$userId = Auth::getUserId();
$anganwadiId = $_SESSION['anganwadi_id'];

// Get anganwadi details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM anganwadi WHERE id = ?");
$stmt->bind_param("i", $anganwadiId);
$stmt->execute();
$anganwadi = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $weekStartDate = $_POST['week_start_date'];
        
        // Monday
        $monChildren = intval($_POST['mon_children']);
        $monPregnant = intval($_POST['mon_pregnant']);
        $monQty = $monChildren + $monPregnant;
        
        // Tuesday
        $tueChildren = intval($_POST['tue_children']);
        $tuePregnant = intval($_POST['tue_pregnant']);
        $tueQty = $tueChildren + $tuePregnant;
        
        // Wednesday
        $wedChildren = intval($_POST['wed_children']);
        $wedPregnant = intval($_POST['wed_pregnant']);
        $wedQty = $wedChildren + $wedPregnant;
        
        // Thursday
        $thuChildren = intval($_POST['thu_children']);
        $thuPregnant = intval($_POST['thu_pregnant']);
        $thuQty = $thuChildren + $thuPregnant;
        
        // Friday
        $friChildren = intval($_POST['fri_children']);
        $friPregnant = intval($_POST['fri_pregnant']);
        $friQty = $friChildren + $friPregnant;
        
        $remarks = sanitize($_POST['remarks']);
        
        // Calculate totals
        $totalQty = $monQty + $tueQty + $wedQty + $thuQty + $friQty;
        $childrenAlloc = $monChildren + $tueChildren + $wedChildren + $thuChildren + $friChildren;
        $pregnantAlloc = $monPregnant + $tuePregnant + $wedPregnant + $thuPregnant + $friPregnant;
        $totalBags = $totalQty; // Since we're using packets directly
        
        // Calculate week end date (Friday)
        $weekEndDate = date('Y-m-d', strtotime($weekStartDate . ' +4 days'));
        
        // Check if order already exists for this week
        $checkStmt = $db->prepare("SELECT id FROM weekly_orders WHERE anganwadi_id = ? AND week_start_date = ?");
        $checkStmt->bind_param("is", $anganwadiId, $weekStartDate);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if ($existing) {
            $error = 'Order already exists for this week. Please check order history.';
        } else {
            // Create order
            $orderData = [
                'user_id' => $userId,
                'anganwadi_id' => $anganwadiId,
                'week_start_date' => $weekStartDate,
                'week_end_date' => $weekEndDate,
                'mon_qty' => $monQty,
                'tue_qty' => $tueQty,
                'wed_qty' => $wedQty,
                'thu_qty' => $thuQty,
                'fri_qty' => $friQty,
                'total_qty' => $totalQty,
                'children_allocation' => $childrenAlloc,
                'pregnant_women_allocation' => $pregnantAlloc,
                'total_bags' => $totalBags,
                'remarks' => $remarks
            ];
            
            $orderId = createWeeklyOrder($orderData);
            
            if ($orderId) {
                $success = 'Order submitted successfully! Order ID: #' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
                
                // Create notification for admins
                $adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
                while ($admin = $adminStmt->fetch_assoc()) {
                    Auth::createNotification(
                        $admin['id'],
                        'New Order Submitted',
                        "New order submitted by {$_SESSION['user_name']} for {$anganwadi['name']}",
                        'order'
                    );
                }
            } else {
                $error = 'Failed to submit order. Please try again.';
            }
        }
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = "Submit Order";

// Get next Monday date as default
$nextMonday = date('Y-m-d', strtotime('next monday'));
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
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .content-area {
            padding: 30px;
        }
        
        .card-custom {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: none;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            font-weight: 600;
            font-size: 18px;
        }
        
        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .form-control,
        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            transition: all 0.3s;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .day-input-group {
            background: #f7fafc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            border: 2px solid #e2e8f0;
        }
        
        .day-input-group .row {
            align-items: center;
        }
        
        .day-label {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 15px;
        }
        
        .input-group-compact {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .input-group-compact input {
            width: 70px;
            padding: 6px 8px;
            font-size: 14px;
        }
        
        .input-group-compact label {
            font-size: 13px;
            color: #718096;
            margin: 0;
            white-space: nowrap;
        }
        
        .day-total {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 16px;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: bold;
            padding-top: 15px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #48bb78, #38a169);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }
        
        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            <a href="submit-order.php" class="active">
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
            <h5 class="mb-0">Submit Weekly Order</h5>
        </div>
        
        <div class="content-area">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <a href="order-history.php" class="alert-link">View Order History</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Anganwadi Info -->
            <div class="info-box">
                <strong><i class="fas fa-building"></i> <?php echo htmlspecialchars($anganwadi['name']); ?></strong><br>
                <small>
                    Code: <?php echo $anganwadi['aw_code']; ?> | 
                    Children: <?php echo $anganwadi['total_children']; ?> | 
                    Pregnant Women: <?php echo $anganwadi['pregnant_women']; ?>
                </small>
            </div>
            
            <form method="POST" id="orderForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <i class="fas fa-calendar-week"></i> Weekly Milk Order (in Packets)
                            </div>
                            <div class="card-body p-4">
                                <!-- Week Selection -->
                                <div class="mb-4">
                                    <label class="form-label" style="font-weight: 700; font-size: 16px;">Select Week Start Date (Monday)</label>
                                    <input type="date" class="form-control" name="week_start_date" 
                                           id="weekStartDate" value="<?php echo $nextMonday; ?>" required>
                                    <small class="text-muted">Select the Monday of the week for which you want to place order</small>
                                </div>
                                
                                <!-- Daily Quantities -->
                                <hr class="my-4">
                                <h6 class="mb-3">Daily Quantities (in Packets)</h6>
                                
                                <!-- Monday -->
                                <div class="day-input-group">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="day-label">
                                                <i class="fas fa-calendar-day text-primary"></i> Monday
                                            </label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="input-group-compact">
                                                <label>Children:</label>
                                                <input type="number" class="form-control children-qty" name="mon_children" 
                                                       min="0" placeholder="0" required data-day="mon">
                                                <label>Pregnant:</label>
                                                <input type="number" class="form-control pregnant-qty" name="mon_pregnant" 
                                                       min="0" placeholder="0" data-day="mon">
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="day-total" id="mon-total">0 packets</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tuesday -->
                                <div class="day-input-group">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="day-label">
                                                <i class="fas fa-calendar-day text-success"></i> Tuesday
                                            </label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="input-group-compact">
                                                <label>Children:</label>
                                                <input type="number" class="form-control children-qty" name="tue_children" 
                                                       min="0" placeholder="0" required data-day="tue">
                                                <label>Pregnant:</label>
                                                <input type="number" class="form-control pregnant-qty" name="tue_pregnant" 
                                                       min="0" placeholder="0" data-day="tue">
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="day-total" id="tue-total">0 packets</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Wednesday -->
                                <div class="day-input-group">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="day-label">
                                                <i class="fas fa-calendar-day text-warning"></i> Wednesday
                                            </label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="input-group-compact">
                                                <label>Children:</label>
                                                <input type="number" class="form-control children-qty" name="wed_children" 
                                                       min="0" placeholder="0" required data-day="wed">
                                                <label>Pregnant:</label>
                                                <input type="number" class="form-control pregnant-qty" name="wed_pregnant" 
                                                       min="0" placeholder="0" data-day="wed">
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="day-total" id="wed-total">0 packets</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Thursday -->
                                <div class="day-input-group">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="day-label">
                                                <i class="fas fa-calendar-day text-info"></i> Thursday
                                            </label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="input-group-compact">
                                                <label>Children:</label>
                                                <input type="number" class="form-control children-qty" name="thu_children" 
                                                       min="0" placeholder="0" required data-day="thu">
                                                <label>Pregnant:</label>
                                                <input type="number" class="form-control pregnant-qty" name="thu_pregnant" 
                                                       min="0" placeholder="0" data-day="thu">
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="day-total" id="thu-total">0 packets</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Friday -->
                                <div class="day-input-group">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <label class="day-label">
                                                <i class="fas fa-calendar-day text-danger"></i> Friday
                                            </label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="input-group-compact">
                                                <label>Children:</label>
                                                <input type="number" class="form-control children-qty" name="fri_children" 
                                                       min="0" placeholder="0" required data-day="fri">
                                                <label>Pregnant:</label>
                                                <input type="number" class="form-control pregnant-qty" name="fri_pregnant" 
                                                       min="0" placeholder="0" data-day="fri">
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="day-total" id="fri-total">0 packets</span>
                                        </div>
                                    </div>
                                </div>
                                 
                                <!-- Remarks -->
                                <div class="mb-3 mt-4">
                                    <label class="form-label">Remarks (Optional)</label>
                                    <textarea class="form-control" name="remarks" rows="3" 
                                              placeholder="Any special instructions or remarks"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Sidebar -->
                    <div class="col-md-4">
                        <div class="summary-box">
                            <h5 class="mb-4"><i class="fas fa-calculator"></i> Order Summary</h5>
                            
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
                            
                            <button type="submit" class="btn btn-submit mt-4">
                                <i class="fas fa-paper-plane"></i> Submit Order
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate day total
        function updateDayTotal(day) {
            const children = parseInt(document.querySelector(`input[name="${day}_children"]`).value) || 0;
            const pregnant = parseInt(document.querySelector(`input[name="${day}_pregnant"]`).value) || 0;
            const total = children + pregnant;
            document.getElementById(`${day}-total`).textContent = total + ' packets';
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
        
        document.getElementById('weekStartDate').addEventListener('change', function() {
            updateSummary();
            // Ensure selected date is Monday
            const date = new Date(this.value);
            if (date.getDay() !== 1) {
                alert('Please select a Monday as week start date');
                this.value = '';
            }
        });
        
        // Form validation
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            let total = 0;
            document.querySelectorAll('.children-qty, .pregnant-qty').forEach(input => {
                total += parseInt(input.value) || 0;
            });
            
            if (total === 0) {
                e.preventDefault();
                alert('Please enter quantity for at least one day');
                return false;
            }
        });
        
        // Initialize
        ['mon', 'tue', 'wed', 'thu', 'fri'].forEach(day => {
            updateDayTotal(day);
        });
        updateSummary();
    </script>
</body>
</html>