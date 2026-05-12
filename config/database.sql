-- HRMS Database Schema
CREATE DATABASE IF NOT EXISTS hrms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrms;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','hr','executive','employee') NOT NULL DEFAULT 'employee',
    department VARCHAR(100),
    position VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    local_leaves INT DEFAULT 22,
    sick_leaves INT DEFAULT 15,
    unpaid_leaves INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in DATETIME,
    clock_out DATETIME,
    break_start DATETIME,
    break_end DATETIME,
    clock_in_location VARCHAR(255),
    clock_out_location VARCHAR(255),
    total_hours DECIMAL(5,2) DEFAULT 0,
    status ENUM('present','absent','late','half_day','on_leave') DEFAULT 'present',
    modified_by INT,
    modification_reason TEXT,
    original_clock_in DATETIME,
    original_clock_out DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date)
);

CREATE TABLE IF NOT EXISTS attendance_modification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    modified_by INT NOT NULL,
    original_clock_in DATETIME,
    original_clock_out DATETIME,
    new_clock_in DATETIME,
    new_clock_out DATETIME,
    reason TEXT NOT NULL,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type ENUM('local','sick','unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT,
    reviewed_at DATETIME,
    review_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    due_date DATE,
    priority ENUM('low','medium','high') DEFAULT 'medium',
    status ENUM('pending','completed','delayed') DEFAULT 'pending',
    delay_reason TEXT,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    posted_by INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS report_archives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    file_path VARCHAR(255),
    generated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Default settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('company_name', 'My Company'),
('company_logo', ''),
('work_start_time', '08:30'),
('work_end_time', '17:00'),
('break_max_hours', '1'),
('break_deadline', '15:00'),
('annual_local_leaves', '22'),
('annual_sick_leaves', '15');

-- Default admin user (password: Admin@123)
INSERT INTO users (employee_id, first_name, last_name, email, password, role, department, position) VALUES
('EMP001', 'System', 'Admin', 'admin@hrms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Management', 'System Administrator');
