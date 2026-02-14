<?php

namespace AhgResearcherPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Import researcher-exchange.json files from the Portable Export viewer's edit mode.
 *
 * Supports collections of type:
 *  - notes        : Research notes on existing AtoM records
 *  - files        : Imported files with captions
 *  - new_items    : New descriptive records (with hierarchy, access points)
 *  - new_creators : New creator/actor records
 *  - new_repositories : New repository records
 */
class ExchangeImportService
{
    private SubmissionService $submissionService;

    public function __construct()
    {
        $this->submissionService = new SubmissionService();
    }

    /**
     * Import an exchange JSON and create a draft submission.
     *
     * @param int         $userId       The importing user
     * @param string      $jsonString   Raw JSON content
     * @param int|null    $repositoryId Target repository (override)
     * @return array{submission_id: int, stats: array}
     */
    public function import(int $userId, string $jsonString, ?int $repositoryId = null): array
    {
        $exchange = $this->parseExchangeJson($jsonString);

        $includeImages = $exchange['export_options']['include_images'] ?? true;

        $submissionId = $this->submissionService->createSubmission($userId, [
            'title'           => 'Import: ' . ($exchange['source_config']['title'] ?? 'Portable Export'),
            'description'     => 'Imported from researcher-exchange.json on ' . date('Y-m-d H:i'),
            'repository_id'   => $repositoryId,
            'source_type'     => 'offline',
            'source_file'     => 'researcher-exchange.json',
            'include_images'  => $includeImages ? 1 : 0,
        ]);

        $stats = [
            'notes'        => 0,
            'files'        => 0,
            'new_items'    => 0,
            'new_creators' => 0,
            'new_repos'    => 0,
            'file_count'   => 0,
        ];

        foreach ($exchange['collections'] as $collection) {
            $type = $collection['type'] ?? 'unknown';

            switch ($type) {
                case 'notes':
                    $stats['notes'] += $this->importNotesCollection($submissionId, $collection);
                    break;

                case 'files':
                    $imported = $this->importFilesCollection($submissionId, $collection, $includeImages);
                    $stats['files'] += $imported['items'];
                    $stats['file_count'] += $imported['files'];
                    break;

                case 'new_items':
                    $imported = $this->importNewItemsCollection($submissionId, $collection, $includeImages);
                    $stats['new_items'] += $imported['items'];
                    $stats['file_count'] += $imported['files'];
                    break;

                case 'new_creators':
                    $stats['new_creators'] += $this->importNewCreatorsCollection($submissionId, $collection);
                    break;

                case 'new_repositories':
                    $stats['new_repos'] += $this->importNewRepositoriesCollection($submissionId, $collection);
                    break;
            }
        }

        $this->submissionService->recalculateTotals($submissionId);

        return [
            'submission_id' => $submissionId,
            'stats'         => $stats,
        ];
    }

    /**
     * Parse and validate the exchange JSON format.
     *
     * @throws \InvalidArgumentException on invalid format
     */
    public function parseExchangeJson(string $jsonString): array
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        if (empty($data['format_version'])) {
            throw new \InvalidArgumentException('Missing format_version in exchange JSON.');
        }

        if ($data['format_version'] !== '1.0') {
            throw new \InvalidArgumentException('Unsupported format version: ' . $data['format_version']);
        }

        if (empty($data['collections']) || !is_array($data['collections'])) {
            throw new \InvalidArgumentException('No collections found in exchange JSON.');
        }

        return $data;
    }

    /**
     * Import a "notes" collection — research notes referencing existing AtoM records.
     */
    public function importNotesCollection(int $submissionId, array $collection): int
    {
        $count = 0;

        foreach ($collection['items'] ?? [] as $item) {
            $this->submissionService->addItem($submissionId, [
                'item_type'           => 'note',
                'title'               => $item['title'] ?? ('Note on ' . ($item['reference_slug'] ?? 'record')),
                'identifier'          => $item['reference_identifier'] ?? null,
                'level_of_description' => $item['level_of_description'] ?? 'item',
                'scope_and_content'   => $item['note'] ?? null,
                'reference_object_id' => $item['reference_id'] ?? null,
                'reference_slug'      => $item['reference_slug'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Import a "files" collection — files with captions.
     */
    public function importFilesCollection(int $submissionId, array $collection, bool $includeImages = true): array
    {
        $itemCount = 0;
        $fileCount = 0;

        foreach ($collection['items'] ?? [] as $item) {
            $itemId = $this->submissionService->addItem($submissionId, [
                'item_type'          => 'description',
                'title'              => $item['title'] ?? 'Imported file',
                'level_of_description' => 'item',
                'scope_and_content'  => $item['scope_and_content'] ?? null,
                'extent_and_medium'  => $item['extent_and_medium'] ?? null,
                'subjects'           => $item['subjects'] ?? null,
                'places'             => $item['places'] ?? null,
                'genres'             => $item['genres'] ?? null,
                'creators'           => $item['creators'] ?? null,
            ]);
            $itemCount++;

            if ($includeImages && !empty($item['files'])) {
                foreach ($item['files'] as $file) {
                    if ($this->importFileData($itemId, $file)) {
                        $fileCount++;
                    }
                }
            }
        }

        return ['items' => $itemCount, 'files' => $fileCount];
    }

    /**
     * Import a "new_items" collection — new descriptive records with hierarchy.
     */
    public function importNewItemsCollection(int $submissionId, array $collection, bool $includeImages = true): array
    {
        $itemCount = 0;
        $fileCount = 0;

        // Build items with parent mapping for hierarchy
        $idMap = []; // exchange_id => db_item_id

        foreach ($collection['items'] ?? [] as $item) {
            $parentItemId = null;
            if (!empty($item['parent_id']) && isset($idMap[$item['parent_id']])) {
                $parentItemId = $idMap[$item['parent_id']];
            }

            $itemId = $this->submissionService->addItem($submissionId, [
                'item_type'             => 'description',
                'parent_item_id'        => $parentItemId,
                'title'                 => $item['title'] ?? 'New item',
                'identifier'            => $item['identifier'] ?? null,
                'level_of_description'  => $item['level_of_description'] ?? 'item',
                'scope_and_content'     => $item['scope_and_content'] ?? null,
                'extent_and_medium'     => $item['extent_and_medium'] ?? null,
                'date_display'          => $item['date_display'] ?? null,
                'date_start'            => $item['date_start'] ?? null,
                'date_end'              => $item['date_end'] ?? null,
                'creators'              => $this->normalizeAccessPoints($item['creators'] ?? null),
                'subjects'              => $this->normalizeAccessPoints($item['subjects'] ?? null),
                'places'                => $this->normalizeAccessPoints($item['places'] ?? null),
                'genres'                => $this->normalizeAccessPoints($item['genres'] ?? null),
                'access_conditions'     => $item['access_conditions'] ?? null,
                'reproduction_conditions' => $item['reproduction_conditions'] ?? null,
                'notes'                 => $item['notes'] ?? null,
            ]);

            // Map exchange item id to DB id for hierarchy
            if (!empty($item['id'])) {
                $idMap[$item['id']] = $itemId;
            }

            $itemCount++;

            if ($includeImages && !empty($item['files'])) {
                foreach ($item['files'] as $file) {
                    if ($this->importFileData($itemId, $file)) {
                        $fileCount++;
                    }
                }
            }
        }

        return ['items' => $itemCount, 'files' => $fileCount];
    }

    /**
     * Import a "new_creators" collection — new creator/actor records.
     */
    public function importNewCreatorsCollection(int $submissionId, array $collection): int
    {
        $count = 0;

        foreach ($collection['items'] ?? [] as $item) {
            $this->submissionService->addItem($submissionId, [
                'item_type'          => 'creator',
                'title'              => $item['name'] ?? $item['title'] ?? 'New creator',
                'scope_and_content'  => $item['history'] ?? $item['biography'] ?? null,
                'date_display'       => $item['dates_of_existence'] ?? null,
                'notes'              => $item['notes'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Import a "new_repositories" collection — new repository records.
     */
    public function importNewRepositoriesCollection(int $submissionId, array $collection): int
    {
        $count = 0;

        foreach ($collection['items'] ?? [] as $item) {
            $this->submissionService->addItem($submissionId, [
                'item_type'           => 'repository',
                'title'               => $item['name'] ?? $item['title'] ?? 'New repository',
                'scope_and_content'   => $item['description'] ?? null,
                'repository_name'     => $item['name'] ?? $item['title'] ?? null,
                'repository_address'  => $item['address'] ?? null,
                'repository_contact'  => $item['contact'] ?? null,
                'notes'               => $item['notes'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Import a file from exchange data (base64 data URL or metadata-only).
     */
    protected function importFileData(int $itemId, array $fileData): bool
    {
        $filename = $fileData['filename'] ?? 'unknown';
        $mimeType = $fileData['mime_type'] ?? 'application/octet-stream';
        $caption = $fileData['caption'] ?? null;

        // Check for embedded base64 data
        if (!empty($fileData['data'])) {
            $rawData = $fileData['data'];

            // Strip data URL prefix if present (e.g., "data:image/jpeg;base64,...")
            if (preg_match('/^data:[^;]+;base64,(.+)$/', $rawData, $m)) {
                $rawData = base64_decode($m[1], true);
            } elseif (preg_match('/^[A-Za-z0-9+\/=]+$/', $rawData)) {
                $rawData = base64_decode($rawData, true);
            }

            if ($rawData === false) {
                return false;
            }

            $id = $this->submissionService->addFileFromData(
                $itemId, $filename, $rawData, $mimeType, $caption
            );

            return $id !== null;
        }

        // Metadata-only (no image data) — create placeholder record
        $item = DB::table('researcher_submission_item')->where('id', $itemId)->first();
        if (!$item) {
            return false;
        }

        DB::table('researcher_submission_file')->insert([
            'item_id'       => $itemId,
            'original_name' => $filename,
            'stored_name'   => 'pending_' . $filename,
            'stored_path'   => '',
            'mime_type'     => $mimeType,
            'file_size'     => $fileData['size'] ?? 0,
            'caption'       => $caption,
            'sort_order'    => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Normalize access points — can be array or comma-separated string.
     */
    protected function normalizeAccessPoints($value): ?string
    {
        if (is_array($value)) {
            return implode(', ', array_filter($value));
        }

        return $value ?: null;
    }
}
