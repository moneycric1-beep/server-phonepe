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

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$key = $db->real_escape_string($input['key'] ?? '');
$deviceId = $db->real_escape_string($input['device_id'] ?? '');
$deviceModel = $db->real_escape_string($input['device_model'] ?? '');
$deviceBrand = $db->real_escape_string($input['device_brand'] ?? '');

// Validate input
if (empty($key) || empty($deviceId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Key and device ID are required'
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
