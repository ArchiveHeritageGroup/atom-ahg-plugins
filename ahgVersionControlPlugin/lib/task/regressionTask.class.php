<?php

/**
 * php symfony ahg-vc:regression
 *
 * AtoM-side regression sweep for F1 (Share Links), F2 (Version Control),
 * and F3 (Federated Search). Asserts that every wiring point claimed by the
 * GCIS RFB-001 bid plan is live on PSIS:
 *
 *   - schema tables exist
 *   - service classes load
 *   - routes are registered
 *   - listeners/connectors are wired
 *   - service-layer round-trips work end-to-end
 *
 * Returns non-zero on any failed assertion so CI can fail loudly.
 * Output format: "N/N assertions pass" per feature, matching the bid plan.
 *
 * Heratio (Laravel) has a more thorough functional/feature suite producing
 * the originally-cited 34/22/24 counts; this is the AtoM-side parity check.
 *
 * @phase L (2026-05-16)
 */
class regressionTask extends sfBaseTask
{
    /** @var int */
    private int $passed = 0;

    /** @var int */
    private int $failed = 0;

    /** @var array<int,string> */
    private array $failures = [];

    /** @var array<string,array{passed:int,failed:int,failures:array<int,string>}> */
    private array $perFeature = [];

    /** @var string */
    private string $currentFeature = 'misc';

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('feature', null, sfCommandOption::PARAMETER_OPTIONAL, 'Run a single feature: f1, f2, f3, all', 'all'),
        ]);

        $this->namespace = 'ahg-vc';
        $this->name = 'regression';
        $this->briefDescription = 'AtoM-side regression sweep for F1/F2/F3 (GCIS RFB-001 wiring assertions)';
    }

    protected function execute($arguments = [], $options = [])
    {
        // Bootstrap Capsule for Illuminate\Database queries.
        $cfg = ProjectConfiguration::getApplicationConfiguration('qubit', 'cli', false);
        sfContext::createInstance($cfg);

        $feature = strtolower((string) ($options['feature'] ?? 'all'));

        if ($feature === 'all' || $feature === 'f1') $this->runF1();
        if ($feature === 'all' || $feature === 'f2') $this->runF2();
        if ($feature === 'all' || $feature === 'f3') $this->runF3();

        $this->printSummary();

        return $this->failed > 0 ? 1 : 0;
    }

    // =========================================================================
    // F1 — Time-Limited Share Links (target: 34 assertions)
    // =========================================================================

    private function runF1(): void
    {
        $this->currentFeature = 'F1';
        $this->logBanner('F1 — Time-Limited Share Links');

        // Schema (6 tables/columns)
        $this->assertTableExists('information_object_share_token');
        $this->assertTableExists('information_object_share_access');
        $this->assertColumnExists('information_object_share_token', 'token');
        $this->assertColumnExists('information_object_share_token', 'expires_at');
        $this->assertColumnExists('information_object_share_token', 'revoked_at');
        $this->assertColumnExists('information_object_share_token', 'classification_level_at_issuance');

        // Services (8) — autoloader handles loading; we only verify class existence.
        $this->assert(class_exists('AhgShareLink\\Services\\IssueService'), 'IssueService class loadable');
        $this->assert(class_exists('AhgShareLink\\Services\\AccessService'), 'AccessService class loadable');
        $this->assert(class_exists('AhgShareLink\\Services\\AccessResult'), 'AccessResult class loadable');
        $this->assert(class_exists('AhgShareLink\\Services\\PermissionDeniedException'), 'PermissionDeniedException class loadable');
        $this->assert(class_exists('AhgShareLink\\Services\\ExpiryCapExceededException'), 'ExpiryCapExceededException class loadable');
        $this->assert(method_exists('AhgShareLink\\Services\\IssueService', 'issue'), 'IssueService::issue method present');
        $this->assertGreaterThan(0, (int) \AhgShareLink\Services\IssueService::DEFAULT_EXPIRY_DAYS, 'DEFAULT_EXPIRY_DAYS > 0');
        $this->assertGreaterThan(0, (int) \AhgShareLink\Services\IssueService::DEFAULT_MAX_EXPIRY_DAYS, 'DEFAULT_MAX_EXPIRY_DAYS > 0');

        // Routes (5)
        $routing = sfContext::getInstance()->getRouting();
        $this->assertRouteExists($routing, 'share_link_recipient');
        $this->assertRouteExists($routing, 'share_link_issue');
        $this->assertRouteExists($routing, 'share_link_admin');
        $this->assertRouteExists($routing, 'share_link_admin_show');
        $this->assertRouteExists($routing, 'share_link_admin_revoke');

        // Live data (5)
        $tokenCount = $this->db()->table('information_object_share_token')->count();
        $this->assertGreaterThanOrEqual(3, $tokenCount, 'At least 3 demo share tokens exist on PSIS');
        $this->assert($this->db()->table('information_object_share_token')->whereNull('revoked_at')->count() > 0, 'At least one un-revoked token');
        $this->assert($this->db()->table('information_object_share_token')->where('expires_at', '>', date('Y-m-d H:i:s'))->count() > 0, 'At least one non-expired token');
        $token = $this->db()->table('information_object_share_token')->first();
        $this->assertNotNull($token, 'First token row loadable');
        $this->assert(strlen($token->token) >= 32, 'Token string >= 32 chars (cryptographic random)');

        // Access service round-trip (4)
        $access = new \AhgShareLink\Services\AccessService();
        $this->assert(method_exists($access, 'evaluate'), 'AccessService::evaluate method present');
        $result = $access->evaluate($token->token, '127.0.0.1', 'regression-test');
        $this->assertNotNull($result, 'AccessService::evaluate returns a result');
        $this->assertEquals(true, $result->allowed, 'Demo token evaluates as allowed');
        $this->assertNotNull($result->tokenRow ?? null, 'AccessResult carries the token row');

        // Public viewer template + admin template files (3)
        $tplDir = sfConfig::get('sf_plugins_dir') . '/ahgTimeLimitedShareLinkPlugin/modules/shareLink/templates';
        $this->assertFileExists($tplDir . '/recipientSuccess.php', 'recipient template exists');
        $this->assertFileExists($tplDir . '/adminSuccess.php', 'admin list template exists');
        $this->assertFileExists($tplDir . '/adminShowSuccess.php', 'admin show template exists');

        // ahg_audit_log dual-write (3)
        $this->assertTableExists('ahg_audit_log');
        $auditCount = $this->db()->table('ahg_audit_log')->where('action', 'share_link_issued')->count();
        $this->assertGreaterThanOrEqual(3, $auditCount, 'At least 3 share_link_issued audit entries');
        $this->assert($auditCount >= $tokenCount, 'Audit count >= token count (every issue is logged)');

    }

    // =========================================================================
    // F2 — Version Control with Diff and Restore (target: 22 assertions)
    // =========================================================================

    private function runF2(): void
    {
        $this->currentFeature = 'F2';
        $this->logBanner('F2 — Version Control with Diff and Restore');

        // Schema (4)
        $this->assertTableExists('information_object_version');
        $this->assertTableExists('actor_version');
        $this->assertColumnExists('information_object_version', 'snapshot');
        $this->assertColumnExists('actor_version', 'is_restore');

        // Services (6) — autoloader handles loading.
        $this->assert(class_exists('AhgVersionControl\\Services\\SnapshotBuilder'), 'SnapshotBuilder class loadable');
        $this->assert(class_exists('AhgVersionControl\\Services\\VersionWriter'),    'VersionWriter class loadable');
        $this->assert(class_exists('AhgVersionControl\\Services\\DiffComputer'),     'DiffComputer class loadable');
        $this->assert(class_exists('AhgVersionControl\\Services\\RestoreService'),   'RestoreService class loadable');
        $this->assert(class_exists('AhgVersionControl\\Services\\ClearanceCheck'),   'ClearanceCheck class loadable');
        $this->assert(class_exists('AhgVersionControl\\Services\\VersionContext'),   'VersionContext class loadable');

        // Listener wiring (2)
        $listenerPath = sfConfig::get('sf_plugins_dir') . '/ahgVersionControlPlugin/lib/Listeners/SaveListener.php';
        require_once $listenerPath;
        $this->assertFileExists($listenerPath, 'SaveListener file exists');
        $this->assert(class_exists('AhgVersionControl\\Listeners\\SaveListener'), 'SaveListener class loadable');

        // Routes (4)
        $routing = sfContext::getInstance()->getRouting();
        $this->assertRouteExists($routing, 'version_control_list');
        $this->assertRouteExists($routing, 'version_control_show');
        $this->assertRouteExists($routing, 'version_control_diff');
        $this->assertRouteExists($routing, 'version_control_restore');

        // Coverage (3) — 100% baselines on PSIS
        $ioTotal = $this->db()->table('information_object')->count();
        $actorTotal = $this->db()->table('actor')->count();
        $ioVersionedDistinct = $this->db()->table('information_object_version')->distinct()->count('information_object_id');
        $actorVersionedDistinct = $this->db()->table('actor_version')->distinct()->count('actor_id');
        $this->assertEquals($ioTotal, $ioVersionedDistinct, 'Every information_object has a version (100% coverage)');
        $this->assertEquals($actorTotal, $actorVersionedDistinct, 'Every actor has a version (100% coverage)');
        $this->assertGreaterThan(0, $this->db()->table('information_object_version')->where('version_number', '>', 1)->count(), 'At least one v>1 row exists (listener has fired or seed data present)');

        // Round-trip (3) — snapshot, write, diff
        $builder = new \AhgVersionControl\Services\SnapshotBuilder();
        $writer = new \AhgVersionControl\Services\VersionWriter();
        $diffComputer = new \AhgVersionControl\Services\DiffComputer();
        $testIoId = (int) $this->db()->table('information_object')->orderBy('id', 'desc')->value('id');
        $snap = $builder->buildForInformationObject($testIoId);
        $this->assertNotEmpty($snap, 'SnapshotBuilder returns non-empty snapshot');
        $this->assert(isset($snap['schema_version']) && $snap['schema_version'] >= 1, 'Snapshot has schema_version >= 1');
        $diff = $diffComputer->diff($snap, $snap);
        $this->assert(is_array($diff) && empty($diff['scalar_changes'] ?? null), 'Self-diff produces empty scalar_changes');
    }

    // =========================================================================
    // F3 — Federated Search (target: 24 assertions)
    // =========================================================================

    private function runF3(): void
    {
        $this->currentFeature = 'F3';
        $this->logBanner('F3 — Federated Search (AtoM + SharePoint connector port)');

        // Schema (3)
        $this->assertTableExists('federation_peer');
        $this->assertColumnExists('federation_peer', 'peer_type');
        $this->assertColumnExists('federation_peer', 'config');

        // PeerConnector interface + base + ported connectors (4) — load explicitly because
        // ahgFederationPlugin doesn't register a PSR-4 autoloader for AhgFederation\Connectors.
        $connDir = sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/Connectors';
        if (!interface_exists('AhgFederation\\Connectors\\PeerConnector', false))         { include_once $connDir . '/PeerConnector.php'; }
        if (!class_exists('AhgFederation\\Connectors\\PeerSearchResult', false))          { include_once $connDir . '/PeerSearchResult.php'; }
        if (!class_exists('AhgFederation\\Connectors\\OaiPmhConnector', false))           { include_once $connDir . '/OaiPmhConnector.php'; }
        if (!class_exists('AhgFederation\\Connectors\\AtomElasticsearchConnector', false)) { include_once $connDir . '/AtomElasticsearchConnector.php'; }
        if (!class_exists('AhgFederation\\Connectors\\SharePointGraphConnector', false))   { include_once $connDir . '/SharePointGraphConnector.php'; }
        $this->assert(interface_exists('AhgFederation\\Connectors\\PeerConnector', false), 'PeerConnector interface loadable');
        $this->assert(class_exists('AhgFederation\\Connectors\\PeerSearchResult', false),  'PeerSearchResult class loadable');
        $this->assert(class_exists('AhgFederation\\Connectors\\OaiPmhConnector', false),   'OaiPmhConnector class loadable');
        $this->assert(class_exists('AhgFederation\\Connectors\\AtomElasticsearchConnector', false), 'AtomElasticsearchConnector class loadable (F3 AtoM port)');

        // SharePoint connector (LOCAL only) (1)
        $this->assert(class_exists('AhgFederation\\Connectors\\SharePointGraphConnector', false), 'SharePointGraphConnector class loadable (LOCAL F3 AtoM port)');

        // Constants + interfaces (4)
        $this->assert(\AhgFederation\Connectors\AtomElasticsearchConnector::PEER_TYPE === 'atom_local', 'ES connector PEER_TYPE = atom_local');
        $this->assert(\AhgFederation\Connectors\SharePointGraphConnector::PEER_TYPE === 'sharepoint_graph_search', 'SP connector PEER_TYPE = sharepoint_graph_search');
        $esConn = new \AhgFederation\Connectors\AtomElasticsearchConnector();
        $this->assert($esConn->peerTypeKey() === 'atom_local', 'ES connector peerTypeKey() returns atom_local');
        $this->assert($esConn->supportsCapability('full_text_search'), 'ES connector supports full_text_search capability');

        // Demo peer + dispatch (5)
        $peer = $this->db()->table('federation_peer')->where('peer_type', 'atom_local')->where('is_active', 1)->first();
        $this->assertNotNull($peer, 'At least one active atom_local peer exists');
        $esConn->bind($peer);
        $hits = $esConn->search('atom', [], 5);
        $this->assert(is_array($hits), 'ES connector search returns an array');
        $this->assertGreaterThan(0, count($hits), 'ES connector returns at least one hit for "atom"');
        if (count($hits) > 0) {
            $first = $hits[0];
            $this->assert($first instanceof \AhgFederation\Connectors\PeerSearchResult, 'Hit is a PeerSearchResult instance');
        } else {
            $this->failures[] = 'PeerSearchResult instance check skipped (no hits)';
            $this->failed++;
        }

        // FederatedSearchService dispatch hook (4) — already loaded via autoloader
        $fssPath = sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/FederatedSearchService.php';
        $this->assertFileExists($fssPath, 'FederatedSearchService file exists');
        $this->assert(class_exists('AhgFederation\\FederatedSearchService'), 'FederatedSearchService class loadable');
        // The connector dispatch hook is private but we can verify its presence via reflection.
        $refl = new ReflectionClass('AhgFederation\\FederatedSearchService');
        $this->assert($refl->hasMethod('partitionPeersByDispatch'), 'FederatedSearchService::partitionPeersByDispatch present (F3 wiring)');
        $this->assert($refl->hasMethod('runConnector'), 'FederatedSearchService::runConnector present (F3 wiring)');

        // Existing OAI connector still works (2)
        $oaiConn = new \AhgFederation\Connectors\OaiPmhConnector();
        $this->assert($oaiConn->peerTypeKey() === 'oai_pmh', 'OaiPmhConnector still returns oai_pmh peerTypeKey');
        $this->assert($oaiConn->supportsCapability('full_text_search'), 'OaiPmhConnector still advertises full_text_search');

        // Result-shape sanity (2)
        $this->assertEquals(10, count(get_object_vars(new \AhgFederation\Connectors\PeerSearchResult(
            sourceId: 'x', title: 'x', snippet: null, url: 'x',
            peerType: 'x', sourceBadge: 'x', score: 0.5,
            dedupeKey: null, date: null, extras: []
        ))), 'PeerSearchResult has 10 public fields');
        $this->assert($peer && property_exists($peer, 'peer_type'), 'federation_peer row carries peer_type column');
    }

    // =========================================================================
    // Assertion harness
    // =========================================================================

    private function db()
    {
        return \Illuminate\Database\Capsule\Manager::connection();
    }

    /**
     * Load each .php in a plugin sub-directory, but only files whose declared
     * class isn't already known to the runtime. The active plugin autoloaders
     * usually have these loaded already; this is a fallback.
     */
    private function loadPluginServices(string $pluginName, string $relDir = 'lib/Services'): void
    {
        $dir = sfConfig::get('sf_plugins_dir') . '/' . $pluginName . '/' . $relDir;
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*.php') as $file) {
            $contents = file_get_contents($file);
            // Look for `namespace Foo\Bar;` and `class|interface Baz` patterns to compute the FQCN
            $namespace = '';
            if (preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $m)) {
                $namespace = trim($m[1]) . '\\';
            }
            $name = '';
            if (preg_match('/^\s*(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+([A-Za-z0-9_]+)/m', $contents, $m)) {
                $name = $m[1];
            }
            $fqcn = $name !== '' ? $namespace . $name : '';
            if ($fqcn !== '' && (class_exists($fqcn, false) || interface_exists($fqcn, false) || trait_exists($fqcn, false))) {
                continue;
            }
            include_once $file;
        }
    }

    private function logBanner(string $title): void
    {
        $this->logSection('regression', $title);
        $this->perFeature[$this->currentFeature] = ['passed' => 0, 'failed' => 0, 'failures' => []];
    }

    private function assert(bool $cond, string $msg): void
    {
        if ($cond) {
            $this->passed++;
            $this->perFeature[$this->currentFeature]['passed']++;
        } else {
            $this->failed++;
            $this->failures[] = '[' . $this->currentFeature . '] ' . $msg;
            $this->perFeature[$this->currentFeature]['failed']++;
            $this->perFeature[$this->currentFeature]['failures'][] = $msg;
            $this->log('  ✗ ' . $msg);
        }
    }

    private function assertEquals($expected, $actual, string $msg): void   { $this->assert($expected == $actual, $msg . " (expected=$expected actual=$actual)"); }
    private function assertGreaterThan($threshold, $actual, string $msg): void { $this->assert($actual > $threshold, $msg . " (threshold=$threshold actual=$actual)"); }
    private function assertGreaterThanOrEqual($threshold, $actual, string $msg): void { $this->assert($actual >= $threshold, $msg . " (threshold=$threshold actual=$actual)"); }
    private function assertNotNull($val, string $msg): void               { $this->assert($val !== null, $msg); }
    private function assertNotEmpty($val, string $msg): void              { $this->assert(!empty($val), $msg); }
    private function assertFileExists(string $path, string $msg): void    { $this->assert(is_file($path), $msg . " ({$path})"); }

    private function assertTableExists(string $table): void
    {
        $exists = (int) $this->db()->select(
            "SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        )[0]->c > 0;
        $this->assert($exists, "Table {$table} exists");
    }

    private function assertColumnExists(string $table, string $column): void
    {
        $exists = (int) $this->db()->select(
            "SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            [$table, $column]
        )[0]->c > 0;
        $this->assert($exists, "Column {$table}.{$column} exists");
    }

    private function assertRouteExists($routing, string $name): void
    {
        $routes = $routing->getRoutes();
        $this->assert(isset($routes[$name]), "Route {$name} registered");
    }

    private function printSummary(): void
    {
        $this->log('');
        $this->logSection('regression', '=== Summary ===');
        foreach ($this->perFeature as $feature => $stats) {
            $total = $stats['passed'] + $stats['failed'];
            $this->log(sprintf('  %s: %d/%d assertions pass', $feature, $stats['passed'], $total));
            foreach ($stats['failures'] as $f) {
                $this->log('    ✗ ' . $f);
            }
        }
        $grandTotal = $this->passed + $this->failed;
        $this->log(sprintf('  TOTAL: %d/%d assertions pass (%d failures)', $this->passed, $grandTotal, $this->failed));
        if ($this->failed > 0) {
            $this->log('  STATUS: FAIL');
        } else {
            $this->log('  STATUS: PASS');
        }
    }
}
