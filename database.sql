-- Complaint and Resolution Tracking System
-- Enrollment: 230210107029 | U=29 | Domain: Network and Connectivity Issues | Area: Zone→Sector→Spot
-- Initial Response SLA: 5 hours | Resolution SLA: 48 hours
-- Special Rule: Repeated complaint flagging (U is odd)
-- Mandatory Report: Reopened complaints summary (R=5)

CREATE DATABASE IF NOT EXISTS complaint_system;
USE complaint_system;

-- Status Master
CREATE TABLE status_master (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL UNIQUE
);
INSERT INTO status_master (status_name) VALUES
('Submitted'),('Verified'),('Assigned'),('In Progress'),('Resolved'),('Closed'),('Reopened'),('Escalated');

-- Roles
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);
INSERT INTO roles (role_name) VALUES ('Complainant'),('Staff'),('Supervisor');

-- Users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Default users (passwords are md5 hashed for demo - use bcrypt in production)
INSERT INTO users (username, password, full_name, email, phone, role_id) VALUES
('admin', MD5('admin123'), 'Admin Supervisor', 'admin@example.com', '9876543210', 3),
('staff1', MD5('staff123'), 'Rahul Sharma', 'rahul@example.com', '9876543211', 2),
('staff2', MD5('staff123'), 'Priya Patel', 'priya@example.com', '9876543212', 2),
('user1', MD5('user123'), 'Amit Kumar', 'amit@example.com', '9876543213', 1),
('user2', MD5('user123'), 'Neha Singh', 'neha@example.com', '9876543214', 1);

-- Zone (Level 1 of area hierarchy)
CREATE TABLE zones (
    zone_id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1
);
INSERT INTO zones (zone_name) VALUES ('North Zone'),('South Zone'),('East Zone'),('West Zone');

-- Sector (Level 2)
CREATE TABLE sectors (
    sector_id INT AUTO_INCREMENT PRIMARY KEY,
    sector_name VARCHAR(100) NOT NULL,
    zone_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
);
INSERT INTO sectors (sector_name, zone_id) VALUES
('Sector A',1),('Sector B',1),('Sector C',2),('Sector D',2),
('Sector E',3),('Sector F',3),('Sector G',4),('Sector H',4);

-- Spot (Level 3)
CREATE TABLE spots (
    spot_id INT AUTO_INCREMENT PRIMARY KEY,
    spot_name VARCHAR(100) NOT NULL,
    sector_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (sector_id) REFERENCES sectors(sector_id)
);
INSERT INTO spots (spot_name, sector_id) VALUES
('Server Room 1',1),('Lab 101',1),('Office Block A',2),('Conference Room',2),
('Data Center',3),('IT Hub',3),('Network Room',4),('Workshop',4),
('Library',5),('Admin Block',5),('Canteen',6),('Hostel Block',6),
('Main Gate',7),('Parking Area',7),('Sports Complex',8),('Auditorium',8);

-- Complaint Categories (Network & Connectivity domain)
CREATE TABLE complaint_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1
);
INSERT INTO complaint_categories (category_name, description) VALUES
('WiFi Connectivity','Issues related to WiFi signal strength, connectivity drops, or authentication failures'),
('LAN/Ethernet Issues','Problems with wired network connections, damaged cables, or port failures'),
('Internet Speed','Slow internet speed, high latency, or bandwidth throttling issues'),
('Server Downtime','Server unavailability, crashes, or maintenance-related outages'),
('VPN/Remote Access','Issues with VPN connectivity, remote desktop, or secure access'),
('Network Security','Unauthorized access, firewall issues, or suspicious network activity'),
('DNS/IP Issues','DNS resolution failures, IP conflicts, or DHCP problems'),
('Hardware Failure','Router, switch, access point, or modem hardware malfunctions');

-- Complaints
CREATE TABLE complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    zone_id INT NOT NULL,
    sector_id INT NOT NULL,
    spot_id INT NOT NULL,
    exact_location VARCHAR(255),
    priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    current_status_id INT DEFAULT 1,
    complainant_id INT NOT NULL,
    assigned_staff_id INT DEFAULT NULL,
    complaint_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    initial_response_deadline DATETIME,
    resolution_deadline DATETIME,
    resolved_at DATETIME DEFAULT NULL,
    closed_at DATETIME DEFAULT NULL,
    is_repeated_flag TINYINT(1) DEFAULT 0,
    reopen_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES complaint_categories(category_id),
    FOREIGN KEY (zone_id) REFERENCES zones(zone_id),
    FOREIGN KEY (sector_id) REFERENCES sectors(sector_id),
    FOREIGN KEY (spot_id) REFERENCES spots(spot_id),
    FOREIGN KEY (current_status_id) REFERENCES status_master(status_id),
    FOREIGN KEY (complainant_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_staff_id) REFERENCES users(user_id)
);

-- Complaint Attachments
CREATE TABLE complaint_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    upload_type ENUM('complaint_proof','action_proof') DEFAULT 'complaint_proof',
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- Complaint History
CREATE TABLE complaint_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    old_status_id INT,
    new_status_id INT NOT NULL,
    changed_by INT NOT NULL,
    remark TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (old_status_id) REFERENCES status_master(status_id),
    FOREIGN KEY (new_status_id) REFERENCES status_master(status_id),
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

-- Assignments
CREATE TABLE assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    staff_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (staff_id) REFERENCES users(user_id),
    FOREIGN KEY (assigned_by) REFERENCES users(user_id)
);

-- Feedback
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Sample complaints for demo
INSERT INTO complaints (complaint_code, title, description, category_id, zone_id, sector_id, spot_id, exact_location, priority, current_status_id, complainant_id, assigned_staff_id, complaint_date, initial_response_deadline, resolution_deadline) VALUES
('CMP-2025-001', 'WiFi not working in Server Room', 'WiFi connectivity is completely down in Server Room 1. Unable to access any network resources.', 1, 1, 1, 1, 'Near the main rack', 'High', 3, 4, 2, NOW(), DATE_ADD(NOW(), INTERVAL 5 HOUR), DATE_ADD(NOW(), INTERVAL 48 HOUR)),
('CMP-2025-002', 'Slow internet in Lab 101', 'Internet speed is extremely slow in Lab 101. Download speed is less than 1 Mbps.', 3, 1, 1, 2, 'All workstations', 'Medium', 1, 4, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 5 HOUR), DATE_ADD(NOW(), INTERVAL 48 HOUR)),
('CMP-2025-003', 'LAN port damaged in Conference Room', 'Two LAN ports in Conference Room are physically damaged and not providing connectivity.', 2, 1, 2, 4, 'Ports near projector', 'Low', 4, 5, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 5 HOUR), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 48 HOUR));

INSERT INTO complaint_history (complaint_id, old_status_id, new_status_id, changed_by, remark) VALUES
(1, NULL, 1, 4, 'Complaint submitted'),
(1, 1, 2, 1, 'Complaint verified by supervisor'),
(1, 2, 3, 1, 'Assigned to staff Rahul Sharma'),
(2, NULL, 1, 4, 'Complaint submitted'),
(3, NULL, 1, 5, 'Complaint submitted'),
(3, 1, 2, 1, 'Verified'),
(3, 2, 3, 1, 'Assigned to Priya'),
(3, 3, 4, 2, 'Work in progress');