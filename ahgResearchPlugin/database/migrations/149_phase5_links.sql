-- ============================================================
-- Issue 149 Phase 5: External Link Typing
-- ============================================================

ALTER TABLE `research_project_resource`
  ADD COLUMN `link_type` ENUM('academic','archive','database','government','website','social_media','other') DEFAULT NULL AFTER `external_url`,
  ADD COLUMN `link_metadata` JSON DEFAULT NULL AFTER `link_type`;
