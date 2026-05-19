-- ==========================================================================
-- Seed external-lookup adapter settings for the authority-resolution engine.
-- Inserted into ahg_settings(setting_group='authority_resolution',
-- setting_key='authority_resolution.lookup.<source>.<param>').
--
-- All sources default to enabled=0 so a fresh install never makes external
-- HTTP calls without an admin explicitly opting in via
--   /admin/authorityResolution/settings/lookup.
--
-- Sources:
--   viaf       - VIAF AutoSuggest         (no key, CC0)
--   wikidata   - Wikidata wbsearchentities (no key, CC0)
--   geonames   - GeoNames searchJSON       (free key, CC BY)
--   tgn        - Getty TGN SPARQL          (no key, ODbL)              [stub]
--   gnd        - DNB GND lobid             (no key, CC0)               [stub]
--   isni       - ISNI                      (key required, restrictive) [stub]
--   sagnc      - South African Geographical Names Council              [stub]
--
-- Plus precedence array for merging conflicting pre-fill values.
--
-- Idempotent (INSERT IGNORE on UNIQUE setting_key). Run manually with the
-- archive root password from config/config.php Propel param:
--   MYSQL_PWD="<pw>" mysql --defaults-file=/dev/null -u root archive \
--     < seed_lookup_settings.sql
-- ==========================================================================

-- VIAF (Virtual International Authority File)
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.viaf.enabled', 'authority_resolution', 'boolean', '0',
 'Enable VIAF AutoSuggest lookup for person/org pre-fill.', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.rate_limit', 'authority_resolution', 'integer', '60',
 'Maximum VIAF requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.cache_ttl', 'authority_resolution', 'integer', '604800',
 'VIAF response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.license_note', 'authority_resolution', 'string', 'CC0-1.0',
 'License under which VIAF data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.viaf.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/publicdomain/zero/1.0/',
 'URL of VIAF license.', 0, NOW(), NOW());

-- Wikidata
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.wikidata.enabled', 'authority_resolution', 'boolean', '0',
 'Enable Wikidata wbsearchentities lookup for any entity type.', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.rate_limit', 'authority_resolution', 'integer', '120',
 'Maximum Wikidata requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.cache_ttl', 'authority_resolution', 'integer', '604800',
 'Wikidata response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.license_note', 'authority_resolution', 'string', 'CC0-1.0',
 'License under which Wikidata data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.wikidata.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/publicdomain/zero/1.0/',
 'URL of Wikidata license.', 0, NOW(), NOW());

-- GeoNames
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.geonames.enabled', 'authority_resolution', 'boolean', '0',
 'Enable GeoNames searchJSON lookup for place pre-fill.', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.rate_limit', 'authority_resolution', 'integer', '60',
 'Maximum GeoNames requests per minute (free tier).', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.cache_ttl', 'authority_resolution', 'integer', '2592000',
 'GeoNames response cache TTL in seconds (default 30 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.license_note', 'authority_resolution', 'string', 'CC BY 4.0',
 'License under which GeoNames data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/licenses/by/4.0/',
 'URL of GeoNames license.', 0, NOW(), NOW()),
('authority_resolution.lookup.geonames.username', 'authority_resolution', 'string', '',
 'GeoNames API username (required when enabled). Sign up: https://www.geonames.org/login', 0, NOW(), NOW());

-- TGN (Getty Thesaurus of Geographic Names) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.tgn.enabled', 'authority_resolution', 'boolean', '0',
 'Enable Getty TGN SPARQL lookup (stub adapter; integration deferred).', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.rate_limit', 'authority_resolution', 'integer', '30',
 'Maximum TGN SPARQL requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.cache_ttl', 'authority_resolution', 'integer', '2592000',
 'TGN response cache TTL in seconds (default 30 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.license_note', 'authority_resolution', 'string', 'ODbL 1.0',
 'License under which TGN data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.tgn.license_url', 'authority_resolution', 'string', 'https://opendatacommons.org/licenses/odbl/1-0/',
 'URL of TGN license.', 0, NOW(), NOW());

-- GND (Deutsche Nationalbibliothek Integrated Authority File) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.gnd.enabled', 'authority_resolution', 'boolean', '0',
 'Enable GND lobid lookup (stub adapter; integration deferred).', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.rate_limit', 'authority_resolution', 'integer', '60',
 'Maximum GND requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.cache_ttl', 'authority_resolution', 'integer', '604800',
 'GND response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.license_note', 'authority_resolution', 'string', 'CC0-1.0',
 'License under which GND data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.gnd.license_url', 'authority_resolution', 'string', 'https://creativecommons.org/publicdomain/zero/1.0/',
 'URL of GND license.', 0, NOW(), NOW());

-- ISNI (International Standard Name Identifier) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.isni.enabled', 'authority_resolution', 'boolean', '0',
 'Enable ISNI SRU lookup (stub adapter; integration deferred). Requires institutional credentials.', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.rate_limit', 'authority_resolution', 'integer', '30',
 'Maximum ISNI requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.cache_ttl', 'authority_resolution', 'integer', '604800',
 'ISNI response cache TTL in seconds (default 7 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.license_note', 'authority_resolution', 'string', 'ISNI Terms of Use',
 'License under which ISNI data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.isni.license_url', 'authority_resolution', 'string', 'https://isni.org/page/terms-of-use/',
 'URL of ISNI terms.', 0, NOW(), NOW());

-- SAGNC (South African Geographical Names Council) - STUB
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.sagnc.enabled', 'authority_resolution', 'boolean', '0',
 'Enable SAGNC lookup (stub adapter; integration deferred).', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.rate_limit', 'authority_resolution', 'integer', '30',
 'Maximum SAGNC requests per minute.', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.cache_ttl', 'authority_resolution', 'integer', '2592000',
 'SAGNC response cache TTL in seconds (default 30 days).', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.license_note', 'authority_resolution', 'string', 'SAGNC Open Data',
 'License under which SAGNC data is redistributable.', 0, NOW(), NOW()),
('authority_resolution.lookup.sagnc.license_url', 'authority_resolution', 'string', '',
 'URL of SAGNC license (TBD).', 0, NOW(), NOW());

-- Precedence: when two adapters return different values for the same field,
-- the first source in this JSON array wins. Stored as a JSON string the
-- PrefillEngine decodes into a PHP array.
INSERT IGNORE INTO ahg_settings (
    setting_key, setting_group, setting_type, setting_value,
    description, is_sensitive, created_at, updated_at
) VALUES
('authority_resolution.lookup.precedence', 'authority_resolution', 'json',
 '["viaf","wikidata","geonames","tgn","gnd","isni","sagnc"]',
 'Pre-fill merge precedence. First source wins when fields conflict.', 0, NOW(), NOW());
