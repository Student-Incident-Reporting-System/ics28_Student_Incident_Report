<?php
// Database connection — edit credentials to match your setup

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // your MySQL password
define('DB_NAME', 'student_safety');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<p style="color:red;font-family:sans-serif">DB Error: ' . $conn->connect_error . '</p>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function logActivity(int $userId, string $action, ?string $table = null, ?int $targetId = null, ?string $details = null): void {
    $db  = getDB();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, target_table, target_id, details, ip_address)
         VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param('ississ', $userId, $action, $table, $targetId, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
