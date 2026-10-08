<?php

namespace AhgInformationObjectManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Sort the records below a description in one action (#205, 2020 wish list #55).
 *
 * Every level of the branch is sorted by the chosen key - identifier in number
 * order ("2" before "10"), title, or earliest date - and the nested set (lft and
 * rgt) is laid out again in the same span, so nothing outside the branch moves.
 * Only rows whose position changed are written, in one transaction. The search
 * index keeps each record's lft for ordering, so changed records are updated
 * there too (or, for very large branches, left to search:populate).
 */
class SortChildrenService
{
    public const KEYS = ['identifier', 'title', 'date'];

    /** Above this many moved records, the search index is left to search:populate. */
    public const REINDEX_LIMIT = 2000;

    /**
     * The new order of the first level, and how many records would move.
     *
     * @return array{children: array, records: int, moved: int}
     */
    public static function plan(int $rootId, string $key, string $direction, string $culture): array
    {
        [$root, $nodes, $children] = self::load($rootId, $culture);
        $layout = self::layout($root, $nodes, $children, $key, $direction);

        $first = [];
        $old = $children[$rootId] ?? [];
        foreach (self::sorted($old, $nodes, $key, $direction) as $i => $id) {
            $first[] = [
                'id' => $id,
                'identifier' => $nodes[$id]['identifier'],
                'title' => $nodes[$id]['title'],
                'date' => $nodes[$id]['date'],
                'from' => array_search($id, $old, true) + 1,
                'to' => $i + 1,
            ];
        }

        return ['children' => $first, 'records' => count($nodes), 'moved' => count($layout)];
    }

    /**
     * Sort the branch.
     *
     * @return array{moved: int, reindexed: int, deferred: int}
     */
    public static function apply(int $rootId, string $key, string $direction, string $culture, ?callable $reindex = null): array
    {
        $reindex = $reindex ?? static function (int $id, int $lft): bool {
            $io = \QubitInformationObject::getById($id);
            if (null === $io) {
                return false;
            }
            \QubitSearch::getInstance()->partialUpdate($io, ['lft' => $lft]);

            return true;
        };

        [$root, $nodes, $children] = self::load($rootId, $culture);
        $layout = self::layout($root, $nodes, $children, $key, $direction);
        if (!$layout) {
            return ['moved' => 0, 'reindexed' => 0, 'deferred' => 0];
        }

        DB::connection()->transaction(function () use ($layout) {
            foreach (array_chunk($layout, 500, true) as $chunk) {
                $lft = $rgt = '';
                foreach ($chunk as $id => [$l, $r]) {
                    $lft .= ' WHEN '.(int) $id.' THEN '.(int) $l;
                    $rgt .= ' WHEN '.(int) $id.' THEN '.(int) $r;
                }
                DB::table('information_object')->whereIn('id', array_keys($chunk))->update([
                    'lft' => DB::raw('CASE id'.$lft.' END'),
                    'rgt' => DB::raw('CASE id'.$rgt.' END'),
                ]);
            }
        });

        $reindexed = 0;
        if (count($layout) <= self::REINDEX_LIMIT) {
            foreach ($layout as $id => [$l]) {
                if ($reindex((int) $id, (int) $l)) {
                    ++$reindexed;
                }
            }
        }

        return ['moved' => count($layout), 'reindexed' => $reindexed, 'deferred' => count($layout) - $reindexed];
    }

    /** @return array{0: object, 1: array<int, array>, 2: array<int, int[]>} root, nodes by id, child ids by parent in current order */
    private static function load(int $rootId, string $culture): array
    {
        $root = DB::table('information_object')->where('id', $rootId)->first(['id', 'lft', 'rgt']);
        if (!$root || $rootId <= 1) {
            throw new \RuntimeException('Description not found');
        }

        $rows = DB::table('information_object as io')
            ->join('information_object_i18n as src', function ($j) {
                $j->on('src.id', '=', 'io.id')->on('src.culture', '=', 'io.source_culture');
            })
            ->leftJoin('information_object_i18n as cur', function ($j) use ($culture) {
                $j->on('cur.id', '=', 'io.id')->where('cur.culture', '=', $culture);
            })
            ->where('io.lft', '>', $root->lft)->where('io.rgt', '<', $root->rgt)
            ->orderBy('io.lft')
            ->get(['io.id', 'io.parent_id', 'io.lft', 'io.rgt', 'io.identifier', DB::raw('COALESCE(cur.title, src.title) as title')]);

        $dates = [];
        if (count($rows)) {
            foreach (DB::table('event')->whereIn('object_id', $rows->pluck('id')->all())->whereNotNull('start_date')
                ->groupBy('object_id')->select('object_id', DB::raw('MIN(start_date) as d'))->get() as $e) {
                $dates[(int) $e->object_id] = (string) $e->d;
            }
        }

        $nodes = [];
        $children = [];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            $nodes[$id] = ['lft' => (int) $row->lft, 'rgt' => (int) $row->rgt, 'identifier' => (string) $row->identifier,
                'title' => (string) $row->title, 'date' => $dates[$id] ?? null];
            $children[(int) $row->parent_id][] = $id;
        }

        return [$root, $nodes, $children];
    }

    /** @return int[] ids in the new order; ties keep their current order */
    private static function sorted(array $ids, array $nodes, string $key, string $direction): array
    {
        $pos = array_flip($ids);
        usort($ids, static function ($a, $b) use ($nodes, $key, $direction, $pos) {
            $x = $nodes[$a];
            $y = $nodes[$b];
            switch ($key) {
                case 'date':
                    // Undated records go last whichever way the dates run.
                    if (null === $x['date'] || null === $y['date']) {
                        $c = (null === $x['date']) <=> (null === $y['date']);

                        return $c ?: $pos[$a] <=> $pos[$b];
                    }
                    $c = strcmp($x['date'], $y['date']);

                    break;
                case 'title':
                    $c = strnatcasecmp($x['title'], $y['title']);

                    break;
                default:
                    $c = strnatcasecmp($x['identifier'], $y['identifier']);
            }
            if ('desc' === $direction) {
                $c = -$c;
            }

            return $c ?: $pos[$a] <=> $pos[$b];
        });

        return $ids;
    }

    /** @return array<int, array{0: int, 1: int}> id => [lft, rgt] for the records that move */
    private static function layout(object $root, array $nodes, array $children, string $key, string $direction): array
    {
        if (!in_array($key, self::KEYS, true)) {
            throw new \RuntimeException('Unknown sort key');
        }
        $new = [];
        $walk = function (int $id, int $lft) use (&$walk, &$new, $children, $nodes, $key, $direction): int {
            $cursor = $lft + 1;
            foreach (self::sorted($children[$id] ?? [], $nodes, $key, $direction) as $child) {
                $cursor = $walk($child, $cursor) + 1;
            }
            $new[$id] = [$lft, $cursor];

            return $cursor;
        };
        $end = $walk((int) $root->id, (int) $root->lft);
        if ($end !== (int) $root->rgt) {
            throw new \RuntimeException('The tree below this description is inconsistent; nothing was changed. Rebuild the nested set first.');
        }
        unset($new[(int) $root->id]);

        $changed = [];
        foreach ($new as $id => [$l, $r]) {
            if ($nodes[$id]['lft'] !== $l || $nodes[$id]['rgt'] !== $r) {
                $changed[$id] = [$l, $r];
            }
        }

        return $changed;
    }
}
