-- ahgIiifPlugin — IIIF AI Extract (#220)
-- Region-scoped, VLM-driven extractions over IIIF canvases.
-- Run once on PSIS (archive) + archaeology. No ENUM (VARCHAR + COMMENT per house rule).

CREATE TABLE IF NOT EXISTS iiif_ai_extract (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  object_id         INT NOT NULL,
  digital_object_id INT NULL,
  canvas_index      INT NOT NULL DEFAULT 0,
  region            VARCHAR(64)  NOT NULL DEFAULT 'full' COMMENT 'full or x,y,w,h (IIIF Image API region)',
  task              VARCHAR(20)  NOT NULL               COMMENT 'caption, describe, transcribe, entities, tags',
  model             VARCHAR(120) NULL                   COMMENT 'gateway model id, e.g. llava:7b',
  prompt            TEXT NULL,
  output_text       LONGTEXT NULL,
  output_json       JSON NULL,
  confidence        DECIMAL(5,4) NULL,
  status            VARCHAR(20)  NOT NULL DEFAULT 'draft' COMMENT 'draft, approved, rejected',
  created_by        INT NULL,
  created_at        DATETIME NULL,
  updated_at        DATETIME NULL,
  KEY idx_object (object_id),
  KEY idx_status (status),
  KEY idx_object_canvas (object_id, canvas_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
