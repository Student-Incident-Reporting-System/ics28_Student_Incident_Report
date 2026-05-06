<?php
// ============================================================
// Database Connection Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'student_safety');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Log user activity
function logActivity($userId, $action, $targetTable = null, $targetId = null, $details = null) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, target_table, target_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ississ', $userId, $action, $targetTable, $targetId, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
?>
