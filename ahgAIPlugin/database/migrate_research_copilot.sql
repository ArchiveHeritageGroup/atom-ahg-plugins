-- Researcher Copilot (#149 strand) — persistent research sessions over the
-- collection RAG assistant (#121). Idempotent.

CREATE TABLE IF NOT EXISTS ahg_research_session (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'New research session',
    culture VARCHAR(10) NOT NULL DEFAULT 'en',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_research_session_user (user_id),
    INDEX idx_research_session_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ahg_research_message (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(12) NOT NULL COMMENT 'user, assistant',
    content MEDIUMTEXT NOT NULL,
    sources_json TEXT DEFAULT NULL COMMENT 'JSON array of {slug,title}',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_research_message_session (session_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
