-- =============================================================================
-- ahgResearchPlugin :: Training curriculum + LMS (#117)
-- PSIS-parity port of Heratio ResearchTrainingService / ResearchTrainingController.
--
-- Institution-neutral training/LMS. A course defines audience/role, language and
-- a configurable pass mark; its modules sequence content (each may REUSE a
-- curriculum lecture from research_lecture, #116 twin, degrade gracefully if
-- absent, or carry its own Markdown); learners enrol, work through modules
-- (progress tracked), take a multiple-choice assessment, and on passing (with all
-- modules complete) are issued a unique certificate.
--
-- Conventions: CREATE TABLE IF NOT EXISTS, InnoDB, utf8mb4. No ENUM (VARCHAR +
-- COMMENT). No INSERT INTO atom_plugin. Mirrors the Heratio table names exactly.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `training_course` (
  `id` int NOT NULL AUTO_INCREMENT,
  `researcher_id` int DEFAULT NULL COMMENT 'FK researcher (course author), nullable',
  `title` varchar(255) NOT NULL,
  `description` text,
  `audience` varchar(255) DEFAULT NULL COMMENT 'audience / role the course targets (data, not hard-coded)',
  `language` varchar(40) DEFAULT NULL COMMENT 'course language code/name (data)',
  `pass_mark` int NOT NULL DEFAULT 80 COMMENT 'default pass mark percentage 0-100',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
  `sort_order` int DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_course_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_module` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `lecture_id` int DEFAULT NULL COMMENT 'FK research_lecture (curriculum lecture, #116) - degrade gracefully if absent',
  `body_markdown` mediumtext COMMENT 'own Markdown content when no lecture reused',
  `body_html` mediumtext COMMENT 'rendered HTML cache of body_markdown',
  `sort_order` int DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_module_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_assessment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `pass_mark` int DEFAULT NULL COMMENT 'overrides course pass_mark when set (0-100)',
  `questions_json` longtext COMMENT 'JSON [{q, options:[...], answer:index}]',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_assessment_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_enrolment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `learner_name` varchar(255) DEFAULT NULL,
  `learner_email` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'enrolled' COMMENT 'enrolled, in_progress, completed',
  `score` int DEFAULT NULL COMMENT 'best assessment score percentage',
  `enrolled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_enrolment_course` (`course_id`),
  KEY `idx_training_enrolment_user` (`user_id`),
  KEY `idx_training_enrolment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enrolment_id` int NOT NULL,
  `module_id` int NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_training_progress_enrol_module` (`enrolment_id`,`module_id`),
  KEY `idx_training_progress_enrol` (`enrolment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_certificate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `enrolment_id` int NOT NULL,
  `certificate_no` varchar(40) NOT NULL,
  `score` int DEFAULT NULL,
  `issued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_training_certificate_no` (`certificate_no`),
  KEY `idx_training_certificate_enrol` (`enrolment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
