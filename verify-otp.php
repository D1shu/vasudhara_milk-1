<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$mobile = $_GET['mobile'] ?? '';
$auto_submit = false;

if (empty($mobile)) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $error = 'Please enter OTP';
    } else {
        $db = getDB();
        
        // Query to get user and verify OTP
        $query = "
            SELECT ol.id, u.id as user_id, u.name, u.mobile, u.email, u.role
            FROM otp_logs ol
            JOIN users u ON u.mobile = ol.mobile
            WHERE ol.mobile = ? 
            AND ol.otp = ? 
            AND ol.expiry_time > NOW()
            AND ol.verified = 0
            ORDER BY ol.created_at DESC
            LIMIT 1
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $mobile, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Update verified status
            $update_query = "UPDATE otp_logs SET verified = 1 WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_mobile'] = $user['mobile'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            if ($user['role'] === 'anganwadi') {
                $_SESSION['anganwadi_name'] = $user['name'];
            }
            
            // Redirect based on role
            $redirect = ($user['role'] === 'admin') ? 'admin/dashboard.php' : 'user/dashboard.php';
            header('Location: ' . $redirect);
            exit;
            
        } else {
            $error = 'Invalid or expired OTP';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Vasudhara Milk Distribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .otp-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        
        .otp-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .otp-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .otp-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="20" opacity="0.1"/><circle cx="80" cy="80" r="25" opacity="0.1"/></svg>');
            opacity: 0.5;
        }
        
        .otp-header-content {
            position: relative;
            z-index: 1;
        }
        
        .otp-header i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .otp-header h2 {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 8px 0;
        }

        .otp-header p {
            font-size: 14px;
            opacity: 0.95;
            margin: 0;
        }
        
        .otp-body {
            padding: 30px 30px;
        }
        
        .otp-info-box {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .otp-info-box i {
            font-size: 32px;
            color: #667eea;
            flex-shrink: 0;
        }
        
        .otp-info-text {
            flex-grow: 1;
        }
        
        .otp-info-text p {
            margin: 0;
            color: #333;
            font-size: 14px;
        }
        
        .otp-info-text .mobile {
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
            margin-top: 5px;
        }
        
        .otp-timer {
            background: #fff3cd;
            color: #856404;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .otp-timer i {
            font-size: 18px;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
            display: block;
            font-size: 15px;
        }
        
        .otp-input-group {
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }
        
        .otp-input-group input {
            width: calc(100% / 6 - 6px);
            padding: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 5px;
            transition: all 0.3s;
        }
        
        .otp-input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: scale(1.05);
        }
        
        .otp-input-single {
            width: 100%;
            padding: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 20px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 8px;
            transition: all 0.3s;
        }
        
        .otp-input-single:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .alert {
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-danger i {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .btn-verify {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-verify:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .btn-verify:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .btn-verify:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .back-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }
        
        .back-section p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .back-section a {
            display: inline-block;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .back-section a:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateX(-5px);
        }
        
        .footer {
            text-align: center;
            padding: 25px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }
        
        .loading {
            display: none;
            text-align: center;
        }
        
        .loading i {
            font-size: 48px;
            color: #667eea;
            animation: spin 2s linear infinite;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-card">
            <!-- Header -->
            <div class="otp-header">
                <div class="otp-header-content">
                    <i class="fas fa-shield-alt"></i>
                    <h2>Verify OTP</h2>
                    <p>Enter the 6-digit code sent to your phone</p>
                </div>
            </div>
            
            <!-- Body -->
            <div class="otp-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Info Box -->
                <div class="otp-info-box">
                    <i class="fas fa-mobile-alt"></i>
                    <div class="otp-info-text">
                        <p>OTP sent to</p>
                        <div class="mobile"><?php echo htmlspecialchars($mobile); ?></div>
                    </div>
                </div>
                
                <!-- Timer -->
                <div class="otp-timer">
                    <i class="fas fa-hourglass-start"></i>
                    <span>Valid for <strong>1 minute</strong></span>
                </div>
                
                <!-- Form -->
                <form method="POST" id="otpForm">
                    <div class="form-group">
                        <label for="otp">Enter OTP Code</label>
                        <input 
                            type="text" 
                            id="otp"
                            name="otp" 
                            class="otp-input-single"
                            placeholder="000000" 
                            maxlength="6" 
                            pattern="\d{6}" 
                            inputmode="numeric"
                            required 
                            autofocus>
                        <small style="color: #999; display: block; margin-top: 10px;">
                            <i class="fas fa-info-circle"></i> Enter 6-digit OTP received on your mobile
                        </small>
                    </div>
                    
                    <button type="submit" class="btn-verify" id="verifyBtn">
                        <i class="fas fa-check-circle"></i>
                        <span>Verify OTP</span>
                    </button>
                </form>
                
                <!-- Loading State -->
                <div class="loading" id="loading">
                    <i class="fas fa-spinner"></i>
                    <p style="margin-top: 15px; color: #667eea; font-weight: 600;">Verifying OTP...</p>
                </div>
                
                <!-- Back Section -->
                <div class="back-section">
                    <p>Didn't receive OTP?</p>
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i> Back to Login
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
        // Auto-submit on 6 digits
        const otpInput = document.getElementById('otp');
        const otpForm = document.getElementById('otpForm');
        const verifyBtn = document.getElementById('verifyBtn');
        const loading = document.getElementById('loading');
        
        otpInput.addEventListener('input', function() {
            // Only numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits entered
            if (this.value.length === 6) {
                autoSubmit();
            }
        });
        
        function autoSubmit() {
            verifyBtn.disabled = true;
            loading.style.display = 'block';
            verifyBtn.style.display = 'none';
            
            setTimeout(() => {
                otpForm.submit();
            }, 500);
        }
        
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (otpInput.value.length === 6) {
                autoSubmit();
            }
        });
        
        // Focus on load
        window.addEventListener('load', () => {
            otpInput.focus();
        });
    </script>
</body>
</html>