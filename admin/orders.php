<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$userId = Auth::getUserId();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $action = $_POST['bulk_action'];
        $orderIds = $_POST['order_ids'] ?? [];
        
        if (empty($orderIds)) {
            $_SESSION['error'] = 'Please select at least one order';
        } else {
            $successCount = 0;
            foreach ($orderIds as $orderId) {
                if ($action === 'approve') {
                    if (updateOrderStatus($orderId, 'approved', $userId)) {
                        $successCount++;
                    }
                } elseif ($action === 'reject') {
                    if (updateOrderStatus($orderId, 'rejected', $userId)) {
                        $successCount++;
                    }
                }
            }
            $_SESSION['success'] = "$successCount order(s) $action" . "d successfully";
        }
        redirect(SITE_URL . '/admin/orders.php');
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = RECORDS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query
$db = getDB();
$whereConditions = [];
$params = [];
$types = "";

if ($statusFilter !== 'all') {
    $whereConditions[] = "wo.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(a.name LIKE ? OR a.aw_code LIKE ? OR u.name LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM weekly_orders wo 
             JOIN anganwadi a ON wo.anganwadi_id = a.id
             JOIN users u ON wo.user_id = u.id
             $whereClause";

$countStmt = $db->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalRecords / $limit);

// Get orders
$sql = "SELECT wo.*, a.name as anganwadi_name, a.aw_code, a.type as anganwadi_type,
               u.name as user_name, u.mobile as user_mobile,
               v.name as village_name, t.name as taluka_name, d.name as district_name
        FROM weekly_orders wo
        JOIN anganwadi a ON wo.anganwadi_id = a.id
        JOIN users u ON wo.user_id = u.id
        LEFT JOIN villages v ON a.village_id = v.id
        LEFT JOIN talukas t ON v.taluka_id = t.id
        LEFT JOIN districts d ON t.district_id = d.id
        $whereClause
        ORDER BY wo.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $db->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

$csrfToken = generateCSRFToken();
$pageTitle = "Order Management";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
            padding: 0;
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
            padding: 0;
        }
        
        .top-navbar {
            background: white;
            padding: 25px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .top-navbar h4 {
            color: #2d3748;
            font-weight: 600;
        }
        
        .content-area {
            padding: 30px;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(102, 126, 234, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .card-custom {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }
        
        .card-custom:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .badge-approved {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .badge-dispatched {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, #f7fafc, #eef2f7);
        }
        
        .table-custom th {
            font-weight: 600;
            color: #2d3748;
            font-size: 13px;
            padding: 15px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .table-custom td {
            padding: 15px;
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .table-custom tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-1px);
        }
        
        .status-badge-lg {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .form-control,
        .form-select {
            font-size: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(72, 187, 120, 0.1);
            color: #22543d;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(245, 101, 101, 0.1);
            color: #742a2a;
            border-left: 4px solid var(--danger-color);
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
                <i class="fas fa-clipboard-list me-2"></i> All Orders
            </a>
            <a href="anganwadi.php">
                <i class="fas fa-building me-2"></i> Anganwadi
            </a>
            <a href="users.php">
                <i class="fas fa-users me-2"></i> Users
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
            <h4 class="mb-0">Order Management</h4>
        </div>
        
        <div class="content-area">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="dispatched" <?php echo $statusFilter === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                            <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by anganwadi name, code, or user..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="card-custom">
                <div class="card-body p-0">
                    <form method="POST" id="bulkActionForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="p-3 border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list"></i> Orders List
                                        <span class="badge bg-primary"><?php echo $totalRecords; ?> total</span>
                                    </h5>
                                </div>
                                <div class="col-md-6 text-end">
                                    <select name="bulk_action" class="form-select d-inline-block w-auto">
                                        <option value="">Bulk Actions</option>
                                        <option value="approve">Approve Selected</option>
                                        <option value="reject">Reject Selected</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i> Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Order ID</th>
                                        <th>Anganwadi</th>
                                        <th>Location</th>
                                        <th>Week Period</th>
                                        <th>Packets</th>
                                        <th>Children</th>
                                        <th>Pregnant</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="11" class="text-center py-5">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No orders found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($order['status'] === 'pending'): ?>
                                                        <input type="checkbox" name="order_ids[]" 
                                                               value="<?php echo $order['id']; ?>" class="order-checkbox">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['anganwadi_name']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo $order['aw_code']; ?> | 
                                                        <?php echo ucfirst($order['anganwadi_type']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php echo $order['village_name']; ?>,<br>
                                                        <?php echo $order['taluka_name']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo formatDate($order['week_start_date']); ?><br>
                                                    <small class="text-muted">to <?php echo formatDate($order['week_end_date']); ?></small>
                                                </td>
                                                <td><strong><?php echo $order['total_qty']; ?></strong></td>
                                                <td><?php echo $order['children_allocation']; ?></td>
                                                <td><?php echo $order['pregnant_women_allocation']; ?></td>
                                                <td>
                                                    <span class="badge-<?php echo $order['status']; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo formatDate($order['created_at']); ?><br>
                                                    <small class="text-muted"><?php echo $order['user_name']; ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="approve-order.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary btn-action">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-sm btn-success btn-action"
                                                                    onclick="quickAction(<?php echo $order['id']; ?>, 'approve')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchQuery); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Quick action
        function quickAction(orderId, action) {
            if (confirm('Are you sure you want to ' + action + ' this order?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="bulk_action" value="${action}">
                    <input type="hidden" name="order_ids[]" value="${orderId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Validate bulk action
        document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
            const action = this.bulk_action.value;
            const checked = document.querySelectorAll('.order-checkbox:checked').length;
            
            if (!action) {
                e.preventDefault();
                alert('Please select a bulk action');
                return false;
            }
            
            if (checked === 0) {
                e.preventDefault();
                alert('Please select at least one order');
                return false;
            }
            
            if (!confirm(`Are you sure you want to ${action} ${checked} order(s)?`)) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>