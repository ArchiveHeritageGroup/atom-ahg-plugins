-- Link a registration request to the account it created, and cascade the delete.
--
-- WHY
--
-- Deleting a user left its approved ahg_registration_request row behind. Nothing
-- connected the two, so no deletion path could clean it up. Because uk_email is
-- unique across every status - not just pending and verified - that orphan made
-- the address permanently unregisterable: createRequest() saw no pending request
-- and no user account, passed both checks, and then broke on the unique index.
-- The raw integrity-constraint exception surfaced as "Oops! An Error Occurred".
--
-- WHY A CONSTRAINT AND NOT APPLICATION CODE
--
-- AtoM deletes users from base code this plugin cannot hook, and users also
-- disappear via CLI tasks and via cascades from the object table. A cleanup in
-- one code path would be missed by the others. The database is the only place
-- that sees them all.
--
-- Guarded on both the column and the constraint: MySQL has no
-- ADD COLUMN IF NOT EXISTS, and this must be safe to re-run.

-- 1. the column
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ahg_registration_request'
      AND COLUMN_NAME = 'user_id'
);

SET @sql := IF(
    @col = 0,
    'ALTER TABLE ahg_registration_request ADD COLUMN user_id INT DEFAULT NULL COMMENT ''user.id created on approval; FK cascades on delete''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. the index the FK needs
SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ahg_registration_request'
      AND INDEX_NAME = 'idx_user'
);

SET @sql := IF(@idx = 0, 'ALTER TABLE ahg_registration_request ADD INDEX idx_user (user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. backfill before the constraint exists, matching on email.
--
-- Email is the stable key: uk_email makes it unique on the request side, and a
-- re-registration through the reuse path rewrites the username but never the
-- address. Only approved rows are linked - a pending request has no account yet.
--
-- COLLATE on BOTH sides is required, not decoration. AtoM's `user` table is
-- utf8mb4_0900_ai_ci (the MySQL 8 default) while this plugin's table declares
-- utf8mb4_unicode_ci, so a bare `u.email = r.email` aborts the migration with
-- "Illegal mix of collations". utf8mb4_general_ci is named explicitly because it
-- exists on MySQL 5.7, MySQL 8 and MariaDB alike - utf8mb4_0900_ai_ci does not.
UPDATE ahg_registration_request r
    JOIN user u
      ON u.email COLLATE utf8mb4_general_ci = r.email COLLATE utf8mb4_general_ci
SET r.user_id = u.id
WHERE r.status = 'approved' AND r.user_id IS NULL;

-- 4. clear any row that points at an account which no longer exists, so the
--    constraint can be added. These are precisely the orphans described above.
DELETE r FROM ahg_registration_request r
    LEFT JOIN user u ON u.id = r.user_id
WHERE r.user_id IS NOT NULL AND u.id IS NULL;

-- 5. the constraint
SET @fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ahg_registration_request'
      AND CONSTRAINT_NAME = 'fk_ahg_registration_request_user'
);

SET @sql := IF(
    @fk = 0,
    'ALTER TABLE ahg_registration_request ADD CONSTRAINT fk_ahg_registration_request_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
