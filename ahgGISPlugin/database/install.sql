-- ahgGISPlugin — Spatial index for coordinate columns
-- No new tables needed; uses existing contact_information, research_map_point, etc.

-- Add spatial index on contact_information if not already present
-- MySQL 8 does not support spatial index on float columns, but a composite
-- B-tree index enables efficient range queries (bounding box).
CREATE INDEX IF NOT EXISTS idx_contact_lat_lng
    ON contact_information (latitude, longitude);
