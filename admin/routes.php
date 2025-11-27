<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$error = '';
$success = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $db = getDB();
        
        if ($action === 'create' || $action === 'update') {
            $routeNumber = sanitize($_POST['route_number']);
            $routeName = sanitize($_POST['route_name']);
            $vehicleNumber = sanitize($_POST['vehicle_number']);
            $driverName = sanitize($_POST['driver_name']);
            $driverMobile = sanitize($_POST['driver_mobile']);
            $status = $_POST['status'] ?? 'active';
            
            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO routes (route_number, route_name, vehicle_number, driver_name, driver_mobile, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $routeNumber, $routeName, $vehicleNumber, $driverName, $driverMobile, $status);
                
                if ($stmt->execute()) {
                    $success = 'Route created successfully!';
                    logActivity(Auth::getUserId(), 'CREATE_ROUTE', 'routes', $db->insert_id);
                } else {
                    $error = 'Failed to create route';
                }
                $stmt->close();
            } else {
                $id = (int)$_POST['id'];
                $stmt = $db->prepare("UPDATE routes SET route_number=?, route_name=?, vehicle_number=?, driver_name=?, driver_mobile=?, status=? WHERE id=?");
                $stmt->bind_param("ssssssi", $routeNumber, $routeName, $vehicleNumber, $driverName, $driverMobile, $status, $id);
                
                if ($stmt->execute()) {
                    $success = 'Route updated successfully!';
                    logActivity(Auth::getUserId(), 'UPDATE_ROUTE', 'routes', $id);
                } else {
                    $error = 'Failed to update route';
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE routes SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'Route deactivated successfully!';
                logActivity(Auth::getUserId(), 'DELETE_ROUTE', 'routes', $id);
            } else {
                $error = 'Failed to deactivate route';
            }
            $stmt->close();
        }
    }
}

// Get all routes
$db = getDB();
$result = $db->query("
    SELECT r.*, 
           (SELECT COUNT(*) FROM anganwadi WHERE route_id = r.id AND status = 'active') as anganwadi_count 
    FROM routes r 
    WHERE r.status = 'active' 
    ORDER BY r.route_number
");
$routes = [];
while ($row = $result->fetch_assoc()) {
    $routes[] = $row;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f7fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #1a202c, #2d3748); position: fixed; width: 260px; }
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.2); color: white; text-align: center; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #cbd5e0; text-decoration: none; }
        .sidebar-menu a.active { background: linear-gradient(90deg, #667eea, transparent); color: white; }
        .main-content { margin-left: 260px; }
        .top-navbar { background: white; padding: 20px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: none; }
        .route-card { background: #f7fafc; padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 2px solid #e2e8f0; }
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
            <a href="routes.php" class="active"><i class="fas fa-route me-2"></i> Routes</a>
            <a href="districts.php"><i class="fas fa-map-marked-alt me-2"></i> Districts</a>
            <a href="reports.php"><i class="fas fa-file-alt me-2"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">Route Management</h4>
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
            
            <div class="card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Routes List (<?php echo count($routes); ?>)</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#routeModal">
                            <i class="fas fa-plus"></i> Add Route
                        </button>
                    </div>
                    
                    <table id="routesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Route No</th>
                                <th>Route Name</th>
                                <th>Vehicle</th>
                                <th>Driver Name</th>
                                <th>Driver Mobile</th>
                                <th>Anganwadis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><strong><?php echo $route['route_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                                <td><?php echo $route['vehicle_number']; ?></td>
                                <td><?php echo htmlspecialchars($route['driver_name']); ?></td>
                                <td><?php echo $route['driver_mobile']; ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $route['anganwadi_count']; ?> Centers</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editRoute(<?php echo json_encode($route); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteRoute(<?php echo $route['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Route Modal -->
    <div class="modal fade" id="routeModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="routeForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Route</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="routeId">
                        
                        <div class="mb-3">
                            <label class="form-label">Route Number *</label>
                            <input type="text" class="form-control" name="route_number" id="routeNumber" 
                                   placeholder="R001" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Route Name *</label>
                            <input type="text" class="form-control" name="route_name" id="routeName" 
                                   placeholder="Pune East Route" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" name="vehicle_number" id="vehicleNumber" 
                                   placeholder="MH-12-AB-1234">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Driver Name</label>
                            <input type="text" class="form-control" name="driver_name" id="driverName" 
                                   placeholder="Driver name">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Driver Mobile</label>
                            <input type="text" class="form-control" name="driver_mobile" id="driverMobile" 
                                   maxlength="10" placeholder="9876543210">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="routeStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Route</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form method="POST" id="deleteForm" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#routesTable').DataTable();
        });
        
        function editRoute(route) {
            $('#modalTitle').text('Edit Route');
            $('#formAction').val('update');
            $('#routeId').val(route.id);
            $('#routeNumber').val(route.route_number);
            $('#routeName').val(route.route_name);
            $('#vehicleNumber').val(route.vehicle_number);
            $('#driverName').val(route.driver_name);
            $('#driverMobile').val(route.driver_mobile);
            $('#routeStatus').val(route.status);
            $('#routeModal').modal('show');
        }
        
        function deleteRoute(id) {
            if (confirm('Are you sure you want to deactivate this route?')) {
                $('#deleteId').val(id);
                $('#deleteForm').submit();
            }
        }
        
        $('#routeModal').on('hidden.bs.modal', function() {
            $('#routeForm')[0].reset();
            $('#modalTitle').text('Add Route');
            $('#formAction').val('create');
            $('#routeId').val('');
        });
    </script>
</body>
</html>