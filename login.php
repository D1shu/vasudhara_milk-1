<?php
require_once 'config.php';
require_once 'auth.php';

// If already logged in, redirect to appropriate dashboard
if (Auth::isLoggedIn()) {
    if (Auth::isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/user/dashboard.php');
    }
}

$error = '';
$success = '';
$step = 'mobile'; // mobile or otp

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
        $mobile = sanitize($_POST['mobile']);
        
        // Validate mobile
        if (empty($mobile)) {
            $error = 'Please enter mobile number';
        } elseif (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            $error = 'Please enter valid 10-digit mobile number';
        } else {
            // Check if user exists
            $user = Auth::getUserByMobile($mobile);
            
            if (!$user) {
                $error = 'Mobile number not registered. Please contact administrator.';
            } else {
                // Generate and send OTP
                $otp = Auth::generateOTP();
                
                if (Auth::saveOTP($mobile, $otp) && Auth::sendOTP($mobile, $otp)) {
                    $_SESSION['temp_mobile'] = $mobile;
                    $step = 'otp';
                    $success = 'OTP sent to your mobile number';
                } else {
                    $error = 'Failed to send OTP. Please try again.';
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        $mobile = $_SESSION['temp_mobile'] ?? '';
        $otp = sanitize($_POST['otp']);
        
        if (empty($otp)) {
            $error = 'Please enter OTP';
            $step = 'otp';
        } elseif (Auth::verifyOTP($mobile, $otp)) {
            // Get user and login
            $user = Auth::getUserByMobile($mobile);
            
            if ($user) {
                Auth::login($user);
                unset($_SESSION['temp_mobile']);
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/dashboard.php');
                } else {
                    redirect(SITE_URL . '/user/dashboard.php');
                }
            } else {
                $error = 'User not found';
                $step = 'mobile';
            }
        } else {
            $error = 'Invalid or expired OTP';
            $step = 'otp';
        }
    }
}

// Check if coming back to OTP step
if (isset($_SESSION['temp_mobile'])) {
    $step = 'otp';
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 450px;
            margin: 0 auto;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-back {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: white;
            width: 100%;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .otp-input {
            font-size: 24px;
            letter-spacing: 10px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-glass-whiskey"></i>
                    <h3 class="mb-0">Vasudhara Milk</h3>
                    <p class="mb-0">Distribution System</p>
                </div>
                <div class="login-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($step === 'mobile'): ?>
                        <form method="POST" action="" id="mobileForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="send_otp">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="text" class="form-control" name="mobile" 
                                           placeholder="Enter 10-digit mobile number" 
                                           maxlength="10" pattern="[6-9][0-9]{9}" required>
                                </div>
                                <small class="text-muted">Enter your registered mobile number</small>
                            </div>
                            
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-paper-plane"></i> Send OTP
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="" id="otpForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="verify_otp">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Enter OTP</label>
                                <input type="text" class="form-control otp-input" name="otp" 
                                       placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                                <small class="text-muted">
                                    OTP sent to <?php echo isset($_SESSION['temp_mobile']) ? $_SESSION['temp_mobile'] : ''; ?>
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-login mb-2">
                                <i class="fas fa-sign-in-alt"></i> Verify & Login
                            </button>
                            
                            <button type="button" class="btn btn-back" onclick="window.location.href='login.php?reset=1'">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            © <?php echo date('Y'); ?> Vasudhara Milk Distribution
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit OTP when 6 digits entered
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.querySelector('.otp-input');
            if (otpInput) {
                otpInput.addEventListener('input', function() {
                    if (this.value.length === 6) {
                        document.getElementById('otpForm').submit();
                    }
                });
            }
            
            // Mobile number validation
            const mobileInput = document.querySelector('input[name="mobile"]');
            if (mobileInput) {
                mobileInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }
        });
    </script>
</body>
</html>
<?php
// Reset temp mobile if requested
if (isset($_GET['reset'])) {
    unset($_SESSION['temp_mobile']);
    redirect(SITE_URL . '/login.php');
}
?>