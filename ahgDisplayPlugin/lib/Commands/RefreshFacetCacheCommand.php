<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Refresh the display facet cache.
 *
 * Stores two sets of counts:
 * - Published only (facet_type = 'subject', 'place', etc.) for guest users
 * - All records (facet_type = 'subject_all', 'place_all', etc.) for authenticated users
 */
class RefreshFacetCacheCommand extends BaseCommand
{
    protected string $name = 'ahg:refresh-facet-cache';
    protected string $description = 'Refresh display facet cache';
    protected string $detailedDescription = <<<'EOF'
    Refreshes the cached facet counts for the display browse page,
    both guest (published only) and authenticated (all records) sets.
    EOF;

    protected function handle(): int
    {
        $pdo = DB::connection()->getPdo();

        $this->info('Starting facet cache refresh...');
        $startTime = microtime(true);

        // Publication status constants
        $pubTypeId = 158;    // Publication status type
        $pubStatusId = 160;  // Published status

        // Clear existing cache
        $pdo->exec('TRUNCATE TABLE display_facet_cache');
        $this->line('  Cleared existing cache');

        // Refresh both published-only and all-records facets
        $facetSets = [
            ['suffix' => '', 'label' => 'published', 'publishedOnly' => true],
            ['suffix' => '_all', 'label' => 'all', 'publishedOnly' => false],
        ];

        foreach ($facetSets as $set) {
            $s = $set['suffix'];
            $pub = $set['publishedOnly'];
            $lbl = $set['label'];

            $this->info("--- Refreshing {$lbl} facets ---");

            $this->refreshTaxonomyFacet($pdo, 'subject' . $s, 35, $pubTypeId, $pubStatusId, $pub);
            $this->refreshTaxonomyFacet($pdo, 'place' . $s, 42, $pubTypeId, $pubStatusId, $pub);
            $this->refreshTaxonomyFacet($pdo, 'genre' . $s, 78, $pubTypeId, $pubStatusId, $pub);
            $this->refreshLevelFacet($pdo, 'level' . $s, $pubTypeId, $pubStatusId, $pub);
            $this->refreshRepositoryFacet($pdo, 'repository' . $s, $pubTypeId, $pubStatusId, $pub);
            $this->refreshCreatorFacet($pdo, 'creator' . $s, $pubTypeId, $pubStatusId, $pub);
            $this->refreshGlamTypeFacet($pdo, 'glam_type' . $s, $pubTypeId, $pubStatusId, $pub);
            $this->refreshMediaTypeFacet($pdo, 'media_type' . $s, $pubTypeId, $pubStatusId, $pub);
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $count = $pdo->query('SELECT COUNT(*) FROM display_facet_cache')->fetchColumn();
        $this->success("Cache refresh complete: {$count} entries in {$elapsed}s");

        return 0;
    }

    private function refreshTaxonomyFacet(\PDO $pdo, string $facetType, int $taxonomyId, int $pubTypeId, int $pubStatusId, bool $publishedOnly): void
    {
        $this->line("  Refreshing {$facetType} facet (taxonomy {$taxonomyId})...");

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
            $params[':pub_type_id'] = $pubTypeId;
            $params[':pub_status_id'] = $pubStatusId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        $this->line("    Added {$count} entries");
    }

    private function refreshLevelFacet(\PDO $pdo, string $facetType, int $pubTypeId, int $pubStatusId, bool $publishedOnly): void
    {
        $this->line("  Refreshing {$facetType} facet...");

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
            $params[':pub_type_id'] = $pubTypeId;
            $params[':pub_status_id'] = $pubStatusId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        $this->line("    Added {$count} entries");
    }

    private function refreshRepositoryFacet(\PDO $pdo, string $facetType, int $pubTypeId, int $pubStatusId, bool $publishedOnly): void
    {
        $this->line("  Refreshing {$facetType} facet...");

        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = io.id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, r.id, COALESCE(ai.authorized_form_of_name, 'Unknown'), COUNT(*)
            FROM information_object io
            INNER JOIN repository r ON io.repository_id = r.id
            {$statusJoin}
            LEFT JOIN actor_i18n ai ON r.id = ai.id AND ai.culture = 'en'
            WHERE io.id > 1
            GROUP BY r.id, ai.authorized_form_of_name
            HAVING COUNT(*) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = $pubTypeId;
            $params[':pub_status_id'] = $pubStatusId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        $this->line("    Added {$count} entries");
    }

    private function refreshCreatorFacet(\PDO $pdo, string $facetType, int $pubTypeId, int $pubStatusId, bool $publishedOnly): void
    {
        $this->line("  Refreshing {$facetType} facet...");

        $statusJoin = $publishedOnly
            ? 'INNER JOIN status s ON s.object_id = e.object_id AND s.type_id = :pub_type_id AND s.status_id = :pub_status_id'
            : '';

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, a.id, COALESCE(ai.authorized_form_of_name, 'Unknown'), COUNT(DISTINCT e.object_id)
            FROM event e
            INNER JOIN actor a ON e.actor_id = a.id
            {$statusJoin}
            LEFT JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            WHERE e.actor_id IS NOT NULL
            GROUP BY a.id, ai.authorized_form_of_name
            HAVING COUNT(DISTINCT e.object_id) > 0
        ";

        $params = [':facet_type' => $facetType];
        if ($publishedOnly) {
            $params[':pub_type_id'] = $pubTypeId;
            $params[':pub_status_id'] = $pubStatusId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        $this->line("    Added {$count} entries");
    }

    private function refreshGlamTypeFacet(\PDO $pdo, string $facetType, int $pubTypeId, int $pubStatusId, bool $publishedOnly): void
    {
        $this->line("  Refreshing {$facetType} facet...");

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
            $params[':pub_type_id'] = $pubTypeId;
            $params[':pub_status_id'] = $pubStatusId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        $this->line("    Added {$count} entries");
    }

    private function refreshMediaTypeFacet(\PDO $pdo, string $facetType, int $pubTypeId, int $pubStatusId, bool $publishedOnly): void
    {
        $this->line("  Refreshing {$facetType} facet...");

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
            $params[':pub_type_id'] = $pubTypeId;
            $params[':pub_status_id'] = $pubStatusId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        $this->line("    Added {$count} entries");
    }
}
