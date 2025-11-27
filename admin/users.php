<?php
require_once '../config.php';
require_once '../auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$error = '';
$success = '';

// Handle Create/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $db = getDB();
        
        if ($action === 'create' || $action === 'update') {
            $name = sanitize($_POST['name']);
            $mobile = sanitize($_POST['mobile']);
            $email = sanitize($_POST['email']);
            $role = sanitize($_POST['role']);
            $anganwadiId = !empty($_POST['anganwadi_id']) ? (int)$_POST['anganwadi_id'] : null;
            $status = $_POST['status'] ?? 'active';
            
            // Validate mobile
            if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
                $error = 'Invalid mobile number';
            } else {
                if ($action === 'create') {
                    // Check if mobile already exists
                    $checkStmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
                    $checkStmt->bind_param("s", $mobile);
                    $checkStmt->execute();
                    
                    if ($checkStmt->get_result()->num_rows > 0) {
                        $error = 'Mobile number already registered';
                    } else {
                        $stmt = $db->prepare("INSERT INTO users (anganwadi_id, name, mobile, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssss", $anganwadiId, $name, $mobile, $email, $role, $status);
                        
                        if ($stmt->execute()) {
                            $success = 'User created successfully!';
                            logActivity(Auth::getUserId(), 'CREATE_USER', 'users', $db->insert_id);
                        } else {
                            $error = 'Failed to create user';
                        }
                        $stmt->close();
                    }
                    $checkStmt->close();
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $db->prepare("UPDATE users SET anganwadi_id=?, name=?, mobile=?, email=?, role=?, status=? WHERE id=?");
                    $stmt->bind_param("isssssi", $anganwadiId, $name, $mobile, $email, $role, $status, $id);
                    
                    if ($stmt->execute()) {
                        $success = 'User updated successfully!';
                        logActivity(Auth::getUserId(), 'UPDATE_USER', 'users', $id);
                    } else {
                        $error = 'Failed to update user';
                    }
                    $stmt->close();
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE users SET status='inactive' WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = 'User deactivated successfully!';
                logActivity(Auth::getUserId(), 'DELETE_USER', 'users', $id);
            } else {
                $error = 'Failed to deactivate user';
            }
            $stmt->close();
        }
    }
}

// Get all users
$db = getDB();
$result = $db->query("
    SELECT u.*, a.name as anganwadi_name, a.aw_code 
    FROM users u 
    LEFT JOIN anganwadi a ON u.anganwadi_id = a.id 
    WHERE u.status = 'active' 
    ORDER BY u.id DESC
");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get anganwadis for dropdown
$anganwadis = getAnganwadiList();

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f7fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #1a202c, #2d3748); position: fixed; width: 260px; }
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.2); color: white; text-align: center; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #cbd5e0; text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a.active { background: linear-gradient(90deg, #667eea, transparent); color: white; }
        .main-content { margin-left: 260px; }
        .top-navbar { background: white; padding: 20px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: none; margin-bottom: 20px; }
        .badge-admin { background: #667eea; }
        .badge-user { background: #48bb78; }
        .badge-supervisor { background: #ed8936; }
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
            <a href="users.php" class="active"><i class="fas fa-users me-2"></i> Users</a>
            <a href="routes.php"><i class="fas fa-route me-2"></i> Routes</a>
            <a href="districts.php"><i class="fas fa-map-marked-alt me-2"></i> Districts</a>
            <a href="reports.php"><i class="fas fa-file-alt me-2"></i> Reports</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <h4 class="mb-0">User Management</h4>
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
                        <h5>Users List (<?php echo count($users); ?>)</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    
                    <table id="usersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Anganwadi</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><?php echo $user['mobile']; ?></td>
                                <td><?php echo $user['email'] ?: '-'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['anganwadi_name']): ?>
                                        <?php echo $user['aw_code']; ?> - <?php echo htmlspecialchars($user['anganwadi_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editUser(<?php echo json_encode($user); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['id'] != Auth::getUserId()): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Modal -->
    <div class="modal fade" id="userModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="userForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="userId">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" id="userName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Mobile Number *</label>
                            <input type="text" class="form-control" name="mobile" id="userMobile" 
                                   maxlength="10" pattern="[6-9][0-9]{9}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="userEmail">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role" id="userRole" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="supervisor">Supervisor</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="anganwadiSection">
                            <label class="form-label">Assign Anganwadi</label>
                            <select class="form-select" name="anganwadi_id" id="userAnganwadi">
                                <option value="">Select Anganwadi</option>
                                <?php foreach ($anganwadis as $aw): ?>
                                    <option value="<?php echo $aw['id']; ?>">
                                        <?php echo $aw['aw_code']; ?> - <?php echo $aw['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Required for users, optional for admins</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="userStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
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
            $('#usersTable').DataTable();
            
            // Show/hide anganwadi dropdown based on role
            $('#userRole').change(function() {
                if ($(this).val() === 'user') {
                    $('#anganwadiSection').show();
                } else {
                    $('#anganwadiSection').hide();
                }
            });
        });
        
        function editUser(user) {
            $('#modalTitle').text('Edit User');
            $('#formAction').val('update');
            $('#userId').val(user.id);
            $('#userName').val(user.name);
            $('#userMobile').val(user.mobile);
            $('#userEmail').val(user.email);
            $('#userRole').val(user.role);
            $('#userAnganwadi').val(user.anganwadi_id);
            $('#userStatus').val(user.status);
            
            if (user.role !== 'user') {
                $('#anganwadiSection').hide();
            }
            
            $('#userModal').modal('show');
        }
        
        function deleteUser(id) {
            if (confirm('Are you sure you want to deactivate this user?')) {
                $('#deleteId').val(id);
                $('#deleteForm').submit();
            }
        }
        
        // Reset form when modal closes
        $('#userModal').on('hidden.bs.modal', function() {
            $('#userForm')[0].reset();
            $('#modalTitle').text('Add User');
            $('#formAction').val('create');
            $('#userId').val('');
            $('#anganwadiSection').show();
        });
    </script>
</body>
</html>