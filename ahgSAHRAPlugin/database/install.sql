-- ============================================================
-- ahgSAHRAPlugin - SAHRA / NHRA Heritage Permit workflow
-- National Heritage Resources Act, 1999 (Act 25 of 1999)
--
-- Workflow: researcher applies -> supervising professor endorses
--           -> submitted to SAHRA -> SAHRA outcome recorded.
--
-- DO NOT include INSERT INTO atom_plugin (plugins enabled manually).
-- No ENUM columns (VARCHAR + COMMENT). No "ADD COLUMN IF NOT EXISTS".
-- ============================================================

-- ---------- Permit applications / permits ----------
CREATE TABLE IF NOT EXISTS sahra_permit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_ref VARCHAR(50) NOT NULL UNIQUE COMMENT 'internal application reference, auto-generated',
    sahra_permit_number VARCHAR(80) DEFAULT NULL COMMENT 'assigned by SAHRA once the permit is issued',

    nhra_section VARCHAR(40) NOT NULL DEFAULT 's35_archaeology' COMMENT 's35_archaeology, s35_palaeontology, s35_meteorite, s32_export, s34_structures, s36_burial',
    issuing_authority VARCHAR(120) NOT NULL DEFAULT 'SAHRA' COMMENT 'SAHRA or a Provincial Heritage Resources Authority (PHRA)',

    -- Applicant (the researcher)
    applicant_user_id INT UNSIGNED NOT NULL,
    applicant_name VARCHAR(255) DEFAULT NULL,
    applicant_email VARCHAR(255) DEFAULT NULL,
    institution VARCHAR(255) DEFAULT NULL,

    -- Supervising professor (endorses before SAHRA submission)
    supervisor_user_id INT UNSIGNED DEFAULT NULL,
    supervisor_name VARCHAR(255) DEFAULT NULL,

    -- The work
    project_title VARCHAR(500) NOT NULL,
    project_description TEXT,
    site_name VARCHAR(255) DEFAULT NULL,
    site_location VARCHAR(255) DEFAULT NULL COMMENT 'description or coordinates',
    province VARCHAR(80) DEFAULT NULL,
    linked_object_id INT UNSIGNED DEFAULT NULL COMMENT 'optional information_object / collection this permit covers',

    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    conditions TEXT COMMENT 'permit conditions imposed by SAHRA',

    fee_amount DECIMAL(10, 2) DEFAULT 0,
    fee_currency VARCHAR(3) DEFAULT 'ZAR',
    fee_paid TINYINT(1) DEFAULT 0,
    fee_receipt VARCHAR(100) DEFAULT NULL,

    status VARCHAR(40) NOT NULL DEFAULT 'draft' COMMENT 'draft, pending_supervisor, supervisor_approved, supervisor_rejected, submitted_to_sahra, sahra_issued, active, sahra_rejected, expired, revoked, closed',

    -- Supervisor endorsement
    supervisor_decision_date DATETIME DEFAULT NULL,
    supervisor_notes TEXT,

    -- SAHRA submission + outcome
    sahra_submitted_date DATETIME DEFAULT NULL,
    sahra_reference VARCHAR(120) DEFAULT NULL COMMENT 'SAHRA case / reference number',
    sahra_decision_date DATETIME DEFAULT NULL,
    sahra_notes TEXT,

    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_applicant (applicant_user_id),
    INDEX idx_supervisor (supervisor_user_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date),
    INDEX idx_section (nhra_section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Workflow / audit log ----------
CREATE TABLE IF NOT EXISTS sahra_permit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(60) NOT NULL COMMENT 'created, submitted, endorsed, rejected, sent_to_sahra, issued, sahra_rejected, revoked, expired, closed, updated',
    actor_id INT UNSIGNED DEFAULT NULL,
    from_status VARCHAR(40) DEFAULT NULL,
    to_status VARCHAR(40) DEFAULT NULL,
    notes TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permit (permit_id),
    INDEX idx_actor (actor_id),
    CONSTRAINT sahra_permit_log_ibfk_1 FOREIGN KEY (permit_id) REFERENCES sahra_permit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- SAHRA reporting obligations ----------
CREATE TABLE IF NOT EXISTS sahra_permit_report (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    report_type VARCHAR(30) NOT NULL DEFAULT 'interim' COMMENT 'interim, final, annual, fieldwork',
    due_date DATE DEFAULT NULL,
    submitted_date DATE DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, submitted, overdue, accepted',
    document_ref VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_permit (permit_id),
    INDEX idx_status (status),
    INDEX idx_due (due_date),
    CONSTRAINT sahra_permit_report_ibfk_1 FOREIGN KEY (permit_id) REFERENCES sahra_permit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Dig areas covered by a permit ----------
-- A permit is linked to ONE site (sahra_permit.linked_object_id) plus any
-- number of dig areas, which are information_object descendants of that site.
CREATE TABLE IF NOT EXISTS sahra_permit_area (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    object_id INT UNSIGNED NOT NULL COMMENT 'information_object - a dig area (child of the site)',
    object_title VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permit (permit_id),
    INDEX idx_object (object_id),
    CONSTRAINT sahra_permit_area_ibfk_1 FOREIGN KEY (permit_id) REFERENCES sahra_permit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Supporting documents attached to a permit ----------
CREATE TABLE IF NOT EXISTS sahra_permit_document (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permit_id BIGINT UNSIGNED NOT NULL,
    doc_type VARCHAR(40) NOT NULL DEFAULT 'supporting' COMMENT 'application, supporting, method_statement, cv, permit_certificate, report, correspondence, other',
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) DEFAULT NULL,
    size_bytes BIGINT UNSIGNED DEFAULT 0,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_permit (permit_id),
    CONSTRAINT sahra_permit_document_ibfk_1 FOREIGN KEY (permit_id) REFERENCES sahra_permit (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- SAHRA reviewers (officials who decide in-system) ----------
-- Users designated as SAHRA reviewers may issue or decline permit
-- applications directly on this instance ("SAHRA approves from their side").
CREATE TABLE IF NOT EXISTS sahra_reviewer (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    authority VARCHAR(120) DEFAULT 'SAHRA' COMMENT 'authority this reviewer acts for (SAHRA or a PHRA)',
    active TINYINT(1) DEFAULT 1,
    added_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user (user_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Plugin settings ----------
CREATE TABLE IF NOT EXISTS sahra_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings (safe to seed; NOT plugin registration)
INSERT INTO sahra_config (config_key, config_value, description) VALUES
    ('permit_validity_months', '36', 'Default permit validity in months (SAHRA archaeology permits are commonly up to 3 years)'),
    ('application_prefix', 'SAHRA-APP', 'Prefix for internal application references'),
    ('default_authority', 'SAHRA', 'Default issuing authority'),
    ('authorities', 'SAHRA|Heritage Western Cape|Amafa aKwaZulu-Natali|Ngwao Boswa Kapa Bokone (Northern Cape)|Free State PHRA|Eastern Cape PHRA|Gauteng PHRA|Limpopo PHRA|Mpumalanga PHRA|North West PHRA', 'Pipe-separated list of issuing authorities (SAHRA + PHRAs)'),
    ('expiry_warning_days', '30', 'Warn about permits expiring within this many days')
ON DUPLICATE KEY UPDATE config_key = config_key;
