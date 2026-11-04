<?php

namespace AhgRicExplorer\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Str;

/**
 * The records and names behind the offline relationship explorer (#196,
 * parity with Heratio's ExplorerDataService, heratio#1524): each record with
 * its subjects, places, genres and people, each name with the records it
 * appears on, and the same-story links between records in scope.
 *
 * Built from catalogue data only. A name is grouped by its printed form;
 * spellings are merged only where they differ in case, punctuation, accents
 * or a leading "The" ("ANC" and "African National Congress" stay apart:
 * merging those is a cataloguing decision, not the exporter's).
 *
 * Keep the output shape in step with Heratio's: the same index.html reads it.
 */
class ExplorerDataService
{
    public const ROOT_ID = 1;
    public const STATUS_TYPE_PUBLICATION = 158;
    public const STATUS_PUBLISHED = 160;
    public const RELATION_NAME_ACCESS_POINT = 161;

    /** taxonomy id => explorer type */
    private const TAXONOMIES = [35 => 'subject', 42 => 'place', 78 => 'genre'];

    /**
     * @param int[]  $ids  records, in the order to list them
     * @param int    $min  leave out names on fewer records than this
     * @param string $hide comma-separated general words kept off the starting list and the map
     * @param string $year prefer a date in this year when a record has several
     *
     * @return array{records: list<array>, entities: list<array>, links: list<array>}
     */
    public static function build(array $ids, string $base, int $min = 1, string $hide = '', string $year = ''): array
    {
        $base = rtrim($base, '/');
        $records = [];
        $mentions = []; // "type|key" => [printed form => [record id => true]]
        foreach (array_chunk($ids, 1000) as $chunk) {
            foreach (self::records($chunk) as $r) {
                $records[$r->id] = ['id' => (string) $r->id, 'title' => trim(html_entity_decode(strip_tags((string) $r->title), ENT_QUOTES)), 'ref' => (string) $r->identifier,
                    'date' => '', 'url' => $r->slug ? "{$base}/{$r->slug}" : null, 'ents' => []];
            }
            foreach (self::dates($chunk, $year) as $id => $date) {
                if (isset($records[$id])) {
                    $records[$id]['date'] = $date;
                }
            }
            foreach (self::names($chunk) as [$id, $type, $name]) {
                $name = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $name)));
                $key = self::groupKey($name);
                if ('' !== $key && isset($records[$id])) {
                    $mentions["{$type}|{$key}"][$name][$id] = true;
                }
            }
        }

        $entities = [];
        $min = max(1, $min);
        $hide = array_flip(array_map([self::class, 'groupKey'], explode(',', $hide)));
        foreach ($mentions as $ek => $forms) {
            $recs = array_keys(array_replace(...array_values($forms)));
            if (count($recs) < $min) {
                continue;
            }
            uksort($forms, fn ($a, $b) => count($forms[$b]) <=> count($forms[$a]) ?: strcmp($a, $b));
            [$type, $key] = explode('|', $ek, 2);
            $id = "{$type}:{$key}";
            $entities[] = ['id' => $id, 'type' => $type, 'name' => (string) array_key_first($forms),
                'variants' => array_map('strval', array_keys($forms)), 'recs' => array_map('strval', $recs)]
                + (isset($hide[$key]) ? ['hidden' => true] : []);
            foreach ($recs as $rid) {
                $records[$rid]['ents'][] = $id;
            }
        }

        return ['records' => array_values($records), 'entities' => $entities, 'links' => SameStoryService::linksAmong($ids)];
    }

    public static function template(): string
    {
        return dirname(__DIR__, 2).'/explorer/index.html';
    }

    /** Group spellings that differ only in case, punctuation, accents or a leading "The". */
    public static function groupKey(string $name): string
    {
        $k = Str::lower(Str::ascii(trim($name)));
        $k = preg_replace('/^the\s+/', '', $k);

        return preg_replace('/[^a-z0-9]+/', '', $k);
    }

    /** Published records in scope, in tree order. */
    public static function scope(?int $rootId, ?string $year, int $limit): array
    {
        $q = DB::table('information_object as io')
            ->join('status as st', fn ($j) => $j->on('st.object_id', '=', 'io.id')->where('st.type_id', self::STATUS_TYPE_PUBLICATION))
            ->where('st.status_id', self::STATUS_PUBLISHED)->where('io.id', '!=', self::ROOT_ID);
        if ($rootId) {
            $root = DB::table('information_object')->where('id', $rootId)->first(['lft', 'rgt']);
            $q->whereBetween('io.lft', [$root->lft, $root->rgt]);
        }
        if ($year) {
            $q->whereExists(fn ($e) => $e->from('event as e')->leftJoin('event_i18n as ei', 'ei.id', '=', 'e.id')->whereColumn('e.object_id', 'io.id')
                ->where(fn ($w) => $w->whereYear('e.start_date', (int) $year)->orWhere('ei.date', 'like', "%{$year}")));
        }

        return $q->orderBy('io.lft')->limit(max(1, $limit))->pluck('io.id')->map(fn ($i) => (int) $i)->all();
    }

    /** A record id from an id or a slug, or null. */
    public static function resolve(string $idOrSlug): ?int
    {
        $id = ctype_digit($idOrSlug) ? (int) $idOrSlug : (int) DB::table('slug')->where('slug', $idOrSlug)->value('object_id');

        return $id && DB::table('information_object')->where('id', $id)->exists() ? $id : null;
    }

    private static function records(array $ids)
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i', fn ($j) => $j->on('i.id', '=', 'io.id')->where('i.culture', 'en'))
            ->leftJoin('information_object_i18n as s', fn ($j) => $j->on('s.id', '=', 'io.id')->on('s.culture', '=', 'io.source_culture'))
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->whereIn('io.id', $ids)
            ->orderByRaw('FIELD(io.id, '.implode(',', array_map('intval', $ids)).')')
            ->get(['io.id', 'io.identifier', DB::raw('COALESCE(i.title, s.title) as title'), 'slug.slug']);
    }

    /** @return array<int,string> record id => display date (with $year, one in that year), else the start date */
    private static function dates(array $ids, string $year): array
    {
        $out = [];
        $rows = DB::table('event as e')->leftJoin('event_i18n as ei', 'ei.id', '=', 'e.id')->whereIn('e.object_id', $ids)
            ->orderBy('e.id')->get(['e.object_id', 'e.start_date', 'ei.date']);
        foreach ($rows as $r) {
            $d = $r->start_date ? substr((string) $r->start_date, 0, 10) : trim((string) $r->date);
            $id = (int) $r->object_id;
            if ('' !== $d && (!isset($out[$id]) || ('' !== $year && !str_contains($out[$id], $year) && str_contains($d, $year)))) {
                $out[$id] = $d;
            }
        }

        return $out;
    }

    /** @return iterable<array{0:int,1:string,2:string}> [record id, type, printed name] */
    private static function names(array $ids): iterable
    {
        $terms = DB::table('object_term_relation as r')->join('term as t', 't.id', '=', 'r.term_id')
            ->leftJoin('term_i18n as ti', fn ($j) => $j->on('ti.id', '=', 't.id')->where('ti.culture', 'en'))
            ->leftJoin('term_i18n as ts', fn ($j) => $j->on('ts.id', '=', 't.id')->on('ts.culture', '=', 't.source_culture'))
            ->whereIn('r.object_id', $ids)->whereIn('t.taxonomy_id', array_keys(self::TAXONOMIES))
            ->get(['r.object_id', 't.taxonomy_id', DB::raw('COALESCE(ti.name, ts.name) as name')]);
        foreach ($terms as $r) {
            yield [(int) $r->object_id, self::TAXONOMIES[(int) $r->taxonomy_id], (string) $r->name];
        }

        // People and bodies: name access points and creators.
        $actor = fn ($q, string $col) => $q->join('actor as a', 'a.id', '=', $col)
            ->leftJoin('actor_i18n as ai', fn ($j) => $j->on('ai.id', '=', 'a.id')->where('ai.culture', 'en'))
            ->leftJoin('actor_i18n as ac', fn ($j) => $j->on('ac.id', '=', 'a.id')->on('ac.culture', '=', 'a.source_culture'));
        $name = DB::raw('COALESCE(ai.authorized_form_of_name, ac.authorized_form_of_name) as name');
        $named = $actor(DB::table('relation as rl'), 'rl.object_id')->where('rl.type_id', self::RELATION_NAME_ACCESS_POINT)->whereIn('rl.subject_id', $ids)
            ->get([DB::raw('rl.subject_id as rid'), $name]);
        $creators = $actor(DB::table('event as e'), 'e.actor_id')->whereIn('e.object_id', $ids)
            ->get([DB::raw('e.object_id as rid'), $name]);
        foreach ($named->concat($creators) as $r) {
            yield [(int) $r->rid, 'name', (string) $r->name];
        }
    }
}
