<!DOCTYPE html>
<html>
<head>
    <title>License Key Manager</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        h2 { color: #666; margin: 30px 0 15px; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .active { color: green; font-weight: bold; }
        .inactive { color: red; font-weight: bold; }
        .expired { color: orange; font-weight: bold; }
        a { color: #2196F3; text-decoration: none; margin: 0 5px; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #da190b; }
        form { background: #f9f9f9; padding: 20px; border-radius: 4px; margin: 20px 0; }
        input, select { padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { flex: 1; padding: 20px; background: #e3f2fd; border-radius: 4px; text-align: center; }
        .stat-box h3 { font-size: 32px; color: #1976d2; margin-bottom: 5px; }
        .stat-box p { color: #666; }
    </style>
</head>
<body>

<?php
// Railway MySQL connection
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'license_system';
$db_port = getenv('MYSQLPORT') ?: 3306;

$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'revoke' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $db->query("UPDATE license_keys SET is_active=0 WHERE id=$id");
        header('Location: admin.php');
        exit;
    }
    if ($_GET['action'] == 'activate' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $db->query("UPDATE license_keys SET is_active=1 WHERE id=$id");
        header('Location: admin.php');
        exit;
    }
    if ($_GET['action'] == 'remove_device' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $db->query("DELETE FROM device_activations WHERE id=$id");
        header('Location: admin.php');
        exit;
    }
}

// Get statistics
$totalKeys = $db->query("SELECT COUNT(*) as count FROM license_keys")->fetch_assoc()['count'];
$activeKeys = $db->query("SELECT COUNT(*) as count FROM license_keys WHERE is_active=1")->fetch_assoc()['count'];
$totalDevices = $db->query("SELECT COUNT(*) as count FROM device_activations")->fetch_assoc()['count'];
$expiredKeys = $db->query("SELECT COUNT(*) as count FROM license_keys WHERE exp_date < CURDATE()")->fetch_assoc()['count'];

// Get all keys with device count
$keys = $db->query("
    SELECT 
        lk.*,
        COUNT(da.id) as device_count
    FROM license_keys lk
    LEFT JOIN device_activations da ON lk.key_code = da.key_code
    GROUP BY lk.id
    ORDER BY lk.created_at DESC
");
?>

<div class="container">
    <h1>🔐 License Key Manager</h1>
    
    <div class="stats">
        <div class="stat-box">
            <h3><?= $totalKeys ?></h3>
            <p>Total Keys</p>
        </div>
        <div class="stat-box">
            <h3><?= $activeKeys ?></h3>
            <p>Active Keys</p>
        </div>
        <div class="stat-box">
            <h3><?= $totalDevices ?></h3>
            <p>Active Devices</p>
        </div>
        <div class="stat-box">
            <h3><?= $expiredKeys ?></h3>
            <p>Expired Keys</p>
        </div>
    </div>
    
    <h2>📋 All License Keys</h2>
    <table>
        <tr>
            <th>Key Code</th>
            <th>Type</th>
            <th>Expiry Date</th>
            <th>Devices</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $keys->fetch_assoc()): ?>
        <?php 
            $isExpired = strtotime($row['exp_date']) < time();
            $statusClass = $isExpired ? 'expired' : ($row['is_active'] ? 'active' : 'inactive');
            $statusText = $isExpired ? 'Expired' : ($row['is_active'] ? 'Active' : 'Disabled');
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($row['key_code']) ?></strong></td>
            <td><?= htmlspecialchars($row['key_type']) ?></td>
            <td><?= $row['exp_date'] ?></td>
            <td><?= $row['device_count'] ?> / <?= $row['max_devices'] ?></td>
            <td class="<?= $statusClass ?>"><?= $statusText ?></td>
            <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
            <td>
                <a href="view_devices.php?key=<?= urlencode($row['key_code']) ?>">View Devices</a> |
                <?php if($row['is_active']): ?>
                    <a href="?action=revoke&id=<?= $row['id'] ?>" class="btn-danger">Revoke</a>
                <?php else: ?>
                    <a href="?action=activate&id=<?= $row['id'] ?>">Activate</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <h2>➕ Add New License Key</h2>
    <form method="POST" action="add_key.php">
        <input type="text" name="key_code" placeholder="KEY-CODE-HERE" required style="width: 250px;">
        <select name="key_type" required>
            <option value="trial">Trial</option>
            <option value="premium">Premium</option>
            <option value="ultimate">Ultimate</option>
            <option value="lifetime">Lifetime</option>
        </select>
        <input type="date" name="exp_date" required>
        <input type="number" name="max_devices" value="1" min="1" max="10" placeholder="Max Devices">
        <input type="text" name="bot_token" placeholder="Telegram Bot Token (optional)" style="width: 200px;">
        <input type="text" name="chat_id" placeholder="Chat ID (optional)" style="width: 150px;">
        <button type="submit" class="btn">Add Key</button>
    </form>
    
    <p style="margin-top: 30px; color: #666; text-align: center;">
        <a href="view_all_devices.php">View All Devices</a> | 
        <a href="generate_keys.php">Generate Multiple Keys</a>
    </p>
</div>

</body>
</html>
