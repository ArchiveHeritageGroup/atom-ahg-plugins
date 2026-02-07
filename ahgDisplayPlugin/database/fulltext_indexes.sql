-- FULLTEXT indexes for fuzzy/typo-tolerant search in GLAM Browse
-- These indexes enable MySQL FULLTEXT natural language mode search
-- which provides stemming and relevance ranking.
--
-- Safe to run on existing tables - adds indexes only, no schema changes.
-- InnoDB FULLTEXT creation is non-blocking in MySQL 8.0.12+.
-- Idempotent: the CLI task checks for existing indexes before creating.

CREATE FULLTEXT INDEX ft_ioi_title ON information_object_i18n(title);
CREATE FULLTEXT INDEX ft_ioi_scope ON information_object_i18n(scope_and_content);
CREATE FULLTEXT INDEX ft_ai_name ON actor_i18n(authorized_form_of_name);
CREATE FULLTEXT INDEX ft_ti_name ON term_i18n(name);
