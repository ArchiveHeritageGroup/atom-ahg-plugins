-- =============================================================================
-- OpenRiC software stack seed for registry_software
-- Source: https://openric.org and the openric/* GitHub organisation
-- Idempotent: keyed by slug; safe to re-run.
-- =============================================================================

-- Remove duplicate of the RiC standard. RiC-O is a standard, not software —
-- it lives in registry_standard (slug 'ric').
DELETE FROM registry_software WHERE slug = 'ric';

-- -----------------------------------------------------------------------------
-- 1. OpenRiC Reference API (openric/service) — Laravel server implementing
--    the OpenRiC contract.
-- -----------------------------------------------------------------------------
UPDATE registry_software SET
  name = 'OpenRiC Reference API',
  short_description = 'Reference Laravel implementation of the OpenRiC HTTP contract — 46 endpoints, full RiC-O 1.1 8-entity CRUD, OAI-PMH v2.0, auto-generated OpenAPI 3.0.',
  description = 'OpenRiC Reference API is the canonical implementation of the OpenRiC HTTP contract on top of RiC-O 1.1. It exposes Records, Agents, Places, Rules, Activities, Instantiations, Repositories and Functions through a uniform CRUD surface, plus OAI-PMH v2.0 for harvesting and content negotiation for JSON-LD/Turtle/HTML. Any OpenRiC-conformant client (viewer, capture, third-party) can drive this server, and any other server that implements the contract can replace it without client changes. Hosted reference deployment at ric.theahg.co.za.',
  category = JSON_ARRAY('integration','discovery'),
  website = 'https://ric.theahg.co.za',
  documentation_url = 'https://openric.org',
  git_provider = 'github',
  git_url = 'https://github.com/openric/service',
  git_default_branch = 'main',
  git_is_public = 1,
  license = 'AGPL-3.0',
  license_url = 'https://www.gnu.org/licenses/agpl-3.0.html',
  pricing_model = 'open_source',
  glam_sectors = JSON_ARRAY('archive','library','museum','gallery','dam'),
  is_verified = 1,
  is_active = 1,
  updated_at = NOW()
WHERE slug = 'openric-api';

-- -----------------------------------------------------------------------------
-- 2. OpenRiC Viewer (openric/viewer, npm @openric/viewer)
-- -----------------------------------------------------------------------------
UPDATE registry_software SET
  name = 'OpenRiC Viewer',
  short_description = 'Standalone 2D/3D graph viewer for OpenRiC-conformant servers. Implementation-neutral — drives any server that implements the OpenRiC Viewing API.',
  description = 'A pure-browser application that renders archival graphs visually in 2D and 3D. The viewer is published on npm as @openric/viewer and can be embedded in any host page. It speaks only the OpenRiC HTTP contract, so it works against the reference API or any other conformant server.',
  category = JSON_ARRAY('discovery','utility'),
  website = 'https://viewer.openric.org',
  documentation_url = 'https://openric.org',
  git_provider = 'github',
  git_url = 'https://github.com/openric/viewer',
  git_default_branch = 'main',
  git_is_public = 1,
  license = 'AGPL-3.0',
  license_url = 'https://www.gnu.org/licenses/agpl-3.0.html',
  latest_version = '0.3.0',
  pricing_model = 'open_source',
  glam_sectors = JSON_ARRAY('archive','library','museum','gallery','dam'),
  is_verified = 1,
  is_active = 1,
  updated_at = NOW()
WHERE slug = 'openric-viewer';

-- -----------------------------------------------------------------------------
-- 3. OpenRiC Capture (openric/capture)
-- -----------------------------------------------------------------------------
UPDATE registry_software SET
  name = 'OpenRiC Capture',
  short_description = 'Pure-browser data-entry client for OpenRiC servers. Create and edit Records, Agents, Places, Rules, Activities, Instantiations and relations against any conformant server.',
  description = 'OpenRiC Capture is a browser-only data-entry client. It uses the OpenRiC write surface (POST/PATCH/DELETE) to create and edit archival entities — records, agents, places, rules, activities, instantiations, and the relations between them. Like the viewer, it is server-agnostic: point it at any conformant OpenRiC server and it works.',
  category = JSON_ARRAY('utility','cms'),
  website = 'https://capture.openric.org',
  documentation_url = 'https://openric.org',
  git_provider = 'github',
  git_url = 'https://github.com/openric/capture',
  git_default_branch = 'main',
  git_is_public = 1,
  license = 'AGPL-3.0',
  license_url = 'https://www.gnu.org/licenses/agpl-3.0.html',
  pricing_model = 'open_source',
  glam_sectors = JSON_ARRAY('archive','library','museum','gallery','dam'),
  is_verified = 1,
  is_active = 1,
  updated_at = NOW()
WHERE slug = 'openric-capture';

-- -----------------------------------------------------------------------------
-- 4. OpenRiC Validator — Python CLI, lives in openric/spec/validator/
-- -----------------------------------------------------------------------------
INSERT INTO registry_software
  (name, slug, vendor_id, category, short_description, description,
   website, documentation_url,
   git_provider, git_url, git_default_branch, git_is_public,
   license, license_url, latest_version,
   pricing_model, glam_sectors, is_verified, is_active, created_at, updated_at)
VALUES (
  'OpenRiC Validator', 'openric-validator', 1,
  JSON_ARRAY('utility'),
  'Python CLI conformance validator for the OpenRiC specification — JSON Schemas, SHACL shapes, profile checks.',
  'OpenRiC Validator (openric-validate) is the official Python CLI that validates artefacts against the OpenRiC specification. It runs the 19 JSON Schemas, the SHACL shapes for each named profile (Core Discovery through Export-Only), and the 27-case fixture pack. Used in CI to keep server implementations and content packages on-spec.',
  'https://openric.org', 'https://openric.org',
  'github', 'https://github.com/openric/spec', 'main', 1,
  'AGPL-3.0', 'https://www.gnu.org/licenses/agpl-3.0.html', '0.1.0',
  'open_source', JSON_ARRAY('archive','library','museum','gallery','dam'),
  1, 1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  vendor_id = VALUES(vendor_id),
  category = VALUES(category),
  short_description = VALUES(short_description),
  description = VALUES(description),
  website = VALUES(website),
  documentation_url = VALUES(documentation_url),
  git_provider = VALUES(git_provider),
  git_url = VALUES(git_url),
  git_default_branch = VALUES(git_default_branch),
  git_is_public = VALUES(git_is_public),
  license = VALUES(license),
  license_url = VALUES(license_url),
  latest_version = VALUES(latest_version),
  pricing_model = VALUES(pricing_model),
  glam_sectors = VALUES(glam_sectors),
  is_verified = VALUES(is_verified),
  is_active = VALUES(is_active),
  updated_at = NOW();

-- -----------------------------------------------------------------------------
-- 5. OpenRiC Conformance Suite — bash + jq probe in openric/spec/conformance/
-- -----------------------------------------------------------------------------
INSERT INTO registry_software
  (name, slug, vendor_id, category, short_description, description,
   website, documentation_url,
   git_provider, git_url, git_default_branch, git_is_public,
   license, license_url,
   pricing_model, glam_sectors, is_verified, is_active, created_at, updated_at)
VALUES (
  'OpenRiC Conformance Suite', 'openric-conformance', 1,
  JSON_ARRAY('utility'),
  'Black-box conformance probe for OpenRiC servers — point it at any server, get a pass/fail report across every documented endpoint.',
  'A bash + jq script that exercises every required endpoint of an OpenRiC server and reports pass/fail per profile. Runs in CI for the reference implementation and is the same script third parties use to certify their own servers as conformant.',
  'https://openric.org/conformance', 'https://openric.org/conformance',
  'github', 'https://github.com/openric/spec', 'main', 1,
  'AGPL-3.0', 'https://www.gnu.org/licenses/agpl-3.0.html',
  'open_source', JSON_ARRAY('archive','library','museum','gallery','dam'),
  1, 1, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  vendor_id = VALUES(vendor_id),
  category = VALUES(category),
  short_description = VALUES(short_description),
  description = VALUES(description),
  website = VALUES(website),
  documentation_url = VALUES(documentation_url),
  git_provider = VALUES(git_provider),
  git_url = VALUES(git_url),
  git_default_branch = VALUES(git_default_branch),
  git_is_public = VALUES(git_is_public),
  license = VALUES(license),
  license_url = VALUES(license_url),
  pricing_model = VALUES(pricing_model),
  glam_sectors = VALUES(glam_sectors),
  is_verified = VALUES(is_verified),
  is_active = VALUES(is_active),
  updated_at = NOW();
