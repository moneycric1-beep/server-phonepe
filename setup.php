<?php
/**
 * Database Setup Script
 * Visit this URL once to create tables and add sample keys
 */

header('Content-Type: text/html; charset=utf-8');

// Railway MySQL connection
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'license_system';
$db_port = getenv('MYSQLPORT') ?: 3306;

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}";
echo "h1{color:#333;}pre{background:#fff;padding:10px;border-radius:4px;}</style></head><body>";

echo "<h1>🔧 Database Setup</h1>";

// Connect to database
$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($db->connect_error) {
    echo "<p class='error'>❌ Connection failed: " . $db->connect_error . "</p>";
    echo "</body></html>";
    exit;
}

echo "<p class='success'>✅ Connected to MySQL database</p>";

// Create license_keys table
echo "<h2>Creating Tables...</h2>";

$sql = "CREATE TABLE IF NOT EXISTS license_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255) UNIQUE NOT NULL,
    key_type VARCHAR(50) NOT NULL,
    exp_date DATE NOT NULL,
    max_devices INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    bot_token VARCHAR(255) DEFAULT '',
    chat_id VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_code (key_code),
    INDEX idx_active (is_active),
    INDEX idx_exp_date (exp_date)
)";

if ($db->query($sql)) {
    echo "<p class='success'>✅ Table 'license_keys' created successfully</p>";
} else {
    echo "<p class='error'>❌ Error creating license_keys: " . $db->error . "</p>";
}

// Create device_activations table
$sql = "CREATE TABLE IF NOT EXISTS device_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255) NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_model VARCHAR(100),
    device_brand VARCHAR(100),
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (key_code, device_id),
    INDEX idx_key_code (key_code),
    INDEX idx_device_id (device_id),
    INDEX idx_last_used (last_used)
)";

if ($db->query($sql)) {
    echo "<p class='success'>✅ Table 'device_activations' created successfully</p>";
} else {
    echo "<p class='error'>❌ Error creating device_activations: " . $db->error . "</p>";
}

// Insert sample keys
echo "<h2>Adding Sample Keys...</h2>";

$sql = "INSERT IGNORE INTO license_keys (key_code, key_type, exp_date, max_devices) VALUES
('PREMIUM-2024-ABC123', 'premium', '2025-12-31', 3),
('LIFETIME-XYZ789', 'lifetime', '2099-12-31', 1),
('TRIAL-30DAYS', 'trial', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1)";

if ($db->query($sql)) {
    echo "<p class='success'>✅ Sample keys added successfully</p>";
} else {
    echo "<p class='error'>❌ Error adding keys: " . $db->error . "</p>";
}

// Verify tables
echo "<h2>Verification:</h2>";

$result = $db->query("SHOW TABLES");
echo "<p><strong>Tables in database:</strong></p><ul>";
while($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Show keys
echo "<h2>License Keys:</h2>";
$result = $db->query("SELECT key_code, key_type, exp_date, max_devices, is_active FROM license_keys");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;background:#fff;'>";
    echo "<tr style='background:#4CAF50;color:white;'>";
    echo "<th>Key Code</th><th>Type</th><th>Expiry</th><th>Max Devices</th><th>Active</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['key_code']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['key_type']) . "</td>";
        echo "<td>" . $row['exp_date'] . "</td>";
        echo "<td>" . $row['max_devices'] . "</td>";
        echo "<td>" . ($row['is_active'] ? '✅ Yes' : '❌ No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>No keys found</p>";
}

$db->close();

echo "<hr>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Update APK with your Railway URL</li>";
echo "<li>Run: <code>update_apk_url.bat YOUR-RAILWAY-URL</code></li>";
echo "<li>Install APK: <code>adb install mymodule-aligned-signed.apk</code></li>";
echo "<li>Test with key: <strong>PREMIUM-2024-ABC123</strong></li>";
echo "</ol>";

echo "<p><strong>Your URLs:</strong></p>";
echo "<ul>";
echo "<li>Admin Panel: <a href='admin.php'>admin.php</a></li>";
echo "<li>API Endpoint: <a href='validate.php'>validate.php</a></li>";
echo "<li>Add Keys: <a href='add_key.php'>add_key.php</a></li>";
echo "</ul>";

echo "<p style='color:#666;margin-top:30px;'>You can delete this setup.php file after setup is complete.</p>";

echo "</body></html>";
?>
