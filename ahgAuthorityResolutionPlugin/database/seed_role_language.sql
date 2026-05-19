-- ==========================================================================
-- Seed role-language tokens for the authority-resolution engine.
-- Inserted into ahg_settings(setting_group='authority_resolution',
-- setting_key='authority_resolution.role_language_tokens').
-- Idempotent (INSERT IGNORE on UNIQUE setting_key).
-- ==========================================================================

INSERT IGNORE INTO ahg_settings (
    setting_key,
    setting_group,
    setting_type,
    setting_value,
    description,
    is_sensitive,
    created_at,
    updated_at
) VALUES (
    'authority_resolution.role_language_tokens',
    'authority_resolution',
    'json',
    JSON_OBJECT(
        'kinship', JSON_ARRAY(
            'son of', 'daughter of', 'child of', 'children of',
            'father of', 'mother of', 'parent of', 'parents of',
            'brother of', 'sister of', 'sibling of',
            'wife of', 'husband of', 'spouse of',
            'descendant of', 'ancestor of',
            'uncle of', 'aunt of', 'cousin of', 'nephew of', 'niece of',
            'grandson of', 'granddaughter of', 'grandfather of', 'grandmother of'
        ),
        'witness', JSON_ARRAY(
            'witnessed by', 'witness was', 'witnesses were',
            'signed by', 'attested by', 'testified by',
            'present at', 'in the presence of', 'in attendance',
            'co-signed by', 'countersigned by'
        ),
        'location', JSON_ARRAY(
            'located in', 'located at', 'situated in', 'situated at',
            'found at', 'found in', 'based in', 'based at',
            'residing at', 'residing in', 'resident of', 'resident at',
            'dwelling at', 'dwelling in', 'living at', 'living in',
            'born at', 'born in', 'born on',
            'died at', 'died in', 'died on',
            'buried at', 'buried in'
        ),
        'movement', JSON_ARRAY(
            'travelled to', 'traveled to', 'travelled from', 'traveled from',
            'moved to', 'moved from', 'relocated to', 'relocated from',
            'returned from', 'returned to', 'departed for', 'departed from',
            'journeyed to', 'journeyed from',
            'sailed from', 'sailed to', 'sailed for',
            'arrived at', 'arrived in', 'arrived from',
            'fled to', 'fled from', 'escaped to', 'escaped from',
            'emigrated to', 'immigrated to', 'migrated to'
        ),
        'other', JSON_ARRAY(
            'officiated by', 'officiated at',
            'ruled by', 'ruled over', 'governed by', 'governed',
            'owned by', 'owned', 'possessed by',
            'served by', 'served as', 'served at', 'served in',
            'appointed by', 'appointed as', 'appointed to',
            'succeeded by', 'succeeded',
            'preceded by', 'preceded',
            'employed by', 'employed at', 'worked for', 'worked at',
            'educated at', 'studied at', 'graduated from',
            'founded', 'founded by', 'co-founded',
            'commanded by', 'commanded', 'led by', 'led'
        )
    ),
    'Role-language tokens for authority-resolution context derivation. Keys are kinds (kinship/witness/location/movement/other); values are lowercased token lists.',
    0,
    NOW(),
    NOW()
);
