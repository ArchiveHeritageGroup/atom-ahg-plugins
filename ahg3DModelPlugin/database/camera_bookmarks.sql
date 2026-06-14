-- ahg3DModelPlugin - saved camera viewpoints ("bookmarks") for a 3D model.
-- Lets curators capture named camera orbits the viewer can jump back to.

CREATE TABLE IF NOT EXISTS `object_3d_camera_bookmark` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `model_id`       INT NOT NULL COMMENT 'object_3d_model.id (logical FK)',
    `name`           VARCHAR(120) NOT NULL,
    `camera_orbit`   VARCHAR(64) NOT NULL COMMENT 'model-viewer camera-orbit, e.g. "45deg 55deg 4m"',
    `field_of_view`  VARCHAR(32) DEFAULT NULL COMMENT 'model-viewer field-of-view, e.g. "30deg"',
    `display_order`  INT NOT NULL DEFAULT 0,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_model` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
