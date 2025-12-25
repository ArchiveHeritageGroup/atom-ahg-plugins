-- Research Plugin Database Schema

CREATE TABLE IF NOT EXISTS research_researcher (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(50),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    affiliation_type ENUM('academic', 'government', 'private', 'independent', 'student', 'other') DEFAULT 'independent',
    institution VARCHAR(255),
    department VARCHAR(255),
    position VARCHAR(255),
    student_id VARCHAR(100),
    research_interests TEXT,
    current_project TEXT,
    orcid_id VARCHAR(50),
    id_type ENUM('passport', 'national_id', 'drivers_license', 'student_card', 'other'),
    id_number VARCHAR(100),
    id_verified TINYINT(1) DEFAULT 0,
    id_verified_by INT,
    id_verified_at DATETIME,
    status ENUM('pending', 'approved', 'suspended', 'expired') DEFAULT 'pending',
    approved_by INT,
    approved_at DATETIME,
    expires_at DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_reading_room (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 10,
    location VARCHAR(255),
    operating_hours TEXT,
    rules TEXT,
    advance_booking_days INT DEFAULT 14,
    max_booking_hours INT DEFAULT 4,
    cancellation_hours INT DEFAULT 24,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT NOT NULL,
    reading_room_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    confirmed_by INT,
    confirmed_at DATETIME,
    cancelled_at DATETIME,
    cancellation_reason TEXT,
    checked_in_at DATETIME,
    checked_out_at DATETIME,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_researcher (researcher_id),
    INDEX idx_room (reading_room_id),
    INDEX idx_date (booking_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_material_request (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    object_id INT NOT NULL,
    quantity INT DEFAULT 1,
    notes TEXT,
    status ENUM('requested', 'retrieved', 'delivered', 'in_use', 'returned', 'unavailable') DEFAULT 'requested',
    retrieved_by INT,
    retrieved_at DATETIME,
    returned_at DATETIME,
    condition_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking (booking_id),
    INDEX idx_object (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_saved_search (
    id INT AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    search_query TEXT NOT NULL,
    search_filters TEXT,
    search_type VARCHAR(50) DEFAULT 'informationobject',
    alert_enabled TINYINT(1) DEFAULT 0,
    alert_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    last_alert_at DATETIME,
    new_results_count INT DEFAULT 0,
    is_public TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_researcher (researcher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    share_token VARCHAR(64),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_researcher (researcher_id),
    INDEX idx_share_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_collection_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    object_id INT NOT NULL,
    notes TEXT,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_collection (collection_id),
    INDEX idx_object (object_id),
    UNIQUE KEY unique_item (collection_id, object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_annotation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT NOT NULL,
    object_id INT NOT NULL,
    digital_object_id INT,
    annotation_type ENUM('note', 'highlight', 'bookmark', 'tag', 'transcription') DEFAULT 'note',
    title VARCHAR(255),
    content TEXT,
    target_selector TEXT,
    tags VARCHAR(500),
    is_private TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_researcher (researcher_id),
    INDEX idx_object (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS research_citation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    researcher_id INT,
    object_id INT NOT NULL,
    citation_style VARCHAR(50) NOT NULL,
    citation_text TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_researcher (researcher_id),
    INDEX idx_object (object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default reading rooms
INSERT INTO research_reading_room (name, description, capacity, location, rules) VALUES
('Main Reading Room', 'General research reading room', 20, 'Building A, Ground Floor', 'No food or drinks. Handle materials with care.'),
('Special Collections Room', 'For rare and fragile materials', 6, 'Building A, First Floor', 'Gloves required. No pens. Supervision required.');
