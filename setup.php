<?php
// ============================================================
// One-time setup — creates DB and seeds data.
// DELETE this file after running it!
// ============================================================
$host = 'localhost';
$user = 'root';
$pass = '';   // ← your MySQL password

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die('<p style="color:red">Connection failed: '.$conn->connect_error.'</p>');

$sql        = file_get_contents(__DIR__.'/schema.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = 0; $errs = [];

foreach ($statements as $stmt) {
    if (!$stmt || str_starts_with($stmt,'--')) continue;
    if ($conn->query($stmt)) { $ok++; }
    elseif ($conn->errno !== 1062) { $errs[] = $conn->error.' | '.substr($stmt,0,80); }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white fw-bold">
            <i class="bi bi-gear me-2"></i>Database Setup
        </div>
        <div class="card-body">
            <?php if (empty($errs)): ?>
            <div class="alert alert-success">
                <strong>✅ Setup complete!</strong> <?= $ok ?> statements executed.
            </div>
            <p><a href="index.php" class="btn btn-primary">Go to Login</a></p>
            <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Delete <code>setup.php</code></strong> after setup for security.
            </div>
            <hr>
            <p class="mb-0">
                <strong>Demo credentials</strong><br>
                Username: <code>admin</code> &nbsp;·&nbsp; Password: <code>password</code>
            </p>
            <?php else: ?>
            <div class="alert alert-danger">
                <strong>❌ Errors:</strong>
                <ul class="mb-0 mt-2"><?php foreach($errs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
            <p>Check your credentials in <code>setup.php</code> and <code>db.php</code>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
