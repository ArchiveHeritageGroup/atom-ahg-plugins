<?php

use Illuminate\Database\Capsule\Manager as DB;

class CreateResearchTables
{
    public function up(): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_researcher (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                institution VARCHAR(255),
                department VARCHAR(255),
                position VARCHAR(100),
                research_interests TEXT,
                id_type VARCHAR(50),
                id_number VARCHAR(100),
                status ENUM('pending','approved','rejected','suspended') DEFAULT 'pending',
                approved_by INT,
                approved_at DATETIME,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_email (email),
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_name (last_name, first_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_reading_room (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                location VARCHAR(255),
                capacity INT DEFAULT 10,
                description TEXT,
                rules TEXT,
                opening_time TIME,
                closing_time TIME,
                days_open VARCHAR(50) DEFAULT 'Mon-Fri',
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_booking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT NOT NULL,
                reading_room_id INT NOT NULL,
                booking_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                purpose TEXT,
                status ENUM('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
                confirmed_by INT,
                confirmed_at DATETIME,
                check_in_at DATETIME,
                check_out_at DATETIME,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_researcher (researcher_id),
                INDEX idx_room (reading_room_id),
                INDEX idx_date (booking_date),
                INDEX idx_status (status),
                CONSTRAINT fk_booking_researcher FOREIGN KEY (researcher_id) REFERENCES research_researcher(id) ON DELETE CASCADE,
                CONSTRAINT fk_booking_room FOREIGN KEY (reading_room_id) REFERENCES research_reading_room(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_material_request (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT NOT NULL,
                booking_id INT,
                object_id INT NOT NULL,
                request_date DATE NOT NULL,
                status ENUM('pending','approved','rejected','delivered','returned') DEFAULT 'pending',
                processed_by INT,
                processed_at DATETIME,
                delivered_at DATETIME,
                returned_at DATETIME,
                purpose TEXT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_researcher (researcher_id),
                INDEX idx_booking (booking_id),
                INDEX idx_object (object_id),
                INDEX idx_status (status),
                INDEX idx_date (request_date),
                CONSTRAINT fk_request_researcher FOREIGN KEY (researcher_id) REFERENCES research_researcher(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_collection (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                is_public TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_researcher (researcher_id),
                INDEX idx_public (is_public),
                CONSTRAINT fk_collection_researcher FOREIGN KEY (researcher_id) REFERENCES research_researcher(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_collection_item (
                id INT AUTO_INCREMENT PRIMARY KEY,
                collection_id INT NOT NULL,
                object_id INT NOT NULL,
                notes TEXT,
                sort_order INT DEFAULT 0,
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_collection (collection_id),
                INDEX idx_object (object_id),
                UNIQUE KEY uk_collection_object (collection_id, object_id),
                CONSTRAINT fk_item_collection FOREIGN KEY (collection_id) REFERENCES research_collection(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_saved_search (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                query_string TEXT NOT NULL,
                filters JSON,
                result_count INT DEFAULT 0,
                last_run_at DATETIME,
                notify_on_new TINYINT(1) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_researcher (researcher_id),
                CONSTRAINT fk_search_researcher FOREIGN KEY (researcher_id) REFERENCES research_researcher(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_annotation (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT NOT NULL,
                object_id INT NOT NULL,
                annotation_type ENUM('note','highlight','bookmark','tag') DEFAULT 'note',
                content TEXT,
                page_number INT,
                position JSON,
                is_private TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_researcher (researcher_id),
                INDEX idx_object (object_id),
                INDEX idx_type (annotation_type),
                CONSTRAINT fk_annotation_researcher FOREIGN KEY (researcher_id) REFERENCES research_researcher(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_citation_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT,
                object_id INT NOT NULL,
                citation_format VARCHAR(50) DEFAULT 'chicago',
                citation_text TEXT,
                exported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_researcher (researcher_id),
                INDEX idx_object (object_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS research_password_reset (
                id INT AUTO_INCREMENT PRIMARY KEY,
                researcher_id INT NOT NULL,
                token VARCHAR(100) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_token (token),
                INDEX idx_researcher (researcher_id),
                CONSTRAINT fk_reset_researcher FOREIGN KEY (researcher_id) REFERENCES research_researcher(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert default reading room
        $pdo->exec("
            INSERT IGNORE INTO research_reading_room (name, location, capacity, opening_time, closing_time, days_open) VALUES
            ('Main Reading Room', 'Ground Floor', 20, '08:00', '17:00', 'Mon-Fri')
        ");
    }

    public function down(): void
    {
        $pdo = DB::connection()->getPdo();
        
        $pdo->exec("DROP TABLE IF EXISTS research_password_reset");
        $pdo->exec("DROP TABLE IF EXISTS research_citation_log");
        $pdo->exec("DROP TABLE IF EXISTS research_annotation");
        $pdo->exec("DROP TABLE IF EXISTS research_saved_search");
        $pdo->exec("DROP TABLE IF EXISTS research_collection_item");
        $pdo->exec("DROP TABLE IF EXISTS research_collection");
        $pdo->exec("DROP TABLE IF EXISTS research_material_request");
        $pdo->exec("DROP TABLE IF EXISTS research_booking");
        $pdo->exec("DROP TABLE IF EXISTS research_reading_room");
        $pdo->exec("DROP TABLE IF EXISTS research_researcher");
    }
}
