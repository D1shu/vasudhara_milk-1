-- Vasudhara Milk Distribution System Database Schema
-- Created: 2025

CREATE DATABASE IF NOT EXISTS vasudhara_milk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vasudhara_milk;

-- Master Tables

CREATE TABLE districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE talukas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    district_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE,
    INDEX idx_district (district_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_taluka (district_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE villages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    taluka_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (taluka_id) REFERENCES talukas(id) ON DELETE CASCADE,
    INDEX idx_taluka (taluka_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_village (taluka_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_number VARCHAR(50) NOT NULL UNIQUE,
    route_name VARCHAR(150) NOT NULL,
    vehicle_number VARCHAR(50),
    driver_name VARCHAR(100),
    driver_mobile VARCHAR(15),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE anganwadi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    village_id INT NOT NULL,
    route_id INT,
    aw_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    type ENUM('anganwadi', 'school') NOT NULL,
    total_children INT DEFAULT 0,
    pregnant_women INT DEFAULT 0,
    contact_person VARCHAR(100),
    mobile VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE SET NULL,
    INDEX idx_village (village_id),
    INDEX idx_route (route_id),
    INDEX idx_status (status),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    village_id INT,
    anganwadi_id INT,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100),
    role ENUM('user', 'admin', 'supervisor') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (village_id) REFERENCES villages(id) ON DELETE SET NULL,
    FOREIGN KEY (anganwadi_id) REFERENCES anganwadi(id) ON DELETE SET NULL,
    INDEX idx_mobile (mobile),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_village (village_id),
    INDEX idx_anganwadi (anganwadi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction Tables

CREATE TABLE otp_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expiry_time TIMESTAMP NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile),
    INDEX idx_expiry (expiry_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE weekly_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    anganwadi_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    week_end_date DATE NOT NULL,
    mon_qty DECIMAL(10,2) DEFAULT 0,
    tue_qty DECIMAL(10,2) DEFAULT 0,
    wed_qty DECIMAL(10,2) DEFAULT 0,
    thu_qty DECIMAL(10,2) DEFAULT 0,
    fri_qty DECIMAL(10,2) DEFAULT 0,
    total_qty DECIMAL(10,2) DEFAULT 0,
    children_allocation DECIMAL(10,2) DEFAULT 0,
    pregnant_women_allocation DECIMAL(10,2) DEFAULT 0,
    total_bags INT DEFAULT 0,
    remarks TEXT,
    status ENUM('pending', 'approved', 'rejected', 'dispatched', 'completed') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    dispatched_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (anganwadi_id) REFERENCES anganwadi(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_anganwadi (anganwadi_id),
    INDEX idx_status (status),
    INDEX idx_week_start (week_start_date),
    INDEX idx_week_end (week_end_date),
    UNIQUE KEY unique_weekly_order (anganwadi_id, week_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES weekly_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('order', 'approval', 'dispatch', 'system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data TEXT,
    new_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Data

INSERT INTO districts (name) VALUES 
('Navsari'), ('Valsad'), ('Dang'), ('Tapi');

INSERT INTO talukas (district_id, name) VALUES 
(1, 'Vansda'), (1, 'Jalalpore'), (1, 'Chikhli'),
(2, 'Valsad'), (2, 'Umbergaon'), (2, 'Pardi');

INSERT INTO villages (taluka_id, name) VALUES 
(1, 'Vansda'), (1, 'Bhedkund'), (1, 'Adoli'),
(2, 'Jalalpore'), (2, 'Unava'), (2, 'Uchhal');

INSERT INTO routes (route_number, route_name, vehicle_number, driver_name, driver_mobile) VALUES 
('R001', 'Vansda Town Route', 'GJ-05-AB-1234', 'Vikram Patel', '9876543210'),
('R002', 'Vansda Rural Route', 'GJ-05-CD-5678', 'Ajay Singh', '9876543211'),
('R003', 'Jalalpore Route', 'GJ-05-EF-9012', 'Rajesh Desai', '9876543212');

INSERT INTO anganwadi (village_id, route_id, aw_code, name, type, total_children, pregnant_women, contact_person, mobile) VALUES 
(1, 1, 'AW001', 'Vansda Anganwadi Center', 'anganwadi', 50, 6, 'Pushpa Damor', '9328366460'),
(1, 1, 'AW002', 'Vansda Government School', 'school', 130, 0, 'Aman Malik', '8160948069'),
(2, 1, 'AW003', 'Jalalpore Anganwadi', 'anganwadi', 40, 4, 'Abhi Patel', '7284832327');

INSERT INTO users (anganwadi_id, name, mobile, email, role, status) VALUES 
(1, 'Pushpa Damor', '9328366460', 'pushpa@example.com', 'user', 'active'),
(2, 'Aman Malik', '8160948069', 'aman@example.com', 'user', 'active'),
(3, 'Abhi Patel', '7284832327', 'abhi@example.com', 'user', 'active'),
(NULL, 'Admin User', '9999999999', 'admin@vasudhara.com', 'admin', 'active'),
(NULL, 'Dishant Admin', '9999999991', 'dishant@vasudhara.com', 'admin', 'active');

INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('sms_api', 'fast2sms', 'SMS API provider (fast2sms/msg91)'),
('sms_api_key', '', 'API key for SMS service'),
('bag_size', '500', 'Milk bag size in ml'),
('otp_expiry', '5', 'OTP expiry time in minutes'),
('session_timeout', '30', 'Session timeout in minutes'),
('order_approval_sms', '1', 'Send SMS on order approval (0/1)'),
('company_name', 'Vasudhara Milk Distribution', 'Company name for reports'),
('company_address', 'Vansda, Navsari, Gujarat', 'Company address'),
('company_phone', '020-12345678', 'Company contact number');

-- Sample weekly orders
INSERT INTO weekly_orders (user_id, anganwadi_id, week_start_date, week_end_date, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, total_qty, children_allocation, pregnant_women_allocation, total_bags, status) VALUES 
(1, 1, '2025-11-24', '2025-11-28', 22, 22, 22, 22, 22, 110, 90, 22, 225, 'pending'),
(2, 2, '2025-11-24', '2025-11-28', 60, 60, 60, 60, 60, 300, 300, 0, 600, 'approved'),
(3, 3, '2025-11-24', '2025-11-28', 20.5, 19, 21, 20.5, 19, 100, 76, 24, 200, 'dispatched');