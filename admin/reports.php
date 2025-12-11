<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$error = '';
$success = '';

// Get data for dropdowns
$anganwadis = getAnganwadiList(['status' => 'active']);
$districts = getDistricts();
$routes = getRoutesList();

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, #f7fafc 0%, #eef2f7 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .sidebar {
            min-height: 100vh;
            max-height: 100vh;
            background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
            position: fixed;
            width: 260px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.2);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            border-bottom: 2px solid rgba(102, 126, 234, 0.3);
        }
        
        .sidebar-header i {
            color: var(--primary-color);
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .sidebar-menu a:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), transparent);
            color: white;
            padding-left: 25px;
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar-menu a.active {
            background: linear-gradient(90deg, var(--primary-color), rgba(102, 126, 234, 0.1));
            color: white;
            border-left: 3px solid var(--primary-color);
            padding-left: 25px;
        }
        
        .sidebar-menu a[href="../logout.php"]:hover {
            background: linear-gradient(90deg, rgba(245, 101, 101, 0.2), transparent) !important;
            border-left-color: var(--danger-color) !important;
        }
        
        .sidebar-menu a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 260px;
        }
        
        .top-navbar {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
        
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s;
            min-height: 380px;
            display: flex;
            flex-direction: column;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .report-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .icon-purple { background: linear-gradient(135deg, #667eea, #764ba2); }
        .icon-green { background: linear-gradient(135deg, #56ab2f, #a8e063); }
        .icon-orange { background: linear-gradient(135deg, #f79d00, #64f38c); }
        .icon-blue { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <h5 class="mb-0">Admin Panel</h5>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
            <a href="orders.php"><i class="fas fa-clipboard-list me-2"></i> Orders</a>
            <a href="anganwadi.php"><i class="fas fa-building me-2"></i> Anganwadi</a>
            <a href="users.php"><i class="fas fa-users me-2"></i> Users</a>
            <a href="reports.php" class="active"><i class="fas fa-file-alt me-2"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">Reports & Analytics</h4>
        </div>
        
        <div class="content-area">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Report Generation Cards -->
            <h5 class="mb-3">Generate Reports</h5>
            <div class="row">
                <!-- Daily Dispatch Sheet -->
                <div class="col-md-6 mb-4">
                    <div class="report-card">
                        <div class="report-icon icon-purple">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h5>Daily Dispatch Sheet</h5>
                        <p class="text-muted mb-3">Generate route-wise daily dispatch sheets with delivery checklist</p>
                        
                        <form method="GET" action="generate_daily_report.php" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Select Date</label>
                                <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Route</label>
                                <select class="form-select" name="route_id">
                                    <option value="">All Routes</option>
                                    <?php foreach ($routes as $r): ?>
                                        <option value="<?php echo $r['id']; ?>">
                                            <?php echo $r['route_number']; ?> - <?php echo $r['route_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-file-pdf"></i> Generate PDF
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Weekly Summary -->
                <div class="col-md-6 mb-4">
                    <div class="report-card">
                        <div class="report-icon icon-green">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <h5>Weekly Summary Report</h5>
                        <p class="text-muted mb-3">Consolidated weekly orders with anganwadi-wise breakdown</p>
                        
                        <form method="GET" action="generate_weekly_report.php" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Week Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d', strtotime('monday this week')); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Format</label>
                                <select class="form-select" name="format">
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-download"></i> Generate Report
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Monthly Dispatch Bill Report -->
                <div class="col-md-6 mb-4">
                    <div class="report-card">
                        <div class="report-icon icon-orange">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h5>Monthly Dispatch Bill Report</h5>
                        <p class="text-muted mb-3">Complete monthly bill with GST calculation</p>
                        
                        <a href="monthly_dispatch_bill.php" target="_blank" class="btn btn-primary w-100">
                            <i class="fas fa-file-invoice"></i> Open Report Generator
                        </a>
                    </div>
                </div>
                
                <!-- Rate-wise Order Report -->
                <div class="col-md-6 mb-4">
                    <div class="report-card">
                        <div class="report-icon icon-blue">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h5>Rate-wise Order Report</h5>
                        <p class="text-muted mb-3">View all orders for a specific time period and rate</p>

                        <form method="GET" action="rate_wise_orders.php" target="_blank">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rate per Packet (₹)</label>
                                <input type="number" class="form-control" name="rate" step="0.01" min="0" required placeholder="e.g., 12.00">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-list"></i> View Orders
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Anganwadi-wise Report -->
                <div class="col-md-6 mb-4">
                    <div class="report-card">
                        <div class="report-icon icon-blue">
                            <i class="fas fa-building"></i>
                        </div>
                        <h5>Anganwadi-wise Report</h5>
                        <p class="text-muted mb-3">Detailed consumption report for specific anganwadi/school</p>

                        <form method="GET" action="generate_anganwadi_report.php" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">Select Anganwadi</label>
                                <select class="form-select" name="anganwadi_id" required>
                                    <option value="">Select Anganwadi</option>
                                    <?php foreach ($anganwadis as $aw): ?>
                                        <option value="<?php echo $aw['id']; ?>">
                                            <?php echo $aw['aw_code']; ?> - <?php echo $aw['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-file-invoice"></i> Generate Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Orders Summary Card -->
            <div class="card-custom">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-chart-bar text-primary"></i> Recent Orders Summary</h5>

                    <?php
                    // Get recent orders summary
                    $db = getDB();
                    $stmt = $db->prepare("
                        SELECT
                            DATE(o.delivery_date) as order_date,
                            COUNT(o.id) as total_orders,
                            SUM(o.quantity * 2) as total_quantity,
                            SUM(o.total_price) as total_amount
                        FROM orders o
                        WHERE o.delivery_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(o.delivery_date)
                        ORDER BY order_date DESC
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $recentOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    ?>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Orders</th>
                                <th>Quantity (Packets)</th>
                                <th>Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No orders found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo $order['total_orders']; ?></td>
                                    <td><?php echo number_format($order['total_quantity'], 2); ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Navsari District Data Card -->
            <div class="card-custom">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-map-marker-alt text-success"></i> Navsari District Data (Villages, Schools & Anganwadis)</h5>

                    <?php
                    // Get Navsari district data
                    $db = getDB();
                    $stmt = $db->prepare("
                        SELECT
                            d.name as district,
                            t.name as taluka,
                            v.name as village,
                            a.aw_code,
                            a.name as anganwadi_name,
                            a.type,
                            a.contact_person,
                            a.mobile,
                            a.total_children,
                            a.pregnant_women
                        FROM districts d
                        JOIN talukas t ON d.id = t.district_id
                        JOIN villages v ON t.id = v.taluka_id
                        JOIN anganwadi a ON v.id = a.village_id
                        WHERE d.name = 'Navsari' AND a.status = 'active'
                        ORDER BY d.name, t.name, v.name, a.name
                    ");
                    $stmt->execute();
                    $navsariData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>District</th>
                                    <th>Taluka</th>
                                    <th>Village</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Contact Person</th>
                                    <th>Mobile</th>
                                    <th>Children</th>
                                    <th>Pregnant Women</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($navsariData)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No data found for Navsari district</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($navsariData as $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['district']); ?></td>
                                        <td><?php echo htmlspecialchars($data['taluka']); ?></td>
                                        <td><?php echo htmlspecialchars($data['village']); ?></td>
                                        <td><?php echo htmlspecialchars($data['aw_code']); ?></td>
                                        <td><?php echo htmlspecialchars($data['anganwadi_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $data['type'] == 'anganwadi' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($data['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($data['contact_person'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($data['mobile'] ?: '-'); ?></td>
                                        <td><?php echo $data['total_children'] ?: '-'; ?></td>
                                        <td><?php echo $data['pregnant_women'] ?: '-'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-muted">
                        <small>Total Records: <?php echo count($navsariData); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>