<?php

namespace AhgPortableExportPlugin\Services;

/**
 * Generates manifest.json with checksums, entity counts, and metadata
 * for archive export packages.
 */
class ManifestBuilder
{
    /**
     * Build manifest.json for the archive export.
     *
     * @param string $outputDir  Root directory of the export package
     * @param array  $entityFiles  Map of entity type => ['path' => ..., 'count' => ...]
     * @param array  $options      Export options (scope, culture, etc.)
     * @return string  Path to generated manifest.json
     */
    public function build(string $outputDir, array $entityFiles, array $options = []): string
    {
        $manifest = [
            'version' => '3.0.0',
            'format' => 'atom-heratio-archive',
            'created_at' => date('c'),
            'source' => [
                'url' => \sfConfig::get('app_siteBaseUrl', ''),
                'site_title' => \sfConfig::get('app_siteTitle', 'AtoM'),
                'framework' => $this->getFrameworkVersion(),
                'plugin_version' => '3.0.0',
            ],
            'scope' => [
                'type' => $options['scope_type'] ?? 'all',
                'slug' => $options['scope_slug'] ?? null,
                'repository_id' => $options['scope_repository_id'] ?? null,
            ],
            'culture' => $options['culture'] ?? 'en',
            'counts' => [],
            'schema' => $this->buildSchema(),
            'files' => [],
        ];

        // Entity counts
        foreach ($entityFiles as $type => $info) {
            $manifest['counts'][$type] = $info['count'] ?? 0;
        }

        // Compute checksums for all files in the package
        $allFiles = $this->inventoryFiles($outputDir);
        foreach ($allFiles as $relativePath) {
            $fullPath = $outputDir . '/' . $relativePath;
            if (!is_file($fullPath)) {
                continue;
            }

            $manifest['files'][] = [
                'path' => $relativePath,
                'sha256' => hash_file('sha256', $fullPath),
                'size' => filesize($fullPath),
            ];
        }

        $manifestPath = $outputDir . '/manifest.json';
        file_put_contents(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // Generate README.md
        $this->buildReadme($outputDir, $manifest, $entityFiles);

        return $manifestPath;
    }

    /**
     * Build schema documentation for each entity type.
     */
    protected function buildSchema(): array
    {
        return [
            'descriptions' => [
                'primary_key' => 'id',
                'table' => 'information_object + information_object_i18n',
                'fields' => ['id', 'identifier', 'parent_id', 'lft', 'rgt', 'level_of_description_id', 'repository_id', 'title', 'scope_and_content', 'extent_and_medium', 'slug'],
                'foreign_keys' => ['parent_id → descriptions.id', 'repository_id → repositories.id', 'level_of_description_id → taxonomies.terms.id'],
            ],
            'authorities' => [
                'primary_key' => 'id',
                'table' => 'actor + actor_i18n',
                'fields' => ['id', 'entity_type_id', 'description_identifier', 'parent_id', 'authorized_form_of_name', 'dates_of_existence', 'history', 'slug'],
                'foreign_keys' => ['entity_type_id → taxonomies.terms.id'],
            ],
            'taxonomies' => [
                'primary_key' => 'id',
                'table' => 'taxonomy + taxonomy_i18n + term + term_i18n',
                'fields' => ['id', 'name', 'usage', 'note', 'terms[].id', 'terms[].parent_id', 'terms[].name'],
                'foreign_keys' => ['terms[].parent_id → terms[].id'],
            ],
            'rights' => [
                'primary_key' => 'id',
                'table' => 'rights + rights_i18n',
                'fields' => ['id', 'object_id', 'basis_id', 'act_id', 'start_date', 'end_date', 'rights_holder_id'],
                'foreign_keys' => ['object_id → descriptions.id', 'rights_holder_id → authorities.id'],
            ],
            'accessions' => [
                'primary_key' => 'id',
                'table' => 'accession + accession_i18n',
                'fields' => ['id', 'identifier', 'date', 'title', 'donors', 'events'],
                'foreign_keys' => ['donors[].donor_id → authorities.id'],
            ],
            'physical_objects' => [
                'primary_key' => 'id',
                'table' => 'physical_object + physical_object_i18n',
                'fields' => ['id', 'type_id', 'name', 'description', 'location'],
                'foreign_keys' => ['type_id → taxonomies.terms.id'],
            ],
            'events' => [
                'primary_key' => 'id',
                'table' => 'event + event_i18n',
                'fields' => ['id', 'object_id', 'actor_id', 'type_id', 'start_date', 'end_date', 'date', 'description'],
                'foreign_keys' => ['object_id → descriptions.id', 'actor_id → authorities.id', 'type_id → taxonomies.terms.id'],
            ],
            'notes' => [
                'primary_key' => 'id',
                'table' => 'note + note_i18n',
                'fields' => ['id', 'object_id', 'type_id', 'scope', 'user_id', 'content'],
                'foreign_keys' => ['object_id → descriptions.id', 'type_id → taxonomies.terms.id', 'user_id → users.users.id'],
            ],
            'relations' => [
                'primary_key' => 'id',
                'table' => 'relation + relation_i18n',
                'fields' => ['id', 'subject_id', 'object_id', 'type_id', 'start_date', 'end_date'],
                'foreign_keys' => ['subject_id → (any entity).id', 'object_id → (any entity).id', 'type_id → taxonomies.terms.id'],
            ],
            'digital_objects' => [
                'primary_key' => 'id',
                'table' => 'digital_object',
                'fields' => ['id', 'object_id', 'usage_id', 'name', 'path', 'mime_type', 'byte_size', 'checksum', 'checksum_type', 'parent_id'],
                'foreign_keys' => ['object_id → descriptions.id', 'parent_id → digital_objects.id'],
            ],
            'repositories' => [
                'primary_key' => 'id',
                'table' => 'repository + actor_i18n + repository_i18n',
                'fields' => ['id', 'identifier', 'authorized_form_of_name', 'history', 'collecting_policies', 'slug', 'contacts'],
                'foreign_keys' => [],
            ],
            'object_term_relations' => [
                'primary_key' => 'id',
                'table' => 'object_term_relation',
                'fields' => ['id', 'object_id', 'term_id', 'term_name'],
                'foreign_keys' => ['object_id → descriptions.id', 'term_id → taxonomies.terms.id'],
            ],
            'settings' => [
                'primary_key' => null,
                'table' => 'setting + setting_i18n + ahg_settings',
                'fields' => ['setting[].id', 'setting[].name', 'setting[].scope', 'setting_i18n[].value', 'ahg_settings[].setting_key', 'ahg_settings[].setting_value'],
                'foreign_keys' => [],
            ],
            'users' => [
                'primary_key' => null,
                'table' => 'user + actor_i18n + acl_group + acl_user_group + acl_permission',
                'fields' => ['users[].id', 'users[].username', 'users[].email', 'users[].display_name', 'groups[].id', 'groups[].name'],
                'foreign_keys' => ['user_groups[].user_id → users[].id', 'user_groups[].group_id → groups[].id'],
                'note' => 'Passwords and salts are excluded for security.',
            ],
            'menus' => [
                'primary_key' => null,
                'table' => 'menu + menu_i18n',
                'fields' => ['menus[].id', 'menus[].parent_id', 'menus[].name', 'menus[].path', 'menus[].lft', 'menus[].rgt', 'menu_i18n[].label'],
                'foreign_keys' => ['menus[].parent_id → menus[].id'],
            ],
        ];
    }

    /**
     * Generate a README.md for the archive package.
     */
    protected function buildReadme(string $outputDir, array $manifest, array $entityFiles): void
    {
        $lines = [];
        $lines[] = '# AtoM Heratio Archive Export';
        $lines[] = '';
        $lines[] = 'This archive was generated by the **AtoM Heratio Portable Export Plugin**.';
        $lines[] = '';
        $lines[] = '## Export Details';
        $lines[] = '';
        $lines[] = '| Property | Value |';
        $lines[] = '|----------|-------|';
        $lines[] = '| Source | ' . ($manifest['source']['url'] ?: 'N/A') . ' |';
        $lines[] = '| Site Title | ' . ($manifest['source']['site_title'] ?? 'AtoM') . ' |';
        $lines[] = '| Export Date | ' . $manifest['created_at'] . ' |';
        $lines[] = '| Framework Version | ' . ($manifest['source']['framework'] ?? 'unknown') . ' |';
        $lines[] = '| Plugin Version | ' . ($manifest['source']['plugin_version'] ?? 'unknown') . ' |';
        $lines[] = '| Schema Version | ' . $manifest['version'] . ' |';
        $lines[] = '| Culture | ' . ($manifest['culture'] ?? 'en') . ' |';
        $lines[] = '| Scope | ' . ($manifest['scope']['type'] ?? 'all') . ' |';
        $lines[] = '';

        $lines[] = '## Entity Counts';
        $lines[] = '';
        $lines[] = '| Entity Type | Count |';
        $lines[] = '|-------------|-------|';
        foreach ($manifest['counts'] as $type => $count) {
            $label = ucwords(str_replace('_', ' ', $type));
            $lines[] = '| ' . $label . ' | ' . number_format($count) . ' |';
        }
        $lines[] = '';

        $lines[] = '## Package Contents';
        $lines[] = '';
        $lines[] = '```';
        $lines[] = 'manifest.json     — Package manifest with SHA-256 checksums';
        $lines[] = 'README.md         — This file';
        $lines[] = 'data/             — Entity JSON files';
        foreach ($entityFiles as $type => $info) {
            $lines[] = '  ' . $type . '.json';
        }
        $lines[] = 'objects/          — Digital object files (if included)';
        $lines[] = '```';
        $lines[] = '';

        $lines[] = '## Re-importing This Archive';
        $lines[] = '';
        $lines[] = 'To import this archive into a fresh AtoM Heratio instance:';
        $lines[] = '';
        $lines[] = '```bash';
        $lines[] = '# Dry run (validate without importing)';
        $lines[] = 'php symfony portable:import --zip=/path/to/archive.zip --mode=dry_run';
        $lines[] = '';
        $lines[] = '# Merge import (skip existing records, import only new)';
        $lines[] = 'php symfony portable:import --zip=/path/to/archive.zip --mode=merge';
        $lines[] = '';
        $lines[] = '# Replace import (clear target tables, full re-import)';
        $lines[] = 'php symfony portable:import --zip=/path/to/archive.zip --mode=replace';
        $lines[] = '```';
        $lines[] = '';

        $lines[] = '## Verification';
        $lines[] = '';
        $lines[] = 'Verify the integrity of this archive using SHA-256 checksums:';
        $lines[] = '';
        $lines[] = '```bash';
        $lines[] = 'php symfony portable:verify --path=/path/to/archive.zip';
        $lines[] = '```';
        $lines[] = '';

        $lines[] = '## Compatibility';
        $lines[] = '';
        $lines[] = '- Requires AtoM Heratio Framework v2.0.0+';
        $lines[] = '- Requires ahgPortableExportPlugin v3.0.0+';
        $lines[] = '- PHP 8.1+';
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '*Generated by The Archive and Heritage Group (Pty) Ltd*';

        file_put_contents($outputDir . '/README.md', implode("\n", $lines));
    }

    /**
     * Recursively inventory all files in the output directory.
     *
     * @return string[] Relative file paths
     */
    protected function inventoryFiles(string $dir, string $prefix = ''): array
    {
        $files = [];
        $items = @scandir($dir);
        if (!$items) {
            return $files;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            $relative = $prefix ? $prefix . '/' . $item : $item;

            // Skip manifest.json itself — it gets written after this runs
            if ($relative === 'manifest.json') {
                continue;
            }

            if (is_dir($path)) {
                $files = array_merge($files, $this->inventoryFiles($path, $relative));
            } else {
                $files[] = $relative;
            }
        }

        return $files;
    }

    /**
     * Get framework version from atom-framework.
     */
    protected function getFrameworkVersion(): string
    {
        $versionFile = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive')
            . '/atom-framework/version.txt';

        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }

        return 'unknown';
    }

    /**
     * Verify a manifest against its package files.
     *
     * @param string $manifestPath  Path to manifest.json
     * @param string $packageDir    Root directory of extracted package
     * @return array{ok: bool, total: int, verified: int, mismatches: array, missing: array}
     */
    public static function verify(string $manifestPath, string $packageDir): array
    {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest || empty($manifest['files'])) {
            return [
                'ok' => false,
                'total' => 0,
                'verified' => 0,
                'mismatches' => [],
                'missing' => ['manifest.json has no files or is invalid'],
            ];
        }

        $total = count($manifest['files']);
        $verified = 0;
        $mismatches = [];
        $missing = [];

        foreach ($manifest['files'] as $fileEntry) {
            $fullPath = $packageDir . '/' . $fileEntry['path'];

            if (!file_exists($fullPath)) {
                $missing[] = $fileEntry['path'];
                continue;
            }

            $actualHash = hash_file('sha256', $fullPath);
            if ($actualHash !== $fileEntry['sha256']) {
                $mismatches[] = [
                    'path' => $fileEntry['path'],
                    'expected' => $fileEntry['sha256'],
                    'actual' => $actualHash,
                ];
            } else {
                $verified++;
            }
        }

        return [
            'ok' => empty($mismatches) && empty($missing),
            'total' => $total,
            'verified' => $verified,
            'mismatches' => $mismatches,
            'missing' => $missing,
        ];
    }
}
