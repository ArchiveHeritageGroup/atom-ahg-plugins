<?php

namespace AhgRicExplorer\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Same-story detection and duplicate-leaf QA (#196, parity with heratio#1524;
 * the method of the October 2026 Brenthurst cuttings explorer).
 *
 * Same story: each record's text (the OCR transcript of its digital object,
 * else its scope and content) is cut into word 6-grams. Two records are
 * linked when the smaller one shares at least 30% of its 6-grams with the
 * other and has at least 25 of them. A 6-gram found in more than 20 records
 * is a stock phrase ("from our own correspondent"), not evidence, and is
 * ignored. No AI involved: text overlap only.
 *
 * Duplicate leaf: when two different parent records (two pages) share 3 or
 * more linked items, the page was photographed twice. Those links are one
 * physical item, not a story printed twice, and are stored as 'same_leaf'.
 */
class SameStoryService
{
    public const N = 6;
    public const MIN_GRAMS = 25;
    public const MIN_OVERLAP = 0.30;
    public const STOCK_PHRASE = 20;
    public const SAME_LEAF = 3;

    /**
     * Compute and store the links among these records, replacing earlier
     * links between them.
     *
     * @param int[] $ids
     *
     * @return array{texts: int, links: int, leaves: list<array{a: int, b: int, items: int}>}
     */
    public static function run(array $ids): array
    {
        $texts = self::texts($ids);
        $parent = $texts ? DB::table('information_object')->whereIn('id', array_keys($texts))->pluck('parent_id', 'id')->all() : [];
        ['texts' => $n, 'links' => $links, 'leaves' => $leaves] = self::match($texts, $parent);

        $now = date('Y-m-d H:i:s');
        DB::connection()->transaction(function () use ($ids, $links, $now) {
            foreach (array_chunk($ids, 1000) as $chunk) {
                DB::table('ric_same_story_link')->whereIn('object_a_id', $chunk)->whereIn('object_b_id', $ids)->delete();
            }
            foreach (array_chunk($links, 500) as $chunk) {
                DB::table('ric_same_story_link')->insert(array_map(fn ($l) => ['object_a_id' => $l['a'], 'object_b_id' => $l['b'],
                    'overlap' => $l['overlap'], 'kind' => $l['kind'], 'computed_at' => $now], $chunk));
            }
        });

        return ['texts' => $n, 'links' => count($links), 'leaves' => $leaves];
    }

    /**
     * The matching itself, without the database: texts by record id, parent
     * record by record id. Links get kind same_leaf when their two pages
     * share SAME_LEAF or more linked items. testing/same-story-check.php
     * runs this on planted texts.
     *
     * @param array<int, string> $texts
     * @param array<int, int>    $parent
     *
     * @return array{texts: int, links: list<array{a: int, b: int, overlap: float, kind: string}>, leaves: list<array{a: int, b: int, items: int}>}
     */
    public static function match(array $texts, array $parent): array
    {
        $grams = [];
        foreach ($texts as $id => $text) {
            $g = self::grams($text);
            if (count($g) >= self::MIN_GRAMS) {
                $grams[$id] = $g;
            }
        }

        $index = [];
        foreach ($grams as $id => $g) {
            foreach ($g as $gram => $_) {
                $index[$gram][] = $id;
            }
        }
        $shared = [];
        foreach ($index as $holders) {
            $n = count($holders);
            if ($n < 2 || $n > self::STOCK_PHRASE) {
                continue;
            }
            for ($i = 0; $i < $n; ++$i) {
                for ($j = $i + 1; $j < $n; ++$j) {
                    [$a, $b] = $holders[$i] < $holders[$j] ? [$holders[$i], $holders[$j]] : [$holders[$j], $holders[$i]];
                    $shared["{$a}:{$b}"] = ($shared["{$a}:{$b}"] ?? 0) + 1;
                }
            }
        }

        $links = [];
        foreach ($shared as $pair => $n) {
            [$a, $b] = array_map('intval', explode(':', $pair));
            $overlap = $n / min(count($grams[$a]), count($grams[$b]));
            if ($overlap >= self::MIN_OVERLAP) {
                $links[] = ['a' => $a, 'b' => $b, 'overlap' => round($overlap, 2), 'kind' => 'same_story'];
            }
        }

        // Duplicate leaves: parent pairs sharing SAME_LEAF or more linked items.
        $page = fn ($l) => [(int) ($parent[$l['a']] ?? 0), (int) ($parent[$l['b']] ?? 0)];
        $pages = [];
        foreach ($links as $l) {
            [$pa, $pb] = $page($l);
            if ($pa && $pb && $pa !== $pb) {
                $key = min($pa, $pb).':'.max($pa, $pb);
                $pages[$key] = ($pages[$key] ?? 0) + 1;
            }
        }
        $leaves = [];
        foreach ($pages as $key => $n) {
            if ($n >= self::SAME_LEAF) {
                [$a, $b] = array_map('intval', explode(':', $key));
                $leaves[] = ['a' => $a, 'b' => $b, 'items' => $n];
            }
        }
        foreach ($links as &$l) {
            [$pa, $pb] = $page($l);
            if (isset($pages[min($pa, $pb).':'.max($pa, $pb)]) && $pages[min($pa, $pb).':'.max($pa, $pb)] >= self::SAME_LEAF) {
                $l['kind'] = 'same_leaf';
            }
        }
        unset($l);

        return ['texts' => count($grams), 'links' => $links, 'leaves' => $leaves];
    }

    /**
     * Stored same-story links between records of this set (same-leaf links
     * left out: they are one physical item), strongest first.
     *
     * @param int[] $ids
     *
     * @return list<array{a: string, b: string, overlap: float}>
     */
    public static function linksAmong(array $ids): array
    {
        if (!$ids || !DB::schema()->hasTable('ric_same_story_link')) {
            return [];
        }
        $in = array_flip($ids);
        $out = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            foreach (DB::table('ric_same_story_link')->where('kind', 'same_story')->whereIn('object_a_id', $chunk)->get() as $l) {
                if (isset($in[(int) $l->object_b_id])) {
                    $out[] = ['a' => (string) $l->object_a_id, 'b' => (string) $l->object_b_id, 'overlap' => (float) $l->overlap];
                }
            }
        }
        usort($out, fn ($x, $y) => $y['overlap'] <=> $x['overlap']);

        return $out;
    }

    /** @return array<string, true> the word 6-grams of a text */
    public static function grams(string $text): array
    {
        preg_match_all('/[a-z]+/', mb_strtolower($text), $m);
        $w = $m[0];
        $out = [];
        for ($i = 0, $n = count($w) - self::N; $i <= $n; ++$i) {
            $out[implode(' ', array_slice($w, $i, self::N))] = true;
        }

        return $out;
    }

    /**
     * Each record's text: its digital object's OCR transcript, else its scope
     * and content.
     *
     * @param int[] $ids
     *
     * @return array<int, string>
     */
    private static function texts(array $ids): array
    {
        $out = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            $rows = DB::table('information_object_i18n as i')
                ->join('information_object as io', fn ($j) => $j->on('io.id', '=', 'i.id')->on('i.culture', '=', 'io.source_culture'))
                ->whereIn('i.id', $chunk)->whereNotNull('i.scope_and_content')->get(['i.id', 'i.scope_and_content']);
            foreach ($rows as $r) {
                $out[(int) $r->id] = strip_tags((string) $r->scope_and_content);
            }
            $transcripts = DB::table('digital_object as d')
                ->join('property as p', fn ($j) => $j->on('p.object_id', '=', 'd.id')->where('p.name', 'transcript'))
                ->join('property_i18n as pi', 'pi.id', '=', 'p.id')
                ->whereIn('d.object_id', $chunk)->get(['d.object_id', 'pi.value']);
            foreach ($transcripts as $r) {
                if ('' !== trim((string) $r->value)) {
                    $out[(int) $r->object_id] = (string) $r->value;
                }
            }
        }

        return $out;
    }
}
