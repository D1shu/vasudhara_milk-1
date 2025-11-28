<?php
require_once 'config.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$mobile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    
    if (empty($mobile)) {
        $error = 'Please enter mobile number';
    } elseif (strlen($mobile) !== 10 || !is_numeric($mobile)) {
        $error = 'Mobile number must be 10 digits';
    } else {
        try {
            $db = getDB();
            
            if (!$db) {
                $error = 'Database connection failed';
            } else {
                // Check if user exists
                $stmt = $db->prepare("SELECT id, name, mobile, email, status FROM users WHERE mobile = ?");
                
                if (!$stmt) {
                    $error = 'Database error: ' . $db->error;
                } else {
                    $stmt->bind_param("s", $mobile);
                    
                    if (!$stmt->execute()) {
                        $error = 'Query error: ' . $stmt->error;
                    } else {
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows === 0) {
                            $error = 'Mobile number not registered. Please contact administrator.';
                        } else {
                            $user = $result->fetch_assoc();
                            
                            // Check user status
                            if ($user['status'] === 'inactive') {
                                $error = 'Your account is inactive. Please contact administrator.';
                            } else {
                                // Generate OTP
                                $otp = rand(100000, 999999);
                                $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute'));  // 1 minute expiry
                                
                                // Save OTP to otp_logs table (without user_id)
                                $stmt2 = $db->prepare("INSERT INTO otp_logs (mobile, otp, expiry_time, verified) VALUES (?, ?, ?, 0)");
                                
                                if (!$stmt2) {
                                    $error = 'Database error: ' . $db->error;
                                } else {
                                    // Remove user_id from bind_param - only 3 parameters now
                                    $stmt2->bind_param("sss", $mobile, $otp, $otp_expiry);
                                    
                                    if ($stmt2->execute()) {
                                        $_SESSION['otp_mobile'] = $mobile;
                                        $_SESSION['otp'] = $otp;
                                        header('Location: verify-otp.php?mobile=' . urlencode($mobile));
                                        exit;
                                    } else {
                                        $error = 'Failed to generate OTP: ' . $stmt2->error;
                                    }
                                    $stmt2->close();
                                }
                            }
                        }
                    }
                    $stmt->close();
                }
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
    <title>Login - Vasudhara Milk Distribution</title>
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
        
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 50px;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        
        .login-header p {
            font-size: 14px;
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            color: #999;
            margin-top: 8px;
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
        
        .btn-submit {
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
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .register-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .register-section p {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .register-section a {
            display: inline-block;
            padding: 10px 30px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .register-section a:hover {
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
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <i class="fas fa-glass-whiskey"></i>
                <h2>Vasudhara Milk</h2>
                <p>Distribution System</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="mobile" 
                                   placeholder="Enter 10-digit mobile number"
                                   value="<?php echo htmlspecialchars($mobile); ?>"
                                   pattern="\d{10}" maxlength="10"
                                   required>
                        </div>
                        <small>Enter your registered mobile number</small>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send OTP
                    </button>
                </form>
                
                <!-- Register Section -->
                <div class="register-section">
                    <p>Don't have an account?</p>
                    <a href="register.php">
                        <i class="fas fa-user-plus"></i> Create New Account
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            © 2025 Vasudhara Milk Distribution
        </div>
    </div>
</body>
</html>