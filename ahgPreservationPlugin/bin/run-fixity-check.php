#!/usr/bin/env php
<?php
/**
 * AHG Preservation Plugin - Fixity Check CLI
 *
 * Run scheduled fixity verification on digital objects.
 *
 * Usage:
 *   php run-fixity-check.php [--limit=100] [--min-age=7] [--verbose]
 *
 * Options:
 *   --limit=N    Maximum number of objects to check (default: 100)
 *   --min-age=N  Minimum days since last check (default: 7)
 *   --verbose    Show detailed output
 *   --all        Check all objects regardless of last check date
 */

define('SF_ROOT_DIR', realpath(dirname(__FILE__) . '/../../../..'));
define('SF_APP', 'qubit');
define('SF_ENVIRONMENT', 'cli');
define('SF_DEBUG', false);

require_once SF_ROOT_DIR . '/config/ProjectConfiguration.class.php';
$configuration = ProjectConfiguration::getApplicationConfiguration(SF_APP, SF_ENVIRONMENT, SF_DEBUG);
sfContext::createInstance($configuration);

require_once SF_ROOT_DIR . '/plugins/ahgPreservationPlugin/lib/PreservationService.php';

// Parse command line arguments
$options = getopt('', ['limit::', 'min-age::', 'verbose', 'all', 'help']);

if (isset($options['help'])) {
    echo "AHG Preservation - Fixity Check CLI\n";
    echo "====================================\n\n";
    echo "Usage: php run-fixity-check.php [options]\n\n";
    echo "Options:\n";
    echo "  --limit=N    Maximum objects to check (default: 100)\n";
    echo "  --min-age=N  Minimum days since last check (default: 7)\n";
    echo "  --verbose    Show detailed output\n";
    echo "  --all        Check all objects\n";
    echo "  --help       Show this help\n";
    exit(0);
}

$limit = isset($options['limit']) ? (int) $options['limit'] : 100;
$minAge = isset($options['all']) ? 0 : (isset($options['min-age']) ? (int) $options['min-age'] : 7);
$verbose = isset($options['verbose']);

echo "============================================\n";
echo "  AHG Preservation - Fixity Verification\n";
echo "============================================\n\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "Limit: {$limit} objects\n";
echo "Min age: {$minAge} days\n\n";

try {
    $service = new PreservationService();

    echo "Running batch fixity check...\n\n";

    $startTime = microtime(true);
    $results = $service->runBatchFixityCheck($limit, $minAge, 'cli');
    $duration = round(microtime(true) - $startTime, 2);

    echo "Results:\n";
    echo "  Total checked: {$results['total']}\n";
    echo "  Passed:        {$results['passed']}\n";
    echo "  Failed:        {$results['failed']}\n";
    echo "  Errors:        {$results['errors']}\n";
    echo "  Duration:      {$duration}s\n\n";

    if ($verbose && !empty($results['details'])) {
        echo "Details:\n";
        foreach ($results['details'] as $objectId => $detail) {
            echo "  Object {$objectId}:\n";
            if (isset($detail['error'])) {
                echo "    Error: {$detail['error']}\n";
            } else {
                foreach ($detail as $algo => $result) {
                    echo "    {$algo}: {$result['status']}";
                    if ($result['error']) {
                        echo " ({$result['error']})";
                    }
                    echo "\n";
                }
            }
        }
        echo "\n";
    }

    if ($results['failed'] > 0) {
        echo "WARNING: {$results['failed']} fixity failures detected!\n";
        echo "Review the preservation dashboard for details.\n\n";
    }

    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

    // Exit with error code if there were failures
    exit($results['failed'] > 0 ? 1 : 0);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(2);
}
