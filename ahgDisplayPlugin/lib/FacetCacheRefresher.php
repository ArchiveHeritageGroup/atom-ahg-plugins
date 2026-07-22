<?php

/**
 * Shared display-facet-cache refresher.
 *
 * The SQL lives here (not in the sfBaseTask) so it can be driven two ways:
 *
 *  1. The symfony task `ahg:refresh-facet-cache` (interactive / manual use).
 *  2. A standalone Illuminate-only runner, `bin/refresh-facet-cache.php`.
 *
 * WHY THE SPLIT MATTERS: running `php symfony <task>` boots the full prod
 * application configuration from the CLI, which on hosts with
 * opcache.validate_timestamps=0 can leave the web runtime pinned to a broken
 * compiled config cache (site-wide HTTP 500 until a config-cache clear + fpm
 * reload). The standalone runner boots ONLY the Illuminate DB layer
 * (atom-framework/bootstrap.php) and never touches the Symfony config cache, so
 * it is safe to call from cron and from the demo seed scripts. Always prefer the
 * standalone runner for anything automated. See the ahgDisplayPlugin bin script.
 *
 * All methods take a PDO connection so no Symfony database manager is required.
 */
class FacetCacheRefresher
{
    /** Publication status type / published status ids (base AtoM constants). */
    const PUB_TYPE_ID = 158;
    const PUB_STATUS_ID = 160;

    /**
     * Rebuild the whole display_facet_cache table (published + all-records sets).
     *
     * @param PDO           $conn PDO connection (e.g. DB::connection()->getPdo())
     * @param callable|null $log  optional fn(string $msg) for progress output
     *
     * @return int total rows written
     */
    public static function refresh(PDO $conn, ?callable $log = null): int
    {
        $log = $log ?? static function ($m) {};

        $conn->exec('TRUNCATE TABLE display_facet_cache');
        $log('Cleared existing cache');

        $facetSets = [
            ['suffix' => '', 'label' => 'published', 'publishedOnly' => true],
            ['suffix' => '_all', 'label' => 'all', 'publishedOnly' => false],
        ];

        foreach ($facetSets as $set) {
            $s = $set['suffix'];
            $pub = $set['publishedOnly'];
            $log("--- Refreshing {$set['label']} facets ---");

            self::taxonomyFacet($conn, 'subject' . $s, 35, $pub);
            self::taxonomyFacet($conn, 'place' . $s, 42, $pub);
            self::taxonomyFacet($conn, 'genre' . $s, 78, $pub);
            self::levelFacet($conn, 'level' . $s, $pub);
            self::repositoryFacet($conn, 'repository' . $s, $pub);
            self::creatorFacet($conn, 'creator' . $s, $pub);
            self::glamTypeFacet($conn, 'glam_type' . $s, $pub);
            self::mediaTypeFacet($conn, 'media_type' . $s, $pub);
        }

        return (int) $conn->query('SELECT COUNT(*) FROM display_facet_cache')->fetchColumn();
    }

    protected static function taxonomyFacet(PDO $conn, string $facetType, int $taxonomyId, bool $publishedOnly): void
    {
        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = otr.object_id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, t.id, COALESCE(ti.name, 'Unknown'), COUNT(DISTINCT otr.object_id)
            FROM term t
            INNER JOIN object_term_relation otr ON otr.term_id = t.id
            {$statusJoin}
            LEFT JOIN term_i18n ti ON t.id = ti.id AND ti.culture = 'en'
            WHERE t.taxonomy_id = :taxonomy_id
            GROUP BY t.id, ti.name
            HAVING COUNT(DISTINCT otr.object_id) > 0
        ";

        $params = [':facet_type' => $facetType, ':taxonomy_id' => $taxonomyId];
        if ($publishedOnly) {
            $params[':pub_type_id'] = self::PUB_TYPE_ID;
            $params[':pub_status_id'] = self::PUB_STATUS_ID;
        }

        $conn->prepare($sql)->execute($params);
    }

    protected static function levelFacet(PDO $conn, string $facetType, bool $publishedOnly): void
    {
        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = io.id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, t.id, COALESCE(ti.name, 'Unknown'), COUNT(*)
            FROM information_object io
            INNER JOIN term t ON io.level_of_description_id = t.id
            {$statusJoin}
            LEFT JOIN term_i18n ti ON t.id = ti.id AND ti.culture = 'en'
            WHERE io.id > 1
            GROUP BY t.id, ti.name
            HAVING COUNT(*) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = self::PUB_TYPE_ID;
            $params[':pub_status_id'] = self::PUB_STATUS_ID;
        }

        $conn->prepare($sql)->execute($params);
    }

    /**
     * SQL fragment excluding draft/embargoed authority records for the given
     * actor-table alias, or '' when the visibility table is absent. Only applied
     * to the guest (published-only) facet variants.
     */
    protected static function hiddenActorClause(PDO $conn, string $alias): string
    {
        static $exists = null;
        if (null === $exists) {
            try {
                $exists = (bool) $conn->query("SHOW TABLES LIKE 'ahg_actor_visibility'")->fetchColumn();
            } catch (\Throwable $e) {
                $exists = false;
            }
        }
        if (!$exists) {
            return '';
        }

        return " AND {$alias}.id NOT IN (SELECT actor_id FROM ahg_actor_visibility"
            . ' WHERE status = \'draft\' OR (embargo_until IS NOT NULL AND embargo_until > CURDATE()))';
    }

    protected static function repositoryFacet(PDO $conn, string $facetType, bool $publishedOnly): void
    {
        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = io.id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';
        $visClause = $publishedOnly ? self::hiddenActorClause($conn, 'r') : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, r.id, COALESCE(ai.authorized_form_of_name, 'Unknown'), COUNT(*)
            FROM information_object io
            INNER JOIN repository r ON io.repository_id = r.id
            {$statusJoin}
            LEFT JOIN actor_i18n ai ON r.id = ai.id AND ai.culture = 'en'
            WHERE io.id > 1
            {$visClause}
            GROUP BY r.id, ai.authorized_form_of_name
            HAVING COUNT(*) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = self::PUB_TYPE_ID;
            $params[':pub_status_id'] = self::PUB_STATUS_ID;
        }

        $conn->prepare($sql)->execute($params);
    }

    protected static function creatorFacet(PDO $conn, string $facetType, bool $publishedOnly): void
    {
        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = e.object_id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';
        $visClause = $publishedOnly ? self::hiddenActorClause($conn, 'a') : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, a.id, COALESCE(ai.authorized_form_of_name, 'Unknown'), COUNT(DISTINCT e.object_id)
            FROM event e
            INNER JOIN actor a ON e.actor_id = a.id
            {$statusJoin}
            LEFT JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            WHERE e.actor_id IS NOT NULL
            {$visClause}
            GROUP BY a.id, ai.authorized_form_of_name
            HAVING COUNT(DISTINCT e.object_id) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = self::PUB_TYPE_ID;
            $params[':pub_status_id'] = self::PUB_STATUS_ID;
        }

        $conn->prepare($sql)->execute($params);
    }

    protected static function glamTypeFacet(PDO $conn, string $facetType, bool $publishedOnly): void
    {
        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = io.id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, 0, doc.object_type, COUNT(*)
            FROM display_object_config doc
            INNER JOIN information_object io ON doc.object_id = io.id
            {$statusJoin}
            GROUP BY doc.object_type
            HAVING COUNT(*) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = self::PUB_TYPE_ID;
            $params[':pub_status_id'] = self::PUB_STATUS_ID;
        }

        $conn->prepare($sql)->execute($params);
    }

    protected static function mediaTypeFacet(PDO $conn, string $facetType, bool $publishedOnly): void
    {
        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = do.object_id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, 0, SUBSTRING_INDEX(do.mime_type, '/', 1), COUNT(*)
            FROM digital_object do
            {$statusJoin}
            WHERE do.parent_id IS NULL AND do.mime_type IS NOT NULL
            GROUP BY SUBSTRING_INDEX(do.mime_type, '/', 1)
            HAVING COUNT(*) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = self::PUB_TYPE_ID;
            $params[':pub_status_id'] = self::PUB_STATUS_ID;
        }

        $conn->prepare($sql)->execute($params);
    }
}
