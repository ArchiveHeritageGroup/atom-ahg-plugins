<?php

/**
 * Subtree selection, sizing and file resolution for ahgSubtreeExportPlugin.
 *
 * TARGET: AtoM 2.8.1. Uses QubitPdo throughout - the Illuminate query builder that
 * the 2.10 ahgPortableExportPlugin relies on does not exist here, and that single
 * dependency was the only thing making that plugin 2.10-only.
 */
class SubtreeExportService
{
    /** AtoM digital object usage ids. Literals because QubitTerm::MASTER_ID and
     *  friends are not safe to reference from a task before the context boots. */
    public const USAGE_MASTER = 140;
    public const USAGE_REFERENCE = 141;
    public const USAGE_THUMBNAIL = 142;

    /**
     * Resolve the start record and the ancestor that bounds the walk.
     *
     * NOT a descendant walk. smt-les-hab1-1 on RARI is a leaf (lft 276585, rgt
     * 276586), so "this record and its descendants" returns exactly one row. What is
     * wanted is this record and the next N onwards in tree order.
     *
     * The bound defaults to the start record's parent. Without a bound the walk runs
     * past the end of its collection and starts exporting the next one, which is a
     * silent wrong answer rather than an error - on RARI the start sits inside
     * smits-lucas-3 (253322-284497) and a thousand records fit only because the
     * bound holds them there.
     *
     * @param null|string $boundSlug explicit bound; null uses the parent
     */
    public static function resolveStart(string $slug, ?string $boundSlug = null): ?array
    {
        $row = QubitPdo::fetchOne(
            'SELECT io.id, io.lft, io.parent_id
               FROM slug s
               JOIN information_object io ON io.id = s.object_id
              WHERE s.slug = ?',
            [$slug]
        );

        if (false === $row) {
            return null;
        }

        $bound = null === $boundSlug
            ? QubitPdo::fetchOne(
                'SELECT p.id, p.lft, p.rgt, ps.slug
                   FROM information_object p
                   JOIN slug ps ON ps.object_id = p.id
                  WHERE p.id = ?',
                [(int) $row->parent_id]
            )
            : QubitPdo::fetchOne(
                'SELECT p.id, p.lft, p.rgt, ps.slug
                   FROM slug ps
                   JOIN information_object p ON p.id = ps.object_id
                  WHERE ps.slug = ?',
                [$boundSlug]
            );

        if (false === $bound) {
            return null;
        }

        // A bound that does not contain the start record would silently export
        // nothing, which reads as "no matching records" rather than "wrong bound".
        if ((int) $row->lft < (int) $bound->lft || (int) $row->lft > (int) $bound->rgt) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'lft' => (int) $row->lft,
            'bound_id' => (int) $bound->id,
            'bound_slug' => $bound->slug,
            'bound_lft' => (int) $bound->lft,
            'bound_rgt' => (int) $bound->rgt,
        ];
    }

    /**
     * The start record and the next N onwards, in tree order, inside the bound.
     *
     * lft ordering makes the slice contiguous and therefore resumable: the last lft
     * of one batch is the cursor for the next.
     *
     * @param null|int $afterLft cursor for the next batch; null starts at the record
     */
    public static function selectItems(array $start, int $limit, ?int $afterLft = null): array
    {
        $from = null === $afterLft ? $start['lft'] : $afterLft;
        $op = null === $afterLft ? '>=' : '>';

        return QubitPdo::fetchAll(
            "SELECT io.id, io.lft, io.rgt, io.identifier, s.slug
               FROM information_object io
               JOIN slug s ON s.object_id = io.id
              WHERE io.lft {$op} ? AND io.lft <= ?
              ORDER BY io.lft LIMIT ".max(1, $limit),
            [$from, $start['bound_rgt']]
        );
    }

    /** Which usage ids a job wants, from its include_* flags. */
    public static function usagesFor(array $flags): array
    {
        $usages = [];

        if (!empty($flags['masters'])) {
            $usages[] = self::USAGE_MASTER;
        }
        if (!empty($flags['references'])) {
            $usages[] = self::USAGE_REFERENCE;
        }
        if (!empty($flags['thumbnails'])) {
            $usages[] = self::USAGE_THUMBNAIL;
        }

        return $usages;
    }

    /**
     * Digital objects for a set of descriptions, restricted to the wanted usages.
     *
     * Derivatives hang off their master by parent_id rather than carrying the
     * description's object_id, so they are reached through the master. Selecting on
     * object_id alone silently returns masters only - which is exactly the bug that
     * would make "include references" appear to work and produce nothing.
     */
    public static function filesFor(array $ioIds, array $usages): array
    {
        if (empty($ioIds) || empty($usages)) {
            return [];
        }

        $ioIn = implode(',', array_map('intval', $ioIds));
        $useIn = implode(',', array_map('intval', $usages));

        return QubitPdo::fetchAll(
            "SELECT d.id, d.usage_id, d.path, d.name, d.byte_size, d.checksum,
                    COALESCE(d.object_id, m.object_id) AS information_object_id
               FROM digital_object d
          LEFT JOIN digital_object m ON m.id = d.parent_id
              WHERE COALESCE(d.object_id, m.object_id) IN ({$ioIn})
                AND d.usage_id IN ({$useIn})
              ORDER BY COALESCE(d.object_id, m.object_id), d.usage_id"
        );
    }

    /**
     * Absolute path of a stored file.
     *
     * digital_object.path is root-relative and its trailing slash is inconsistent
     * across AtoM versions, so both ends are normalised rather than concatenated
     * and hoped for.
     */
    public static function absolutePath(string $atomRoot, object $file): string
    {
        return rtrim($atomRoot, '/').'/'.trim((string) $file->path, '/').'/'.ltrim((string) $file->name, '/');
    }

    /**
     * Count and byte total before anything is written.
     *
     * Worth running always: masters on a real instance reach several GB each, so a
     * thousand-description subtree is anywhere between a few GB and hundreds, and
     * that is not a number to discover halfway through a copy.
     */
    public static function estimate(array $start, int $maxItems, array $usages, ?int $afterLft = null): array
    {
        $items = self::selectItems($start, $maxItems, $afterLft);

        if (empty($items)) {
            return ['items' => 0, 'files' => 0, 'bytes' => 0, 'last_lft' => $afterLft];
        }

        $ids = array_map(static function ($r) { return (int) $r->id; }, $items);
        $files = self::filesFor($ids, $usages);

        $bytes = 0;
        foreach ($files as $f) {
            $bytes += (int) $f->byte_size;
        }

        return [
            'items' => count($items),
            'files' => count($files),
            'bytes' => $bytes,
            'last_lft' => (int) end($items)->lft,
        ];
    }

    /** Bytes as something a person can read in a confirmation prompt. */
    public static function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $n = (float) $bytes;

        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            ++$i;
        }

        return sprintf('%.1f %s', $n, $units[$i]);
    }
}
