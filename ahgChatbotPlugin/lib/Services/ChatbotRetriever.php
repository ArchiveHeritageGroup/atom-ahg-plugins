<?php

namespace AhgChatbotPlugin\Services;

use AtomExtensions\Services\Search\SearchAccessFilterService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Finds the catalogue records a question is about, and only ones this visitor
 * may see.
 *
 * Two paths, merged semantic-first: gateway-fed vector search (Qdrant) and
 * MySQL FULLTEXT on title and scope. Neither index decides visibility. Every
 * hit is re-checked when it is used (visibleOnly), because the vector index can
 * hold records unpublished or restricted since it was built.
 */
class ChatbotRetriever
{
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED_ID = 160;
    private const MASTER_USAGE_ID = 140;
    private const THUMBNAIL_USAGE_ID = 142;

    /** Records handed to the model. */
    public const MAX_RECORDS = 6;

    /** Fetched per path before filtering, so dropped hits still leave enough. */
    private const CANDIDATES = 18;

    /**
     * @param string|null $pageSlug slug of the record the visitor is looking at;
     *                              when visible it leads the context, so "who
     *                              made this?" means this record
     *
     * @return array<int,object> rows: id, identifier, title, scope_and_content, slug, thumbnail
     */
    public static function retrieve(string $question, string $culture, ?int $userId, ?string $pageSlug = null): array
    {
        $ids = [];
        if (null !== $pageSlug && '' !== $pageSlug) {
            $pageId = (int) DB::table('slug')->where('slug', $pageSlug)->value('object_id');
            if ($pageId > 1) {
                $ids[] = $pageId;
            }
        }

        try {
            $ids = array_merge($ids, (new ChatbotVectorIndex())->search($question, self::CANDIDATES));
        } catch (\Throwable $e) {
            error_log('chatbot.semantic_failed: '.$e->getMessage());
        }
        $ids = array_merge($ids, self::fulltextIds($question, $culture));

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $visible = array_slice(self::visibleOnly($ids, $userId), 0, self::MAX_RECORDS);

        return self::hydrate($visible, $culture);
    }

    /**
     * Keep only ids this user may see: published, and not restricted by
     * security classification, donor agreement or full embargo. Order is kept.
     *
     * FAILS CLOSED: if visibility cannot be established, nothing is returned.
     * The alternative is a public assistant quoting a record it should never
     * have seen.
     *
     * @param int[] $ids
     *
     * @return int[]
     */
    public static function visibleOnly(array $ids, ?int $userId): array
    {
        if ([] === $ids) {
            return [];
        }

        try {
            $published = array_flip(array_map('intval', DB::table('status')
                ->whereIn('object_id', $ids)
                ->where('type_id', self::PUBLICATION_STATUS_TYPE_ID)
                ->where('status_id', self::PUBLICATION_STATUS_PUBLISHED_ID)
                ->pluck('object_id')->all()));

            $restricted = array_flip(array_map('intval', array_merge(
                SearchAccessFilterService::getInstance()->getRestrictedObjectIds($userId),
                self::icipBlocked($ids),
                self::odrlBlocked($ids)
            )));

            return array_values(array_filter($ids, static fn ($id) => isset($published[$id]) && !isset($restricted[$id])));
        } catch (\Throwable $e) {
            error_log('chatbot.visibility_check_failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Ids under an active ICIP (Indigenous cultural and intellectual property)
     * protection: an access restriction of any type, or a cultural notice
     * whose type blocks access - on the record itself, or on an ancestor whose
     * entry applies to descendants. SearchAccessFilterService does not cover
     * these. The chatbot cannot run the notice-acknowledgement step, so it
     * leaves such records out for everyone.
     *
     * Reads ahgICIPPlugin's tables when they exist; throws on any other
     * failure so visibleOnly() fails closed.
     *
     * @param int[] $ids
     *
     * @return int[]
     */
    private static function icipBlocked(array $ids): array
    {
        $schema = DB::getSchemaBuilder();
        if (!$schema->hasTable('icip_access_restriction')) {
            return [];
        }

        $today = date('Y-m-d');
        $active = static function ($q, string $alias) use ($today) {
            $q->where(static fn ($w) => $w->whereNull("{$alias}.start_date")->orWhere("{$alias}.start_date", '<=', $today))
                ->where(static fn ($w) => $w->whereNull("{$alias}.end_date")->orWhere("{$alias}.end_date", '>=', $today));
        };
        // The candidate, or an ancestor (nested set) whose entry covers descendants.
        $covering = static fn (string $table, string $alias) => DB::table('information_object as c')
            ->join('information_object as a', static fn ($j) => $j->on('a.lft', '<=', 'c.lft')->on('a.rgt', '>=', 'c.rgt'))
            ->join("{$table} as {$alias}", "{$alias}.information_object_id", '=', 'a.id')
            ->whereIn('c.id', $ids)
            ->where(static fn ($w) => $w->whereColumn('a.id', 'c.id')->orWhere("{$alias}.applies_to_descendants", 1));

        $blocked = $covering('icip_access_restriction', 'r')
            ->where(static fn ($q) => $active($q, 'r'))
            ->distinct()->pluck('c.id')->all();

        if ($schema->hasTable('icip_cultural_notice')) {
            $blocked = array_merge($blocked, $covering('icip_cultural_notice', 'n')
                ->join('icip_cultural_notice_type as t', 't.id', '=', 'n.notice_type_id')
                ->where('t.is_active', 1)->where('t.blocks_access', 1)
                ->where(static fn ($q) => $active($q, 'n'))
                ->distinct()->pluck('c.id')->all());
        }

        return array_map('intval', $blocked);
    }

    /**
     * Ids carrying an ODRL "use" prohibition (ahgResearchPlugin's
     * research_rights_policy) - the same rule ahgPortableExportPlugin's
     * DisclosureGate applies. Reads the table when it exists; throws on any
     * other failure so visibleOnly() fails closed.
     *
     * @param int[] $ids
     *
     * @return int[]
     */
    private static function odrlBlocked(array $ids): array
    {
        if (!DB::getSchemaBuilder()->hasTable('research_rights_policy')) {
            return [];
        }

        return array_map('intval', DB::table('research_rights_policy')
            ->whereIn('target_type', ['archival_description', 'information_object'])
            ->where('policy_type', 'prohibition')
            ->where('action_type', 'use')
            ->whereIn('target_id', $ids)
            ->pluck('target_id')->all());
    }

    /**
     * MySQL FULLTEXT does not stem, so "photographs" misses "photograph". When
     * the plain query finds nothing, retry with each word cut to a prefix
     * (photograph*, mushroom*) in boolean mode.
     *
     * @return int[]
     */
    private static function fulltextIds(string $question, string $culture): array
    {
        try {
            $ids = self::match($question, $culture, 'NATURAL LANGUAGE MODE');
            if ([] !== $ids) {
                return $ids;
            }

            $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY);
            $prefixes = array_unique(array_map(
                static fn ($w) => preg_replace('/(ies|es|s)$/u', '', $w).'*',
                array_filter($words, static fn ($w) => mb_strlen($w) >= 4)
            ));

            return [] === $prefixes ? [] : self::match(implode(' ', $prefixes), $culture, 'BOOLEAN MODE');
        } catch (\Throwable $e) {
            error_log('chatbot.fulltext_failed: '.$e->getMessage());

            return [];
        }
    }

    /** @return int[] */
    private static function match(string $terms, string $culture, string $mode): array
    {
        return DB::table('information_object_i18n as ioi')
            ->where('ioi.culture', $culture)
            ->where('ioi.id', '>', 1)
            ->whereRaw("(MATCH(ioi.title) AGAINST(? IN {$mode}) OR MATCH(ioi.scope_and_content) AGAINST(? IN {$mode}))", [$terms, $terms])
            ->orderByRaw("(MATCH(ioi.title) AGAINST(? IN {$mode}) * 2 + MATCH(ioi.scope_and_content) AGAINST(? IN {$mode})) DESC", [$terms, $terms])
            ->limit(self::CANDIDATES)
            ->pluck('ioi.id')->all();
    }

    /**
     * @param int[] $ids already filtered, in rank order
     *
     * @return array<int,object>
     */
    private static function hydrate(array $ids, string $culture): array
    {
        if ([] === $ids) {
            return [];
        }

        $rows = DB::table('information_object as io')
            ->join('slug as s', 's.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', static function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'io.id')->where('ioi.culture', $culture);
            })
            ->leftJoin('digital_object as do', static function ($j) {
                $j->on('do.object_id', '=', 'io.id')->where('do.usage_id', self::MASTER_USAGE_ID);
            })
            ->leftJoin('digital_object as dt', static function ($j) {
                $j->on('dt.parent_id', '=', 'do.id')->where('dt.usage_id', self::THUMBNAIL_USAGE_ID);
            })
            ->whereIn('io.id', $ids)
            ->get(['io.id', 'io.identifier', 'ioi.title', 'ioi.scope_and_content', 's.slug', 'dt.path as thumb_path', 'dt.name as thumb_name'])
            ->keyBy('id');

        $out = [];
        foreach ($ids as $id) {
            if (!isset($rows[$id])) {
                continue;
            }
            $r = $rows[$id];
            $r->thumbnail = $r->thumb_path && $r->thumb_name ? $r->thumb_path.$r->thumb_name : null;
            unset($r->thumb_path, $r->thumb_name);
            $out[] = $r;
        }

        return $out;
    }
}
