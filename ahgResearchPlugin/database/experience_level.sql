-- ahgResearchPlugin — research mode / experience level (cloned from Heratio)
-- Per-researcher mode (beginning/intermediate/advanced) that curates the
-- research sidebar: Beginning = core essentials, Intermediate adds the working
-- tools, Advanced reveals everything. Defaults to 'intermediate'.
-- Run-once, additive. MySQL 8 ADD COLUMN is INSTANT.

ALTER TABLE `research_researcher`
    ADD COLUMN `experience_level` VARCHAR(20) NOT NULL DEFAULT 'intermediate'
    COMMENT 'beginning, intermediate, advanced' AFTER `status`;
