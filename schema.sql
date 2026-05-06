-- ============================================================
-- Student Safety & Incident Reporting System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_safety;
USE student_safety;

-- Users table (staff/admins who log in)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    grade VARCHAR(10) NOT NULL,
    section VARCHAR(10),
    date_of_birth DATE,
    guardian_name VARCHAR(100),
    guardian_contact VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Incident categories table
CREATE TABLE IF NOT EXISTS incident_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    severity_level ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'low'
);

-- Incidents table
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_code VARCHAR(20) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    category_id INT NOT NULL,
    reported_by INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(100),
    incident_date DATE NOT NULL,
    incident_time TIME,
    status ENUM('open', 'under_review', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    action_taken TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES incident_categories(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_table VARCHAR(50),
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
-- Seed Data
-- ============================================================

-- Default users (password for all: "password")
-- Hash generated with: password_hash('password', PASSWORD_DEFAULT)
INSERT INTO users (username, password, full_name, role, email) VALUES
('admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'admin@school.edu'),
('jsmith',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith',           'staff', 'jsmith@school.edu'),
('mjohnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Johnson',         'staff', 'mjohnson@school.edu');

-- Incident categories
INSERT INTO incident_categories (name, description, severity_level) VALUES
('Bullying', 'Physical or verbal bullying between students', 'high'),
('Physical Injury', 'Accidents or injuries on school premises', 'critical'),
('Theft', 'Stolen property or belongings', 'medium'),
('Vandalism', 'Damage to school or personal property', 'medium'),
('Substance Abuse', 'Involvement with prohibited substances', 'critical'),
('Unauthorized Absence', 'Truancy or leaving school without permission', 'low'),
('Cyberbullying', 'Online harassment or bullying', 'high'),
('Verbal Altercation', 'Arguments or verbal disputes', 'low');

-- Sample students
INSERT INTO students (student_id, first_name, last_name, grade, section, date_of_birth, guardian_name, guardian_contact) VALUES
('STU-001', 'Alice', 'Martinez', '10', 'A', '2009-03-15', 'Rosa Martinez', '555-0101'),
('STU-002', 'Bob', 'Chen', '10', 'B', '2009-07-22', 'Wei Chen', '555-0102'),
('STU-003', 'Carol', 'Williams', '11', 'A', '2008-11-05', 'James Williams', '555-0103'),
('STU-004', 'David', 'Brown', '9', 'C', '2010-01-30', 'Susan Brown', '555-0104'),
('STU-005', 'Emma', 'Davis', '12', 'A', '2007-06-18', 'Michael Davis', '555-0105'),
('STU-006', 'Frank', 'Garcia', '11', 'B', '2008-09-12', 'Ana Garcia', '555-0106'),
('STU-007', 'Grace', 'Lee', '9', 'A', '2010-04-25', 'Kevin Lee', '555-0107'),
('STU-008', 'Henry', 'Wilson', '10', 'C', '2009-12-08', 'Patricia Wilson', '555-0108');

-- Sample incidents
INSERT INTO incidents (incident_code, student_id, category_id, reported_by, title, description, location, incident_date, incident_time, status, action_taken) VALUES
('INC-2024-001', 1, 1, 2, 'Bullying in cafeteria', 'Student was verbally harassed during lunch break.', 'Cafeteria', '2024-01-15', '12:30:00', 'resolved', 'Parents notified, counseling session scheduled.'),
('INC-2024-002', 3, 2, 2, 'Playground injury', 'Student fell from playground equipment and injured knee.', 'Playground', '2024-01-18', '10:15:00', 'closed', 'First aid administered, parents informed.'),
('INC-2024-003', 5, 3, 3, 'Missing backpack', 'Student reported backpack stolen from locker room.', 'Locker Room', '2024-02-05', '14:00:00', 'under_review', 'Investigation ongoing, CCTV reviewed.'),
('INC-2024-004', 2, 7, 3, 'Online harassment', 'Student received threatening messages via social media.', 'Online', '2024-02-10', '20:00:00', 'open', NULL),
('INC-2024-005', 6, 6, 2, 'Unauthorized absence', 'Student left school premises without permission.', 'Main Gate', '2024-02-14', '13:45:00', 'resolved', 'Parents contacted, warning issued.'),
('INC-2024-006', 4, 4, 3, 'Graffiti on wall', 'Student found writing on bathroom wall.', 'Bathroom Block B', '2024-03-01', '11:00:00', 'closed', 'Student required to clean area, parents notified.'),
('INC-2024-007', 7, 1, 2, 'Physical altercation', 'Two students involved in physical fight near gym.', 'Gymnasium', '2024-03-10', '15:30:00', 'under_review', 'Both students suspended pending investigation.'),
('INC-2024-008', 8, 8, 3, 'Verbal dispute with teacher', 'Student argued with teacher during class.', 'Classroom 2B', '2024-03-15', '09:00:00', 'resolved', 'Mediation session held.');

-- Sample activity logs
INSERT INTO activity_logs (user_id, action, target_table, target_id, details, ip_address) VALUES
(1, 'LOGIN', NULL, NULL, 'Admin logged in', '127.0.0.1'),
(2, 'CREATE', 'incidents', 1, 'Created incident INC-2024-001', '127.0.0.1'),
(2, 'CREATE', 'incidents', 2, 'Created incident INC-2024-002', '127.0.0.1'),
(3, 'CREATE', 'incidents', 3, 'Created incident INC-2024-003', '127.0.0.1'),
(1, 'UPDATE', 'incidents', 1, 'Updated status to resolved', '127.0.0.1'),
(3, 'CREATE', 'incidents', 4, 'Created incident INC-2024-004', '127.0.0.1');
