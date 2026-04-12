-- =============================================================================
-- Registry — Repeatable URLs for Institution & Vendor
-- Adds a single registry_entity_url table so institutions and vendors can
-- attach multiple typed URLs (archives site, AtoM instance, digital repository,
-- social profiles, source control, etc.) instead of a fixed handful of columns.
-- =============================================================================

CREATE TABLE IF NOT EXISTS registry_entity_url (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL COMMENT 'institution, vendor',
    entity_id BIGINT UNSIGNED NOT NULL,
    link_type VARCHAR(30) NOT NULL DEFAULT 'website' COMMENT 'website, atom_instance, repository, catalogue, blog, social, github, gitlab, linkedin, facebook, twitter, youtube, other',
    url VARCHAR(500) NOT NULL,
    label VARCHAR(150) DEFAULT NULL COMMENT 'optional custom label shown instead of link_type',
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_link_type (link_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill: migrate existing single-column URLs into the new repeatable table
-- so no data is lost when forms switch to the repeatable widget.
INSERT INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'institution', id, 'website', website, 10
FROM registry_institution
WHERE website IS NOT NULL AND website <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'institution' AND u.entity_id = registry_institution.id
        AND u.link_type = 'website' AND u.url = registry_institution.website
  );

INSERT INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'website', website, 10
FROM registry_vendor
WHERE website IS NOT NULL AND website <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'website' AND u.url = registry_vendor.website
  );

INSERT INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'github', github_url, 20
FROM registry_vendor
WHERE github_url IS NOT NULL AND github_url <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'github' AND u.url = registry_vendor.github_url
  );

INSERT INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'gitlab', gitlab_url, 30
FROM registry_vendor
WHERE gitlab_url IS NOT NULL AND gitlab_url <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'gitlab' AND u.url = registry_vendor.gitlab_url
  );

INSERT INTO registry_entity_url (entity_type, entity_id, link_type, url, sort_order)
SELECT 'vendor', id, 'linkedin', linkedin_url, 40
FROM registry_vendor
WHERE linkedin_url IS NOT NULL AND linkedin_url <> ''
  AND NOT EXISTS (
      SELECT 1 FROM registry_entity_url u
      WHERE u.entity_type = 'vendor' AND u.entity_id = registry_vendor.id
        AND u.link_type = 'linkedin' AND u.url = registry_vendor.linkedin_url
  );
