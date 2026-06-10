-- Exhibition Space — Heratio digital-twin parity columns (port 2026-06-10).
-- Idempotent: each ADD COLUMN guarded via INFORMATION_SCHEMA (MySQL has no
-- ADD COLUMN IF NOT EXISTS). Safe to re-run. Mirrors 002 pattern.

SET @db := DATABASE();

-- ── ahg_exhibition_placement ──────────────────────────────────────────────

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='rotation_deg');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `rotation_deg` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='scale');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `scale` DECIMAL(8,4) NOT NULL DEFAULT 1", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='label_visible');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `label_visible` TINYINT(1) NOT NULL DEFAULT 1", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='wall_or_zone');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `wall_or_zone` VARCHAR(40) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='wall_u');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `wall_u` DECIMAL(8,4) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='wall_v');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `wall_v` DECIMAL(8,4) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='model_tilt_x');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `model_tilt_x` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='model_tilt_z');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `model_tilt_z` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='spotlight');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `spotlight` INT NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='display_case');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `display_case` TINYINT(1) NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='on_floor');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `on_floor` TINYINT(1) NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='view_x');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `view_x` DECIMAL(8,4) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='view_y');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `view_y` DECIMAL(8,4) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ── ahg_exhibition_space ──────────────────────────────────────────────────

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='room_w');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `room_w` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='room_d');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `room_d` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='room_h');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `room_h` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='building_id');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `building_id` VARCHAR(64) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='building_seq');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `building_seq` INT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='bld_x');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `bld_x` DECIMAL(12,4) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='bld_y');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `bld_y` DECIMAL(12,4) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='bld_rot');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `bld_rot` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='bld_locked');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `bld_locked` TINYINT(1) NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='bld_group');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `bld_group` VARCHAR(40) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floor_level');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floor_level` INT NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='is_outdoor');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `is_outdoor` TINYINT(1) NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='walls_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `walls_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='doors_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `doors_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='windows_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `windows_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='shape_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `shape_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='wall_colors_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `wall_colors_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='wall_images_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `wall_images_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='wall_image_path');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `wall_image_path` VARCHAR(500) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='ceiling_image_path');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `ceiling_image_path` VARCHAR(500) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floorplan_image_path');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floorplan_image_path` VARCHAR(500) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floor_image_path');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floor_image_path` VARCHAR(500) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floor_grout');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floor_grout` TINYINT(1) NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floor_tile_m');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floor_tile_m` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floor_grout_mm');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floor_grout_mm` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='scan_shell_path');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `scan_shell_path` VARCHAR(500) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='scan_shell_scale');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `scan_shell_scale` DECIMAL(8,2) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='scan_embed_url');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `scan_embed_url` VARCHAR(500) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='furniture_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `furniture_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='stairs_json');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `stairs_json` TEXT NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='sensor_token');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `sensor_token` VARCHAR(64) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- backfill from the #136-era columns
UPDATE ahg_exhibition_placement SET rotation_deg=COALESCE(rotation_deg,rotation,0) WHERE rotation_deg IS NULL;
UPDATE ahg_exhibition_placement SET wall_or_zone=COALESCE(wall_or_zone,wall) WHERE wall_or_zone IS NULL AND wall IS NOT NULL;
UPDATE ahg_exhibition_space SET room_w=COALESCE(room_w,6), room_d=COALESCE(room_d,5), room_h=COALESCE(room_h,3) WHERE room_w IS NULL;
