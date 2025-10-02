-- Create database
CREATE DATABASE IF NOT EXISTS inventory_management_system;
USE inventory_management_system;

-- User Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(255) NOT NULL,
    employee_no VARCHAR(50) UNIQUE NOT NULL,
    date_of_birth DATE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory Table
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(255) NOT NULL,
    item_description TEXT,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit_of_measurement VARCHAR(50) NOT NULL COMMENT 'pc(s)/box(s)/roll(s)/rem(s)/pack(s)/ml/etc.',
    property_number VARCHAR(100),
    accountable_person VARCHAR(255),
    date_acquired DATE,
    fund_cluster VARCHAR(50),
    remarks_notes TEXT,
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    inventory_code_type ENUM('ICS', 'PAR', 'RIS') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_name (item_name),
    INDEX idx_inventory_code_type (inventory_code_type),
    INDEX idx_accountable_person (accountable_person)
);

-- PAR (Property Acknowledgement Receipt) Table
CREATE TABLE par (
    id INT PRIMARY KEY AUTO_INCREMENT,
    par_no VARCHAR(50) UNIQUE NOT NULL,
    fund_cluster VARCHAR(50),
    quantity DECIMAL(10,2) NOT NULL,
    unit_of_measurement VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_description TEXT,
    property_number VARCHAR(100),
    date_acquired DATE,
    total_cost DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_par_no (par_no),
    INDEX idx_item_name (item_name)
);

-- ICS (Inventory Custodian Slip) Table
CREATE TABLE ics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ics_no VARCHAR(50) UNIQUE NOT NULL,
    fund_cluster VARCHAR(50),
    quantity DECIMAL(10,2) NOT NULL,
    unit_of_measurement VARCHAR(50) NOT NULL,
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    item_name VARCHAR(255) NOT NULL,
    item_description TEXT,
    inventory_item_no VARCHAR(100),
    estimated_useful_life VARCHAR(50),
    date_acquired DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ics_no (ics_no),
    INDEX idx_item_name (item_name),
    INDEX idx_inventory_item_no (inventory_item_no)
);

-- RIS (Requisition and Issue Slip) Table
CREATE TABLE ris (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ris_no VARCHAR(50) UNIQUE NOT NULL,
    office VARCHAR(255),
    stock_no VARCHAR(50),
    responsibility_center_code VARCHAR(50),
    unit_of_measurement VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    remarks TEXT,
    purpose TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ris_no (ris_no),
    INDEX idx_office (office),
    INDEX idx_item_name (item_name)
);

ALTER TABLE par
ADD COLUMN received_by VARCHAR(255) AFTER total_cost,
ADD COLUMN received_by_position VARCHAR(255) AFTER received_by,
ADD COLUMN received_date DATE AFTER received_by_position,
ADD COLUMN issued_by VARCHAR(255) AFTER received_date,
ADD COLUMN issued_by_position VARCHAR(255) AFTER issued_by,
ADD COLUMN issued_date DATE AFTER issued_by_position,
ADD COLUMN reference VARCHAR(255) AFTER issued_date;