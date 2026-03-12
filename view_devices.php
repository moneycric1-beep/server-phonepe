<!DOCTYPE html>
<html>
<head>
    <title>Device List</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2196F3; color: white; }
        a { color: #2196F3; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn-danger { color: #f44336; }
        .back-link { display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'license_system';
$db_port = getenv('MYSQLPORT') ?: 3306;

$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
$key = $_GET['key'] ?? '';

if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $db->query("DELETE FROM device_activations WHERE id=$id");
    header("Location: view_devices.php?key=" . urlencode($key));
    exit;
}

$devices = $db->query("
    SELECT * FROM device_activations 
    WHERE key_code='" . $db->real_escape_string($key) . "' 
    ORDER BY last_used DESC
");
?>

<div class="container">
    <a href="admin.php" class="back-link">← Back to Dashboard</a>
    
    <h1>📱 Devices for Key: <?= htmlspecialchars($key) ?></h1>
    
    <?php if ($devices->num_rows == 0): ?>
        <p>No devices activated yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Device ID</th>
            <th>Model</th>
            <th>Brand</th>
            <th>Activated</th>
            <th>Last Used</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $devices->fetch_assoc()): ?>
        <tr>
            <td><code><?= htmlspecialchars($row['device_id']) ?></code></td>
            <td><?= htmlspecialchars($row['device_model']) ?></td>
            <td><?= htmlspecialchars($row['device_brand']) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($row['activated_at'])) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($row['last_used'])) ?></td>
            <td>
                <a href="?remove=<?= $row['id'] ?>&key=<?= urlencode($key) ?>" 
                   class="btn-danger" 
                   onclick="return confirm('Remove this device?')">Remove</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
