<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

// AJAX Handler for Talukas - QUICK FIX
if (isset($_GET['get_talukas']) && isset($_GET['district_id'])) {
    header('Content-Type: application/json');
    $districtId = (int)$_GET['district_id'];
    
    if ($districtId > 0) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name FROM talukas WHERE district_id = ? AND status = 'active' ORDER BY name");
        $stmt->bind_param("i", $districtId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $talukas = [];
        while ($row = $result->fetch_assoc()) {
            $talukas[] = $row;
        }
        $stmt->close();
        echo json_encode($talukas);
    } else {
        echo json_encode([]);
    }
    exit;
}

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
            $talukaId = (int)$_POST['taluka_id'];
            $status = $_POST['status'] ?? 'active';
            
            if (empty($name) || empty($talukaId)) {
                $error = 'All fields are required';
            } else {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO villages (taluka_id, name, status) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $talukaId, $name, $status);
                    
                    if ($stmt->execute()) {
                        $success = 'Village created successfully!';
                        logActivity(Auth::getUserId(), 'CREATE_VILLAGE', 'villages', $db->insert_id);
                    } else {
                        $error = 'Failed to create village';
                    }
                    $stmt->close();
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $db->prepare("UPDATE villages SET taluka_id=?, name=?, status=? WHERE id=?");
                    $stmt->bind_param("issi", $talukaId, $name, $status, $id);
                    
                    if ($stmt->execute()) {
                        $success = 'Village updated successfully!';
                        logActivity(Auth::getUserId(), 'UPDATE_VILLAGE', 'villages', $id);
                    } else {
                        $error = 'Failed to update village';
                    }
                    $stmt->close();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE villages SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'Village deactivated successfully!';
                logActivity(Auth::getUserId(), 'DELETE_VILLAGE', 'villages', $id);
            } else {
                $error = 'Failed to deactivate village';
            }
            $stmt->close();
        }
    }
}

// Get all villages with district and taluka
$db = getDB();
$result = $db->query("
    SELECT v.*, t.name as taluka_name, d.name as district_name, t.district_id,
           (SELECT COUNT(*) FROM anganwadi WHERE village_id = v.id AND status = 'active') as anganwadi_count
    FROM villages v
    JOIN talukas t ON v.taluka_id = t.id
    JOIN districts d ON t.district_id = d.id
    WHERE v.status = 'active'
    ORDER BY d.name, t.name, v.name
");
$villages = [];
while ($row = $result->fetch_assoc()) {
    $villages[] = $row;
}

// Get districts for dropdown
$districtResult = $db->query("SELECT id, name FROM districts WHERE status = 'active' ORDER BY name");
$districts = [];
while ($row = $districtResult->fetch_assoc()) {
    $districts[] = $row;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Village Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f7fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { 
            min-height: 100vh; 
            max-height: 100vh;
            background: linear-gradient(135deg, #1a202c, #2d3748); 
            position: fixed; 
            width: 260px;
            overflow-y: auto;
            overflow-x: hidden;
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
        .sidebar-header { 
            padding: 25px 20px; 
            background: rgba(0,0,0,0.2); 
            color: white; 
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #cbd5e0; text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover,
        .sidebar-menu a.active { 
            background: linear-gradient(90deg, #667eea, transparent); 
            color: white;
            padding-left: 25px;
        }
        .sidebar-menu a i { margin-right: 12px; }
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
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="orders.php"><i class="fas fa-clipboard-list"></i> Orders</a>
            <a href="anganwadi.php"><i class="fas fa-building"></i> Anganwadi</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="routes.php"><i class="fas fa-route"></i> Routes</a>
            <a href="districts.php"><i class="fas fa-map-marked-alt"></i> Districts</a>
            <a href="talukas.php"><i class="fas fa-map-marker-alt"></i> Talukas</a>
            <a href="villages.php" class="active"><i class="fas fa-home"></i> Villages</a>
            <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">Village & Taluka Management</h4>
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
                        <h5>Villages List (<?php echo count($villages); ?>)</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#villageModal">
                            <i class="fas fa-plus"></i> Add Village
                        </button>
                    </div>
                    
                    <table id="villagesTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Village Name</th>
                                <th>Taluka</th>
                                <th>District</th>
                                <th>Anganwadis</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($villages as $village): ?>
                            <tr>
                                <td><?php echo $village['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($village['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($village['taluka_name']); ?></td>
                                <td><?php echo htmlspecialchars($village['district_name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $village['anganwadi_count']; ?> Centers</span></td>
                                <td><?php echo formatDate($village['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editVillage(<?php echo json_encode($village); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteVillage(<?php echo $village['id']; ?>)">
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
    
    <!-- Village Modal -->
    <div class="modal fade" id="villageModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="villageForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Village</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="villageId">
                        
                        <div class="mb-3">
                            <label class="form-label">District *</label>
                            <select class="form-select" id="districtSelect" required>
                                <option value="">Select District</option>
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Taluka *</label>
                            <select class="form-select" name="taluka_id" id="talukaSelect" required>
                                <option value="">Select District First</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Village Name *</label>
                            <input type="text" class="form-control" name="name" id="villageName" 
                                   placeholder="Enter village name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="villageStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Village</button>
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
            $('#villagesTable').DataTable();
            
            // Load talukas when district changes - FIXED
            $('#districtSelect').change(function() {
                const districtId = $(this).val();
                const talukaSelect = $('#talukaSelect');
                
                talukaSelect.html('<option value="">Loading...</option>');
                
                if (districtId) {
                    $.ajax({
                        url: 'villages.php?get_talukas=1',
                        type: 'GET',
                        data: { district_id: districtId },
                        dataType: 'json',
                        success: function(data) {
                            console.log('Talukas loaded:', data);
                            let options = '<option value="">Select Taluka</option>';
                            if (data && data.length > 0) {
                                data.forEach(t => {
                                    options += `<option value="${t.id}">${t.name}</option>`;
                                });
                            } else {
                                options = '<option value="">No talukas found</option>';
                            }
                            talukaSelect.html(options);
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            console.error('Status:', status);
                            console.error('Response:', xhr.responseText);
                            talukaSelect.html('<option value="">Error loading talukas</option>');
                            alert('Failed to load talukas. Check console for details.');
                        }
                    });
                } else {
                    talukaSelect.html('<option value="">Select District First</option>');
                }
            });
        });
        
        function editVillage(village) {
            $('#modalTitle').text('Edit Village');
            $('#formAction').val('update');
            $('#villageId').val(village.id);
            $('#villageName').val(village.name);
            $('#villageStatus').val(village.status);
            
            // Set district and load talukas
            $('#districtSelect').val(village.district_id).trigger('change');
            
            // Wait for talukas to load, then select the correct one
            setTimeout(() => {
                $('#talukaSelect').val(village.taluka_id);
            }, 500);
            
            $('#villageModal').modal('show');
        }
        
        function deleteVillage(id) {
            if (confirm('Are you sure you want to deactivate this village?')) {
                $('#deleteId').val(id);
                $('#deleteForm').submit();
            }
        }
        
        $('#villageModal').on('hidden.bs.modal', function() {
            $('#villageForm')[0].reset();
            $('#modalTitle').text('Add Village');
            $('#formAction').val('create');
            $('#villageId').val('');
            $('#talukaSelect').html('<option value="">Select District First</option>');
        });
    </script>
</body>
</html>