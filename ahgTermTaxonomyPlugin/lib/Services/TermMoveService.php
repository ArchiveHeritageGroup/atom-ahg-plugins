<?php

namespace AhgTermTaxonomy\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Move a term, with the terms below it, to another taxonomy (#209, 2020 wish
 * list #50) - for a subject that should have been a place, say.
 *
 * Everything that points at the term (descriptions, authority records, other
 * terms) points at its id, so the links survive the move untouched. The term
 * goes to the top level of the target taxonomy; the terms below it keep their
 * own hierarchy and come with it.
 *
 * Saves go through Propel, which keeps AtoM's nested set and the search index
 * of the terms themselves in step. Descriptions that use the moved terms are
 * then re-indexed, so their facets follow the term into its new taxonomy.
 *
 * Only ordinary taxonomies take part: not the ones AtoM locks
 * (QubitTaxonomy::$lockedTaxonomies), not terms AtoM's own code relies on
 * (QubitTerm::isProtected), and not levels of description.
 */
class TermMoveService
{
    /** Above this many linked descriptions, re-indexing is left to search:populate. */
    public const REINDEX_LIMIT = 500;

    /** Taxonomies AtoM treats as fixed vocabularies, besides the locked ones. */
    private const SYSTEM_TAXONOMIES = [
        \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID,
        \QubitTaxonomy::EVENT_TYPE_ID,
        \QubitTaxonomy::NOTE_TYPE_ID,
    ];

    public static function canMoveFrom(\QubitTerm $term): bool
    {
        return !\QubitTerm::isProtected($term->id) && self::isOrdinaryTaxonomy((int) $term->taxonomyId);
    }

    public static function isOrdinaryTaxonomy(int $taxonomyId): bool
    {
        return $taxonomyId > 0
            && !in_array($taxonomyId, \QubitTaxonomy::$lockedTaxonomies, false)
            && !in_array($taxonomyId, self::SYSTEM_TAXONOMIES, false);
    }

    /** @return array<int, string> id => name of the taxonomies a term may move to */
    public static function targets(\QubitTerm $term, string $culture): array
    {
        $rows = DB::table('taxonomy as t')
            ->join('taxonomy_i18n as i', function ($j) use ($culture) {
                $j->on('i.id', '=', 't.id')->where('i.culture', '=', $culture);
            })
            ->where('t.id', '<>', (int) $term->taxonomyId)
            ->orderBy('i.name')
            ->get(['t.id', 'i.name']);

        $out = [];
        foreach ($rows as $row) {
            if (self::isOrdinaryTaxonomy((int) $row->id) && '' !== trim((string) $row->name)) {
                $out[(int) $row->id] = (string) $row->name;
            }
        }

        return $out;
    }

    /** What a move would touch: the term and its descendants, and the descriptions using them. */
    public static function impact(\QubitTerm $term): array
    {
        $ids = self::subtreeIds($term);

        return [
            'terms' => count($ids),
            'descriptions' => (int) DB::table('object_term_relation as r')
                ->join('information_object as io', 'io.id', '=', 'r.object_id')
                ->whereIn('r.term_id', $ids)->distinct()->count('r.object_id'),
        ];
    }

    /**
     * Move the term and its descendants. Runs inside AtoM's request transaction
     * (Propel), so a failure part-way rolls the whole move back.
     *
     * @return array{terms: int, reindexed: int, deferred: int}
     */
    public static function move(\QubitTerm $term, int $targetTaxonomyId): array
    {
        if (!self::canMoveFrom($term)) {
            throw new \RuntimeException('This term cannot be moved: AtoM relies on it or on its taxonomy.');
        }
        if ((int) $term->taxonomyId === $targetTaxonomyId || !self::isOrdinaryTaxonomy($targetTaxonomyId)
            || null === \QubitTaxonomy::getById($targetTaxonomyId)) {
            throw new \RuntimeException('Choose another ordinary taxonomy to move the term to.');
        }

        $ids = self::subtreeIds($term);

        $term->parentId = \QubitTerm::ROOT_ID;
        $term->taxonomyId = $targetTaxonomyId;
        $term->save();

        foreach ($ids as $id) {
            if ($id === (int) $term->id) {
                continue;
            }
            $child = \QubitTerm::getById($id);
            if (null !== $child) {
                $child->taxonomyId = $targetTaxonomyId;
                $child->save();
            }
        }

        $descriptionIds = DB::table('object_term_relation as r')
            ->join('information_object as io', 'io.id', '=', 'r.object_id')
            ->whereIn('r.term_id', $ids)->distinct()->pluck('r.object_id')->all();

        $reindexed = 0;
        if (count($descriptionIds) <= self::REINDEX_LIMIT) {
            foreach ($descriptionIds as $id) {
                $io = \QubitInformationObject::getById((int) $id);
                if (null !== $io) {
                    \QubitSearch::getInstance()->update($io);
                    ++$reindexed;
                }
            }
        }

        return ['terms' => count($ids), 'reindexed' => $reindexed, 'deferred' => count($descriptionIds) - $reindexed];
    }

    /** @return int[] the term and every term below it, from the nested set */
    private static function subtreeIds(\QubitTerm $term): array
    {
        $row = DB::table('term')->where('id', (int) $term->id)->first(['lft', 'rgt']);
        if (!$row) {
            return [(int) $term->id];
        }

        return array_map('intval', DB::table('term')->whereBetween('lft', [(int) $row->lft, (int) $row->rgt])->pluck('id')->all());
    }
}
