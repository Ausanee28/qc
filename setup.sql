-- ============================================
-- QC Lab Tracking System โ€” Database Setup
-- Run this script in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS qc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qc;

-- Departments
CREATE TABLE IF NOT EXISTS Departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    internal_phone VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internal Users (for login & test execution)
CREATE TABLE IF NOT EXISTS Internal_Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    employee_id VARCHAR(50),
    name VARCHAR(100),
    role VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- External Users (senders from other departments)
CREATE TABLE IF NOT EXISTS External_Users (
    external_id INT AUTO_INCREMENT PRIMARY KEY,
    external_name VARCHAR(100) NOT NULL,
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES Departments(department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipments
CREATE TABLE IF NOT EXISTS Equipments (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test Methods
CREATE TABLE IF NOT EXISTS Test_Methods (
    method_id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL,
    tool_name VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Header (job intake)
CREATE TABLE IF NOT EXISTS Transaction_Header (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    external_id INT NOT NULL,
    internal_id INT NOT NULL,
    equipment_id INT NOT NULL,
    dmc VARCHAR(100),
    line VARCHAR(100),
    receive_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Pending',
    return_date DATETIME,
    FOREIGN KEY (external_id) REFERENCES External_Users(external_id),
    FOREIGN KEY (internal_id) REFERENCES Internal_Users(user_id),
    FOREIGN KEY (equipment_id) REFERENCES Equipments(equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction Detail (test results)
CREATE TABLE IF NOT EXISTS Transaction_Detail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    method_id INT NOT NULL,
    internal_id INT NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    judgement VARCHAR(50),
    remark VARCHAR(255),
    FOREIGN KEY (transaction_id) REFERENCES Transaction_Header(transaction_id),
    FOREIGN KEY (method_id) REFERENCES Test_Methods(method_id),
    FOREIGN KEY (internal_id) REFERENCES Internal_Users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- SEED DATA
-- ============================================

-- Departments
INSERT INTO Departments (department_name, internal_phone) VALUES
('Production Line A', '1001'),
('Production Line B', '1002'),
('Assembly', '1003'),
('Quality Assurance', '1004');

-- Internal Users (password = 'password' hashed with password_hash)
INSERT INTO Internal_Users (user_name, user_password, employee_id, name, role) VALUES
('admin', '$2y$10$7eeZE4b3Jv3bkpLLI0kU6.Y7eLoFV3dMiVjLkIyYTO9SjzdZRuIsC', 'EMP001', 'Admin User', 'admin'),
('inspector1', '$2y$10$7eeZE4b3Jv3bkpLLI0kU6.Y7eLoFV3dMiVjLkIyYTO9SjzdZRuIsC', 'EMP002', 'Somchai Tester', 'inspector'),
('inspector2', '$2y$10$7eeZE4b3Jv3bkpLLI0kU6.Y7eLoFV3dMiVjLkIyYTO9SjzdZRuIsC', 'EMP003', 'Nattaya Inspector', 'inspector');

-- External Users (senders)
INSERT INTO External_Users (external_name, department_id) VALUES
('Anong Sender', 1),
('Boonmee Operator', 2),
('Chaiya Supervisor', 3);

-- Equipments
INSERT INTO Equipments (equipment_name) VALUES
('Caliper'),
('Micrometer'),
('CMM Machine'),
('Hardness Tester'),
('Surface Roughness Tester');

-- Test Methods
INSERT INTO Test_Methods (method_name, tool_name) VALUES
('Dimensional Check', 'Caliper'),
('Surface Inspection', 'Visual'),
('Hardness Test', 'Rockwell Hardness Tester'),
('Roughness Measurement', 'Surface Roughness Tester'),
('CMM Measurement', 'CMM Machine');

