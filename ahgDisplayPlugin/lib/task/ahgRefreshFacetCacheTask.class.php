<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Refresh the display facet cache for guest users.
 *
 * This task pre-computes facet counts for published items only,
 * storing results in display_facet_cache table for fast retrieval.
 *
 * Run via: php symfony ahg:refresh-facet-cache
 * Schedule via cron: 0 * * * * cd /usr/share/nginx/archive && php symfony ahg:refresh-facet-cache --env=prod
 */
class ahgRefreshFacetCacheTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'refresh-facet-cache';
        $this->briefDescription = 'Refresh display facet cache for guest users';
        $this->detailedDescription = <<<EOF
The [ahg:refresh-facet-cache|INFO] task refreshes the cached facet counts
for the display browse page. This improves performance for guest users.

Call it with:

  [php symfony ahg:refresh-facet-cache|INFO]

Schedule it to run hourly via cron for best results.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $conn = DB::connection()->getPdo();

        $this->logSection('facet-cache', 'Starting facet cache refresh...');
        $startTime = microtime(true);

        // Publication status constants
        $pubTypeId = 158;    // Publication status type
        $pubStatusId = 160;  // Published status

        // Clear existing cache
        $conn->exec('TRUNCATE TABLE display_facet_cache');
        $this->logSection('facet-cache', 'Cleared existing cache');

        // 1. Subject facet (taxonomy_id = 35)
        $this->refreshTaxonomyFacet($conn, 'subject', 35, $pubTypeId, $pubStatusId);

        // 2. Place facet (taxonomy_id = 42)
        $this->refreshTaxonomyFacet($conn, 'place', 42, $pubTypeId, $pubStatusId);

        // 3. Genre facet (taxonomy_id = 78)
        $this->refreshTaxonomyFacet($conn, 'genre', 78, $pubTypeId, $pubStatusId);

        // 4. Level of description facet
        $this->refreshLevelFacet($conn, $pubTypeId, $pubStatusId);

        // 5. Repository facet
        $this->refreshRepositoryFacet($conn, $pubTypeId, $pubStatusId);

        // 6. Creator facet
        $this->refreshCreatorFacet($conn, $pubTypeId, $pubStatusId);

        // 7. GLAM type facet
        $this->refreshGlamTypeFacet($conn, $pubTypeId, $pubStatusId);

        // 8. Media type facet
        $this->refreshMediaTypeFacet($conn, $pubTypeId, $pubStatusId);

        $elapsed = round(microtime(true) - $startTime, 2);
        $count = $conn->query('SELECT COUNT(*) FROM display_facet_cache')->fetchColumn();
        $this->logSection('facet-cache', "Cache refresh complete: {$count} entries in {$elapsed}s");
    }

    protected function refreshTaxonomyFacet($conn, $facetType, $taxonomyId, $pubTypeId, $pubStatusId)
    {
        $this->logSection('facet-cache', "Refreshing {$facetType} facet (taxonomy {$taxonomyId})...");

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT :facet_type, t.id, COALESCE(ti.name, 'Unknown'), COUNT(DISTINCT otr.object_id)
            FROM term t
            INNER JOIN object_term_relation otr ON otr.term_id = t.id
            INNER JOIN status s ON s.object_id = otr.object_id
                AND s.type_id = :pub_type_id
                AND s.status_id = :pub_status_id
            LEFT JOIN term_i18n ti ON t.id = ti.id AND ti.culture = 'en'
            WHERE t.taxonomy_id = :taxonomy_id
            GROUP BY t.id, ti.name
            HAVING COUNT(DISTINCT otr.object_id) > 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':facet_type' => $facetType,
            ':taxonomy_id' => $taxonomyId,
            ':pub_type_id' => $pubTypeId,
            ':pub_status_id' => $pubStatusId,
        ]);

        $count = $stmt->rowCount();
        $this->logSection('facet-cache', "  Added {$count} {$facetType} entries");
    }

    protected function refreshLevelFacet($conn, $pubTypeId, $pubStatusId)
    {
        $this->logSection('facet-cache', 'Refreshing level facet...');

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT 'level', t.id, COALESCE(ti.name, 'Unknown'), COUNT(*)
            FROM information_object io
            INNER JOIN term t ON io.level_of_description_id = t.id
            INNER JOIN status s ON s.object_id = io.id
                AND s.type_id = :pub_type_id
                AND s.status_id = :pub_status_id
            LEFT JOIN term_i18n ti ON t.id = ti.id AND ti.culture = 'en'
            WHERE io.id > 1
            GROUP BY t.id, ti.name
            HAVING COUNT(*) > 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pub_type_id' => $pubTypeId,
            ':pub_status_id' => $pubStatusId,
        ]);

        $count = $stmt->rowCount();
        $this->logSection('facet-cache', "  Added {$count} level entries");
    }

    protected function refreshRepositoryFacet($conn, $pubTypeId, $pubStatusId)
    {
        $this->logSection('facet-cache', 'Refreshing repository facet...');

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT 'repository', r.id, COALESCE(ai.authorized_form_of_name, 'Unknown'), COUNT(*)
            FROM information_object io
            INNER JOIN repository r ON io.repository_id = r.id
            INNER JOIN status s ON s.object_id = io.id
                AND s.type_id = :pub_type_id
                AND s.status_id = :pub_status_id
            LEFT JOIN actor_i18n ai ON r.id = ai.id AND ai.culture = 'en'
            WHERE io.id > 1
            GROUP BY r.id, ai.authorized_form_of_name
            HAVING COUNT(*) > 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pub_type_id' => $pubTypeId,
            ':pub_status_id' => $pubStatusId,
        ]);

        $count = $stmt->rowCount();
        $this->logSection('facet-cache', "  Added {$count} repository entries");
    }

    protected function refreshCreatorFacet($conn, $pubTypeId, $pubStatusId)
    {
        $this->logSection('facet-cache', 'Refreshing creator facet...');

        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT 'creator', a.id, COALESCE(ai.authorized_form_of_name, 'Unknown'), COUNT(DISTINCT e.object_id)
            FROM event e
            INNER JOIN actor a ON e.actor_id = a.id
            INNER JOIN status s ON s.object_id = e.object_id
                AND s.type_id = :pub_type_id
                AND s.status_id = :pub_status_id
            LEFT JOIN actor_i18n ai ON a.id = ai.id AND ai.culture = 'en'
            WHERE e.actor_id IS NOT NULL
            GROUP BY a.id, ai.authorized_form_of_name
            HAVING COUNT(DISTINCT e.object_id) > 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pub_type_id' => $pubTypeId,
            ':pub_status_id' => $pubStatusId,
        ]);

        $count = $stmt->rowCount();
        $this->logSection('facet-cache', "  Added {$count} creator entries");
    }

    protected function refreshGlamTypeFacet($conn, $pubTypeId, $pubStatusId)
    {
        $this->logSection('facet-cache', 'Refreshing GLAM type facet...');

        // For GLAM type, we use term_id=0 and store the type name in term_name
        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT 'glam_type', 0, doc.object_type, COUNT(*)
            FROM display_object_config doc
            INNER JOIN information_object io ON doc.object_id = io.id
            INNER JOIN status s ON s.object_id = io.id
                AND s.type_id = :pub_type_id
                AND s.status_id = :pub_status_id
            GROUP BY doc.object_type
            HAVING COUNT(*) > 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pub_type_id' => $pubTypeId,
            ':pub_status_id' => $pubStatusId,
        ]);

        $count = $stmt->rowCount();
        $this->logSection('facet-cache', "  Added {$count} GLAM type entries");
    }

    protected function refreshMediaTypeFacet($conn, $pubTypeId, $pubStatusId)
    {
        $this->logSection('facet-cache', 'Refreshing media type facet...');

        // For media type, we use term_id=0 and store the type in term_name
        $sql = "
            INSERT INTO display_facet_cache (facet_type, term_id, term_name, count)
            SELECT 'media_type', 0, SUBSTRING_INDEX(do.mime_type, '/', 1), COUNT(*)
            FROM digital_object do
            INNER JOIN status s ON s.object_id = do.object_id
                AND s.type_id = :pub_type_id
                AND s.status_id = :pub_status_id
            WHERE do.parent_id IS NULL AND do.mime_type IS NOT NULL
            GROUP BY SUBSTRING_INDEX(do.mime_type, '/', 1)
            HAVING COUNT(*) > 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':pub_type_id' => $pubTypeId,
            ':pub_status_id' => $pubStatusId,
        ]);

        $count = $stmt->rowCount();
        $this->logSection('facet-cache', "  Added {$count} media type entries");
    }
}
