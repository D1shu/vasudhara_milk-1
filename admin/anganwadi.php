<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

// AJAX Handler for Talukas
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

// AJAX Handler for Villages
if (isset($_GET['get_villages']) && isset($_GET['taluka_id'])) {
    header('Content-Type: application/json');
    $talukaId = (int)$_GET['taluka_id'];
    
    if ($talukaId > 0) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name FROM villages WHERE taluka_id = ? AND status = 'active' ORDER BY name");
        $stmt->bind_param("i", $talukaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $villages = [];
        while ($row = $result->fetch_assoc()) {
            $villages[] = $row;
        }
        $stmt->close();
        echo json_encode($villages);
    } else {
        echo json_encode([]);
    }
    exit;
}

$error = '';
$success = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'create' || $action === 'update') {
            $villageId = (int)$_POST['village_id'];
            $routeId = !empty($_POST['route_id']) ? (int)$_POST['route_id'] : null;
            $awCode = sanitize($_POST['aw_code']);
            $name = sanitize($_POST['name']);
            $type = sanitize($_POST['type']);
            $totalChildren = (int)$_POST['total_children'];
            $pregnantWomen = (int)$_POST['pregnant_women'];
            $contactPerson = sanitize($_POST['contact_person']);
            $mobile = sanitize($_POST['mobile']);
            $email = sanitize($_POST['email']);
            $address = sanitize($_POST['address']);
            
            $db = getDB();
            
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO anganwadi (village_id, route_id, aw_code, name, type, total_children, 
                                          pregnant_women, contact_person, mobile, email, address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iisssiissss", $villageId, $routeId, $awCode, $name, $type, $totalChildren, 
                                  $pregnantWomen, $contactPerson, $mobile, $email, $address);
                
                if ($stmt->execute()) {
                    $success = 'Anganwadi created successfully!';
                    logActivity(Auth::getUserId(), 'CREATE_ANGANWADI', 'anganwadi', $db->insert_id);
                } else {
                    $error = 'Failed to create anganwadi';
                }
                $stmt->close();
            } else {
                $id = (int)$_POST['id'];
                $stmt = $db->prepare("
                    UPDATE anganwadi 
                    SET village_id=?, route_id=?, aw_code=?, name=?, type=?, total_children=?, 
                        pregnant_women=?, contact_person=?, mobile=?, email=?, address=?
                    WHERE id=?
                ");
                $stmt->bind_param("iisssiissssi", $villageId, $routeId, $awCode, $name, $type, $totalChildren, 
                                  $pregnantWomen, $contactPerson, $mobile, $email, $address, $id);
                
                if ($stmt->execute()) {
                    $success = 'Anganwadi updated successfully!';
                    logActivity(Auth::getUserId(), 'UPDATE_ANGANWADI', 'anganwadi', $id);
                } else {
                    $error = 'Failed to update anganwadi';
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $db = getDB();
            $stmt = $db->prepare("UPDATE anganwadi SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'Anganwadi deactivated successfully!';
                logActivity(Auth::getUserId(), 'DELETE_ANGANWADI', 'anganwadi', $id);
            } else {
                $error = 'Failed to deactivate anganwadi';
            }
            $stmt->close();
        }
    }
}

// Get all data for lists
$districts = getDistricts();
$routes = getRoutesList();
$anganwadis = getAnganwadiList(['status' => 'active']);

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anganwadi Management - <?php echo SITE_NAME; ?></title>
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
        
        .card-custom {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(102, 126, 234, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .card-custom:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .card-body {
            padding: 25px;
        }
        
        .card-body h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 20px;
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
        
        .table {
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #f7fafc, #eef2f7);
            color: #2d3748;
            font-weight: 600;
            border: 1px solid rgba(102, 126, 234, 0.1);
            padding: 15px;
        }
        
        .table tbody td {
            vertical-align: middle;
            border: 1px solid rgba(0,0,0,0.05);
            padding: 12px 15px;
        }
        
        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
            transition: all 0.2s ease;
        }
        
        .btn-sm:hover {
            transform: translateY(-1px);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body h6 {
            font-weight: 600;
            color: var(--primary-color);
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-body h6:first-of-type {
            margin-top: 0;
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
        
        .form-control::placeholder {
            color: #cbd5e0;
            font-size: 13px;
        }
        
        .border-bottom {
            border-color: #e2e8f0 !important;
            padding-bottom: 12px;
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
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-2x mb-2"></i>
            <h5 class="mb-0">Admin Panel</h5>
        </div>
        
        <div class="sidebar-menu">
<a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="orders.php"><i class="fas fa-clipboard-list"></i> Orders</a>
            <a href="anganwadi.php"  class="active"><i class="fas fa-building"></i> Anganwadi</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="routes.php"><i class="fas fa-route"></i> Routes</a>
            <a href="districts.php"><i class="fas fa-map-marked-alt"></i> Districts</a>
            <a href="talukas.php" ><i class="fas fa-map-marker-alt"></i> Talukas</a>
            <a href="villages.php" ><i class="fas fa-home"></i> Villages</a>
            <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">Anganwadi/School Management</h4>
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
                        <h5>Anganwadi List (<?php echo count($anganwadis); ?>)</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </div>
                    
                    <table id="anganwadiTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Children</th>
                                <th>Pregnant</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anganwadis as $aw): ?>
                            <tr>
                                <td><?php echo $aw['aw_code']; ?></td>
                                <td><?php echo htmlspecialchars($aw['name']); ?></td>
                                <td><?php echo ucfirst($aw['type']); ?></td>
                                <td><?php echo $aw['village_name']; ?>, <?php echo $aw['taluka_name']; ?></td>
                                <td><?php echo $aw['total_children']; ?></td>
                                <td><?php echo $aw['pregnant_women']; ?></td>
                                <td><?php echo $aw['mobile']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick='editAnganwadi(<?php echo json_encode($aw); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteAnganwadi(<?php echo $aw['id']; ?>)">
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
    
    <!-- Add/Edit Modal - PROFESSIONAL LAYOUT -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="anganwadiForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Anganwadi/School</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="anganwadiId">
                        
                        <!-- Basic Information -->
                        <h6 class="mb-3 text-primary border-bottom pb-2">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-semibold">Name *</label>
                                <input type="text" class="form-control" name="name" id="name" 
                                       placeholder="Enter anganwadi/school name" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Code *</label>
                                <input type="text" class="form-control" name="aw_code" id="awCode" 
                                       placeholder="e.g., AW001" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Type *</label>
                                <select class="form-select" name="type" id="type" required>
                                    <option value="">Select Type</option>
                                    <option value="anganwadi">Anganwadi</option>
                                    <option value="school">School</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Assigned Route</label>
                                <select class="form-select" name="route_id" id="route">
                                    <option value="">Select Route (Optional)</option>
                                    <?php foreach ($routes as $r): ?>
                                        <option value="<?php echo $r['id']; ?>">
                                            <?php echo $r['route_number']; ?> - <?php echo $r['route_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Location Information -->
                        <h6 class="mb-3 mt-4 text-primary border-bottom pb-2">
                            <i class="fas fa-map-marker-alt"></i> Location Details
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">District *</label>
                                <select class="form-select" id="district" required>
                                    <option value="">Select District</option>
                                    <?php foreach ($districts as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Taluka *</label>
                                <select class="form-select" id="taluka" required>
                                    <option value="">Select District First</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Village *</label>
                                <select class="form-select" name="village_id" id="village" required>
                                    <option value="">Select Taluka First</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Complete Address</label>
                                <textarea class="form-control" name="address" id="address" rows="2"
                                          placeholder="Enter street address, landmarks, etc."></textarea>
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <h6 class="mb-3 mt-4 text-primary border-bottom pb-2">
                            <i class="fas fa-address-book"></i> Contact Information
                        </h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold">Contact Person Name *</label>
                                <input type="text" class="form-control" name="contact_person" 
                                       id="contactPerson" placeholder="Enter full name" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Mobile Number *</label>
                                <input type="text" class="form-control" name="mobile" id="mobile" 
                                       maxlength="10" pattern="[6-9][0-9]{9}" 
                                       placeholder="10-digit mobile number" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" name="email" id="email"
                                       placeholder="email@example.com (optional)">
                            </div>
                        </div>
                        
                        <!-- Beneficiary Information -->
                        <h6 class="mb-3 mt-4 text-primary border-bottom pb-2">
                            <i class="fas fa-users"></i> Beneficiary Statistics
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Total Children</label>
                                <input type="number" class="form-control" name="total_children" 
                                       id="totalChildren" value="0" min="0" placeholder="Number of children">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Pregnant Women</label>
                                <input type="number" class="form-control" name="pregnant_women" 
                                       id="pregnantWomen" value="0" min="0" placeholder="Number of pregnant women">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Anganwadi
                        </button>
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
            $('#anganwadiTable').DataTable();
            
            // Load talukas based on district
            $('#district').change(function() {
                const districtId = $(this).val();
                $('#taluka').html('<option value="">Loading...</option>');
                $('#village').html('<option value="">Select Village</option>');
                
                if (districtId) {
                    $.ajax({
                        url: 'anganwadi.php?get_talukas=1',
                        type: 'GET',
                        data: { district_id: districtId },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">Select Taluka</option>';
                            if (data && data.length > 0) {
                                data.forEach(t => options += `<option value="${t.id}">${t.name}</option>`);
                            } else {
                                options = '<option value="">No talukas found</option>';
                            }
                            $('#taluka').html(options);
                        },
                        error: function(xhr, status, error) {
                            console.error('Taluka Error:', error);
                            $('#taluka').html('<option value="">Error loading talukas</option>');
                        }
                    });
                } else {
                    $('#taluka').html('<option value="">Select Taluka</option>');
                }
            });
            
            // Load villages based on taluka
            $('#taluka').change(function() {
                const talukaId = $(this).val();
                $('#village').html('<option value="">Loading...</option>');
                
                if (talukaId) {
                    $.ajax({
                        url: 'anganwadi.php?get_villages=1',
                        type: 'GET',
                        data: { taluka_id: talukaId },
                        dataType: 'json',
                        success: function(data) {
                            let options = '<option value="">Select Village</option>';
                            if (data && data.length > 0) {
                                data.forEach(v => options += `<option value="${v.id}">${v.name}</option>`);
                            } else {
                                options = '<option value="">No villages found</option>';
                            }
                            $('#village').html(options);
                        },
                        error: function(xhr, status, error) {
                            console.error('Village Error:', error);
                            $('#village').html('<option value="">Error loading villages</option>');
                        }
                    });
                } else {
                    $('#village').html('<option value="">Select Village</option>');
                }
            });
        });
        
        function editAnganwadi(aw) {
            $('#modalTitle').text('Edit Anganwadi/School');
            $('#formAction').val('update');
            $('#anganwadiId').val(aw.id);
            $('#awCode').val(aw.aw_code);
            $('#name').val(aw.name);
            $('#type').val(aw.type);
            $('#totalChildren').val(aw.total_children);
            $('#pregnantWomen').val(aw.pregnant_women);
            $('#contactPerson').val(aw.contact_person);
            $('#mobile').val(aw.mobile);
            $('#email').val(aw.email);
            $('#address').val(aw.address);
            $('#route').val(aw.route_id);
            
            // First, get the district, taluka, and village IDs for this anganwadi
            const districtId = aw.district_id;
            const talukaId = aw.taluka_id;
            const villageId = aw.village_id;
            
            // Set district
            $('#district').val(districtId);
            
            // Load and set talukas
            if (districtId) {
                $.ajax({
                    url: 'anganwadi.php?get_talukas=1',
                    type: 'GET',
                    data: { district_id: districtId },
                    dataType: 'json',
                    success: function(data) {
                        let options = '<option value="">Select Taluka</option>';
                        if (data && data.length > 0) {
                            data.forEach(t => options += `<option value="${t.id}">${t.name}</option>`);
                        }
                        $('#taluka').html(options);
                        $('#taluka').val(talukaId);
                        
                        // Load and set villages
                        if (talukaId) {
                            $.ajax({
                                url: 'anganwadi.php?get_villages=1',
                                type: 'GET',
                                data: { taluka_id: talukaId },
                                dataType: 'json',
                                success: function(data) {
                                    let villageOptions = '<option value="">Select Village</option>';
                                    if (data && data.length > 0) {
                                        data.forEach(v => villageOptions += `<option value="${v.id}">${v.name}</option>`);
                                    }
                                    $('#village').html(villageOptions);
                                    $('#village').val(villageId);
                                },
                                error: function(xhr, status, error) {
                                    console.error('Village Error:', error);
                                    $('#village').html('<option value="">Error loading villages</option>');
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Taluka Error:', error);
                        $('#taluka').html('<option value="">Error loading talukas</option>');
                    }
                });
            }
            
            $('#addModal').modal('show');
        }
        
        function deleteAnganwadi(id) {
            if (confirm('Are you sure you want to deactivate this anganwadi?')) {
                $('#deleteId').val(id);
                $('#deleteForm').submit();
            }
        }
        
        // Reset form when modal closes
        $('#addModal').on('hidden.bs.modal', function() {
            $('#anganwadiForm')[0].reset();
            $('#modalTitle').text('Add Anganwadi/School');
            $('#formAction').val('create');
            $('#anganwadiId').val('');
            $('#taluka').html('<option value="">Select Taluka</option>');
            $('#village').html('<option value="">Select Village</option>');
        });
    </script>
</body>
</html>