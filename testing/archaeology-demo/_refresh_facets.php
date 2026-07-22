<?php
/**
 * Shared: refresh the browse facet cache after a demo seed.
 *
 * Browse facets ("Narrow your results by:") read the pre-computed
 * display_facet_cache table, NOT live aggregations. After a seed changes the
 * data the cache is stale - and if it was empty, the facet sidebar shows
 * nothing at all. So every seed script calls this at the end.
 *
 * Runs as www-data (not root), so no cache file or row is created root-owned -
 * the classic artisan-as-root trap on this host. The facet rebuild uses the
 * standalone Illuminate-only runner rather than the symfony task (see below).
 */
function refresh_demo_facets(string $atomRoot = '/usr/share/nginx/archeology'): void
{
    if (!is_dir($atomRoot . '/lib/task') && !file_exists($atomRoot . '/symfony')) {
        echo "  (facet refresh skipped: {$atomRoot} is not an AtoM root)\n";

        return;
    }

    echo "  refreshing browse facets...\n";
    // display:auto-detect assigns each record its GLAM type; the cache rebuild
    // then reads from the current data. Order matters.
    //
    // The facet rebuild is run via the STANDALONE Illuminate-only runner, NOT
    // `php symfony ahg:refresh-facet-cache`: that task boots the full prod app
    // from the CLI and, on this host (opcache.validate_timestamps=0), leaves the
    // web runtime pinned to a broken compiled config cache -> site-wide HTTP 500.
    // The standalone runner boots only the DB layer and is safe. (display:auto-detect
    // is a different task that does NOT exhibit this, so it stays as-is.)
    $runner = $atomRoot . '/atom-ahg-plugins/ahgDisplayPlugin/bin/refresh-facet-cache.php';
    passthru('cd ' . escapeshellarg($atomRoot) . ' && sudo -u www-data php symfony display:auto-detect >/dev/null 2>&1');
    passthru('sudo -u www-data php ' . escapeshellarg($runner) . ' 2>&1 | tail -1');
}
