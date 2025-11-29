<?php
/**
 * Vasudhara Milk Distribution System
 * Configuration File
 */

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vasudhara_milk');

// Application Configuration
define('SITE_URL', 'http://localhost/vasudhara_milk');
define('SITE_NAME', 'Vasudhara Milk Distribution System');
define('ADMIN_EMAIL', 'admin@vasudhara.com');

// Session Configuration
define('SESSION_TIMEOUT', 30); // minutes
define('OTP_EXPIRY', 5); // minutes

// SMS API Configuration (Fast2SMS)
define('SMS_API_KEY', 'YOUR_FAST2SMS_API_KEY_HERE');
define('SMS_SENDER_ID', 'VASUDHARA');
define('SMS_API_URL', 'https://www.fast2sms.com/dev/bulkV2');

// Alternative: MSG91 Configuration
// define('MSG91_AUTH_KEY', 'YOUR_MSG91_AUTH_KEY');
// define('MSG91_SENDER_ID', 'VASUDHARA');
// define('MSG91_ROUTE', '4');

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Pagination
define('RECORDS_PER_PAGE', 25);

// Date Format
define('DATE_FORMAT', 'd-m-Y');
define('DATETIME_FORMAT', 'd-m-Y H:i:s');

// Milk Bag Configuration
define('BAG_SIZE_ML', 500); // 500ml per bag

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
}

// Helper Functions
function getDB() {
    return Database::getInstance()->getConnection();
}

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $token)) {
        return false;
    }
    return true;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function formatDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = DATETIME_FORMAT) {
    return date($format, strtotime($datetime));
}

function calculateBags($quantity) {
    return ceil(($quantity * 1000) / BAG_SIZE_ML);
}

function logActivity($db, $userId, $action, $tableName = null, $recordId = null, $oldData = null, $newData = null) {
    // Validate parameters
    if (!is_object($db) || !($db instanceof mysqli)) {
        error_log("Invalid database object passed to logActivity");
        return false;
    }
    
    if (!is_numeric($userId)) {
        error_log("Invalid userId passed to logActivity: " . print_r($userId, true));
        return false;
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $db->error);
        return false;
    }
    
    $oldDataJson = $oldData ? json_encode($oldData) : null;
    $newDataJson = $newData ? json_encode($newData) : null;
    
    $stmt->bind_param("isssisss", $userId, $action, $tableName, $recordId, $oldDataJson, $newDataJson, $ipAddress, $userAgent);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function getSetting($key, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['setting_value'];
    }
    
    $stmt->close();
    return $default;
}

function updateSetting($key, $value, $description = null) {
    $db = getDB();
    
    if ($description) {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, description = ?");
        $stmt->bind_param("sssss", $key, $value, $description, $value, $description);
    } else {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > (SESSION_TIMEOUT * 60)) {
    session_unset();
    session_destroy();
    if (isset($_SESSION['user_id'])) {
        redirect(SITE_URL . '/login.php?timeout=1');
    }
}
$_SESSION['last_activity'] = time();
?>