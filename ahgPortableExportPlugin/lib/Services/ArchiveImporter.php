<?php

namespace AhgPortableExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Imports AtoM archive packages produced by ArchiveExtractor.
 *
 * Reads JSON entity files from an extracted archive directory and creates
 * AtoM records in FK-dependency order with full ID remapping.
 *
 * Supports three modes:
 *   - merge:   Skip existing records, import only new ones
 *   - replace: Clear target tables before import (dangerous)
 *   - dry_run: Validate and report without writing to DB
 */
class ArchiveImporter
{
    protected string $culture;
    /** @var callable|null */
    protected $progressCallback;

    /** @var array<string, array<int, int>> Old ID → new ID per entity type */
    protected array $idMap = [];

    /** @var array Import statistics */
    protected array $stats = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    /** @var string[] Error log entries */
    protected array $errorLog = [];

    /**
     * Entity import order — respects FK dependencies.
     * Later entities may reference earlier ones via remapped IDs.
     */
    protected const IMPORT_ORDER = [
        'taxonomies',
        'repositories',
        'authorities',
        'users',
        'descriptions',
        'events',
        'notes',
        'relations',
        'rights',
        'accessions',
        'physical_objects',
        'object_term_relations',
        'digital_objects',
        'settings',
        'menus',
    ];

    public function __construct(string $culture = 'en', ?callable $progressCallback = null)
    {
        $this->culture = $culture;
        $this->progressCallback = $progressCallback;
    }

    // ─── Validation ─────────────────────────────────────────────────

    /**
     * Validate an archive directory structure and manifest integrity.
     *
     * @return array{valid: bool, manifest: ?array, errors: string[], entity_counts: array}
     */
    public function validate(string $archiveDir): array
    {
        $errors = [];
        $manifest = null;
        $entityCounts = [];

        // Check manifest.json
        $manifestPath = $archiveDir . '/manifest.json';
        if (!file_exists($manifestPath)) {
            $errors[] = 'manifest.json not found in archive root';

            return ['valid' => false, 'manifest' => null, 'errors' => $errors, 'entity_counts' => []];
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest) {
            $errors[] = 'manifest.json is invalid JSON';

            return ['valid' => false, 'manifest' => null, 'errors' => $errors, 'entity_counts' => []];
        }

        // Validate format
        if (($manifest['format'] ?? '') !== 'atom-heratio-archive') {
            $errors[] = 'Unsupported archive format: ' . ($manifest['format'] ?? 'unknown');
        }

        // Check data directory
        $dataDir = $archiveDir . '/data';
        if (!is_dir($dataDir)) {
            $errors[] = 'data/ directory not found';
        }

        // Verify each expected entity file
        $entityTypes = array_keys($manifest['counts'] ?? []);
        foreach ($entityTypes as $type) {
            $filePath = $dataDir . '/' . $type . '.json';
            if (!file_exists($filePath)) {
                $errors[] = "Entity file missing: data/{$type}.json";

                continue;
            }

            $data = json_decode(file_get_contents($filePath), true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Invalid JSON in data/{$type}.json: " . json_last_error_msg();

                continue;
            }

            $entityCounts[$type] = is_array($data) ? count($data) : 0;
        }

        // Verify file checksums from manifest
        $checksumErrors = 0;
        foreach ($manifest['files'] ?? [] as $fileEntry) {
            $fullPath = $archiveDir . '/' . $fileEntry['path'];
            if (!file_exists($fullPath)) {
                $errors[] = 'File missing: ' . $fileEntry['path'];
                $checksumErrors++;

                continue;
            }

            $actualHash = hash_file('sha256', $fullPath);
            if ($actualHash !== ($fileEntry['sha256'] ?? '')) {
                $errors[] = 'Checksum mismatch: ' . $fileEntry['path'];
                $checksumErrors++;
            }
        }

        if ($checksumErrors > 0) {
            $errors[] = "{$checksumErrors} file(s) failed checksum verification";
        }

        return [
            'valid' => empty($errors),
            'manifest' => $manifest,
            'errors' => $errors,
            'entity_counts' => $entityCounts,
        ];
    }

    // ─── Main Import Pipeline ───────────────────────────────────────

    /**
     * Run the full import pipeline.
     *
     * @param int    $importId    portable_import.id
     * @param string $archiveDir  Path to extracted archive directory
     * @param string $mode        merge, replace, or dry_run
     */
    public function import(int $importId, string $archiveDir, string $mode = 'merge'): array
    {
        $this->idMap = [];
        $this->stats = ['imported' => 0, 'skipped' => 0, 'errors' => 0];
        $this->errorLog = [];

        $dataDir = $archiveDir . '/data';

        // Determine which entity types to import
        $importRow = DB::table('portable_import')->where('id', $importId)->first();
        $selectedTypes = null;
        if ($importRow && $importRow->entity_types) {
            $selectedTypes = json_decode($importRow->entity_types, true);
        }

        // Filter import order to only selected types that have data files
        $typesToImport = [];
        foreach (self::IMPORT_ORDER as $type) {
            if ($selectedTypes !== null && !in_array($type, $selectedTypes)) {
                continue;
            }
            $filePath = $dataDir . '/' . $type . '.json';
            if (file_exists($filePath)) {
                $typesToImport[] = $type;
            }
        }

        $totalTypes = count($typesToImport);
        $processed = 0;

        foreach ($typesToImport as $type) {
            $filePath = $dataDir . '/' . $type . '.json';
            $data = json_decode(file_get_contents($filePath), true);

            if (!is_array($data)) {
                $this->logError("Skipping {$type}: invalid data format");
                $processed++;

                continue;
            }

            try {
                $this->importEntityType($type, $data, $mode, $archiveDir);
            } catch (\Exception $e) {
                $this->logError("Error importing {$type}: " . $e->getMessage());
            }

            $processed++;
            $this->updateImportProgress($importId, $processed, $totalTypes);

            if ($this->progressCallback) {
                ($this->progressCallback)($processed, $totalTypes);
            }
        }

        // Save final state
        DB::table('portable_import')->where('id', $importId)->update([
            'status' => 'completed',
            'progress' => 100,
            'imported_entities' => $this->stats['imported'],
            'skipped_entities' => $this->stats['skipped'],
            'error_count' => $this->stats['errors'],
            'id_mapping' => json_encode($this->idMap),
            'error_log' => !empty($this->errorLog) ? implode("\n", $this->errorLog) : null,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'imported' => $this->stats['imported'],
            'skipped' => $this->stats['skipped'],
            'errors' => $this->stats['errors'],
            'error_log' => $this->errorLog,
            'id_mapping' => $this->idMap,
        ];
    }

    /**
     * Dispatch to the correct import method per entity type.
     */
    protected function importEntityType(string $type, array $data, string $mode, string $archiveDir): void
    {
        switch ($type) {
            case 'taxonomies':
                $this->importTaxonomies($data, $mode);
                break;
            case 'repositories':
                $this->importRepositories($data, $mode);
                break;
            case 'authorities':
                $this->importAuthorities($data, $mode);
                break;
            case 'users':
                $this->importUsers($data, $mode);
                break;
            case 'descriptions':
                $this->importDescriptions($data, $mode);
                break;
            case 'events':
                $this->importEvents($data, $mode);
                break;
            case 'notes':
                $this->importNotes($data, $mode);
                break;
            case 'relations':
                $this->importRelations($data, $mode);
                break;
            case 'rights':
                $this->importRights($data, $mode);
                break;
            case 'accessions':
                $this->importAccessions($data, $mode);
                break;
            case 'physical_objects':
                $this->importPhysicalObjects($data, $mode);
                break;
            case 'object_term_relations':
                $this->importObjectTermRelations($data, $mode);
                break;
            case 'digital_objects':
                $this->importDigitalObjects($data, $mode, $archiveDir);
                break;
            case 'settings':
                $this->importSettings($data, $mode);
                break;
            case 'menus':
                $this->importMenus($data, $mode);
                break;
        }
    }

    // ─── Entity Import Methods ──────────────────────────────────────

    protected function importTaxonomies(array $data, string $mode): void
    {
        foreach ($data as $tax) {
            $oldTaxId = (int) $tax['id'];

            // Check if taxonomy exists by name
            $existing = DB::table('taxonomy_i18n')
                ->where('name', $tax['name'])
                ->where('culture', $this->culture)
                ->first();

            if ($existing) {
                $this->idMap['taxonomy'][$oldTaxId] = (int) $existing->id;

                // Import terms within existing taxonomy
                if (!empty($tax['terms'])) {
                    $this->importTerms($tax['terms'], (int) $existing->id, $mode);
                }
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            // Create new taxonomy
            $newTaxId = DB::table('taxonomy')->insertGetId([
                'usage' => $tax['usage'] ?? null,
                'source_culture' => $this->culture,
            ]);

            DB::table('taxonomy_i18n')->insert([
                'id' => $newTaxId,
                'culture' => $this->culture,
                'name' => $tax['name'],
                'note' => $tax['note'] ?? null,
            ]);

            $this->idMap['taxonomy'][$oldTaxId] = $newTaxId;
            $this->stats['imported']++;

            if (!empty($tax['terms'])) {
                $this->importTerms($tax['terms'], $newTaxId, $mode);
            }
        }
    }

    protected function importTerms(array $terms, int $taxonomyId, string $mode): void
    {
        foreach ($terms as $term) {
            $oldTermId = (int) $term['id'];

            // Check if term exists in this taxonomy by name
            $existing = DB::table('term as t')
                ->join('term_i18n as ti', 't.id', '=', 'ti.id')
                ->where('t.taxonomy_id', $taxonomyId)
                ->where('ti.name', $term['name'] ?? '')
                ->where('ti.culture', $this->culture)
                ->first();

            if ($existing) {
                $this->idMap['term'][$oldTermId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            // Create object row first
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitTerm',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Remap parent_id
            $parentId = $this->remapId($term['parent_id'] ?? null, 'term');

            DB::table('term')->insert([
                'id' => $objectId,
                'taxonomy_id' => $taxonomyId,
                'parent_id' => $parentId ?: \QubitTerm::ROOT_ID,
                'source_culture' => $this->culture,
            ]);

            DB::table('term_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'name' => $term['name'] ?? '',
            ]);

            $this->idMap['term'][$oldTermId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importRepositories(array $data, string $mode): void
    {
        foreach ($data as $repo) {
            $oldId = (int) $repo['id'];

            // Match by slug or name
            $existing = null;
            if (!empty($repo['slug'])) {
                $slugRow = DB::table('slug')->where('slug', $repo['slug'])->first();
                if ($slugRow) {
                    $existing = DB::table('repository')->where('id', $slugRow->object_id)->first();
                }
            }
            if (!$existing && !empty($repo['authorized_form_of_name'])) {
                $actorMatch = DB::table('actor_i18n')
                    ->where('authorized_form_of_name', $repo['authorized_form_of_name'])
                    ->where('culture', $this->culture)
                    ->first();
                if ($actorMatch) {
                    $existing = DB::table('repository')->where('id', $actorMatch->id)->first();
                }
            }

            if ($existing && $mode === 'merge') {
                $this->idMap['repository'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            // Create: object → actor → repository
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitRepository',
                'created_at' => $repo['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $repo['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('actor')->insert([
                'id' => $objectId,
                'parent_id' => \QubitActor::ROOT_ID ?? 3,
                'source_culture' => $this->culture,
            ]);

            DB::table('actor_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'authorized_form_of_name' => $repo['authorized_form_of_name'] ?? '',
                'history' => $repo['history'] ?? null,
                'places' => $repo['places'] ?? null,
                'dates_of_existence' => $repo['dates_of_existence'] ?? null,
            ]);

            DB::table('repository')->insert([
                'id' => $objectId,
                'identifier' => $repo['identifier'] ?? null,
                'desc_status_id' => $this->remapId($repo['desc_status_id'] ?? null, 'term'),
                'desc_detail_id' => $this->remapId($repo['desc_detail_id'] ?? null, 'term'),
                'source_culture' => $this->culture,
            ]);

            // Repository i18n
            DB::table('repository_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'geocultural_context' => $repo['geocultural_context'] ?? null,
                'collecting_policies' => $repo['collecting_policies'] ?? null,
                'buildings' => $repo['buildings'] ?? null,
                'holdings' => $repo['holdings'] ?? null,
                'finding_aids' => $repo['finding_aids'] ?? null,
                'opening_times' => $repo['opening_times'] ?? null,
                'access_conditions' => $repo['access_conditions'] ?? null,
                'disabled_access' => $repo['disabled_access'] ?? null,
                'research_services' => $repo['research_services'] ?? null,
                'reproduction_services' => $repo['reproduction_services'] ?? null,
                'public_facilities' => $repo['public_facilities'] ?? null,
            ]);

            // Slug
            $slug = $repo['slug'] ?? $this->generateSlug($repo['authorized_form_of_name'] ?? 'repository');
            $this->insertSlug($objectId, $slug);

            // Contacts
            if (!empty($repo['contacts'])) {
                foreach ($repo['contacts'] as $contact) {
                    $contactData = (array) $contact;
                    unset($contactData['id']);
                    $contactData['actor_id'] = $objectId;
                    DB::table('contact_information')->insert($contactData);
                }
            }

            $this->idMap['repository'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importAuthorities(array $data, string $mode): void
    {
        foreach ($data as $actor) {
            $oldId = (int) $actor['id'];

            // Match by slug
            $existing = null;
            if (!empty($actor['slug'])) {
                $slugRow = DB::table('slug')->where('slug', $actor['slug'])->first();
                if ($slugRow) {
                    $existing = DB::table('actor')->where('id', $slugRow->object_id)->first();
                }
            }

            if ($existing && $mode === 'merge') {
                $this->idMap['actor'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitActor',
                'created_at' => $actor['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $actor['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('actor')->insert([
                'id' => $objectId,
                'entity_type_id' => $this->remapId($actor['entity_type_id'] ?? null, 'term'),
                'description_identifier' => $actor['description_identifier'] ?? null,
                'corporate_body_identifiers' => $actor['corporate_body_identifiers'] ?? null,
                'parent_id' => \QubitActor::ROOT_ID ?? 3,
                'source_culture' => $this->culture,
            ]);

            DB::table('actor_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'authorized_form_of_name' => $actor['authorized_form_of_name'] ?? '',
                'dates_of_existence' => $actor['dates_of_existence'] ?? null,
                'history' => $actor['history'] ?? null,
                'places' => $actor['places'] ?? null,
                'legal_status' => $actor['legal_status'] ?? null,
                'functions' => $actor['functions'] ?? null,
                'mandates' => $actor['mandates'] ?? null,
                'internal_structures' => $actor['internal_structures'] ?? null,
                'general_context' => $actor['general_context'] ?? null,
            ]);

            // Additional i18n cultures
            if (!empty($actor['i18n'])) {
                foreach ($actor['i18n'] as $i18n) {
                    if (($i18n['culture'] ?? '') === $this->culture) {
                        continue; // Already inserted
                    }
                    $i18n['id'] = $objectId;
                    DB::table('actor_i18n')->insert($i18n);
                }
            }

            $slug = $actor['slug'] ?? $this->generateSlug($actor['authorized_form_of_name'] ?? 'actor');
            $this->insertSlug($objectId, $slug);

            $this->idMap['actor'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importUsers(array $data, string $mode): void
    {
        if ($mode === 'dry_run') {
            $this->stats['imported'] += count($data['users'] ?? []);

            return;
        }

        // Import ACL groups
        foreach ($data['groups'] ?? [] as $group) {
            $oldId = (int) $group['id'];

            // Skip root group (100) and admin group (99)
            if ($oldId <= 100) {
                $this->idMap['acl_group'][$oldId] = $oldId;

                continue;
            }

            $existing = DB::table('acl_group_i18n')
                ->where('name', $group['name'] ?? '')
                ->where('culture', $this->culture)
                ->first();

            if ($existing && $mode === 'merge') {
                $this->idMap['acl_group'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            $newId = DB::table('acl_group')->insertGetId([
                'parent_id' => $group['parent_id'] ?? null,
                'created_at' => $group['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $group['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('acl_group_i18n')->insert([
                'id' => $newId,
                'culture' => $this->culture,
                'name' => $group['name'] ?? '',
                'description' => $group['description'] ?? null,
            ]);

            $this->idMap['acl_group'][$oldId] = $newId;
            $this->stats['imported']++;
        }

        // Import users (without passwords)
        foreach ($data['users'] ?? [] as $user) {
            $oldId = (int) $user['id'];

            $existing = DB::table('user')
                ->where('email', $user['email'] ?? '')
                ->first();

            if ($existing && $mode === 'merge') {
                $this->idMap['user'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            // User extends Actor — create object → actor → user
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitUser',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('actor')->insert([
                'id' => $objectId,
                'parent_id' => \QubitActor::ROOT_ID ?? 3,
                'source_culture' => $this->culture,
            ]);

            DB::table('actor_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'authorized_form_of_name' => $user['display_name'] ?? $user['username'] ?? '',
            ]);

            // Insert user with a random temporary password (must be reset)
            $tempPassword = bin2hex(random_bytes(16));
            $salt = bin2hex(random_bytes(32));
            DB::table('user')->insert([
                'id' => $objectId,
                'username' => $user['username'] ?? $user['email'],
                'email' => $user['email'] ?? null,
                'password_hash' => sha1($salt . $tempPassword),
                'salt' => $salt,
                'active' => $user['active'] ?? 1,
            ]);

            $slug = $user['slug'] ?? $this->generateSlug($user['username'] ?? 'user');
            $this->insertSlug($objectId, $slug);

            $this->idMap['user'][$oldId] = $objectId;
            $this->stats['imported']++;
        }

        // Import user-group memberships
        foreach ($data['user_groups'] ?? [] as $ug) {
            $newUserId = $this->remapId($ug['user_id'] ?? null, 'user');
            $newGroupId = $this->remapId($ug['group_id'] ?? null, 'acl_group');

            if (!$newUserId || !$newGroupId) {
                continue;
            }

            if ($mode !== 'dry_run') {
                $exists = DB::table('acl_user_group')
                    ->where('user_id', $newUserId)
                    ->where('group_id', $newGroupId)
                    ->exists();

                if (!$exists) {
                    DB::table('acl_user_group')->insert([
                        'user_id' => $newUserId,
                        'group_id' => $newGroupId,
                    ]);
                }
            }
        }
    }

    protected function importDescriptions(array $data, string $mode): void
    {
        // Sort by lft to maintain hierarchy order
        usort($data, function ($a, $b) {
            return ($a['lft'] ?? 0) <=> ($b['lft'] ?? 0);
        });

        foreach ($data as $desc) {
            $oldId = (int) $desc['id'];

            // Match by slug
            $existing = null;
            if (!empty($desc['slug'])) {
                $slugRow = DB::table('slug')->where('slug', $desc['slug'])->first();
                if ($slugRow) {
                    $existing = DB::table('information_object')->where('id', $slugRow->object_id)->first();
                }
            }

            if ($existing && $mode === 'merge') {
                $this->idMap['information_object'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => $desc['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $desc['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            // Remap parent_id and repository_id
            $parentId = $this->remapId($desc['parent_id'] ?? null, 'information_object');
            $repositoryId = $this->remapId($desc['repository_id'] ?? null, 'repository');

            DB::table('information_object')->insert([
                'id' => $objectId,
                'identifier' => $desc['identifier'] ?? null,
                'parent_id' => $parentId ?: (\QubitInformationObject::ROOT_ID ?? 1),
                'level_of_description_id' => $this->remapId($desc['level_of_description_id'] ?? null, 'term'),
                'repository_id' => $repositoryId,
                'source_culture' => $desc['source_culture'] ?? $this->culture,
                'description_status_id' => $this->remapId($desc['description_status_id'] ?? null, 'term'),
                'level_of_detail_id' => $this->remapId($desc['level_of_detail_id'] ?? null, 'term'),
            ]);

            DB::table('information_object_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'title' => $desc['title'] ?? null,
                'scope_and_content' => $desc['scope_and_content'] ?? null,
                'extent_and_medium' => $desc['extent_and_medium'] ?? null,
                'archival_history' => $desc['archival_history'] ?? null,
                'acquisition' => $desc['acquisition'] ?? null,
                'arrangement' => $desc['arrangement'] ?? null,
                'access_conditions' => $desc['access_conditions'] ?? null,
                'reproduction_conditions' => $desc['reproduction_conditions'] ?? null,
                'physical_characteristics' => $desc['physical_characteristics'] ?? null,
                'finding_aids' => $desc['finding_aids'] ?? null,
                'location_of_originals' => $desc['location_of_originals'] ?? null,
                'location_of_copies' => $desc['location_of_copies'] ?? null,
                'related_units_of_description' => $desc['related_units_of_description'] ?? null,
                'rules' => $desc['rules'] ?? null,
                'revision_history' => $desc['revision_history'] ?? null,
                'sources' => $desc['sources'] ?? null,
            ]);

            // Additional i18n cultures
            if (!empty($desc['i18n'])) {
                foreach ($desc['i18n'] as $i18n) {
                    if (($i18n['culture'] ?? '') === $this->culture) {
                        continue;
                    }
                    $i18n['id'] = $objectId;
                    DB::table('information_object_i18n')->insert($i18n);
                }
            }

            // Properties
            if (!empty($desc['properties'])) {
                foreach ($desc['properties'] as $prop) {
                    $propObjId = DB::table('object')->insertGetId([
                        'class_name' => 'QubitProperty',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    DB::table('property')->insert([
                        'id' => $propObjId,
                        'object_id' => $objectId,
                        'name' => $prop['name'] ?? null,
                        'scope' => $prop['scope'] ?? null,
                        'source_culture' => $this->culture,
                    ]);

                    if (isset($prop['value'])) {
                        DB::table('property_i18n')->insert([
                            'id' => $propObjId,
                            'culture' => $this->culture,
                            'value' => $prop['value'],
                        ]);
                    }
                }
            }

            $slug = $desc['slug'] ?? $this->generateSlug($desc['title'] ?? $desc['identifier'] ?? 'description');
            $this->insertSlug($objectId, $slug);

            $this->idMap['information_object'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importEvents(array $data, string $mode): void
    {
        foreach ($data as $event) {
            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => $event['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $event['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('event')->insert([
                'id' => $objectId,
                'object_id' => $this->remapId($event['object_id'] ?? null, 'information_object'),
                'actor_id' => $this->remapId($event['actor_id'] ?? null, 'actor'),
                'type_id' => $this->remapId($event['type_id'] ?? null, 'term'),
                'start_date' => $event['start_date'] ?? null,
                'end_date' => $event['end_date'] ?? null,
                'source_culture' => $this->culture,
            ]);

            DB::table('event_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'date' => $event['date'] ?? null,
                'description' => $event['description'] ?? null,
                'name' => $event['name'] ?? null,
            ]);

            $oldId = (int) $event['id'];
            $this->idMap['event'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importNotes(array $data, string $mode): void
    {
        foreach ($data as $note) {
            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitNote',
                'created_at' => $note['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $note['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('note')->insert([
                'id' => $objectId,
                'object_id' => $this->remapId($note['object_id'] ?? null, 'information_object'),
                'type_id' => $this->remapId($note['type_id'] ?? null, 'term'),
                'scope' => $note['scope'] ?? null,
                'user_id' => $this->remapId($note['user_id'] ?? null, 'user'),
                'source_culture' => $this->culture,
            ]);

            DB::table('note_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'content' => $note['content'] ?? null,
            ]);

            $this->stats['imported']++;
        }
    }

    protected function importRelations(array $data, string $mode): void
    {
        foreach ($data as $relation) {
            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            // Try to remap subject_id and object_id across all entity types
            $subjectId = $this->remapIdAny($relation['subject_id'] ?? null);
            $objectIdVal = $this->remapIdAny($relation['object_id'] ?? null);

            if (!$subjectId && !$objectIdVal) {
                $this->stats['skipped']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitRelation',
                'created_at' => $relation['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $relation['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('relation')->insert([
                'id' => $objectId,
                'subject_id' => $subjectId ?: ($relation['subject_id'] ?? null),
                'object_id' => $objectIdVal ?: ($relation['object_id'] ?? null),
                'type_id' => $this->remapId($relation['type_id'] ?? null, 'term'),
                'start_date' => $relation['start_date'] ?? null,
                'end_date' => $relation['end_date'] ?? null,
                'source_culture' => $this->culture,
            ]);

            if (!empty($relation['description']) || !empty($relation['date'])) {
                DB::table('relation_i18n')->insert([
                    'id' => $objectId,
                    'culture' => $this->culture,
                    'description' => $relation['description'] ?? null,
                    'date' => $relation['date'] ?? null,
                ]);
            }

            $this->stats['imported']++;
        }
    }

    protected function importRights(array $data, string $mode): void
    {
        foreach ($data as $right) {
            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitRights',
                'created_at' => $right['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $right['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('rights')->insert([
                'id' => $objectId,
                'object_id' => $this->remapId($right['object_id'] ?? null, 'information_object'),
                'basis_id' => $this->remapId($right['basis_id'] ?? null, 'term'),
                'act_id' => $this->remapId($right['act_id'] ?? null, 'term'),
                'start_date' => $right['start_date'] ?? null,
                'end_date' => $right['end_date'] ?? null,
                'rights_holder_id' => $this->remapId($right['rights_holder_id'] ?? null, 'actor'),
                'copyright_status_id' => $this->remapId($right['copyright_status_id'] ?? null, 'term'),
            ]);

            DB::table('rights_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'rights_note' => $right['rights_note'] ?? null,
                'copyright_note' => $right['copyright_note'] ?? null,
                'license_identifier' => $right['license_identifier'] ?? null,
                'license_terms' => $right['license_terms'] ?? null,
                'license_note' => $right['license_note'] ?? null,
                'statute_jurisdiction' => $right['statute_jurisdiction'] ?? null,
                'statute_citation' => $right['statute_citation'] ?? null,
                'statute_determination_date' => $right['statute_determination_date'] ?? null,
                'statute_note' => $right['statute_note'] ?? null,
            ]);

            $this->stats['imported']++;
        }
    }

    protected function importAccessions(array $data, string $mode): void
    {
        foreach ($data as $acc) {
            $oldId = (int) $acc['id'];

            // Match by identifier
            $existing = null;
            if (!empty($acc['identifier'])) {
                $existing = DB::table('accession')
                    ->where('identifier', $acc['identifier'])
                    ->first();
            }

            if ($existing && $mode === 'merge') {
                $this->idMap['accession'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitAccession',
                'created_at' => $acc['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $acc['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('accession')->insert([
                'id' => $objectId,
                'identifier' => $acc['identifier'] ?? null,
                'date' => $acc['date'] ?? null,
                'source_of_acquisition_id' => $this->remapId($acc['source_of_acquisition_id'] ?? null, 'term'),
                'location_information' => $acc['location_information'] ?? null,
                'source_culture' => $this->culture,
            ]);

            DB::table('accession_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'appraisal' => $acc['appraisal'] ?? null,
                'archival_history' => $acc['archival_history'] ?? null,
                'scope_and_content' => $acc['scope_and_content'] ?? null,
                'physical_characteristics' => $acc['physical_characteristics'] ?? null,
                'received_extent_units' => $acc['received_extent_units'] ?? null,
                'processing_notes' => $acc['processing_notes'] ?? null,
                'title' => $acc['title'] ?? null,
            ]);

            $this->idMap['accession'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importPhysicalObjects(array $data, string $mode): void
    {
        foreach ($data as $po) {
            $oldId = (int) $po['id'];

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitPhysicalObject',
                'created_at' => $po['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $po['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            DB::table('physical_object')->insert([
                'id' => $objectId,
                'type_id' => $this->remapId($po['type_id'] ?? null, 'term'),
                'source_culture' => $this->culture,
            ]);

            DB::table('physical_object_i18n')->insert([
                'id' => $objectId,
                'culture' => $this->culture,
                'name' => $po['name'] ?? null,
                'description' => $po['description'] ?? null,
                'location' => $po['location'] ?? null,
            ]);

            $this->idMap['physical_object'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importObjectTermRelations(array $data, string $mode): void
    {
        foreach ($data as $otr) {
            $newObjectId = $this->remapId($otr['object_id'] ?? null, 'information_object');
            $newTermId = $this->remapId($otr['term_id'] ?? null, 'term');

            if (!$newObjectId || !$newTermId) {
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            // Check if relation already exists
            $exists = DB::table('object_term_relation')
                ->where('object_id', $newObjectId)
                ->where('term_id', $newTermId)
                ->exists();

            if ($exists) {
                $this->stats['skipped']++;

                continue;
            }

            DB::table('object_term_relation')->insert([
                'object_id' => $newObjectId,
                'term_id' => $newTermId,
            ]);

            $this->stats['imported']++;
        }
    }

    protected function importDigitalObjects(array $data, string $mode, string $archiveDir): void
    {
        foreach ($data as $do) {
            $oldId = (int) $do['id'];
            $newObjectId = $this->remapId($do['object_id'] ?? null, 'information_object');

            if (!$newObjectId) {
                $this->stats['skipped']++;

                continue;
            }

            if ($mode === 'dry_run') {
                $this->stats['imported']++;

                continue;
            }

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitDigitalObject',
                'created_at' => $do['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $do['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            // Copy asset files from archive to uploads
            $path = $do['path'] ?? '';
            $name = $do['name'] ?? '';
            if ($path && $name) {
                $this->copyAssetFile($archiveDir, $path, $name, $objectId);
            }

            DB::table('digital_object')->insert([
                'id' => $objectId,
                'object_id' => $newObjectId,
                'usage_id' => $this->remapId($do['usage_id'] ?? null, 'term') ?: ($do['usage_id'] ?? null),
                'name' => $name,
                'path' => $path,
                'mime_type' => $do['mime_type'] ?? null,
                'byte_size' => $do['byte_size'] ?? null,
                'checksum' => $do['checksum'] ?? null,
                'checksum_type' => $do['checksum_type'] ?? null,
                'parent_id' => $this->remapId($do['parent_id'] ?? null, 'digital_object'),
                'sequence' => $do['sequence'] ?? null,
            ]);

            $this->idMap['digital_object'][$oldId] = $objectId;
            $this->stats['imported']++;
        }
    }

    protected function importSettings(array $data, string $mode): void
    {
        if ($mode === 'dry_run') {
            $this->stats['imported'] += count($data['ahg_settings'] ?? []);

            return;
        }

        // Only import ahg_settings in merge mode (merge = INSERT IGNORE pattern)
        foreach ($data['ahg_settings'] ?? [] as $setting) {
            $exists = DB::table('ahg_settings')
                ->where('setting_key', $setting['setting_key'] ?? '')
                ->exists();

            if ($exists && $mode === 'merge') {
                $this->stats['skipped']++;

                continue;
            }

            if ($exists && $mode === 'replace') {
                DB::table('ahg_settings')
                    ->where('setting_key', $setting['setting_key'])
                    ->update([
                        'setting_value' => $setting['setting_value'] ?? '',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } else {
                DB::table('ahg_settings')->insert([
                    'setting_key' => $setting['setting_key'] ?? '',
                    'setting_value' => $setting['setting_value'] ?? '',
                    'setting_group' => $setting['setting_group'] ?? 'general',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->stats['imported']++;
        }
    }

    protected function importMenus(array $data, string $mode): void
    {
        // Menus are complex (MPTT hierarchy) — only import in replace mode
        // In merge mode, skip entirely to avoid breaking the navigation tree
        if ($mode === 'merge') {
            $this->stats['skipped'] += count($data['menus'] ?? []);

            return;
        }

        if ($mode === 'dry_run') {
            $this->stats['imported'] += count($data['menus'] ?? []);

            return;
        }

        // Replace mode: clear and re-insert (dangerous but requested)
        // This is handled at a higher level with confirmation
        foreach ($data['menus'] ?? [] as $menu) {
            $oldId = (int) $menu['id'];
            $existing = DB::table('menu')->where('name', $menu['name'] ?? '')->first();

            if ($existing) {
                $this->idMap['menu'][$oldId] = (int) $existing->id;
                $this->stats['skipped']++;

                continue;
            }

            $newId = DB::table('menu')->insertGetId([
                'parent_id' => $this->remapId($menu['parent_id'] ?? null, 'menu'),
                'name' => $menu['name'] ?? null,
                'path' => $menu['path'] ?? null,
                'lft' => $menu['lft'] ?? 0,
                'rgt' => $menu['rgt'] ?? 0,
                'source_culture' => $menu['source_culture'] ?? $this->culture,
                'created_at' => $menu['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $menu['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);

            $this->idMap['menu'][$oldId] = $newId;
            $this->stats['imported']++;
        }

        // Menu i18n
        foreach ($data['menu_i18n'] ?? [] as $mi18n) {
            $newMenuId = $this->remapId($mi18n['id'] ?? null, 'menu');
            if (!$newMenuId) {
                continue;
            }

            $exists = DB::table('menu_i18n')
                ->where('id', $newMenuId)
                ->where('culture', $mi18n['culture'] ?? $this->culture)
                ->exists();

            if (!$exists) {
                DB::table('menu_i18n')->insert([
                    'id' => $newMenuId,
                    'culture' => $mi18n['culture'] ?? $this->culture,
                    'label' => $mi18n['label'] ?? null,
                    'description' => $mi18n['description'] ?? null,
                ]);
            }
        }
    }

    // ─── Asset Copying ──────────────────────────────────────────────

    /**
     * Copy a digital object file from the archive to the AtoM uploads directory.
     */
    protected function copyAssetFile(string $archiveDir, string $path, string $name, int $newObjectId): void
    {
        $atomRoot = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive');

        // Try to find the file in the archive's objects/ directory
        $sourceBase = $archiveDir . '/objects';
        if (!is_dir($sourceBase)) {
            return;
        }

        // The asset collector stores files by path structure matching AtoM's uploads/r/
        $sourcePath = $sourceBase . '/' . ltrim($path, '/') . $name;
        if (!file_exists($sourcePath)) {
            // Try alternate location (flat structure)
            $sourcePath = $sourceBase . '/' . $name;
        }

        if (!file_exists($sourcePath)) {
            return;
        }

        $destDir = $atomRoot . '/uploads/r/' . ltrim($path, '/');
        @mkdir($destDir, 0755, true);
        @copy($sourcePath, $destDir . $name);
    }

    // ─── ID Remapping Helpers ───────────────────────────────────────

    /**
     * Remap an old source ID to the new target ID for a specific entity type.
     */
    protected function remapId(?int $oldId, string $entityType): ?int
    {
        if ($oldId === null) {
            return null;
        }

        return $this->idMap[$entityType][$oldId] ?? $oldId;
    }

    /**
     * Try to remap an ID across all entity types (for polymorphic references).
     */
    protected function remapIdAny(?int $oldId): ?int
    {
        if ($oldId === null) {
            return null;
        }

        foreach ($this->idMap as $entityMap) {
            if (isset($entityMap[$oldId])) {
                return $entityMap[$oldId];
            }
        }

        return $oldId;
    }

    // ─── Slug + Progress Helpers ────────────────────────────────────

    /**
     * Generate a URL-safe slug from a title.
     */
    protected function generateSlug(string $title): string
    {
        $slug = mb_strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 240) ?: 'item';

        // Ensure uniqueness
        $base = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Insert a slug record, handling duplicates.
     */
    protected function insertSlug(int $objectId, string $slug): void
    {
        // Ensure uniqueness
        $base = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);
    }

    /**
     * Update import progress in the database.
     */
    protected function updateImportProgress(int $importId, int $processed, int $total): void
    {
        $progress = $total > 0 ? (int) (($processed / $total) * 95) : 0;

        DB::table('portable_import')->where('id', $importId)->update([
            'progress' => min($progress, 95),
            'imported_entities' => $this->stats['imported'],
            'skipped_entities' => $this->stats['skipped'],
            'error_count' => $this->stats['errors'],
        ]);
    }

    /**
     * Log an error.
     */
    protected function logError(string $message): void
    {
        $this->errorLog[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        $this->stats['errors']++;
    }

    // ─── Public Getters ─────────────────────────────────────────────

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getIdMap(): array
    {
        return $this->idMap;
    }

    public function getErrorLog(): array
    {
        return $this->errorLog;
    }
}
