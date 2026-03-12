-- Database Schema for License System
-- Combined: Online + Device-Based

CREATE DATABASE IF NOT EXISTS license_system;
USE license_system;

-- License keys table
CREATE TABLE IF NOT EXISTS license_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255) UNIQUE NOT NULL,
    key_type VARCHAR(50) NOT NULL,
    exp_date DATE NOT NULL,
    max_devices INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    bot_token VARCHAR(255) DEFAULT '',
    chat_id VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100),
    notes TEXT,
    INDEX idx_key_code (key_code),
    INDEX idx_active (is_active),
    INDEX idx_exp_date (exp_date)
);

-- Device activations table
CREATE TABLE IF NOT EXISTS device_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255) NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_model VARCHAR(100),
    device_brand VARCHAR(100),
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (key_code, device_id),
    FOREIGN KEY (key_code) REFERENCES license_keys(key_code) ON DELETE CASCADE,
    INDEX idx_key_code (key_code),
    INDEX idx_device_id (device_id),
    INDEX idx_last_used (last_used)
);

-- Sample data
INSERT INTO license_keys (key_code, key_type, exp_date, max_devices, bot_token, chat_id) VALUES
('PREMIUM-2024-ABC123', 'premium', '2025-12-31', 3, '', ''),
('LIFETIME-XYZ789', 'lifetime', '2099-12-31', 1, '', ''),
('TRIAL-30DAYS-DEF456', 'trial', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, '', ''),
('ULTIMATE-2024-GHI789', 'ultimate', '2025-12-31', 5, '', '');

-- Admin user table (optional)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity log table (optional)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_code VARCHAR(255),
    device_id VARCHAR(255),
    action VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_code (key_code),
    INDEX idx_created_at (created_at)
);
