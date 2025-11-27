<?php
/**
 * Authentication Functions
 */

require_once 'config.php';

class Auth {
    
    /**
     * Generate OTP
     */
    public static function generateOTP() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send OTP via Fast2SMS
     */
    public static function sendOTP($mobile, $otp) {
        $apiKey = SMS_API_KEY;
        
        if (empty($apiKey) || $apiKey === 'YOUR_FAST2SMS_API_KEY_HERE') {
            // For testing without API key
            error_log("OTP for $mobile: $otp");
            return true;
        }
        
        $message = "Your OTP for Vasudhara Milk Distribution login is: $otp. Valid for " . OTP_EXPIRY . " minutes.";
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => SMS_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'route' => 'v3',
                'sender_id' => SMS_SENDER_ID,
                'message' => $message,
                'language' => 'english',
                'flash' => 0,
                'numbers' => $mobile
            ]),
            CURLOPT_HTTPHEADER => array(
                "authorization: $apiKey",
                "accept: */*",
                "cache-control: no-cache",
                "content-type: application/json"
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            error_log("SMS Error: " . $err);
            return false;
        }
        
        $result = json_decode($response, true);
        return isset($result['return']) && $result['return'] === true;
    }
    
    /**
     * Save OTP to database
     */
    public static function saveOTP($mobile, $otp) {
        $db = getDB();
        
        // Clean mobile number
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        // Delete old OTPs for this mobile
        $stmt = $db->prepare("DELETE FROM otp_logs WHERE mobile = ? AND verified = 0");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $stmt->close();
        
        // Save new OTP
        $expiryTime = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY . ' minutes'));
        $stmt = $db->prepare("INSERT INTO otp_logs (mobile, otp, expiry_time) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $mobile, $otp, $expiryTime);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Verify OTP
     */
    public static function verifyOTP($mobile, $otp) {
        $db = getDB();
        
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        $stmt = $db->prepare("SELECT id FROM otp_logs WHERE mobile = ? AND otp = ? AND expiry_time > NOW() AND verified = 0");
        $stmt->bind_param("ss", $mobile, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Mark OTP as verified
            $updateStmt = $db->prepare("UPDATE otp_logs SET verified = 1 WHERE id = ?");
            $updateStmt->bind_param("i", $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $stmt->close();
            return true;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Get user by mobile
     */
    public static function getUserByMobile($mobile) {
        $db = getDB();
        
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        $stmt = $db->prepare("
            SELECT u.*, a.name as anganwadi_name, a.aw_code, a.type as anganwadi_type
            FROM users u
            LEFT JOIN anganwadi a ON u.anganwadi_id = a.id
            WHERE u.mobile = ? AND u.status = 'active'
        ");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Login user
     */
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_mobile'] = $user['mobile'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['anganwadi_id'] = $user['anganwadi_id'];
        $_SESSION['anganwadi_name'] = $user['anganwadi_name'] ?? null;
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();
        
        // Log activity
        logActivity($user['id'], 'LOGIN', 'users', $user['id']);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            redirect(SITE_URL . '/login.php');
        }
    }
    
    /**
     * Require admin
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            redirect(SITE_URL . '/user/dashboard.php');
        }
    }
    
    /**
     * Create notification
     */
    public static function createNotification($userId, $title, $message, $type = 'system') {
        $db = getDB();
        
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $title, $message, $type);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get unread notifications count
     */
    public static function getUnreadNotificationsCount($userId) {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'];
    }
}
?>