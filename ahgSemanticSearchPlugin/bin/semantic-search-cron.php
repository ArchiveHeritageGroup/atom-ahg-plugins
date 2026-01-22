#!/usr/bin/env php
<?php

/**
 * Semantic Search Cron Job
 *
 * Performs scheduled maintenance tasks for the semantic search plugin:
 * - Sync thesaurus from WordNet (Datamuse API)
 * - Sync thesaurus from Wikidata
 * - Update vector embeddings
 * - Export synonyms to Elasticsearch
 * - Clean up stale entries
 *
 * Usage:
 *   php semantic-search-cron.php [task] [options]
 *
 * Tasks:
 *   sync-wordnet    Sync synonyms from WordNet/Datamuse
 *   sync-wikidata   Sync terms from Wikidata SPARQL
 *   update-embeddings  Generate/update vector embeddings
 *   export-es       Export synonyms to Elasticsearch format
 *   cleanup         Remove stale/orphaned entries
 *   all             Run all tasks (default)
 *
 * Options:
 *   --domain=X      Filter by domain (archival, library, museum, general)
 *   --limit=N       Limit number of terms to process
 *   --force         Force update even if recently synced
 *   --dry-run       Show what would be done without making changes
 *   --quiet         Suppress output except errors
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

declare(strict_types=1);

// Determine AtoM root
$atomRoot = getenv('ATOM_ROOT') ?: '/usr/share/nginx/archive';
$frameworkRoot = $atomRoot . '/atom-framework';
$pluginRoot = $atomRoot . '/atom-ahg-plugins/ahgSemanticSearchPlugin';

// Bootstrap
require_once $frameworkRoot . '/vendor/autoload.php';
require_once $pluginRoot . '/lib/Services/ThesaurusService.php';
require_once $pluginRoot . '/lib/Services/WordNetSyncService.php';
require_once $pluginRoot . '/lib/Services/WikidataSyncService.php';
require_once $pluginRoot . '/lib/Services/EmbeddingService.php';
require_once $pluginRoot . '/lib/Services/SemanticSearchService.php';

use Illuminate\Database\Capsule\Manager as DB;

// Parse command line arguments
$task = $argv[1] ?? 'all';
$options = parseOptions(array_slice($argv, 2));

// Configuration
$config = [
    'log_path' => $atomRoot . '/log/semantic-search-cron.log',
    'lock_file' => '/tmp/semantic-search-cron.lock',
    'max_runtime' => 3600, // 1 hour max
];

// Initialize database connection
initDatabase($atomRoot);

// Main execution
try {
    $cron = new SemanticSearchCron($config, $options);
    $cron->run($task);
} catch (Exception $e) {
    logError("Cron failed: " . $e->getMessage());
    exit(1);
}

/**
 * Parse command line options
 */
function parseOptions(array $args): array
{
    $options = [
        'domain' => null,
        'limit' => 1000,
        'force' => false,
        'dry_run' => false,
        'quiet' => false,
    ];

    foreach ($args as $arg) {
        if (strpos($arg, '--domain=') === 0) {
            $options['domain'] = substr($arg, 9);
        } elseif (strpos($arg, '--limit=') === 0) {
            $options['limit'] = (int) substr($arg, 8);
        } elseif ($arg === '--force') {
            $options['force'] = true;
        } elseif ($arg === '--dry-run') {
            $options['dry_run'] = true;
        } elseif ($arg === '--quiet') {
            $options['quiet'] = true;
        }
    }

    return $options;
}

/**
 * Initialize database connection
 */
function initDatabase(string $atomRoot): void
{
    $configFile = $atomRoot . '/config/config.php';

    // Default database config
    $dbConfig = [
        'host' => 'localhost',
        'database' => 'archive',
        'username' => 'root',
        'password' => '',
    ];

    // Try to load from AtoM config
    if (file_exists($configFile)) {
        $config = include $configFile;
        if (isset($config['database'])) {
            $dbConfig = array_merge($dbConfig, $config['database']);
        }
    }

    // Initialize Eloquent
    $capsule = new DB;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $dbConfig['host'],
        'database' => $dbConfig['database'],
        'username' => $dbConfig['username'],
        'password' => $dbConfig['password'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

/**
 * Log error message
 */
function logError(string $message): void
{
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, '/var/log/atom/semantic-search-cron.log');
    fwrite(STDERR, $message . "\n");
}

/**
 * Semantic Search Cron Handler
 */
class SemanticSearchCron
{
    private array $config;
    private array $options;
    private $lockHandle = null;
    private int $startTime;

    public function __construct(array $config, array $options)
    {
        $this->config = $config;
        $this->options = $options;
        $this->startTime = time();
    }

    /**
     * Run the specified task
     */
    public function run(string $task): void
    {
        // Acquire lock
        if (!$this->acquireLock()) {
            $this->output("Another instance is already running. Exiting.");
            exit(0);
        }

        $this->output("Starting semantic search cron: $task");
        $this->output("Options: " . json_encode($this->options));

        try {
            switch ($task) {
                case 'sync-wordnet':
                    $this->syncWordNet();
                    break;
                case 'sync-wikidata':
                    $this->syncWikidata();
                    break;
                case 'update-embeddings':
                    $this->updateEmbeddings();
                    break;
                case 'export-es':
                    $this->exportElasticsearch();
                    break;
                case 'cleanup':
                    $this->cleanup();
                    break;
                case 'all':
                default:
                    $this->runAll();
                    break;
            }

            $duration = time() - $this->startTime;
            $this->output("Completed in {$duration} seconds");
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Run all tasks
     */
    private function runAll(): void
    {
        $this->output("\n=== Running all semantic search tasks ===\n");

        // Check if sync is due (weekly by default)
        $lastSync = $this->getLastSyncTime();
        $syncInterval = 7 * 24 * 3600; // 1 week

        if ($this->options['force'] || (time() - $lastSync) > $syncInterval) {
            $this->syncWordNet();
            $this->syncWikidata();
            $this->updateLastSyncTime();
        } else {
            $this->output("Skipping sync (last sync: " . date('Y-m-d H:i:s', $lastSync) . ")");
        }

        // Always run these
        $this->updateEmbeddings();
        $this->exportElasticsearch();
        $this->cleanup();
    }

    /**
     * Sync from WordNet/Datamuse API
     */
    private function syncWordNet(): void
    {
        $this->output("\n--- Syncing from WordNet/Datamuse ---");

        if ($this->options['dry_run']) {
            $this->output("[DRY RUN] Would sync WordNet synonyms");
            return;
        }

        try {
            $service = new \AtomFramework\Services\SemanticSearch\WordNetSyncService();

            $domains = $this->options['domain']
                ? [$this->options['domain']]
                : ['archival', 'library', 'museum', 'general'];

            foreach ($domains as $domain) {
                $this->output("  Syncing domain: $domain");
                $result = $service->syncDomain($domain, $this->options['limit']);
                $this->output("    Added: {$result['added']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}");
            }
        } catch (Exception $e) {
            $this->output("  ERROR: " . $e->getMessage());
        }
    }

    /**
     * Sync from Wikidata SPARQL
     */
    private function syncWikidata(): void
    {
        $this->output("\n--- Syncing from Wikidata ---");

        if ($this->options['dry_run']) {
            $this->output("[DRY RUN] Would sync Wikidata terms");
            return;
        }

        try {
            $service = new \AtomFramework\Services\SemanticSearch\WikidataSyncService();
            $result = $service->syncArchivalTerms($this->options['limit']);
            $this->output("  Added: {$result['added']}, Updated: {$result['updated']}");
        } catch (Exception $e) {
            $this->output("  ERROR: " . $e->getMessage());
        }
    }

    /**
     * Update vector embeddings
     */
    private function updateEmbeddings(): void
    {
        $this->output("\n--- Updating vector embeddings ---");

        if ($this->options['dry_run']) {
            $this->output("[DRY RUN] Would update embeddings");
            return;
        }

        try {
            $service = new \AtomFramework\Services\SemanticSearch\EmbeddingService();

            // Get terms without embeddings
            $terms = DB::table('semantic_synonym')
                ->whereNull('embedding')
                ->orWhere('embedding_updated_at', '<', date('Y-m-d H:i:s', strtotime('-30 days')))
                ->limit($this->options['limit'])
                ->pluck('term')
                ->toArray();

            $this->output("  Found " . count($terms) . " terms needing embeddings");

            $updated = 0;
            foreach ($terms as $term) {
                if ($service->generateEmbedding($term)) {
                    $updated++;
                }

                // Check runtime limit
                if ((time() - $this->startTime) > $this->config['max_runtime']) {
                    $this->output("  Reached max runtime, stopping");
                    break;
                }
            }

            $this->output("  Updated $updated embeddings");
        } catch (Exception $e) {
            $this->output("  ERROR: " . $e->getMessage());
        }
    }

    /**
     * Export synonyms to Elasticsearch format
     */
    private function exportElasticsearch(): void
    {
        $this->output("\n--- Exporting to Elasticsearch ---");

        if ($this->options['dry_run']) {
            $this->output("[DRY RUN] Would export to Elasticsearch");
            return;
        }

        try {
            $service = new \AtomFramework\Services\SemanticSearch\ThesaurusService();
            $result = $service->exportToElasticsearch();
            $this->output("  Exported {$result['count']} synonym groups to {$result['path']}");
        } catch (Exception $e) {
            $this->output("  ERROR: " . $e->getMessage());
        }
    }

    /**
     * Cleanup stale entries
     */
    private function cleanup(): void
    {
        $this->output("\n--- Cleaning up stale entries ---");

        if ($this->options['dry_run']) {
            $stale = DB::table('semantic_synonym')
                ->where('updated_at', '<', date('Y-m-d H:i:s', strtotime('-90 days')))
                ->where('source', '!=', 'local')
                ->count();
            $this->output("[DRY RUN] Would remove $stale stale entries");
            return;
        }

        try {
            // Remove old auto-generated entries not updated in 90 days
            $deleted = DB::table('semantic_synonym')
                ->where('updated_at', '<', date('Y-m-d H:i:s', strtotime('-90 days')))
                ->where('source', '!=', 'local') // Keep manually added ones
                ->delete();

            $this->output("  Removed $deleted stale entries");

            // Optimize table
            DB::statement('OPTIMIZE TABLE semantic_synonym');
            $this->output("  Optimized table");
        } catch (Exception $e) {
            $this->output("  ERROR: " . $e->getMessage());
        }
    }

    /**
     * Get last sync timestamp
     */
    private function getLastSyncTime(): int
    {
        $setting = DB::table('ahg_semantic_search_settings')
            ->where('setting_key', 'last_cron_sync')
            ->first();

        return $setting ? (int) $setting->setting_value : 0;
    }

    /**
     * Update last sync timestamp
     */
    private function updateLastSyncTime(): void
    {
        DB::table('ahg_semantic_search_settings')->updateOrInsert(
            ['setting_key' => 'last_cron_sync'],
            ['setting_value' => (string) time(), 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Acquire exclusive lock
     */
    private function acquireLock(): bool
    {
        $this->lockHandle = fopen($this->config['lock_file'], 'c');
        if (!$this->lockHandle) {
            return false;
        }
        return flock($this->lockHandle, LOCK_EX | LOCK_NB);
    }

    /**
     * Release lock
     */
    private function releaseLock(): void
    {
        if ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            @unlink($this->config['lock_file']);
        }
    }

    /**
     * Output message (unless quiet mode)
     */
    private function output(string $message): void
    {
        if (!$this->options['quiet']) {
            echo $message . "\n";
        }

        // Also log to file
        $logMessage = date('[Y-m-d H:i:s] ') . $message . "\n";
        @file_put_contents($this->config['log_path'], $logMessage, FILE_APPEND);
    }
}
