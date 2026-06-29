-- ahgGISPlugin — Spatial index for coordinate columns
-- No new tables needed; uses existing contact_information, research_map_point, etc.

-- Add spatial index on contact_information if not already present
-- MySQL 8 does not support spatial index on float columns, but a composite
-- B-tree index enables efficient range queries (bounding box).
-- MySQL 8 has no "CREATE INDEX IF NOT EXISTS"; guard via information_schema so this
-- is idempotent AND safe when contact_information (ahgContactPlugin) isn't present.
SET @gis_has_tbl := (SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'contact_information');
SET @gis_has_idx := (SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'contact_information' AND index_name = 'idx_contact_lat_lng');
SET @gis_sql := IF(@gis_has_tbl = 1 AND @gis_has_idx = 0,
    'CREATE INDEX idx_contact_lat_lng ON contact_information (latitude, longitude)', 'DO 0');
PREPARE gis_stmt FROM @gis_sql; EXECUTE gis_stmt; DEALLOCATE PREPARE gis_stmt;
