<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$error = '';
$success = '';

// Get district filter
$districtFilter = isset($_GET['district']) ? (int)$_GET['district'] : 0;

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $db = getDB();
        
        if ($action === 'create' || $action === 'update') {
            $district_id = (int)$_POST['district_id'];
            $name = sanitize($_POST['name']);
            $status = $_POST['status'] ?? 'active';
            
            if (empty($name)) {
                $error = 'Taluka name is required';
            } elseif ($district_id <= 0) {
                $error = 'Please select a district';
            } else {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO talukas (district_id, name, status) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $district_id, $name, $status);
                    
                    if ($stmt->execute()) {
                        $success = 'Taluka created successfully!';
                        logActivity(Auth::getUserId(), 'CREATE_TALUKA', 'talukas', $db->insert_id);
                    } else {
                        $error = 'Failed to create taluka';
                    }
                    $stmt->close();
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $db->prepare("UPDATE talukas SET district_id=?, name=?, status=? WHERE id=?");
                    $stmt->bind_param("issi", $district_id, $name, $status, $id);
                    
                    if ($stmt->execute()) {
                        $success = 'Taluka updated successfully!';
                        logActivity(Auth::getUserId(), 'UPDATE_TALUKA', 'talukas', $id);
                    } else {
                        $error = 'Failed to update taluka';
                    }
                    $stmt->close();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE talukas SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'Taluka deactivated successfully!';
                logActivity(Auth::getUserId(), 'DELETE_TALUKA', 'talukas', $id);
            } else {
                $error = 'Failed to deactivate taluka';
            }
            $stmt->close();
        }
    }
}

// Get all districts for dropdown
$db = getDB();
$districtResult = $db->query("SELECT id, name FROM districts WHERE status = 'active' ORDER BY name");
$allDistricts = [];
while ($row = $districtResult->fetch_assoc()) {
    $allDistricts[] = $row;
}

// Get all talukas with district info and counts
$query = "
    SELECT t.*, 
           d.name as district_name,
           (SELECT COUNT(*) FROM villages WHERE taluka_id = t.id AND status = 'active') as village_count
    FROM talukas t 
    JOIN districts d ON t.district_id = d.id
    WHERE t.status = 'active'
";

if ($districtFilter > 0) {
    $query .= " AND t.district_id = " . $districtFilter;
}

$query .= " ORDER BY d.name, t.name";

$result = $db->query($query);
$talukas = [];
while ($row = $result->fetch_assoc()) {
    $talukas[] = $row;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taluka Management - <?php echo SITE_NAME; ?></title>
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
        .filter-card { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
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
            <a href="districts.php"><i class="fas fa-map-marked-alt me-2"></i> Districts</a>
            <a href="talukas.php" class="active"><i class="fas fa-map-marker-alt me-2"></i> Talukas</a>
            <a href="villages.php"><i class="fas fa-home me-2"></i> Villages</a>
            <a href="reports.php"><i class="fas fa-file-alt me-2"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Taluka Management</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="districts.php">Districts</a></li>
                        <li class="breadcrumb-item active">Talukas</li>
                    </ol>
                </nav>
            </div>
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
            
            <!-- Filter Section -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by District</label>
                        <select name="district" class="form-select" onchange="this.form.submit()">
                            <option value="0">All Districts</option>
                            <?php foreach ($allDistricts as $dist): ?>
                                <option value="<?php echo $dist['id']; ?>" 
                                    <?php echo ($districtFilter == $dist['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dist['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <?php if ($districtFilter > 0): ?>
                            <a href="talukas.php" class="btn btn-secondary me-2">
                                <i class="fas fa-times"></i> Clear Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="card-custom">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Talukas List (<?php echo count($talukas); ?>)</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#talukaModal">
                            <i class="fas fa-plus"></i> Add Taluka
                        </button>
                    </div>
                    
                    <table id="talukasTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>District</th>
                                <th>Taluka Name</th>
                                <th>Villages</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($talukas as $taluka): ?>
                            <tr>
                                <td><?php echo $taluka['id']; ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($taluka['district_name']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($taluka['name']); ?></strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $taluka['village_count']; ?> Villages
                                    </span>
                                </td>
                                <td><?php echo formatDate($taluka['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editTaluka(<?php echo json_encode($taluka); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteTaluka(<?php echo $taluka['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <a href="villages.php?taluka=<?php echo $taluka['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View Villages
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
    
    <!-- Taluka Modal -->
    <div class="modal fade" id="talukaModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="talukaForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Taluka</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="talukaId">
                        
                        <div class="mb-3">
                            <label class="form-label">District *</label>
                            <select class="form-select" name="district_id" id="districtId" required>
                                <option value="">Select District</option>
                                <?php foreach ($allDistricts as $dist): ?>
                                    <option value="<?php echo $dist['id']; ?>">
                                        <?php echo htmlspecialchars($dist['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Taluka Name *</label>
                            <input type="text" class="form-control" name="name" id="talukaName" 
                                   placeholder="Enter taluka name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="talukaStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Taluka</button>
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
            $('#talukasTable').DataTable({
                order: [[1, 'asc'], [2, 'asc']]
            });
            
            // Auto-select district if filter is applied
            <?php if ($districtFilter > 0): ?>
                $('#districtId').val(<?php echo $districtFilter; ?>);
            <?php endif; ?>
        });
        
        function editTaluka(taluka) {
            $('#modalTitle').text('Edit Taluka');
            $('#formAction').val('update');
            $('#talukaId').val(taluka.id);
            $('#districtId').val(taluka.district_id);
            $('#talukaName').val(taluka.name);
            $('#talukaStatus').val(taluka.status);
            $('#talukaModal').modal('show');
        }
        
        function deleteTaluka(id) {
            if (confirm('Are you sure you want to deactivate this taluka?')) {
                $('#deleteId').val(id);
                $('#deleteForm').submit();
            }
        }
        
        $('#talukaModal').on('hidden.bs.modal', function() {
            $('#talukaForm')[0].reset();
            $('#modalTitle').text('Add Taluka');
            $('#formAction').val('create');
            $('#talukaId').val('');
            
            // Re-apply district filter if exists
            <?php if ($districtFilter > 0): ?>
                $('#districtId').val(<?php echo $districtFilter; ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>