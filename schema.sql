-- Student Safety & Incident Reporting System - Database Schema

CREATE student_safety;
USE student_safety;

-- Users table (staff/admins who log in)
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(100) NOT NULL,
    role       ENUM('admin','s
    taff') NOT NULL DEFAULT 'staff',
    email      VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAM
);

-- Students table
CREATE TABLE students (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    student_code     VARCHAR(20)  NOT NULL UNIQUE,
    first_name       VARCHAR(50)  NOT NULL,
    last_name        VARCHAR(50)  NOT NULL,
    grade            VARCHAR(10)  NOT NULL,
    section          VARCHAR(10),
    date_of_birth    DATE,
    guardian_name    VARCHAR(100),
    guardian_contact VARCHAR(20),
    address          TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Incident categories table
CREATE TABLE incident_categories (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    description    TEXT,
    severity_level ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low'
);

-- Incidents table
CREATE TABLE incidents (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    incident_code VARCHAR(20)  NOT NULL UNIQUE,
    student_id    INT          NOT NULL,
    category_id   INT          NOT NULL,
    reported_by   INT          NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT         NOT NULL,
    location      VARCHAR(100),
    incident_date DATE         NOT NULL,
    incident_time TIME,
    status        ENUM('open','under_review','resolved','closed') NOT NULL DEFAULT 'open',
    action_taken  TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id)  REFERENCES students(id)            ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES incident_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (reported_by) REFERENCES users(id)               ON DELETE RESTRICT
);

-- Activity logs table
CREATE TABLE activity_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    action       VARCHAR(100) NOT NULL,
    target_table VARCHAR(50),
    target_id    INT,
    details      TEXT,
    ip_address   VARCHAR(45),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

