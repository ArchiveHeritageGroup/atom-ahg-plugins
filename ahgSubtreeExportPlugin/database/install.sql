-- ahgSubtreeExportPlugin schema
--
-- TARGET: AtoM 2.8.1. Deliberately does NOT touch ahg_settings - that is an AHG/2.10
-- table and a vanilla 2.8 instance has no such thing, so writing defaults there would
-- break a clean install on the target. Defaults live in the task's options.
--
-- SELECTION MODEL: a start record plus a forward walk in tree order, bounded by an
-- ancestor. NOT "a record and its descendants" - the record this was built for,
-- smt-les-hab1-1 on RARI, is a LEAF, so a descendant walk returns exactly one row.
-- What is wanted is "this record and the next N onwards".
--
-- The bound is not optional decoration. An unbounded forward walk runs past the end
-- of its collection and silently starts exporting the next one; on RARI the start
-- record sits at lft 276585 inside smits-lucas-3 (253322-284497), and a thousand
-- records stay inside only because the bound says so.

CREATE TABLE IF NOT EXISTS `subtree_export` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Where the walk starts. Resolved once, so an editor moving the tree mid-run
    -- cannot change what a half-finished job is exporting.
    `start_slug`          VARCHAR(255)    NOT NULL,
    `start_id`            INT             NOT NULL,
    `start_lft`           INT             NOT NULL,

    -- The ancestor the walk may not leave. Defaults to the start record's parent.
    `bound_slug`          VARCHAR(255)             DEFAULT NULL,
    `bound_lft`           INT             NOT NULL,
    `bound_rgt`           INT             NOT NULL,

    -- max_items is the cap on the walk. batch_size 0 is a single pass; any other
    -- value drains in sequential batches resuming from resume_after_lft.
    `max_items`           INT UNSIGNED    NOT NULL DEFAULT 1000,
    `batch_size`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `batch_index`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `resume_after_lft`    INT                      DEFAULT NULL COMMENT 'cursor: last lft exported',

    -- Masters and references on by default: masters are the point, and references
    -- cost almost nothing (on RARI they round to 0.00 GB against 20.67 GB of
    -- masters) while making the package viewable without processing.
    `include_masters`     TINYINT(1)      NOT NULL DEFAULT 1,
    `include_references`  TINYINT(1)      NOT NULL DEFAULT 1,
    `include_thumbnails`  TINYINT(1)      NOT NULL DEFAULT 0,
    `include_metadata`    TINYINT(1)      NOT NULL DEFAULT 1,

    `output_path`         VARCHAR(1024)   NOT NULL COMMENT 'external destination, not the AtoM root',
    `status`              VARCHAR(20)     NOT NULL DEFAULT 'pending' COMMENT 'pending, running, paused, completed, failed',
    `items_total`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `items_done`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `files_done`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `bytes_estimated`     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `bytes_written`       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `files_missing`       INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'rows whose file was absent on disk',
    `error_message`       TEXT                     DEFAULT NULL,

    `started_at`          DATETIME                 DEFAULT NULL,
    `completed_at`        DATETIME                 DEFAULT NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_subtree_export_status` (`status`),
    KEY `idx_subtree_export_start` (`start_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per file. Makes a resumed batch idempotent: a file already recorded here
-- is not copied again, so an interrupted run neither duplicates work nor
-- double-counts bytes.
CREATE TABLE IF NOT EXISTS `subtree_export_file` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `export_id`             BIGINT UNSIGNED NOT NULL,
    `digital_object_id`     INT             NOT NULL,
    `information_object_id` INT             NOT NULL,
    `usage_id`              INT                      DEFAULT NULL,
    `source_path`           VARCHAR(1024)   NOT NULL,
    `relative_path`         VARCHAR(1024)   NOT NULL COMMENT 'path inside the package',
    `byte_size`             BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `checksum`              VARCHAR(255)             DEFAULT NULL,
    `status`                VARCHAR(20)     NOT NULL DEFAULT 'copied' COMMENT 'copied, missing, failed',
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_export_digital_object` (`export_id`, `digital_object_id`),
    KEY `idx_subtree_export_file_export` (`export_id`),
    CONSTRAINT `fk_subtree_export_file` FOREIGN KEY (`export_id`)
        REFERENCES `subtree_export` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
