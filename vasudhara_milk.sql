-- vasudhara_milk_clean_test.sql
-- Vasudhara Milk Distribution System - Clean schema + minimal test records
-- Created: 2025-11-28
-- Purpose: schema + minimal test data for development/testing (no trash)

CREATE DATABASE IF NOT EXISTS vasudhara_milk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vasudhara_milk;

-- -------------------------
-- MASTER TABLES
-- -------------------------

CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS talukas (
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

CREATE TABLE IF NOT EXISTS villages (
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

CREATE TABLE IF NOT EXISTS routes (
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

CREATE TABLE IF NOT EXISTS anganwadi (
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

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anganwadi_id INT,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100),
    role ENUM('user', 'admin', 'supervisor') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (anganwadi_id) REFERENCES anganwadi(id) ON DELETE SET NULL,
    INDEX idx_mobile (mobile),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_anganwadi (anganwadi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------
-- TRANSACTION TABLES
-- -------------------------

CREATE TABLE IF NOT EXISTS otp_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expiry_time TIMESTAMP NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile),
    INDEX idx_expiry (expiry_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS weekly_orders (
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

CREATE TABLE IF NOT EXISTS order_status_history (
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

CREATE TABLE IF NOT EXISTS notifications (
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

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
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

-- -------------------------
-- CLEAN TEST DATA (minimal)
-- -------------------------

-- districts (minimal set for tests)
INSERT INTO districts (name, status) VALUES
('Navsari', 'active'),
('Valsad',  'active'),
('Dang',    'active');

-- talukas (couple per district)
INSERT INTO talukas (district_id, name, status) VALUES
((SELECT id FROM districts WHERE name='Navsari' LIMIT 1), 'Vansda',  'active'),
((SELECT id FROM districts WHERE name='Navsari' LIMIT 1), 'Jalalpore','active'),

((SELECT id FROM districts WHERE name='Valsad' LIMIT 1), 'Valsad',  'active'),
((SELECT id FROM districts WHERE name='Valsad' LIMIT 1), 'Pardi',   'active'),

((SELECT id FROM districts WHERE name='Dang' LIMIT 1), 'Ahwa',      'active'),
((SELECT id FROM districts WHERE name='Dang' LIMIT 1), 'Waghai',    'active');

-- villages (one or two per taluka for testing)
INSERT INTO villages (taluka_id, name, status) VALUES
((SELECT id FROM talukas WHERE name='Vansda'   AND district_id=(SELECT id FROM districts WHERE name='Navsari') LIMIT 1), 'Vansda','active'),
((SELECT id FROM talukas WHERE name='Vansda'   AND district_id=(SELECT id FROM districts WHERE name='Navsari') LIMIT 1), 'Khadakiya','active'),

((SELECT id FROM talukas WHERE name='Jalalpore' AND district_id=(SELECT id FROM districts WHERE name='Navsari') LIMIT 1), 'Jalalpore','active'),

((SELECT id FROM talukas WHERE name='Valsad'   AND district_id=(SELECT id FROM districts WHERE name='Valsad') LIMIT 1), 'Valsad','active'),
((SELECT id FROM talukas WHERE name='Pardi'    AND district_id=(SELECT id FROM districts WHERE name='Valsad') LIMIT 1), 'Pardi','active'),

((SELECT id FROM talukas WHERE name='Ahwa'     AND district_id=(SELECT id FROM districts WHERE name='Dang') LIMIT 1), 'Ahwa','active'),
((SELECT id FROM talukas WHERE name='Waghai'   AND district_id=(SELECT id FROM districts WHERE name='Dang') LIMIT 1), 'Waghai','active');

-- routes (single test route)
INSERT INTO routes (route_number, route_name, vehicle_number, driver_name, driver_mobile, status) VALUES
('RT-TEST-01', 'Test Route 1', 'GJ-XX-0001', 'Test Driver', '9000000000', 'active');

-- anganwadi (two test centres)
INSERT INTO anganwadi (village_id, route_id, aw_code, name, type, total_children, pregnant_women, contact_person, mobile, status) VALUES
(
  (SELECT id FROM villages WHERE name='Vansda' AND taluka_id=(SELECT id FROM talukas WHERE name='Vansda' AND district_id=(SELECT id FROM districts WHERE name='Navsari')) LIMIT 1),
  (SELECT id FROM routes WHERE route_number='RT-TEST-01' LIMIT 1),
  'AW001', 'Vansda Test Anganwadi', 'anganwadi', 10, 1, 'Test Worker 1', '9999999991', 'active'
),
(
  (SELECT id FROM villages WHERE name='Khadakiya' AND taluka_id=(SELECT id FROM talukas WHERE name='Vansda' AND district_id=(SELECT id FROM districts WHERE name='Navsari')) LIMIT 1),
  (SELECT id FROM routes WHERE route_number='RT-TEST-01' LIMIT 1),
  'AW002', 'Khadakiya Test Anganwadi', 'anganwadi', 8, 0, 'Test Worker 2', '9999999992', 'active'
);

-- users (two test users linked to the test anganwadis)
INSERT INTO users (anganwadi_id, name, mobile, email, role, status) VALUES
((SELECT id FROM anganwadi WHERE aw_code='AWW001' LIMIT 1), 'Abhi Test', '9999999991', 'priya.test@example.com', 'user', 'active'),
((SELECT id FROM anganwadi WHERE aw_code='AWW002' LIMIT 1), 'Aman Test', '9999999992', 'mohan.test@example.com', 'user', 'active'),
(NULL, 'Admin Test', '9999999999', 'admin.test@example.com', 'admin', 'active');

-- system settings (minimal)
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('sms_api', 'mock', 'SMS API provider - test'),
('bag_size', '500', 'Milk bag size in ml - test');

-- optional: one sample weekly order (test) — links user + anganwadi
INSERT INTO weekly_orders (user_id, anganwadi_id, week_start_date, week_end_date, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, total_qty, children_allocation, pregnant_women_allocation, total_bags, status)
VALUES
(
  (SELECT id FROM users WHERE mobile='9999999991' LIMIT 1),
  (SELECT id FROM anganwadi WHERE aw_code='AW001' LIMIT 1),
  CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY),
  1.0, 1.0, 1.0, 1.0, 1.0,
  5.0, 5.0, 0.0, 10, 'pending'
);
INSERT INTO weekly_orders (user_id, anganwadi_id, week_start_date, week_end_date, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, total_qty, children_allocation, pregnant_women_allocation, total_bags, status)
VALUES
(
  (SELECT id FROM users WHERE mobile='9999999992' LIMIT 1),
  (SELECT id FROM anganwadi WHERE aw_code='AW002' LIMIT 1),
  CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 DAY),
  1.0, 1.0, 1.0, 1.0, 1.0,
  5.0, 5.0, 0.0, 10, 'pending'
);



-- -------------------------
-- CLEANUP / Sanity notes
-- -------------------------
-- This file is intentionally minimal — add more test rows as needed.
-- If you want me to:
--  1) generate more test anganwadis/users in bulk, or
--  2) create a CSV with 100+ villages for Navsari/Valsad/Dang,
-- say the word and I'll prepare it.

