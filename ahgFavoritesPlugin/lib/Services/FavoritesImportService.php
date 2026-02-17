<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Services;

require_once __DIR__.'/FavoritesService.php';

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Favorites Import Service - Import from CSV, slugs, or object IDs
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FavoritesImportService
{
    private FavoritesService $favService;

    public function __construct()
    {
        $this->favService = new FavoritesService();
    }

    /**
     * Import from CSV content (expects slug or reference_code column)
     *
     * @return array ['imported', 'skipped', 'errors']
     */
    public function importFromCsv(int $userId, string $csvContent, ?int $folderId = null): array
    {
        $lines = str_getcsv($csvContent, "\n");
        if (empty($lines)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Empty CSV content.']];
        }

        // Strip BOM if present
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);

        // Parse header
        $header = str_getcsv($lines[0]);
        $header = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $slugCol = array_search('slug', $header);
        $refCol = array_search('reference_code', $header);

        if ($slugCol === false && $refCol === false) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['CSV must have a "slug" or "reference_code" column.']];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (empty(array_filter($row))) {
                continue;
            }

            $objectId = null;

            // Try slug first
            if ($slugCol !== false && !empty($row[$slugCol])) {
                $slug = trim($row[$slugCol]);
                $objectId = DB::table('slug')
                    ->where('slug', $slug)
                    ->value('object_id');
            }

            // Fallback to reference_code
            if (!$objectId && $refCol !== false && !empty($row[$refCol])) {
                $ref = trim($row[$refCol]);
                $objectId = DB::table('information_object')
                    ->where('identifier', $ref)
                    ->value('id');
            }

            if (!$objectId) {
                $identifier = ($slugCol !== false && !empty($row[$slugCol])) ? $row[$slugCol] : ($row[$refCol] ?? '');
                $errors[] = "Row {$i}: could not resolve '{$identifier}'.";
                $skipped++;
                continue;
            }

            $result = $this->favService->addToFavorites($userId, (int) $objectId);
            if ($result['success']) {
                // Move to folder if specified
                if ($folderId && isset($result['id'])) {
                    DB::table('favorites')
                        ->where('id', $result['id'])
                        ->update(['folder_id' => $folderId, 'updated_at' => date('Y-m-d H:i:s')]);
                }
                $imported++;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import by slug array
     */
    public function importFromSlugs(int $userId, array $slugs, ?int $folderId = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($slugs as $slug) {
            $slug = trim($slug);
            if (empty($slug)) {
                continue;
            }

            $objectId = DB::table('slug')
                ->where('slug', $slug)
                ->value('object_id');

            if (!$objectId) {
                $errors[] = "Slug '{$slug}' not found.";
                $skipped++;
                continue;
            }

            $result = $this->favService->addToFavorites($userId, (int) $objectId);
            if ($result['success']) {
                if ($folderId && isset($result['id'])) {
                    DB::table('favorites')
                        ->where('id', $result['id'])
                        ->update(['folder_id' => $folderId, 'updated_at' => date('Y-m-d H:i:s')]);
                }
                $imported++;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import by object ID array
     */
    public function importFromObjectIds(int $userId, array $objectIds, ?int $folderId = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($objectIds as $oid) {
            $oid = (int) $oid;
            if ($oid <= 0) {
                continue;
            }

            if (!DB::table('object')->where('id', $oid)->exists()) {
                $errors[] = "Object ID {$oid} not found.";
                $skipped++;
                continue;
            }

            $result = $this->favService->addToFavorites($userId, $oid);
            if ($result['success']) {
                if ($folderId && isset($result['id'])) {
                    DB::table('favorites')
                        ->where('id', $result['id'])
                        ->update(['folder_id' => $folderId, 'updated_at' => date('Y-m-d H:i:s')]);
                }
                $imported++;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }
}
