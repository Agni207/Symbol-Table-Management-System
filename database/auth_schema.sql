-- Auth tables for Symbol Table Management System
-- Run this once in phpMyAdmin or via mysql CLI (safe to re-run)

USE symbol_table_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    auth_token VARCHAR(64) DEFAULT NULL,
    token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    reset_token VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_email (email)
);

CREATE TABLE IF NOT EXISTS mail_outbox (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(191) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default demo account: admin@symboltable.local / Admin@123
INSERT INTO users (full_name, email, password_hash)
SELECT 'System Admin', 'admin@symboltable.local', '$2y$10$etx7ZuM1AjdHRZImlngnpOKOmIPZEmuf0LKv4EV8M/OHpJYLWPpBq'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@symboltable.local');
