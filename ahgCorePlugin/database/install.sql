-- ahgCorePlugin Installation SQL
-- This plugin doesn't require its own tables, but we need to register it

-- Register plugin in atom_plugin table (if not exists)
INSERT IGNORE INTO atom_plugin (name, class_name, version, description, category, is_enabled, is_core, is_locked, load_order, created_at)
VALUES (
    'ahgCorePlugin',
    'ahgCorePluginConfiguration',
    '1.0.0',
    'Core utilities and shared services for AHG plugins',
    'core',
    1,
    1,
    1,
    1,
    NOW()
);

-- Update if already exists
UPDATE atom_plugin SET
    version = '1.0.0',
    description = 'Core utilities and shared services for AHG plugins',
    category = 'core',
    is_enabled = 1,
    is_core = 1,
    is_locked = 1,
    load_order = 1,
    updated_at = NOW()
WHERE name = 'ahgCorePlugin';
