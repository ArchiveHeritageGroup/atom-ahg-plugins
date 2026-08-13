-- ahgUserRegistrationPlugin - Registration request table
-- Stores pending registration requests with email verification tokens

CREATE TABLE IF NOT EXISTS ahg_registration_request (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    salt VARCHAR(64) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    institution VARCHAR(255) DEFAULT NULL,
    research_interest TEXT DEFAULT NULL,
    reason TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, verified, approved, rejected, expired',
    email_token VARCHAR(64) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    admin_notes TEXT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    assigned_group_id INT DEFAULT NULL,
    -- The account this request produced, set on approval.
    --
    -- The FK cascades: deleting the user deletes the request that created it.
    -- Without it the request row outlives the account, and because uk_email is
    -- unique across every status that orphan made the address permanently
    -- unregisterable. It is a database constraint rather than application code
    -- because AtoM deletes users from base code this plugin cannot hook, and any
    -- path that misses the cleanup reintroduces the orphan.
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email (email),
    UNIQUE KEY uk_email_token (email_token),
    INDEX idx_status (status),
    INDEX idx_ip_created (ip_address, created_at),
    INDEX idx_user (user_id),
    CONSTRAINT fk_ahg_registration_request_user
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
