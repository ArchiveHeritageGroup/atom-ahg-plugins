-- ahgIiifPlugin - audio-description tracks (WebVTT kind="descriptions") for
-- accessibility. One described track per digital object (video).

CREATE TABLE IF NOT EXISTS `media_audio_description` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `digital_object_id` INT NOT NULL,
    `object_id`         INT DEFAULT NULL,
    `language`          VARCHAR(10) NOT NULL DEFAULT 'en',
    `label`             VARCHAR(120) NOT NULL DEFAULT 'Audio description',
    `vtt_content`       MEDIUMTEXT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_do` (`digital_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
