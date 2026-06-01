<?php

namespace AtomFramework\Console\Commands\Scan;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * scan:install — ahgScanPlugin.
 *
 * Convenience installer + folder registration helper.
 *
 *   php bin/atom scan:install --schema
 *       Apply the plugin's database/install.sql (scan_folder, scan_event,
 *       and the ingest_session streaming columns).
 *
 *   php bin/atom scan:install --add --code=incoming --label="Incoming" \
 *       --path=/srv/scan/incoming --parent=123 --sector=archive --standard=isadg
 *       Register a watched folder + its backing ingest_session.
 *
 *   php bin/atom scan:install --list
 *       List configured folders.
 */
class ScanInstallCommand extends BaseCommand
{
    protected string $name = 'scan:install';
    protected string $description = 'Install scan schema and register watched folders';
    protected string $detailedDescription = <<<'EOF'
    Apply the ahgScanPlugin schema and register watched folders.

    Examples:
      php bin/atom scan:install --schema
      php bin/atom scan:install --list
      php bin/atom scan:install --add --code=incoming --label="Incoming archive" \
          --path=/srv/scan/incoming --parent=123 --sector=archive --standard=isadg
    EOF;

    protected function configure(): void
    {
        $this->addOption('schema', null, 'Apply database/install.sql');
        $this->addOption('list', null, 'List configured watched folders');
        $this->addOption('add', null, 'Register a new watched folder');
        $this->addOption('code', null, 'Folder code (unique)');
        $this->addOption('label', null, 'Human label');
        $this->addOption('path', null, 'Absolute watched directory path');
        $this->addOption('layout', null, 'flat or path', 'flat');
        $this->addOption('parent', null, 'Parent information_object id for new records');
        $this->addOption('repository', null, 'Repository id');
        $this->addOption('sector', null, 'archive, museum, library, gallery, dam', 'archive');
        $this->addOption('standard', null, 'isadg, dc, spectrum, cco, rad, dacs', 'isadg');
        $this->addOption('disposition-success', null, 'move, delete, leave', 'move');
        $this->addOption('disposition-failure', null, 'quarantine, leave', 'quarantine');
        $this->addOption('quiet', null, 'Quiet seconds before ingest', '10');
        $this->addOption('user', null, 'Creating user id', '1');
    }

    protected function handle(): int
    {
        if ($this->hasOption('schema')) {
            return $this->applySchema();
        }

        $this->requireServices();

        if ($this->hasOption('list')) {
            return $this->listFolders();
        }

        if ($this->hasOption('add')) {
            return $this->addFolder();
        }

        $this->showHelp();

        return 0;
    }

    private function applySchema(): int
    {
        $sqlFile = $this->getPluginsRoot() . '/ahgScanPlugin/database/install.sql';
        if (!is_file($sqlFile)) {
            $this->error("install.sql not found at {$sqlFile}");

            return 1;
        }

        $this->info('Applying ahgScanPlugin schema...');
        $sql = file_get_contents($sqlFile);
        $pdo = DB::connection()->getPdo();

        // Split on semicolons at end-of-line; the guarded ALTER blocks use
        // PREPARE/EXECUTE which are statement-terminated, so a naive split is
        // safe here (no procedure bodies / no embedded ; in string literals
        // except the default value, which lives on its own statement line).
        foreach ($this->splitStatements($sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            try {
                $pdo->exec($stmt);
            } catch (\Throwable $e) {
                $this->warning('Statement skipped: ' . $e->getMessage());
            }
        }

        $ok = DB::schema()->hasTable('scan_folder') && DB::schema()->hasTable('scan_event');
        if ($ok) {
            $this->success('Schema installed (scan_folder, scan_event).');

            return 0;
        }

        $this->error('Schema install incomplete — check warnings above.');

        return 1;
    }

    private function listFolders(): int
    {
        $svc = new \AhgScanPlugin\Services\WatchedFolderService();
        $folders = $svc->listAll();
        if (empty($folders)) {
            $this->comment('No watched folders configured.');

            return 0;
        }

        $rows = [];
        foreach ($folders as $f) {
            $rows[] = [
                $f->id,
                $f->code,
                $f->enabled ? 'on' : 'off',
                $f->layout,
                $f->path,
                $f->session_title ?? ('session ' . $f->ingest_session_id),
                $f->last_scanned_at ?? 'never',
            ];
        }
        $this->table(['ID', 'Code', 'Enabled', 'Layout', 'Path', 'Session', 'Last scan'], $rows);

        return 0;
    }

    private function addFolder(): int
    {
        $code = $this->option('code');
        $path = $this->option('path');
        if (!$code || !$path) {
            $this->error('--code and --path are required for --add.');

            return 1;
        }
        if (!is_dir($path)) {
            $this->warning("Path does not exist yet: {$path} (the watcher will skip it until created).");
        }

        $svc = new \AhgScanPlugin\Services\WatchedFolderService();
        if ($svc->findByCode($code)) {
            $this->error("A folder with code '{$code}' already exists.");

            return 1;
        }

        $id = $svc->create([
            'code' => $code,
            'label' => $this->option('label') ?: $code,
            'path' => $path,
            'layout' => $this->option('layout'),
            'parent_id' => $this->option('parent') ? (int) $this->option('parent') : null,
            'repository_id' => $this->option('repository') ? (int) $this->option('repository') : null,
            'sector' => $this->option('sector'),
            'standard' => $this->option('standard'),
            'disposition_success' => $this->option('disposition-success'),
            'disposition_failure' => $this->option('disposition-failure'),
            'min_quiet_seconds' => (int) $this->option('quiet'),
        ], (int) $this->option('user'));

        $this->success("Registered watched folder #{$id} ({$code}).");
        $this->line('Start watching with: php bin/atom scan:watch --once');

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        // Strip line comments, then split on ';' at line ends.
        $lines = preg_split('/\R/', $sql) ?: [];
        $clean = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*--/', $line)) {
                continue;
            }
            $clean[] = $line;
        }

        return explode(";\n", implode("\n", $clean) . "\n");
    }

    private function requireServices(): void
    {
        $base = $this->getPluginsRoot() . '/ahgScanPlugin/lib/Services';
        require_once $base . '/WatchedFolderService.php';
        require_once $base . '/ScannerService.php';
    }
}
