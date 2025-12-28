<?php

use Illuminate\Database\Capsule\Manager as DB;

class CreateSecurityTables
{
    public function up(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_classification (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                name_i18n VARCHAR(100),
                level INT NOT NULL DEFAULT 0,
                color VARCHAR(20),
                description TEXT,
                requires_clearance TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_name (name),
                INDEX idx_level (level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS object_security_classification (
                id INT AUTO_INCREMENT PRIMARY KEY,
                object_id INT NOT NULL,
                classification_id INT NOT NULL,
                classified_by INT,
                classified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                declassify_on DATE,
                reason TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_object (object_id),
                INDEX idx_classification (classification_id),
                CONSTRAINT fk_osc_classification FOREIGN KEY (classification_id) REFERENCES security_classification(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_security_clearance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                clearance_level INT NOT NULL DEFAULT 0,
                granted_by INT,
                granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATE,
                status ENUM('active','expired','revoked','pending') DEFAULT 'active',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_level (clearance_level)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_clearance_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                old_level INT,
                new_level INT,
                action ENUM('granted','upgraded','downgraded','revoked','expired') NOT NULL,
                changed_by INT,
                reason TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_security_clearance_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                clearance_id INT,
                action VARCHAR(50) NOT NULL,
                details JSON,
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_clearance (clearance_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_compartment (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(20) NOT NULL,
                description TEXT,
                parent_id INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_code (code),
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_access_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                object_id INT NOT NULL,
                action ENUM('view','download','print','export') NOT NULL,
                classification_id INT,
                granted TINYINT(1) DEFAULT 1,
                ip_address VARCHAR(45),
                user_agent VARCHAR(500),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_object (object_id),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50),
                entity_id INT,
                old_value JSON,
                new_value JSON,
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_action (action),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS security_declassification_schedule (
                id INT AUTO_INCREMENT PRIMARY KEY,
                object_id INT NOT NULL,
                current_classification_id INT NOT NULL,
                target_classification_id INT,
                scheduled_date DATE NOT NULL,
                status ENUM('pending','completed','cancelled') DEFAULT 'pending',
                completed_at DATETIME,
                completed_by INT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_object (object_id),
                INDEX idx_scheduled (scheduled_date),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert default classification levels
        $pdo->exec("
            INSERT IGNORE INTO security_classification (name, level, color, description, requires_clearance) VALUES
            ('Public', 0, '#28a745', 'Publicly accessible information', 0),
            ('Internal', 1, '#17a2b8', 'Internal use only', 0),
            ('Confidential', 2, '#ffc107', 'Confidential information', 1),
            ('Secret', 3, '#fd7e14', 'Secret information requiring clearance', 1),
            ('Top Secret', 4, '#dc3545', 'Top secret information - highest clearance required', 1)
        ");
    }

    public function down(): void
    {
        $pdo = DB::connection()->getPdo();
        
        $pdo->exec("DROP TABLE IF EXISTS security_declassification_schedule");
        $pdo->exec("DROP TABLE IF EXISTS security_audit_log");
        $pdo->exec("DROP TABLE IF EXISTS security_access_log");
        $pdo->exec("DROP TABLE IF EXISTS security_compartment");
        $pdo->exec("DROP TABLE IF EXISTS user_security_clearance_log");
        $pdo->exec("DROP TABLE IF EXISTS security_clearance_history");
        $pdo->exec("DROP TABLE IF EXISTS user_security_clearance");
        $pdo->exec("DROP TABLE IF EXISTS object_security_classification");
        $pdo->exec("DROP TABLE IF EXISTS security_classification");
    }
}
