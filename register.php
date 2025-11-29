<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Fetch districts
$db = getDB();
$districts = [];
$stmt = $db->prepare("SELECT id, name FROM districts ORDER BY name");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    $stmt->close();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $village_id = $_POST['village_id'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    // Validation
    if (empty($name) || empty($mobile) || empty($email) || empty($village_id)) {
        $error = 'All fields are required';
    } elseif (strlen($mobile) !== 10 || !is_numeric($mobile)) {
        $error = 'Mobile number must be 10 digits';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        try {
            $db = getDB();

            // Check if mobile already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
            if (!$stmt) {
                $error = 'Database error: ' . $db->error;
            } else {
                $stmt->bind_param("s", $mobile);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error = 'Mobile number already registered. Please <a href="login.php">login here</a>';
                } else {
                    // Insert new user
                    $insertStmt = $db->prepare("INSERT INTO users (name, mobile, email, village_id, role, status) VALUES (?, ?, ?, ?, ?, 'active')");

                    if (!$insertStmt) {
                        $error = 'Database error: ' . $db->error;
                    } else {
                        $insertStmt->bind_param("sssis", $name, $mobile, $email, $village_id, $role);

                        if ($insertStmt->execute()) {
                            $success = 'Registration successful! Redirecting to login...';
                            echo '<meta http-equiv="refresh" content="2; url=login.php">';
                        } else {
                            $error = 'Registration failed: ' . $insertStmt->error;
                        }
                        $insertStmt->close();
                    }
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Vasudhara Milk Distribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .register-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .register-header h2 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        
        .register-header p {
            font-size: 14px;
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .register-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .form-group small {
            display: block;
            color: #999;
            margin-top: 5px;
            font-size: 12px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .alert a {
            color: inherit;
            font-weight: bold;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-link p {
            color: #666;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            padding: 10px 20px;
            border: 2px solid #667eea;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            background: #667eea;
            color: white;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p>Join Vasudhara Milk Distribution</p>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" 
                                   placeholder="Enter your full name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile">Mobile Number *</label>
                            <input type="tel" id="mobile" name="mobile" 
                                   placeholder="Enter 10-digit mobile number" 
                                   value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                                   pattern="\d{10}" maxlength="10"
                                   required>
                            <small>10 digits only (e.g., 9876543210)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email"
                                   placeholder="Enter your email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="district">District *</label>
                            <select class="form-select" id="district" required>
                                <option value="">Select District</option>
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="taluka">Taluka *</label>
                            <select class="form-select" id="taluka" disabled required>
                                <option value="">Select District First</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="village">Village *</label>
                            <select class="form-select" name="village_id" id="village" disabled required>
                                <option value="">Select Taluka First</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-register">
                            <i class="fas fa-check"></i> Create Account
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- Login Link -->
                <div class="login-link">
                    <p>Already have an account?</p>
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            © 2025 Vasudhara Milk Distribution System
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const districtSelect = document.getElementById('district');
        const talukaSelect = document.getElementById('taluka');
        const villageSelect = document.getElementById('village');
        
        // District change par Talukas load karo
        districtSelect.addEventListener('change', function() {
            const districtId = this.value;
            
            // Reset taluka and village
            talukaSelect.innerHTML = '<option value="">Loading...</option>';
            talukaSelect.disabled = true;
            villageSelect.innerHTML = '<option value="">Select Taluka First</option>';
            villageSelect.disabled = true;
            
            if (districtId) {
                // Fetch talukas
                fetch('ajax/get-talukas.php?district_id=' + districtId)
                    .then(response => response.json())
                    .then(data => {
                        talukaSelect.innerHTML = '<option value="">Select Taluka</option>';
                        
                        if (data.success && data.data && data.data.length > 0) {
                            data.data.forEach(taluka => {
                                const option = document.createElement('option');
                                option.value = taluka.id;
                                option.textContent = taluka.name;
                                talukaSelect.appendChild(option);
                            });
                            talukaSelect.disabled = false;
                        } else {
                            talukaSelect.innerHTML = '<option value="">No Talukas Found</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading talukas:', error);
                        talukaSelect.innerHTML = '<option value="">Error Loading Talukas</option>';
                        alert('Error loading talukas. Please try again.');
                    });
            } else {
                talukaSelect.innerHTML = '<option value="">Select District First</option>';
            }
        });
        
        // Taluka change par Villages load karo
        talukaSelect.addEventListener('change', function() {
            const talukaId = this.value;
            
            // Reset village
            villageSelect.innerHTML = '<option value="">Loading...</option>';
            villageSelect.disabled = true;
            
            if (talukaId) {
                // Fetch villages
                fetch('ajax/get-villages.php?taluka_id=' + talukaId)
                    .then(response => response.json())
                    .then(data => {
                        villageSelect.innerHTML = '<option value="">Select Village</option>';
                        
                        if (data.success && data.data && data.data.length > 0) {
                            data.data.forEach(village => {
                                const option = document.createElement('option');
                                option.value = village.id;
                                option.textContent = village.name;
                                villageSelect.appendChild(option);
                            });
                            villageSelect.disabled = false;
                        } else {
                            villageSelect.innerHTML = '<option value="">No Villages Found</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading villages:', error);
                        villageSelect.innerHTML = '<option value="">Error Loading Villages</option>';
                        alert('Error loading villages. Please try again.');
                    });
            } else {
                villageSelect.innerHTML = '<option value="">Select Taluka First</option>';
            }
        });
    });
    </script>
</body>
</html>