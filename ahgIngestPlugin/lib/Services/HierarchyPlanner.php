<?php

namespace AhgIngestPlugin\Services;

/**
 * Order and check the rows of a hierarchical CSV (legacyId / parentId).
 *
 * The commit creates records one at a time and a child can only be placed
 * under a parent that already exists, so parents must come first whatever the
 * order of the file. order() is a stable topological sort: rows keep their file
 * order except where a child has to wait for its parent. problems() finds what
 * no order can fix: a row that is its own parent, and rows whose parent chain
 * loops back on itself.
 *
 * Rows are objects or arrays with row_number, legacy_id and parent_id_ref. A
 * parent_id_ref that is not a legacyId in the batch (an existing slug, or
 * empty) puts no constraint on the order.
 */
class HierarchyPlanner
{
    /**
     * @param array<object|array> $rows
     *
     * @return array<object|array> the same rows, parents before children
     */
    public static function order(array $rows): array
    {
        [$byLegacy, $parentOf] = self::index($rows);

        $out = [];
        $placed = [];
        $visiting = [];
        $visit = function ($i) use (&$visit, &$out, &$placed, &$visiting, $rows, $byLegacy, $parentOf) {
            if (isset($placed[$i]) || isset($visiting[$i])) {
                return; // already out, or a loop (reported by problems(), left in file order)
            }
            $visiting[$i] = true;
            $p = $parentOf[$i];
            if (null !== $p && isset($byLegacy[$p]) && $byLegacy[$p] !== $i) {
                $visit($byLegacy[$p]);
            }
            unset($visiting[$i]);
            if (!isset($placed[$i])) {
                $placed[$i] = true;
                $out[] = $rows[$i];
            }
        };
        foreach (array_keys($rows) as $i) {
            $visit($i);
        }

        return $out;
    }

    /**
     * @param array<object|array> $rows
     *
     * @return array<int, string> row_number => message
     */
    public static function problems(array $rows): array
    {
        [$byLegacy, $parentOf] = self::index($rows);
        $problems = [];

        foreach (array_keys($rows) as $i) {
            $p = $parentOf[$i];
            if (null === $p) {
                continue;
            }
            $legacy = self::get($rows[$i], 'legacy_id');
            if (null !== $legacy && (string) $legacy === $p) {
                $problems[self::get($rows[$i], 'row_number')] = "Row is its own parent (legacyId '{$p}')";

                continue;
            }
            // Walk up; a loop is a parent chain that comes back to this row.
            $seen = [$i => true];
            $at = $i;
            while (null !== ($q = $parentOf[$at]) && isset($byLegacy[$q])) {
                $at = $byLegacy[$q];
                if (isset($seen[$at])) {
                    if ($at === $i) {
                        $problems[self::get($rows[$i], 'row_number')] = "Parent chain loops back to this row (via legacyId '{$p}')";
                    }

                    break;
                }
                $seen[$at] = true;
            }
        }

        return $problems;
    }

    /** @return array{0: array<string, int>, 1: array<int, ?string>} legacyId => index, index => parent ref */
    private static function index(array $rows): array
    {
        $byLegacy = [];
        $parentOf = [];
        foreach ($rows as $i => $r) {
            $legacy = self::get($r, 'legacy_id');
            if (null !== $legacy && '' !== (string) $legacy && !isset($byLegacy[(string) $legacy])) {
                $byLegacy[(string) $legacy] = $i;
            }
            $ref = self::get($r, 'parent_id_ref');
            $parentOf[$i] = (null === $ref || '' === trim((string) $ref)) ? null : trim((string) $ref);
        }

        return [$byLegacy, $parentOf];
    }

    private static function get($row, string $key)
    {
        return is_array($row) ? ($row[$key] ?? null) : ($row->{$key} ?? null);
    }
}
