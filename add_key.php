<?php
// Railway MySQL connection
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'license_system';
$db_port = getenv('MYSQLPORT') ?: 3306;

$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $keyCode = $db->real_escape_string($_POST['key_code']);
    $keyType = $db->real_escape_string($_POST['key_type']);
    $expDate = $db->real_escape_string($_POST['exp_date']);
    $maxDevices = (int)$_POST['max_devices'];
    $botToken = $db->real_escape_string($_POST['bot_token'] ?? '');
    $chatId = $db->real_escape_string($_POST['chat_id'] ?? '');
    
    $result = $db->query("
        INSERT INTO license_keys 
        (key_code, key_type, exp_date, max_devices, bot_token, chat_id) 
        VALUES 
        ('$keyCode', '$keyType', '$expDate', $maxDevices, '$botToken', '$chatId')
    ");
    
    if ($result) {
        header('Location: admin.php?success=1');
    } else {
        header('Location: admin.php?error=' . urlencode($db->error));
    }
} else {
    header('Location: admin.php');
}
?>
