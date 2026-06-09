-- #136 — Exhibition Space builder: layout coordinates + room dimensions.
-- Idempotent: each ADD COLUMN is guarded via INFORMATION_SCHEMA (MySQL has no
-- ADD COLUMN IF NOT EXISTS). Safe to re-run.

-- ── ahg_exhibition_space: room canvas dimensions + colours ──────────────────
SET @db := DATABASE();

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='room_width');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `room_width` DECIMAL(8,2) NOT NULL DEFAULT 1200 COMMENT 'builder canvas width (units)'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='room_height');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `room_height` DECIMAL(8,2) NOT NULL DEFAULT 700 COMMENT 'builder canvas height (units)'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='wall_color');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `wall_color` VARCHAR(20) NOT NULL DEFAULT '#f3f0ea'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_space' AND COLUMN_NAME='floor_color');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_space` ADD COLUMN `floor_color` VARCHAR(20) NOT NULL DEFAULT '#d8c9ac'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ── ahg_exhibition_placement: 2.5D layout per placed object ─────────────────
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='pos_x');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `pos_x` DECIMAL(8,2) NULL COMMENT 'x on builder canvas'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='pos_y');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `pos_y` DECIMAL(8,2) NULL COMMENT 'y on builder canvas'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='item_w');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `item_w` DECIMAL(8,2) NOT NULL DEFAULT 120 COMMENT 'object display width'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='item_h');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `item_h` DECIMAL(8,2) NOT NULL DEFAULT 120 COMMENT 'object display height'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='wall');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `wall` VARCHAR(20) NOT NULL DEFAULT 'north' COMMENT 'north, east, south, west, floor'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='z_order');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `z_order` INT NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='rotation');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `rotation` DECIMAL(6,2) NOT NULL DEFAULT 0", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='ahg_exhibition_placement' AND COLUMN_NAME='tour_order');
SET @s := IF(@c=0, "ALTER TABLE `ahg_exhibition_placement` ADD COLUMN `tour_order` INT NULL COMMENT 'guided-tour sequence position'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
