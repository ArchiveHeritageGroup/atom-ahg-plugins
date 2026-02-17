<?php

namespace ahgDoiPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * DoiService - Core service for DOI minting and management via DataCite.
 *
 * Handles:
 * - DOI minting (draft, registered, findable states)
 * - Metadata mapping from AtoM to DataCite schema
 * - DataCite API communication
 * - Queue processing for batch operations
 */
class DoiService
{
    private ?object $config = null;

    // =========================================
    // CONFIGURATION
    // =========================================

    /**
     * Get configuration for a repository.
     *
     * @param int|null $repositoryId Repository ID
     *
     * @return object|null
     */
    public function getConfig(?int $repositoryId = null): ?object
    {
        // First try repository-specific config
        if ($repositoryId) {
            $config = DB::table('ahg_doi_config')
                ->where('repository_id', $repositoryId)
                ->where('is_active', 1)
                ->first();

            if ($config) {
                return $config;
            }
        }

        // Fall back to global config
        return DB::table('ahg_doi_config')
            ->whereNull('repository_id')
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Save configuration.
     *
     * @param array    $data         Configuration data
     * @param int|null $repositoryId Repository ID
     *
     * @return int Config ID
     */
    public function saveConfig(array $data, ?int $repositoryId = null): int
    {
        $existing = DB::table('ahg_doi_config')
            ->where('repository_id', $repositoryId)
            ->first();

        $configData = [
            'datacite_repo_id' => $data['datacite_repo_id'],
            'datacite_prefix' => $data['datacite_prefix'],
            'datacite_password' => $data['datacite_password'] ?? null,
            'datacite_url' => $data['datacite_url'] ?? 'https://api.datacite.org',
            'environment' => $data['environment'] ?? 'test',
            'auto_mint' => $data['auto_mint'] ?? false,
            'auto_mint_levels' => json_encode($data['auto_mint_levels'] ?? []),
            'require_digital_object' => $data['require_digital_object'] ?? false,
            'default_publisher' => $data['default_publisher'] ?? null,
            'default_resource_type' => $data['default_resource_type'] ?? 'Text',
            'suffix_pattern' => $data['suffix_pattern'] ?? '{repository_code}/{year}/{object_id}',
            'is_active' => $data['is_active'] ?? true,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            DB::table('ahg_doi_config')
                ->where('id', $existing->id)
                ->update($configData);

            return $existing->id;
        }

        $configData['repository_id'] = $repositoryId;
        $configData['created_at'] = date('Y-m-d H:i:s');

        return DB::table('ahg_doi_config')->insertGetId($configData);
    }

    /**
     * Test DataCite connection.
     *
     * @param int|null $repositoryId Repository ID
     *
     * @return array Test result
     */
    public function testConnection(?int $repositoryId = null): array
    {
        $config = $this->getConfig($repositoryId);
        if (!$config) {
            return ['success' => false, 'message' => 'No configuration found'];
        }

        try {
            $url = rtrim($config->datacite_url, '/') . '/dois?page[size]=1';

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/vnd.api+json',
                ],
                CURLOPT_USERPWD => $config->datacite_repo_id . ':' . $config->datacite_password,
                CURLOPT_TIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return ['success' => true, 'message' => 'Connection successful'];
            }

            return ['success' => false, 'message' => "HTTP {$httpCode}: " . substr($response, 0, 200)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================
    // DOI MINTING
    // =========================================

    /**
     * Mint a DOI for an information object.
     *
     * @param int    $objectId Information object ID
     * @param string $state    Initial state: draft, registered, or findable
     *
     * @return array Result with DOI or error
     */
    public function mintDoi(int $objectId, string $state = 'findable'): array
    {
        // Check if DOI already exists
        $existing = DB::table('ahg_doi')
            ->where('information_object_id', $objectId)
            ->first();

        if ($existing) {
            return ['success' => false, 'error' => 'DOI already exists', 'doi' => $existing->doi];
        }

        // Get record details
        $record = $this->getRecordDetails($objectId);
        if (!$record) {
            return ['success' => false, 'error' => 'Record not found'];
        }

        // Get config
        $config = $this->getConfig($record->repository_id);
        if (!$config) {
            return ['success' => false, 'error' => 'No DOI configuration for this repository'];
        }

        // Generate DOI suffix
        $suffix = $this->generateDoiSuffix($record, $config);
        $doi = $config->datacite_prefix . '/' . $suffix;

        // Build DataCite metadata
        $metadata = $this->buildDataCiteMetadata($record, $doi, $config);

        // Send to DataCite
        $result = $this->sendToDataCite($doi, $metadata, $state, $config);

        if (!$result['success']) {
            $this->logDoiAction(null, $objectId, 'failed', null, null, [
                'error' => $result['error'],
                'doi' => $doi,
            ]);

            return $result;
        }

        // Save DOI record
        $doiId = DB::table('ahg_doi')->insertGetId([
            'information_object_id' => $objectId,
            'doi' => $doi,
            'status' => $state,
            'minted_at' => date('Y-m-d H:i:s'),
            'minted_by' => $this->getCurrentUserId(),
            'datacite_response' => json_encode($result['response']),
            'metadata_json' => json_encode($metadata),
            'last_sync_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logDoiAction($doiId, $objectId, 'minted', null, $state, [
            'doi' => $doi,
        ]);

        return [
            'success' => true,
            'doi' => $doi,
            'doi_id' => $doiId,
            'status' => $state,
            'url' => 'https://doi.org/' . $doi,
        ];
    }

    /**
     * Update DOI metadata at DataCite.
     *
     * @param int $doiId DOI record ID
     *
     * @return array Result
     */
    public function updateDoi(int $doiId): array
    {
        $doiRecord = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doiRecord) {
            return ['success' => false, 'error' => 'DOI record not found'];
        }

        $record = $this->getRecordDetails($doiRecord->information_object_id);
        if (!$record) {
            return ['success' => false, 'error' => 'Information object not found'];
        }

        $config = $this->getConfig($record->repository_id);
        if (!$config) {
            return ['success' => false, 'error' => 'No configuration found'];
        }

        $metadata = $this->buildDataCiteMetadata($record, $doiRecord->doi, $config);
        $result = $this->sendToDataCite($doiRecord->doi, $metadata, $doiRecord->status, $config, 'PUT');

        if (!$result['success']) {
            return $result;
        }

        DB::table('ahg_doi')
            ->where('id', $doiId)
            ->update([
                'datacite_response' => json_encode($result['response']),
                'metadata_json' => json_encode($metadata),
                'last_sync_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logDoiAction($doiId, $doiRecord->information_object_id, 'updated', $doiRecord->status, $doiRecord->status);

        return ['success' => true, 'doi' => $doiRecord->doi];
    }

    /**
     * Verify a DOI resolves correctly.
     *
     * @param int $doiId DOI record ID
     *
     * @return array Result
     */
    public function verifyDoi(int $doiId): array
    {
        $doiRecord = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doiRecord) {
            return ['success' => false, 'error' => 'DOI record not found'];
        }

        $url = 'https://doi.org/' . $doiRecord->doi;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $resolves = in_array($httpCode, [200, 301, 302]);

        $this->logDoiAction($doiId, $doiRecord->information_object_id, 'verified', null, null, [
            'resolves' => $resolves,
            'http_code' => $httpCode,
            'final_url' => $finalUrl,
        ]);

        return [
            'success' => true,
            'resolves' => $resolves,
            'http_code' => $httpCode,
            'final_url' => $finalUrl,
        ];
    }

    // =========================================
    // QUEUE MANAGEMENT
    // =========================================

    /**
     * Queue a record for DOI minting.
     *
     * @param int    $objectId Information object ID
     * @param string $action   Action: mint, update, delete, verify
     * @param int    $priority Priority (higher = first)
     *
     * @return int Queue ID
     */
    public function queueForMinting(int $objectId, string $action = 'mint', int $priority = 100): int
    {
        // Check if already queued
        $existing = DB::table('ahg_doi_queue')
            ->where('information_object_id', $objectId)
            ->where('action', $action)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('ahg_doi_queue')->insertGetId([
            'information_object_id' => $objectId,
            'action' => $action,
            'status' => 'pending',
            'priority' => $priority,
            'scheduled_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Process the queue.
     *
     * @param int $limit Max items to process
     *
     * @return array Processing results
     */
    public function processQueue(int $limit = 10): array
    {
        $results = ['processed' => 0, 'success' => 0, 'failed' => 0, 'errors' => []];

        $items = DB::table('ahg_doi_queue')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', date('Y-m-d H:i:s'))
            ->where('attempts', '<', DB::raw('max_attempts'))
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            $results['processed']++;

            // Mark as processing
            DB::table('ahg_doi_queue')
                ->where('id', $item->id)
                ->update([
                    'status' => 'processing',
                    'started_at' => date('Y-m-d H:i:s'),
                    'attempts' => $item->attempts + 1,
                ]);

            try {
                $result = $this->processQueueItem($item);

                if ($result['success']) {
                    $results['success']++;
                    DB::table('ahg_doi_queue')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'completed',
                            'completed_at' => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Object {$item->information_object_id}: " . $result['error'];

                    $newStatus = ($item->attempts + 1 >= $item->max_attempts) ? 'failed' : 'pending';
                    DB::table('ahg_doi_queue')
                        ->where('id', $item->id)
                        ->update([
                            'status' => $newStatus,
                            'last_error' => $result['error'],
                        ]);
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Object {$item->information_object_id}: " . $e->getMessage();

                DB::table('ahg_doi_queue')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'failed',
                        'last_error' => $e->getMessage(),
                    ]);
            }
        }

        return $results;
    }

    /**
     * Process a single queue item.
     *
     * @param object $item Queue item
     *
     * @return array Result
     */
    protected function processQueueItem(object $item): array
    {
        switch ($item->action) {
            case 'mint':
                return $this->mintDoi($item->information_object_id);

            case 'update':
                $doi = DB::table('ahg_doi')
                    ->where('information_object_id', $item->information_object_id)
                    ->first();
                if (!$doi) {
                    return ['success' => false, 'error' => 'No DOI exists for this record'];
                }

                return $this->updateDoi($doi->id);

            case 'verify':
                $doi = DB::table('ahg_doi')
                    ->where('information_object_id', $item->information_object_id)
                    ->first();
                if (!$doi) {
                    return ['success' => false, 'error' => 'No DOI exists for this record'];
                }

                return $this->verifyDoi($doi->id);

            default:
                return ['success' => false, 'error' => "Unknown action: {$item->action}"];
        }
    }

    // =========================================
    // DATACITE API
    // =========================================

    /**
     * Send metadata to DataCite.
     *
     * @param string $doi      DOI string
     * @param array  $metadata DataCite metadata
     * @param string $state    DOI state
     * @param object $config   Configuration
     * @param string $method   HTTP method
     *
     * @return array Result
     */
    protected function sendToDataCite(string $doi, array $metadata, string $state, object $config, string $method = 'POST'): array
    {
        $url = rtrim($config->datacite_url, '/') . '/dois';
        if ($method === 'PUT') {
            $url .= '/' . urlencode($doi);
        }

        // Build landing page URL
        $landingUrl = $this->buildLandingUrl($metadata['informationObjectId'] ?? null);

        $payload = [
            'data' => [
                'type' => 'dois',
                'attributes' => [
                    'doi' => $doi,
                    'event' => $this->stateToEvent($state),
                    'url' => $landingUrl,
                    'creators' => $metadata['creators'] ?? [['name' => 'Unknown']],
                    'titles' => $metadata['titles'] ?? [['title' => 'Untitled']],
                    'publisher' => $metadata['publisher'] ?? $config->default_publisher ?? 'Archive',
                    'publicationYear' => $metadata['publicationYear'] ?? date('Y'),
                    'types' => [
                        'resourceTypeGeneral' => $config->default_resource_type ?? 'Text',
                    ],
                ],
            ],
        ];

        // Add optional fields
        if (!empty($metadata['subjects'])) {
            $payload['data']['attributes']['subjects'] = $metadata['subjects'];
        }
        if (!empty($metadata['descriptions'])) {
            $payload['data']['attributes']['descriptions'] = $metadata['descriptions'];
        }
        if (!empty($metadata['dates'])) {
            $payload['data']['attributes']['dates'] = $metadata['dates'];
        }
        if (!empty($metadata['language'])) {
            $payload['data']['attributes']['language'] = $metadata['language'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/vnd.api+json',
            ],
            CURLOPT_USERPWD => $config->datacite_repo_id . ':' . $config->datacite_password,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL error: ' . $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'response' => $responseData];
        }

        $errorMsg = $responseData['errors'][0]['title'] ?? "HTTP {$httpCode}";

        return ['success' => false, 'error' => $errorMsg, 'response' => $responseData];
    }

    /**
     * Convert state to DataCite event.
     *
     * @param string $state State
     *
     * @return string Event
     */
    protected function stateToEvent(string $state): string
    {
        $events = [
            'draft' => 'draft',
            'registered' => 'register',
            'findable' => 'publish',
        ];

        return $events[$state] ?? 'publish';
    }

    // =========================================
    // METADATA MAPPING
    // =========================================

    /**
     * Build DataCite metadata from AtoM record.
     *
     * @param object $record AtoM record
     * @param string $doi    DOI string
     * @param object $config Configuration
     *
     * @return array DataCite metadata
     */
    protected function buildDataCiteMetadata(object $record, string $doi, object $config): array
    {
        $metadata = [
            'informationObjectId' => $record->id,
        ];

        // Get custom mappings
        $mappings = DB::table('ahg_doi_mapping')
            ->where(function ($q) use ($record) {
                $q->whereNull('repository_id')
                    ->orWhere('repository_id', $record->repository_id);
            })
            ->orderBy('sort_order')
            ->get();

        foreach ($mappings as $mapping) {
            $value = $this->getMappedValue($record, $mapping);

            if ($value !== null) {
                $metadata[$mapping->datacite_field] = $value;
            } elseif ($mapping->fallback_value) {
                $metadata[$mapping->datacite_field] = $this->formatForDataCite($mapping->datacite_field, $mapping->fallback_value);
            }
        }

        // Ensure required fields
        if (empty($metadata['creators'])) {
            $metadata['creators'] = [['name' => 'Unknown']];
        }
        if (empty($metadata['titles'])) {
            $metadata['titles'] = [['title' => $record->title ?? 'Untitled']];
        }
        if (empty($metadata['publisher'])) {
            $metadata['publisher'] = $config->default_publisher ?? 'Archive';
        }
        if (empty($metadata['publicationYear'])) {
            $year = $this->extractYear($record->dates ?? '');
            $metadata['publicationYear'] = $year ?: date('Y');
        }

        return $metadata;
    }

    /**
     * Get mapped value from record.
     *
     * @param object $record  AtoM record
     * @param object $mapping Mapping definition
     *
     * @return mixed
     */
    protected function getMappedValue(object $record, object $mapping)
    {
        $value = null;

        switch ($mapping->source_type) {
            case 'field':
                $fieldName = $mapping->source_value;
                $value = $record->$fieldName ?? null;
                break;

            case 'constant':
                $value = $mapping->source_value;
                break;

            case 'template':
                $value = $this->parseTemplate($mapping->source_value, $record);
                break;

            case 'property':
                // Get from property table
                $value = DB::table('property')
                    ->join('property_i18n', 'property.id', '=', 'property_i18n.id')
                    ->where('property.object_id', $record->id)
                    ->where('property.type_id', $mapping->source_value)
                    ->value('property_i18n.value');
                break;
        }

        if ($value === null || $value === '') {
            return null;
        }

        // Apply transformation if specified
        if ($mapping->transformation) {
            $value = $this->applyTransformation($value, $mapping->transformation);
        }

        return $this->formatForDataCite($mapping->datacite_field, $value);
    }

    /**
     * Format value for DataCite schema.
     *
     * @param string $field DataCite field name
     * @param mixed  $value Value
     *
     * @return mixed Formatted value
     */
    protected function formatForDataCite(string $field, $value)
    {
        switch ($field) {
            case 'creators':
            case 'contributors':
                if (is_string($value)) {
                    return [['name' => $value]];
                }

                return $value;

            case 'titles':
                if (is_string($value)) {
                    return [['title' => $value]];
                }

                return $value;

            case 'subjects':
                if (is_string($value)) {
                    return array_map(function ($s) {
                        return ['subject' => trim($s)];
                    }, explode(';', $value));
                }

                return $value;

            case 'descriptions':
                if (is_string($value)) {
                    return [['description' => $value, 'descriptionType' => 'Abstract']];
                }

                return $value;

            case 'dates':
                if (is_string($value)) {
                    return [['date' => $value, 'dateType' => 'Created']];
                }

                return $value;

            default:
                return $value;
        }
    }

    // =========================================
    // HELPERS
    // =========================================

    /**
     * Generate DOI suffix based on pattern.
     *
     * @param object $record AtoM record
     * @param object $config Configuration
     *
     * @return string DOI suffix
     */
    protected function generateDoiSuffix(object $record, object $config): string
    {
        $pattern = $config->suffix_pattern ?? '{repository_code}/{year}/{object_id}';

        $replacements = [
            '{repository_code}' => $record->repository_code ?? 'REPO',
            '{year}' => date('Y'),
            '{month}' => date('m'),
            '{object_id}' => $record->id,
            '{slug}' => $record->slug ?? $record->id,
            '{identifier}' => $record->identifier ?? $record->id,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Get record details for DOI minting.
     *
     * @param int $objectId Information object ID
     *
     * @return object|null
     */
    protected function getRecordDetails(int $objectId): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('repository as r', 'io.repository_id', '=', 'r.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', $objectId)
            ->select([
                'io.*',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'ioi.access_conditions',
                'slug.slug',
                'r.identifier as repository_code',
                'ai.authorized_form_of_name as repository_name',
            ])
            ->first();
    }

    /**
     * Build landing page URL for the record.
     *
     * @param int|null $objectId Object ID
     *
     * @return string URL
     */
    protected function buildLandingUrl(?int $objectId): string
    {
        if (!$objectId) {
            return '';
        }

        $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
        $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');

        return $baseUrl . '/index.php/' . ($slug ?? $objectId);
    }

    /**
     * Check if record should be auto-minted.
     *
     * @param object $record Record object
     *
     * @return bool
     */
    public function shouldAutoMint(object $record): bool
    {
        // Get repository ID
        $repositoryId = null;
        if (method_exists($record, 'getRepositoryId')) {
            $repositoryId = $record->getRepositoryId();
        } elseif (isset($record->repository_id)) {
            $repositoryId = $record->repository_id;
        }

        $config = $this->getConfig($repositoryId);
        if (!$config || !$config->auto_mint) {
            return false;
        }

        // Check level of description
        $levels = json_decode($config->auto_mint_levels ?: '[]', true);
        if (!empty($levels)) {
            $levelId = null;
            if (method_exists($record, 'getLevelOfDescriptionId')) {
                $levelId = $record->getLevelOfDescriptionId();
            } elseif (isset($record->level_of_description_id)) {
                $levelId = $record->level_of_description_id;
            }

            if ($levelId) {
                $levelTerm = DB::table('term_i18n')
                    ->where('id', $levelId)
                    ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->value('name');

                if ($levelTerm && !in_array(strtolower($levelTerm), array_map('strtolower', $levels))) {
                    return false;
                }
            }
        }

        // Check digital object requirement
        if ($config->require_digital_object) {
            $objectId = $record->id ?? (method_exists($record, 'getId') ? $record->getId() : null);
            if ($objectId) {
                $hasDigitalObject = DB::table('digital_object')
                    ->where('object_id', $objectId)
                    ->exists();
                if (!$hasDigitalObject) {
                    return false;
                }
            }
        }

        // Check if already has DOI
        $objectId = $record->id ?? (method_exists($record, 'getId') ? $record->getId() : null);
        if ($objectId) {
            $hasDoi = DB::table('ahg_doi')
                ->where('information_object_id', $objectId)
                ->exists();
            if ($hasDoi) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract year from date string.
     *
     * @param string $dateString Date string
     *
     * @return string|null
     */
    protected function extractYear(string $dateString): ?string
    {
        if (preg_match('/(\d{4})/', $dateString, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parse template string with record values.
     *
     * @param string $template Template string
     * @param object $record   Record object
     *
     * @return string
     */
    protected function parseTemplate(string $template, object $record): string
    {
        $replacements = [
            '{date_year}' => $this->extractYear($record->dates ?? '') ?? date('Y'),
            '{title}' => $record->title ?? '',
            '{identifier}' => $record->identifier ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Apply transformation to value.
     *
     * @param mixed  $value          Value
     * @param string $transformation Transformation name
     *
     * @return mixed
     */
    protected function applyTransformation($value, string $transformation)
    {
        switch ($transformation) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'strip_html':
                return strip_tags($value);
            case 'truncate':
                return substr($value, 0, 1000);
            default:
                return $value;
        }
    }

    /**
     * Log DOI action.
     *
     * @param int|null    $doiId        DOI record ID
     * @param int         $objectId     Information object ID
     * @param string      $action       Action name
     * @param string|null $statusBefore Status before
     * @param string|null $statusAfter  Status after
     * @param array       $details      Additional details
     */
    protected function logDoiAction(?int $doiId, int $objectId, string $action, ?string $statusBefore, ?string $statusAfter, array $details = []): void
    {
        DB::table('ahg_doi_log')->insert([
            'doi_id' => $doiId,
            'information_object_id' => $objectId,
            'action' => $action,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'details' => json_encode($details),
            'performed_by' => $this->getCurrentUserId(),
            'performed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = DB::table('ahg_doi')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered,
                SUM(CASE WHEN status = 'findable' THEN 1 ELSE 0 END) as findable,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        $queueStats = DB::table('ahg_doi_queue')
            ->selectRaw("
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'by_status' => [
                'draft' => (int) ($stats->draft ?? 0),
                'registered' => (int) ($stats->registered ?? 0),
                'findable' => (int) ($stats->findable ?? 0),
                'failed' => (int) ($stats->failed ?? 0),
            ],
            'queue_pending' => (int) ($queueStats->pending ?? 0),
            'queue_failed' => (int) ($queueStats->failed ?? 0),
        ];
    }

    /**
     * Get current user ID.
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        if (class_exists('sfContext') && \sfContext::hasInstance()) {
            $user = \sfContext::getInstance()->getUser();
            if ($user && method_exists($user, 'getAttribute')) {
                return $user->getAttribute('user_id');
            }
        }

        return null;
    }

    // =========================================
    // BULK OPERATIONS
    // =========================================

    /**
     * Bulk sync all DOI metadata to DataCite.
     *
     * @param array $options Options: status, repository_id, limit
     *
     * @return array Results with counts
     */
    public function bulkSync(array $options = []): array
    {
        $results = [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $query = DB::table('ahg_doi as d')
            ->join('information_object as io', 'd.information_object_id', '=', 'io.id')
            ->whereIn('d.status', ['findable', 'registered', 'draft']);

        // Filter by status
        if (!empty($options['status'])) {
            $query->where('d.status', $options['status']);
        }

        // Filter by repository
        if (!empty($options['repository_id'])) {
            $query->where('io.repository_id', $options['repository_id']);
        }

        // Apply limit
        $limit = $options['limit'] ?? 100;
        $dois = $query->select('d.id')->limit($limit)->get();

        $results['total'] = $dois->count();

        foreach ($dois as $doi) {
            $updateResult = $this->updateDoi($doi->id);

            if ($updateResult['success']) {
                $results['synced']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "DOI #{$doi->id}: " . ($updateResult['error'] ?? 'Unknown error');
            }
        }

        return $results;
    }

    /**
     * Queue all DOIs for sync.
     *
     * @param array $options Filter options
     *
     * @return int Number queued
     */
    public function queueForSync(array $options = []): int
    {
        $query = DB::table('ahg_doi as d')
            ->join('information_object as io', 'd.information_object_id', '=', 'io.id')
            ->whereIn('d.status', ['findable', 'registered', 'draft']);

        // Filter by status
        if (!empty($options['status'])) {
            $query->where('d.status', $options['status']);
        }

        // Filter by repository
        if (!empty($options['repository_id'])) {
            $query->where('io.repository_id', $options['repository_id']);
        }

        $dois = $query->select('d.information_object_id')->get();
        $queued = 0;

        foreach ($dois as $doi) {
            $this->queueForMinting($doi->information_object_id, 'update');
            $queued++;
        }

        return $queued;
    }

    // =========================================
    // DEACTIVATION / TOMBSTONE
    // =========================================

    /**
     * Deactivate a DOI (create tombstone).
     *
     * This sets the DOI to "registered" state (hides from discovery)
     * but maintains the landing page for citation integrity.
     *
     * @param int    $doiId  DOI record ID
     * @param string $reason Reason for deactivation
     *
     * @return array Result
     */
    public function deactivateDoi(int $doiId, string $reason = ''): array
    {
        $doiRecord = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doiRecord) {
            return ['success' => false, 'error' => 'DOI record not found'];
        }

        if ($doiRecord->status === 'deleted') {
            return ['success' => false, 'error' => 'DOI already deactivated'];
        }

        $record = $this->getRecordDetails($doiRecord->information_object_id);
        $config = $record ? $this->getConfig($record->repository_id) : $this->getConfig();

        if (!$config) {
            return ['success' => false, 'error' => 'No configuration found'];
        }

        $previousStatus = $doiRecord->status;

        // Send hide event to DataCite (changes to registered state)
        $result = $this->sendStateChange($doiRecord->doi, 'hide', $config);

        if (!$result['success']) {
            $this->logDoiAction($doiId, $doiRecord->information_object_id, 'deactivate_failed', $previousStatus, null, [
                'error' => $result['error'],
                'reason' => $reason,
            ]);

            return $result;
        }

        // Update local record
        DB::table('ahg_doi')
            ->where('id', $doiId)
            ->update([
                'status' => 'deleted',
                'deactivated_at' => date('Y-m-d H:i:s'),
                'deactivation_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logDoiAction($doiId, $doiRecord->information_object_id, 'deactivated', $previousStatus, 'deleted', [
            'reason' => $reason,
            'datacite_response' => $result['response'] ?? null,
        ]);

        return [
            'success' => true,
            'doi' => $doiRecord->doi,
            'previous_status' => $previousStatus,
        ];
    }

    /**
     * Reactivate a deactivated DOI.
     *
     * @param int $doiId DOI record ID
     *
     * @return array Result
     */
    public function reactivateDoi(int $doiId): array
    {
        $doiRecord = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doiRecord) {
            return ['success' => false, 'error' => 'DOI record not found'];
        }

        if ($doiRecord->status !== 'deleted') {
            return ['success' => false, 'error' => 'DOI is not deactivated'];
        }

        $record = $this->getRecordDetails($doiRecord->information_object_id);
        $config = $record ? $this->getConfig($record->repository_id) : $this->getConfig();

        if (!$config) {
            return ['success' => false, 'error' => 'No configuration found'];
        }

        // Send publish event to DataCite (changes back to findable)
        $result = $this->sendStateChange($doiRecord->doi, 'publish', $config);

        if (!$result['success']) {
            return $result;
        }

        // Update local record
        DB::table('ahg_doi')
            ->where('id', $doiId)
            ->update([
                'status' => 'findable',
                'deactivated_at' => null,
                'deactivation_reason' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logDoiAction($doiId, $doiRecord->information_object_id, 'reactivated', 'deleted', 'findable');

        return [
            'success' => true,
            'doi' => $doiRecord->doi,
        ];
    }

    /**
     * Send state change event to DataCite.
     *
     * @param string $doi    DOI string
     * @param string $event  Event: publish, register, hide
     * @param object $config Configuration
     *
     * @return array Result
     */
    protected function sendStateChange(string $doi, string $event, object $config): array
    {
        $url = rtrim($config->datacite_url, '/') . '/dois/' . urlencode($doi);

        $payload = [
            'data' => [
                'type' => 'dois',
                'attributes' => [
                    'event' => $event,
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/vnd.api+json',
            ],
            CURLOPT_USERPWD => $config->datacite_repo_id . ':' . $config->datacite_password,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL error: ' . $error];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'response' => $responseData];
        }

        $errorMsg = $responseData['errors'][0]['title'] ?? "HTTP {$httpCode}";

        return ['success' => false, 'error' => $errorMsg, 'response' => $responseData];
    }

    // =========================================
    // EXPORT
    // =========================================

    /**
     * Export DOIs to CSV format.
     *
     * @param array $options Filter options: status, repository_id, from_date, to_date
     *
     * @return string CSV content
     */
    public function exportToCsv(array $options = []): string
    {
        $dois = $this->getDoiExportData($options);

        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, [
            'DOI',
            'Title',
            'Status',
            'Minted Date',
            'Landing URL',
            'Repository',
            'Object ID',
        ]);

        // Data rows
        foreach ($dois as $doi) {
            fputcsv($output, [
                $doi->doi,
                $doi->title ?? 'Untitled',
                $doi->status,
                $doi->minted_at,
                'https://doi.org/' . $doi->doi,
                $doi->repository_name ?? '',
                $doi->information_object_id,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export DOIs to JSON format.
     *
     * @param array $options Filter options
     *
     * @return string JSON content
     */
    public function exportToJson(array $options = []): string
    {
        $dois = $this->getDoiExportData($options);

        $data = [];
        foreach ($dois as $doi) {
            $data[] = [
                'doi' => $doi->doi,
                'url' => 'https://doi.org/' . $doi->doi,
                'title' => $doi->title ?? 'Untitled',
                'status' => $doi->status,
                'minted_at' => $doi->minted_at,
                'repository' => $doi->repository_name ?? null,
                'object_id' => $doi->information_object_id,
                'last_sync' => $doi->last_sync_at,
            ];
        }

        return json_encode([
            'exported_at' => date('c'),
            'count' => count($data),
            'dois' => $data,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get DOI data for export.
     *
     * @param array $options Filter options
     *
     * @return Collection
     */
    protected function getDoiExportData(array $options = []): Collection
    {
        $query = DB::table('ahg_doi as d')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('d.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object as io', 'd.information_object_id', '=', 'io.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('io.repository_id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select([
                'd.doi',
                'd.status',
                'd.minted_at',
                'd.last_sync_at',
                'd.information_object_id',
                'ioi.title',
                'ai.authorized_form_of_name as repository_name',
            ]);

        // Filter by status
        if (!empty($options['status'])) {
            $query->where('d.status', $options['status']);
        }

        // Filter by repository
        if (!empty($options['repository_id'])) {
            $query->where('io.repository_id', $options['repository_id']);
        }

        // Filter by date range
        if (!empty($options['from_date'])) {
            $query->where('d.minted_at', '>=', $options['from_date']);
        }
        if (!empty($options['to_date'])) {
            $query->where('d.minted_at', '<=', $options['to_date'] . ' 23:59:59');
        }

        return $query->orderBy('d.minted_at', 'desc')->get();
    }

    /**
     * Get DOI by information object ID.
     *
     * @param int $objectId Information object ID
     *
     * @return object|null
     */
    public function getDoiByObjectId(int $objectId): ?object
    {
        return DB::table('ahg_doi')
            ->where('information_object_id', $objectId)
            ->first();
    }

    /**
     * Check if record has a minted DOI.
     *
     * @param int $objectId Information object ID
     *
     * @return bool
     */
    public function hasDoi(int $objectId): bool
    {
        return DB::table('ahg_doi')
            ->where('information_object_id', $objectId)
            ->whereIn('status', ['findable', 'registered', 'draft'])
            ->exists();
    }
}
