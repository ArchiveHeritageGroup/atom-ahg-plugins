<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for DONUT document understanding / structured parsing.
 *
 * Mirrors the Heratio ahg:donut path. Sends document images to the DONUT
 * gateway (ahg-ai python service on host .115:5008) and stores the
 * structured result in ahg_donut_extraction. Degrades gracefully when the
 * gateway is unavailable.
 *
 * Examples:
 *   php bin/atom ai:donut-extract --file=/path/to/scan.jpg
 *   php bin/atom ai:donut-extract --file=/path/scan.tif --object=12345
 *   php bin/atom ai:donut-extract --dir=/incoming/scans --limit=50
 *   php bin/atom ai:donut-extract --health
 */
class DonutExtractCommand extends BaseCommand
{
    protected string $name = 'ai:donut-extract';
    protected string $description = 'DONUT structured document parsing of document images';
    protected string $detailedDescription = <<<'EOF'
    Send document images to the DONUT model (via the ahg-ai service) for
    structured parsing and store the results in ahg_donut_extraction.

    Examples:
      php bin/atom ai:donut-extract --file=/path/to/scan.jpg
      php bin/atom ai:donut-extract --file=/path/scan.tif --object=12345
      php bin/atom ai:donut-extract --dir=/incoming/scans --limit=50
      php bin/atom ai:donut-extract --health
    EOF;

    protected function configure(): void
    {
        $this->addOption('file', null, 'Extract a single document image (absolute path)');
        $this->addOption('dir', null, 'Extract every supported image in a directory');
        $this->addOption('object', null, 'Attach the extraction to this information object id');
        $this->addOption('limit', null, 'Maximum number of files to process from --dir', '100');
        $this->addOption('classify', null, 'Classify document type only (no full extraction)');
        $this->addOption('health', null, 'Check DONUT gateway availability and exit');
        $this->addOption('dry-run', null, 'List the files that would be processed, do nothing');
    }

    protected function handle(): int
    {
        $servicePath = dirname(__DIR__, 2) . '/lib/Services/ahgDonutService.php';
        if (!is_file($servicePath)) {
            $this->error('ahgDonutService not found at ' . $servicePath);
            return 1;
        }
        require_once $servicePath;
        /** @var \ahgDonutService $service */
        $service = new \ahgDonutService();

        if ($this->hasOption('health')) {
            $health = $service->health();
            if ($health === null) {
                $this->error('DONUT gateway is UNAVAILABLE.');
                return 1;
            }
            $this->info('DONUT gateway is available.');
            $this->line(json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $files = $this->resolveFiles();
        if (empty($files)) {
            $this->error('Nothing to process. Provide --file=<path> or --dir=<path>.');
            return 1;
        }

        if ($this->hasOption('dry-run')) {
            $this->info(sprintf('Would process %d file(s):', count($files)));
            foreach ($files as $f) {
                $this->line('  ' . $f);
            }
            return 0;
        }

        if (!$service->isAvailable()) {
            $this->error('DONUT gateway unavailable - aborting (no files processed).');
            return 1;
        }

        $objectId = $this->option('object') !== null ? (int) $this->option('object') : null;
        $classifyOnly = $this->hasOption('classify');

        $ok = 0;
        $fail = 0;
        foreach ($files as $file) {
            $result = $classifyOnly
                ? $service->classify($file)
                : $service->extract($file, $objectId);

            if (!is_array($result) || !empty($result['error'])) {
                $fail++;
                $this->error('  FAIL ' . basename($file) . ': '
                    . (is_array($result) ? ($result['error'] ?? 'unknown error') : 'gateway returned null'));
                continue;
            }

            $ok++;
            $label = $classifyOnly
                ? ('type=' . ($result['doc_type'] ?? $result['document_type'] ?? '?'))
                : ('extraction_id=' . ($result['extraction_id'] ?? 'n/a')
                    . ' conf=' . ($result['confidence'] ?? '?'));
            $this->info('  OK   ' . basename($file) . ' (' . $label . ')');
        }

        $this->line('');
        $this->info(sprintf('DONUT done: %d ok, %d failed.', $ok, $fail));

        return $fail > 0 && $ok === 0 ? 1 : 0;
    }

    /**
     * @return array<int,string>
     */
    private function resolveFiles(): array
    {
        $exts = ['jpg', 'jpeg', 'png', 'tif', 'tiff', 'pdf'];

        if ($this->option('file') !== null) {
            $f = (string) $this->option('file');
            if (!is_readable($f)) {
                $this->error('File not readable: ' . $f);
                return [];
            }
            return [$f];
        }

        if ($this->option('dir') !== null) {
            $dir = rtrim((string) $this->option('dir'), '/');
            if (!is_dir($dir)) {
                $this->error('Directory not found: ' . $dir);
                return [];
            }
            $limit = (int) $this->option('limit');
            $found = [];
            foreach (scandir($dir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $path = $dir . '/' . $entry;
                if (!is_file($path)) {
                    continue;
                }
                if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $exts, true)) {
                    $found[] = $path;
                    if ($limit > 0 && count($found) >= $limit) {
                        break;
                    }
                }
            }
            sort($found);
            return $found;
        }

        return [];
    }
}
