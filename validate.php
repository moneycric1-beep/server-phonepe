<?php
/**
 * License Key Validation API
 * Combined System: Online + Device-Based
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Railway MySQL connection (uses environment variables)
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'license_system';
$db_port = getenv('MYSQLPORT') ?: 3306;

// Connect to database
$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($db->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed'
    ]));
}

// Auto-create tables if they don't exist
$db->query("CREATE TABLE IF NOT EXISTS license_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255) UNIQUE NOT NULL,
    key_type VARCHAR(50) NOT NULL,
    exp_date DATE NOT NULL,
    max_devices INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    bot_token VARCHAR(255) DEFAULT '',
    chat_id VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS device_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255) NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_model VARCHAR(100),
    device_brand VARCHAR(100),
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (key_code, device_id)
)");

// Add sample keys if table is empty
$result = $db->query("SELECT COUNT(*) as count FROM license_keys");
if ($result && $result->fetch_assoc()['count'] == 0) {
    $db->query("INSERT INTO license_keys (key_code, key_type, exp_date, max_devices) VALUES
    ('PREMIUM-2024-ABC123', 'premium', '2025-12-31', 3),
    ('LIFETIME-XYZ789', 'lifetime', '2099-12-31', 1),
    ('TRIAL-30DAYS', 'trial', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1)");
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$key = $db->real_escape_string($input['key'] ?? '');
$deviceId = $db->real_escape_string($input['device_id'] ?? 'unknown-' . uniqid());
$deviceModel = $db->real_escape_string($input['device_model'] ?? 'Unknown');
$deviceBrand = $db->real_escape_string($input['device_brand'] ?? 'Unknown');

// Validate input
if (empty($key)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'License key is required'
    ]);
    exit;
}

// Check if key exists and is active
$result = $db->query("
    SELECT * FROM license_keys 
    WHERE key_code='$key' 
    AND is_active=1 
    AND exp_date >= CURDATE()
");

if ($row = $result->fetch_assoc()) {
    
    // Check how many devices are using this key
    $deviceCheck = $db->query("
        SELECT COUNT(*) as count 
        FROM device_activations 
        WHERE key_code='$key'
    ");
    $deviceCount = $deviceCheck->fetch_assoc()['count'];
    
    // Check if THIS device is already registered
    $existingDevice = $db->query("
        SELECT * FROM device_activations 
        WHERE key_code='$key' 
        AND device_id='$deviceId'
    ");
    
    if ($existingDevice->num_rows > 0) {
        // Device already registered - allow login
        $deviceData = $existingDevice->fetch_assoc();
        
        // Update last used timestamp
        $db->query("
            UPDATE device_activations 
            SET last_used=NOW() 
            WHERE id={$deviceData['id']}
        ");
        
        // Create session data
        $sessionData = [
            'key' => $key,
            'device_id' => $deviceId,
            'activated' => true,
            'type' => $row['key_type'],
            'exp_date' => $row['exp_date'],
            'bot_token' => $row['bot_token'] ?? '',
            'chat_id' => $row['chat_id'] ?? ''
        ];
        
        // Encode session data (simple base64)
        $encoded = base64_encode(json_encode($sessionData));
        
        echo json_encode([
            'status' => 'ok',
            'data' => $encoded,
            'message' => 'Welcome back!'
        ]);
        
    } elseif ($deviceCount < $row['max_devices']) {
        // New device - check if we can add it
        
        // Register new device
        $db->query("
            INSERT INTO device_activations 
            (key_code, device_id, device_model, device_brand, activated_at, last_used) 
            VALUES 
            ('$key', '$deviceId', '$deviceModel', '$deviceBrand', NOW(), NOW())
        ");
        
        // Create session data
        $sessionData = [
            'key' => $key,
            'device_id' => $deviceId,
            'activated' => true,
            'type' => $row['key_type'],
            'exp_date' => $row['exp_date'],
            'bot_token' => $row['bot_token'] ?? '',
            'chat_id' => $row['chat_id'] ?? ''
        ];
        
        // Encode session data
        $encoded = base64_encode(json_encode($sessionData));
        
        echo json_encode([
            'status' => 'ok',
            'data' => $encoded,
            'message' => 'Device activated successfully!'
        ]);
        
    } else {
        // Device limit reached
        echo json_encode([
            'status' => 'error',
            'message' => "Maximum devices ({$row['max_devices']}) reached for this key"
        ]);
    }
    
} else {
    // Invalid or expired key
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired license key'
    ]);
}

$db->close();
?>
