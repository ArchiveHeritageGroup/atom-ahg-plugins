<?php
/**
 * PSIS / AtoM-AHG - bench command: build + sign a C2PA manifest for a fake AI
 * suggestion against an IO, dump it, write a sidecar.
 *
 * Symfony 1.4 / PHP 8.3 port of Heratio's c2pa:smoke Artisan command to the
 * atom-framework BaseCommand CLI (php bin/atom c2pa:smoke).
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

namespace AtomFramework\Console\Commands\C2pa;

use AhgC2pa\Services\C2paService;
use AtomFramework\Console\BaseCommand;

class C2paSmokeCommand extends BaseCommand
{
    protected string $name = 'c2pa:smoke';
    protected string $description = 'Build, sign and dump a C2PA manifest for a hypothetical AI suggestion (deployment check)';
    protected string $detailedDescription = <<<'EOF'
Builds + signs a C2PA manifest for a simulated AI output against an IO and
writes a sidecar. Use it to confirm the signing key + crypto library + c2patool
are all wired correctly after deployment.

Examples:
  php bin/atom c2pa:smoke 1234
  php bin/atom c2pa:smoke 1234 "Custom AI suggestion text" --action=ai-assisted
  php bin/atom c2pa:smoke 1234 --model=qwen3:14b --no-write
  php bin/atom c2pa:smoke 1234 --sidecar-dir=/tmp/c2pa-smoke

Signing requires an Ed25519 key installed via ahgAiCompliancePlugin:
  php symfony ai-compliance:install-key
Without a key, the manifest is still built and dumped (unsigned).
EOF;

    protected function configure(): void
    {
        $this->addArgument('ioId', 'information_object id the fake AI output is attached to', true);
        $this->addArgument('output-text', 'Body of the simulated AI output');
        $this->addOption('action', null, 'One of ai-generated, ai-assisted', 'ai-generated');
        $this->addOption('model', null, 'Model id to embed in the manifest', 'qwen3:14b');
        $this->addOption('model-version', null, 'Model version string');
        $this->addOption('no-write', null, 'Skip sidecar write; print to stdout only');
        $this->addOption('sidecar-dir', null, 'Directory for the sidecar file');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/c2pa_bootstrap.php';
        \C2paBootstrap::load();

        $ioId = (int) $this->argument('ioId');
        $action = (string) $this->option('action', 'ai-generated');
        $modelId = (string) $this->option('model', 'qwen3:14b');
        $modelVersion = $this->option('model-version');
        $output = (string) ($this->argument('output-text')
            ?? 'Smoke-test AI suggestion: this archival description appears to describe a 1920s mining permit.');

        $signer = \C2paBootstrap::loadSigner();
        $service = new C2paService($signer);

        try {
            $manifest = $service->manifestForAiSuggestion(
                informationObjectId: $ioId,
                action: $action,
                modelId: $modelId,
                modelVersion: $modelVersion !== null ? (string) $modelVersion : null,
                output: $output,
            );
        } catch (\Throwable $e) {
            $this->error('c2pa:smoke: ' . $e->getMessage());

            return 1;
        }

        if (!$service->canSign()) {
            $this->warning('No signing key installed - dumping UNSIGNED manifest. Run `php symfony ai-compliance:install-key` to sign.');
            $unsigned = $manifest;
            unset($unsigned['_claim_object']);
            $this->line('--- Unsigned C2PA manifest ---');
            $this->line((string) json_encode($unsigned, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return 0;
        }

        try {
            $signed = $service->signManifest($manifest);
        } catch (\Throwable $e) {
            $this->error('c2pa:smoke: ' . $e->getMessage());

            return 1;
        }

        $this->line('--- Signed C2PA manifest ---');
        $this->line((string) json_encode($signed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($this->hasOption('no-write')) {
            return 0;
        }

        $dir = $this->option('sidecar-dir');
        if (!is_string($dir) || $dir === '') {
            $dir = sys_get_temp_dir() . '/c2pa-smoke';
        }
        $artefact = rtrim($dir, '/') . '/io-' . $ioId . '-' . date('Ymd-His');

        try {
            $sidecarPath = $service->sidecar($signed, $artefact);
        } catch (\Throwable $e) {
            $this->error('c2pa:smoke: sidecar write failed: ' . $e->getMessage());

            return 1;
        }

        // Best-effort persist for audit parity with the Heratio command.
        $rowId = $service->persist($signed, $ioId, $action, $modelId, $modelVersion !== null ? (string) $modelVersion : null, $sidecarPath);

        $this->newline();
        $this->success('Sidecar written: ' . $sidecarPath);
        if ($rowId !== null) {
            $this->info('Persisted to ahg_c2pa_manifest id=' . $rowId);
        } else {
            $this->comment('ahg_c2pa_manifest not present - skipped DB persist (run database/install.sql).');
        }
        if ($service->canEmbed()) {
            $this->comment('c2patool found at ' . $service->toolPath() . ' - JUMBF embedding available via C2paService::embedInJpeg().');
        } else {
            $this->comment('c2patool not installed - embed falls back to sidecar.');
        }

        return 0;
    }
}
