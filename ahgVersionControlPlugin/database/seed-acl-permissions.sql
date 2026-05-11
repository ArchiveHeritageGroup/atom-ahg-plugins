-- ahgVersionControlPlugin — default ACL permissions (Phase K)
--
-- Idempotent: INSERT IGNORE keyed on (group_id, action) effectively, but
-- acl_permission has no unique constraint on that pair, so we use a NOT EXISTS
-- guard per row.
--
-- AtoM default groups (acl_group):
--   98  anonymous
--   99  authenticated
--   100 administrator   ← gets allow-all via action=NULL row (already seeded by base AtoM)
--   101 editor          ← curatorial role: list + diff + restore (non-classified)
--   102 contributor     ← read-only of version history: list + diff
--   103 translator      ← can see versions exist (for understanding context): list
--
-- restore_classified is GRANTED to editor + administrator only. The Phase J
-- clearance check still requires the user's actual security clearance to
-- match the record's classification level.

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.list', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.list');

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.diff', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.diff');

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.restore', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.restore');

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 101, NULL, 'version.restore_classified', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=101 AND action='version.restore_classified');

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 102, NULL, 'version.list', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=102 AND action='version.list');

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 102, NULL, 'version.diff', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=102 AND action='version.diff');

INSERT INTO acl_permission (user_id, group_id, object_id, action, grant_deny, created_at, updated_at)
SELECT NULL, 103, NULL, 'version.list', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM acl_permission WHERE group_id=103 AND action='version.list');

-- Final report.
SELECT g.id AS group_id, gi.name AS group_name, p.action, p.grant_deny
FROM acl_permission p
JOIN acl_group g ON g.id = p.group_id
LEFT JOIN acl_group_i18n gi ON gi.id = g.id AND gi.culture = 'en'
WHERE p.action LIKE 'version.%'
ORDER BY g.id, p.action;
