<?php
// ============================================================
// One-time Setup Script
// Run this once to create the database and seed data.
// DELETE this file after setup is complete!
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';   // Change to your MySQL password

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die('<p style="color:red">Connection failed: ' . $conn->connect_error . '</p>');
}

$sql = file_get_contents(__DIR__ . '/schema.sql');

// Split and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
$errors = [];
$success = 0;

foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    if ($conn->query($stmt) === true) {
        $success++;
    } else {
        // Ignore duplicate entry errors for seed data
        if ($conn->errno !== 1062) {
            $errors[] = $conn->error . ' | SQL: ' . substr($stmt, 0, 80);
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup — Student Safety System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">
            <i class="bi bi-gear me-2"></i>Database Setup
        </div>
        <div class="card-body">
            <?php if (empty($errors)): ?>
            <div class="alert alert-success">
                <strong>✅ Setup complete!</strong> Database and tables created successfully.
                <?= $success ?> statements executed.
            </div>
            <p>You can now <a href="index.php" class="btn btn-primary btn-sm">Go to Login</a></p>
            <div class="alert alert-warning mt-3">
                <strong>⚠️ Security:</strong> Delete <code>setup.php</code> after setup!
            </div>
            <p class="mb-0"><strong>Demo credentials:</strong><br>
            Username: <code>admin</code> | Password: <code>password</code></p>
            <?php else: ?>
            <div class="alert alert-danger">
                <strong>❌ Errors occurred:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <p>Check your database credentials in <code>setup.php</code> and <code>db.php</code>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</body>
</html>
