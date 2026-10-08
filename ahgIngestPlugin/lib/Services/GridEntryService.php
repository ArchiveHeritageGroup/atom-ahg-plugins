<?php

namespace AhgIngestPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Grid entry: many descriptions typed or pasted into a spreadsheet-style grid
 * under one parent description.
 *
 * The grid never writes information_object rows itself. It stages its rows as
 * an ordinary ingest session (one ingest_row per grid row, identity column
 * mapping, the wizard's own enrich + validate steps) and the records are then
 * created by the wizard's commit job, so slugs, nested set, publication
 * status, search index and the audit trail behave exactly as in a CSV ingest.
 */
class GridEntryService
{
    /** Grid column => AtoM CSV field name used by the ingest pipeline. */
    public const COLUMNS = [
        'identifier' => 'Identifier',
        'title' => 'Title',
        'levelOfDescription' => 'Level of description',
        'creationDates' => 'Dates (display)',
        'creationDatesStart' => 'Start date',
        'creationDatesEnd' => 'End date',
        'extentAndMedium' => 'Extent and medium',
        'scopeAndContent' => 'Scope and content',
    ];

    public const MAX_ROWS = 1000;

    protected IngestService $ingest;

    public function __construct(?IngestService $ingest = null)
    {
        $this->ingest = $ingest ?? new IngestService();
    }

    /**
     * Level-of-description term names, in the given culture where translated.
     */
    public function levelNames(string $culture = 'en'): array
    {
        $taxonomyId = class_exists('QubitTaxonomy') ? \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID : 34;
        $rows = DB::table('term')
            ->leftJoin('term_i18n as cur', function ($j) use ($culture) {
                $j->on('cur.id', '=', 'term.id')->where('cur.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as src', function ($j) {
                $j->on('src.id', '=', 'term.id')->on('src.culture', '=', 'term.source_culture');
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->orderBy('term.lft')
            ->select(DB::raw('COALESCE(cur.name, src.name) AS name'))
            ->pluck('name')
            ->all();

        return array_values(array_unique(array_filter(array_map('strval', $rows), 'strlen')));
    }

    /**
     * Trim cells, drop rows with nothing in them, keep the grid position.
     *
     * @return array<int, array> grid row index => [field => value]
     */
    public function normalise(array $rows): array
    {
        $out = [];
        foreach (array_values($rows) as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $clean = [];
            foreach (array_keys(self::COLUMNS) as $field) {
                $v = $row[$field] ?? '';
                $v = is_scalar($v) ? trim((string) $v) : '';
                // Normalise Windows line endings from pasted cells.
                $clean[$field] = str_replace("\r\n", "\n", $v);
            }
            if ('' === implode('', $clean)) {
                continue;
            }
            // A start/end date with no display date would otherwise create no
            // event at commit; derive the display text from them.
            if ('' === $clean['creationDates'] && ('' !== $clean['creationDatesStart'] || '' !== $clean['creationDatesEnd'])) {
                $s = $clean['creationDatesStart'];
                $e = $clean['creationDatesEnd'];
                $clean['creationDates'] = ('' === $s || '' === $e || $s === $e) ? ($s ?: $e) : $s . ' - ' . $e;
            }
            $out[$i] = $clean;
        }

        return $out;
    }

    /**
     * Blocking checks. Returns [grid row index => [field => message]].
     * Level names are matched without regard to case and rewritten to the
     * exact term name, since the commit looks the term up by exact name.
     */
    public function validate(array &$rows, array $levelNames): array
    {
        $errors = [];
        $levels = [];
        foreach ($levelNames as $name) {
            $levels[mb_strtolower($name)] = $name;
        }

        if (count($rows) > self::MAX_ROWS) {
            $errors[array_key_first($rows)]['title'] = 'At most ' . self::MAX_ROWS . ' rows can be saved at once.';

            return $errors;
        }

        foreach ($rows as $i => &$row) {
            if ('' === $row['title']) {
                $errors[$i]['title'] = 'Title is required.';
            } elseif (mb_strlen($row['title']) > 1024) {
                $errors[$i]['title'] = 'Title is longer than 1024 characters.';
            }
            if (mb_strlen($row['identifier']) > 1024) {
                $errors[$i]['identifier'] = 'Identifier is longer than 1024 characters.';
            }
            if ('' !== $row['levelOfDescription']) {
                $key = mb_strtolower($row['levelOfDescription']);
                if (isset($levels[$key])) {
                    $row['levelOfDescription'] = $levels[$key];
                } else {
                    $errors[$i]['levelOfDescription'] = 'Unknown level of description "' . $row['levelOfDescription'] . '".';
                }
            }
            foreach (['creationDatesStart', 'creationDatesEnd'] as $f) {
                if ('' !== $row[$f] && !self::isIsoDate($row[$f])) {
                    $errors[$i][$f] = 'Use YYYY, YYYY-MM or YYYY-MM-DD.';
                }
            }
            if ('' !== $row['creationDatesStart'] && '' !== $row['creationDatesEnd']
                && empty($errors[$i]['creationDatesStart']) && empty($errors[$i]['creationDatesEnd'])
                && self::padDate($row['creationDatesStart'], false) > self::padDate($row['creationDatesEnd'], true)) {
                $errors[$i]['creationDatesEnd'] = 'End date is before the start date.';
            }
        }
        unset($row);

        return $errors;
    }

    public static function isIsoDate(string $v): bool
    {
        if (!preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/', $v, $m)) {
            return false;
        }
        $month = isset($m[2]) ? (int) $m[2] : 1;
        $day = isset($m[3]) ? (int) $m[3] : 1;

        return checkdate($month, $day, (int) $m[1]);
    }

    protected static function padDate(string $v, bool $end): string
    {
        $parts = explode('-', $v);

        return $parts[0] . '-' . ($parts[1] ?? ($end ? '12' : '01')) . '-' . ($parts[2] ?? ($end ? '31' : '01'));
    }

    /**
     * Stage validated grid rows as an ingest session under $parentId and run
     * the wizard's enrich + validate steps. Returns [session id, stats].
     */
    public function stage(int $userId, int $parentId, string $parentTitle, array $rows): array
    {
        $sessionId = $this->ingest->createSession($userId, [
            'title' => 'Grid entry under ' . $parentTitle . ' (' . count($rows) . ' rows)',
            'entity_type' => 'description',
            'sector' => 'archive',
            'standard' => 'isadg',
            'parent_id' => $parentId,
            'parent_placement' => 'existing',
            'output_create_records' => 1,
            'derivative_thumbnails' => 0,
            'derivative_reference' => 0,
            'process_virus_scan' => 0,
            'source' => 'grid',
        ]);

        $order = 0;
        foreach (array_keys(self::COLUMNS) as $field) {
            DB::table('ingest_mapping')->insert([
                'session_id' => $sessionId,
                'source_column' => $field,
                'target_field' => $field,
                'is_ignored' => 0,
                'sort_order' => ++$order,
            ]);
        }

        $n = 0;
        foreach ($rows as $row) {
            DB::table('ingest_row')->insert([
                'session_id' => $sessionId,
                'row_number' => ++$n,
                'level_of_description' => '' !== $row['levelOfDescription'] ? $row['levelOfDescription'] : null,
                'title' => $row['title'],
                'data' => json_encode($row, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->ingest->enrichRows($sessionId);
        $stats = $this->ingest->validateSession($sessionId);
        $this->ingest->updateSessionStatus($sessionId, 'commit');

        return [$sessionId, $stats];
    }

    /**
     * Existing child descriptions of $parentId for display beside the grid.
     */
    public function children(int $parentId, string $culture = 'en', int $limit = 500): array
    {
        $creationId = class_exists('QubitTerm') ? \QubitTerm::CREATION_ID : 111;

        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as cur', function ($j) use ($culture) {
                $j->on('cur.id', '=', 'io.id')->where('cur.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as src', function ($j) {
                $j->on('src.id', '=', 'io.id')->on('src.culture', '=', 'io.source_culture');
            })
            ->leftJoin('term_i18n as lvl', function ($j) use ($culture) {
                $j->on('lvl.id', '=', 'io.level_of_description_id')->where('lvl.culture', '=', $culture);
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->where('io.parent_id', $parentId)
            ->orderBy('io.lft')
            ->limit($limit)
            ->select(
                'io.id',
                'io.identifier',
                'slug.slug',
                'lvl.name as level',
                DB::raw('COALESCE(cur.title, src.title) AS title'),
                DB::raw('COALESCE(cur.extent_and_medium, src.extent_and_medium) AS extent'),
                DB::raw('COALESCE(cur.scope_and_content, src.scope_and_content) AS scope'),
                DB::raw("(SELECT CONCAT_WS('|', COALESCE(ei.date, ''), COALESCE(e.start_date, ''), COALESCE(e.end_date, ''))
                          FROM event e LEFT JOIN event_i18n ei ON ei.id = e.id AND ei.culture = e.source_culture
                          WHERE e.object_id = io.id AND e.type_id = {$creationId} ORDER BY e.id LIMIT 1) AS dates")
            )
            ->get()
            ->all();
    }
}
