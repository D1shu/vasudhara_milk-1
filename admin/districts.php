<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$error = '';
$success = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $db = getDB();
        
        if ($action === 'create' || $action === 'update') {
            $name = sanitize($_POST['name']);
            $status = $_POST['status'] ?? 'active';
            
            if (empty($name)) {
                $error = 'District name is required';
            } else {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO districts (name, status) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $status);
                    
                    if ($stmt->execute()) {
                        $success = 'District created successfully!';
                        logActivity(Auth::getUserId(), 'CREATE_DISTRICT', 'districts', $db->insert_id);
                    } else {
                        $error = 'Failed to create district';
                    }
                    $stmt->close();
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $db->prepare("UPDATE districts SET name=?, status=? WHERE id=?");
                    $stmt->bind_param("ssi", $name, $status, $id);
                    
                    if ($stmt->execute()) {
                        $success = 'District updated successfully!';
                        logActivity(Auth::getUserId(), 'UPDATE_DISTRICT', 'districts', $id);
                    } else {
                        $error = 'Failed to update district';
                    }
                    $stmt->close();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE districts SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'District deactivated successfully!';
                logActivity(Auth::getUserId(), 'DELETE_DISTRICT', 'districts', $id);
            } else {
                $error = 'Failed to deactivate district';
            }
            $stmt->close();
        }
    }
}

// Get all districts with counts
$db = getDB();
$result = $db->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM talukas WHERE district_id = d.id AND status = 'active') as taluka_count,
           (SELECT COUNT(*) FROM villages v 
            JOIN talukas t ON v.taluka_id = t.id 
            WHERE t.district_id = d.id AND v.status = 'active') as village_count
    FROM districts d 
    WHERE d.status = 'active' 
    ORDER BY d.name
");
$districts = [];
while ($row = $result->fetch_assoc()) {
    $districts[] = $row;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District Management - <?php echo SITE_NAME; ?></title>
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
            <a href="routes.php"><i class="fas fa-route me-2"></i> Routes</a>
            <a href="districts.php" class="active"><i class="fas fa-map-marked-alt me-2"></i> Districts</a>
            <a href="villages.php"><i class="fas fa-home me-2"></i> Villages</a>
            <a href="reports.php"><i class="fas fa-file-alt me-2"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">District Management</h4>
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
                        <h5>Districts List (<?php echo count($districts); ?>)</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#districtModal">
                            <i class="fas fa-plus"></i> Add District
                        </button>
                    </div>
                    
                    <table id="districtsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>District Name</th>
                                <th>Talukas</th>
                                <th>Villages</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($districts as $district): ?>
                            <tr>
                                <td><?php echo $district['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($district['name']); ?></strong></td>
                                <td><span class="badge bg-primary"><?php echo $district['taluka_count']; ?> Talukas</span></td>
                                <td><span class="badge bg-info"><?php echo $district['village_count']; ?> Villages</span></td>
                                <td><?php echo formatDate($district['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editDistrict(<?php echo json_encode($district); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDistrict(<?php echo $district['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="talukas.php?district=<?php echo $district['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View Talukas
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- District Modal -->
    <div class="modal fade" id="districtModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="districtForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add District</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="districtId">
                        
                        <div class="mb-3">
                            <label class="form-label">District Name *</label>
                            <input type="text" class="form-control" name="name" id="districtName" 
                                   placeholder="Enter district name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="districtStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save District</button>
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
            $('#districtsTable').DataTable();
        });
        
        function editDistrict(district) {
            $('#modalTitle').text('Edit District');
            $('#formAction').val('update');
            $('#districtId').val(district.id);
            $('#districtName').val(district.name);
            $('#districtStatus').val(district.status);
            $('#districtModal').modal('show');
        }
        
        function deleteDistrict(id) {
            if (confirm('Are you sure you want to deactivate this district?')) {
                $('#deleteId').val(id);
                $('#deleteForm').submit();
            }
        }
        
        $('#districtModal').on('hidden.bs.modal', function() {
            $('#districtForm')[0].reset();
            $('#modalTitle').text('Add District');
            $('#formAction').val('create');
            $('#districtId').val('');
        });
    </script>
</body>
</html>