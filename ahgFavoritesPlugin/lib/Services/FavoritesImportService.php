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
        $rows = $this->readCsv($csvContent);

        if (empty($rows)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => [\__('Empty CSV content.')]];
        }

        $header = array_map([$this, 'normaliseHeading'], array_shift($rows));

        $slugCol = array_search('slug', $header);
        $refCol = array_search('reference_code', $header);
        $dateCol = array_search('date_added', $header);

        if (false === $slugCol && false === $refCol) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => [\__('CSV must have a "slug" or "reference_code" column.')]];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $i = $index + 1;

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
                $errors[] = \__('Row %1%: could not resolve "%2%".', ['%1%' => $i, '%2%' => $identifier]);
                $skipped++;
                continue;
            }

            $result = $this->favService->addToFavorites($userId, (int) $objectId);
            if ($result['success']) {
                $changes = [];

                if ($folderId) {
                    $changes['folder_id'] = $folderId;
                }

                // Preserve the original date when the file carries one. The exporter
                // writes it, so a round trip keeps when the item was favourited
                // rather than resetting everything to the moment of import.
                if (false !== $dateCol && !empty($row[$dateCol])) {
                    $added = $this->parseDate($row[$dateCol]);

                    if (null === $added) {
                        $errors[] = \__('Row %1%: unrecognised date "%2%", kept the import date.', ['%1%' => $i, '%2%' => trim($row[$dateCol])]);
                    } else {
                        $changes['created_at'] = $added;
                    }
                }

                if ($changes && isset($result['id'])) {
                    $changes['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('favorites')->where('id', $result['id'])->update($changes);
                }

                $imported++;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Parse a CSV document into rows.
     *
     * fgetcsv over a stream rather than splitting the text on newlines. The previous
     * str_getcsv($content, "\n") applied CSV quoting rules with a newline delimiter,
     * which strips the quotes around a field and then hands the line back to a second
     * str_getcsv - so a title containing a comma became two fields and shifted every
     * column after it. The slug column then read whatever happened to land in its
     * position and the row could not be resolved. It also could not survive a value
     * containing a line break at all.
     */
    private function readCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (false !== $row = fgetcsv($handle)) {
            if ([null] === $row) {
                continue;   // blank line
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Reduce a column heading to the machine name this importer matches on.
     *
     * The exporter writes headings for people to read and runs them through the
     * translator - 'Reference Code', 'Date Added'. Matching those against literal
     * 'reference_code' never succeeded, so a file this plugin had just produced
     * could only be re-imported because the English word 'Slug' happened to match
     * on its own. Any other locale had no usable column at all.
     */
    private function normaliseHeading(string $heading): string
    {
        $heading = preg_replace('/^\xEF\xBB\xBF/', '', trim($heading));

        return strtolower(preg_replace('/[\s-]+/', '_', $heading));
    }

    /**
     * Normalise a date from a CSV into what MySQL DATETIME accepts.
     *
     * Anything the database would reject is reported and the row keeps the import
     * date rather than failing, since the date is incidental to what is being
     * imported.
     *
     * Order is decided by the value wherever the value can decide it, and only
     * falls back to the instance's locale when it genuinely cannot - 03/04/2026 is
     * the sole ambiguous shape, and 25/12 or 12/25 answer for themselves.
     */
    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ('' === $value) {
            return null;
        }

        // Unambiguous forms first: ISO, and anything naming the month in words.
        foreach (['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d', 'd M Y', 'j F Y'] as $format) {
            if (null !== $parsed = $this->fromFormat($format, $value)) {
                return $parsed;
            }
        }

        // Numeric d/m/Y against m/d/Y. Which one a file means is a property of
        // whoever wrote it, not of this code, so read it from the value where the
        // value settles it and fall back to the instance's locale where it does not.
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})(.*)$#', $value, $parts)) {
            $first = (int) $parts[1];
            $second = (int) $parts[2];

            if ($first > 12) {
                $order = ['d', 'm'];          // 25/12/2026 can only be day-first
            } elseif ($second > 12) {
                $order = ['m', 'd'];          // 12/25/2026 can only be month-first
            } else {
                $order = $this->monthFirstLocale() ? ['m', 'd'] : ['d', 'm'];
            }

            $separator = false !== strpos($value, '/') ? '/' : '-';
            $base = implode($separator, $order).$separator.'Y';

            foreach ([$base.' H:i:s', $base.' H:i', $base] as $format) {
                if (null !== $parsed = $this->fromFormat($format, $value)) {
                    return $parsed;
                }
            }
        }

        // Last resort, so an ISO 8601 value with an offset is still understood.
        try {
            return (new \DateTime($value))->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse against one format, or null if the value is not exactly that shape.
     */
    private function fromFormat(string $format, string $value): ?string
    {
        $date = \DateTime::createFromFormat($format, $value);

        if (false === $date || $date->format($format) !== $value) {
            return null;
        }

        // createFromFormat fills anything the format does not mention from the
        // current clock, so a date with no time would be stamped with the moment
        // of import.
        if (false === strpos($format, 'H')) {
            $date->setTime(0, 0, 0);
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Does this instance's locale write the month first?
     *
     * Month-first is essentially a United States convention; nearly everywhere
     * else writes the day first, so the default is day-first and en_US is the
     * exception. This used to be hardcoded day-first "because this is an en-ZA
     * product", which quietly turned 03/04/2026 from a US export into 3 April.
     */
    private function monthFirstLocale(): bool
    {
        try {
            $culture = \sfContext::getInstance()->getUser()->getCulture();
        } catch (\Throwable $e) {
            return false;
        }

        return in_array(str_replace('-', '_', (string) $culture), ['en_US', 'en_PH'], true);
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
                $errors[] = \__('Slug "%1%" not found.', ['%1%' => $slug]);
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
                $errors[] = \__('Object ID %1% not found.', ['%1%' => $oid]);
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
