<?php

/**
 * Standalone display-facet-cache refresher (Illuminate-only, NO Symfony boot).
 *
 * This is the SAFE path for cron and for the demo seed scripts. It boots only
 * the atom-framework Illuminate DB layer and never loads the Symfony prod
 * application configuration, so it cannot corrupt the compiled config cache /
 * take the web runtime down (unlike `php symfony ahg:refresh-facet-cache`, which
 * boots the full app from the CLI - see FacetCacheRefresher for the full note).
 *
 * Usage:
 *   php /path/to/atom-root/atom-ahg-plugins/ahgDisplayPlugin/bin/refresh-facet-cache.php \
 *       [--atom-root=/usr/share/nginx/archeology]
 *
 * If --atom-root is omitted it is derived from this file's location. Run it as
 * www-data so no cache rows/files are created root-owned.
 */

$atomRoot = null;
foreach ($argv as $arg) {
    if (0 === strpos($arg, '--atom-root=')) {
        $atomRoot = substr($arg, strlen('--atom-root='));
    }
}

if (null === $atomRoot) {
    // <atom-root>/atom-ahg-plugins/ahgDisplayPlugin/bin/refresh-facet-cache.php
    //   __DIR__ = .../ahgDisplayPlugin/bin  ->  up 3 = <atom-root>
    $atomRoot = dirname(__DIR__, 3);
}

$bootstrap = $atomRoot . '/atom-framework/bootstrap.php';
if (!is_file($bootstrap)) {
    fwrite(STDERR, "atom-framework bootstrap not found at {$bootstrap}\n");
    exit(1);
}

require $bootstrap;
require __DIR__ . '/../lib/FacetCacheRefresher.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    $conn = DB::connection()->getPdo();
    $start = microtime(true);
    $rows = FacetCacheRefresher::refresh($conn, static function ($m) {
        fwrite(STDOUT, '  ' . $m . "\n");
    });
    $elapsed = round(microtime(true) - $start, 2);
    fwrite(STDOUT, "facet-cache: {$rows} entries in {$elapsed}s\n");
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'facet-cache FAILED: ' . $e->getMessage() . "\n");
    exit(1);
}
