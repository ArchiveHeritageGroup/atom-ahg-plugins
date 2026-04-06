<?php

declare(strict_types=1);

namespace AhgDiscovery;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PageIndex Migration — creates ahg_pageindex_tree and ahg_pageindex_query_log tables.
 *
 * Safe to run multiple times: checks table/column existence before every DDL statement.
 * Does NOT use ADD COLUMN IF NOT EXISTS (unsupported on this MySQL instance).
 */
class PageIndexMigration
{
    /**
     * Run the migration.
     *
     * @return array Summary of actions taken
     */
    public function up(): array
    {
        $actions = [];

        // ── Table: ahg_pageindex_tree ──────────────────────────────────
        if (!$this->tableExists('ahg_pageindex_tree')) {
            DB::statement("
                CREATE TABLE ahg_pageindex_tree (
                    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    object_id       INT NOT NULL COMMENT 'information_object.id or external doc ID',
                    object_type     VARCHAR(20) NOT NULL COMMENT 'ead, pdf, rico',
                    tree_json       LONGTEXT NOT NULL COMMENT 'Hierarchical JSON tree built by LLM',
                    status          VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, building, ready, error',
                    error_message   TEXT NULL COMMENT 'Error details if status=error',
                    indexed_at      TIMESTAMP NULL COMMENT 'When the tree was last built',
                    model_used      VARCHAR(100) NULL COMMENT 'LLM model that built the tree',
                    node_count      INT NOT NULL DEFAULT 0 COMMENT 'Number of nodes in the tree',
                    source_hash     VARCHAR(64) NULL COMMENT 'SHA-256 of source content for change detection',
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_object (object_id, object_type),
                    INDEX idx_status (status),
                    INDEX idx_object_type (object_type),
                    INDEX idx_indexed_at (indexed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $actions[] = 'Created table ahg_pageindex_tree';
        } else {
            $actions[] = 'Table ahg_pageindex_tree already exists';

            // Check for columns that may be missing from earlier versions
            $requiredColumns = [
                'error_message' => "ALTER TABLE ahg_pageindex_tree ADD COLUMN error_message TEXT NULL COMMENT 'Error details if status=error' AFTER status",
                'node_count'    => "ALTER TABLE ahg_pageindex_tree ADD COLUMN node_count INT NOT NULL DEFAULT 0 COMMENT 'Number of nodes in the tree' AFTER model_used",
                'source_hash'   => "ALTER TABLE ahg_pageindex_tree ADD COLUMN source_hash VARCHAR(64) NULL COMMENT 'SHA-256 of source content for change detection' AFTER node_count",
                'updated_at'    => "ALTER TABLE ahg_pageindex_tree ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
            ];

            foreach ($requiredColumns as $col => $ddl) {
                if (!$this->columnExists('ahg_pageindex_tree', $col)) {
                    DB::statement($ddl);
                    $actions[] = "Added column ahg_pageindex_tree.{$col}";
                }
            }
        }

        // ── Table: ahg_pageindex_query_log ─────────────────────────────
        if (!$this->tableExists('ahg_pageindex_query_log')) {
            DB::statement("
                CREATE TABLE ahg_pageindex_query_log (
                    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    query_text      TEXT NOT NULL,
                    tree_id         BIGINT UNSIGNED NULL COMMENT 'ahg_pageindex_tree.id searched',
                    matched_node_ids TEXT NULL COMMENT 'JSON array of matched node IDs from tree',
                    result_count    INT NOT NULL DEFAULT 0,
                    reasoning_text  TEXT NULL COMMENT 'LLM reasoning explanation for the match',
                    model_used      VARCHAR(100) NULL,
                    response_ms     INT NULL COMMENT 'LLM response time in milliseconds',
                    user_id         INT NULL,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_tree (tree_id),
                    INDEX idx_created (created_at),
                    INDEX idx_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $actions[] = 'Created table ahg_pageindex_query_log';
        } else {
            $actions[] = 'Table ahg_pageindex_query_log already exists';

            // Check for columns that may be missing from earlier versions
            $requiredColumns = [
                'tree_id'        => "ALTER TABLE ahg_pageindex_query_log ADD COLUMN tree_id BIGINT UNSIGNED NULL COMMENT 'ahg_pageindex_tree.id searched' AFTER query_text",
                'reasoning_text' => "ALTER TABLE ahg_pageindex_query_log ADD COLUMN reasoning_text TEXT NULL COMMENT 'LLM reasoning explanation for the match' AFTER result_count",
                'model_used'     => "ALTER TABLE ahg_pageindex_query_log ADD COLUMN model_used VARCHAR(100) NULL AFTER reasoning_text",
                'response_ms'    => "ALTER TABLE ahg_pageindex_query_log ADD COLUMN response_ms INT NULL COMMENT 'LLM response time in milliseconds' AFTER model_used",
                'user_id'        => "ALTER TABLE ahg_pageindex_query_log ADD COLUMN user_id INT NULL AFTER response_ms",
            ];

            foreach ($requiredColumns as $col => $ddl) {
                if (!$this->columnExists('ahg_pageindex_query_log', $col)) {
                    DB::statement($ddl);
                    $actions[] = "Added column ahg_pageindex_query_log.{$col}";
                }
            }
        }

        return $actions;
    }

    /**
     * Drop the PageIndex tables (rollback).
     *
     * @return array Summary of actions taken
     */
    public function down(): array
    {
        $actions = [];

        if ($this->tableExists('ahg_pageindex_query_log')) {
            DB::statement('DROP TABLE ahg_pageindex_query_log');
            $actions[] = 'Dropped table ahg_pageindex_query_log';
        }

        if ($this->tableExists('ahg_pageindex_tree')) {
            DB::statement('DROP TABLE ahg_pageindex_tree');
            $actions[] = 'Dropped table ahg_pageindex_tree';
        }

        return $actions;
    }

    /**
     * Check if a table exists in the database.
     */
    private function tableExists(string $table): bool
    {
        $result = DB::select("SHOW TABLES LIKE ?", [$table]);

        return !empty($result);
    }

    /**
     * Check if a column exists in a table.
     */
    private function columnExists(string $table, string $column): bool
    {
        $result = DB::select(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return !empty($result);
    }
}
