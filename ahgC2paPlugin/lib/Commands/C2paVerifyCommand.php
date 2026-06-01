<?php
/**
 * PSIS / AtoM-AHG - validate a stored C2PA manifest (re-hash assertions,
 * validate claim signature, walk the ingredient chain).
 *
 * Symfony 1.4 / PHP 8.3 port of Heratio's c2pa:verify Artisan command to the
 * atom-framework BaseCommand CLI (php bin/atom c2pa:verify).
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

namespace AtomFramework\Console\Commands\C2pa;

use AhgC2pa\Manifest\Assertion;
use AhgC2pa\Manifest\C2paSigner;
use AhgC2pa\Services\C2paService;
use AtomFramework\Console\BaseCommand;

class C2paVerifyCommand extends BaseCommand
{
    protected string $name = 'c2pa:verify';
    protected string $description = 'Verify a C2PA manifest: re-hash assertions, validate claim signature, walk ingredients';
    protected string $detailedDescription = <<<'EOF'
Verifies a C2PA manifest sidecar (.c2pa.json) or a signed-manifest JSON file.

Examples:
  php bin/atom c2pa:verify /mnt/nas/heratio/archive/.../photo.jpg.c2pa.json
  php bin/atom c2pa:verify manifest.json --public-key=<64-hex-chars>

Steps performed:
  1. Re-hash every assertion and compare against the claim's pinned hash.
  2. Verify the Ed25519 claim signature under the resolver-supplied public key.
     The key is resolved via ai_inference_key (kid) or the on-disk public key,
     unless --public-key overrides it.
  3. List declared ingredients.
EOF;

    protected function configure(): void
    {
        $this->addArgument('manifest-path', 'Absolute path to a .c2pa.json sidecar or signed-manifest JSON file', true);
        $this->addOption('public-key', null, 'Override: hex-encoded 32-byte raw Ed25519 public key (skips DB key lookup)');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/c2pa_bootstrap.php';
        \C2paBootstrap::load();

        $path = (string) $this->argument('manifest-path');
        if (!is_readable($path)) {
            $this->error("c2pa:verify: cannot read {$path}");

            return 1;
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            $this->error("c2pa:verify: empty manifest at {$path}");

            return 1;
        }

        try {
            $manifest = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->error('c2pa:verify: invalid JSON: ' . $e->getMessage());

            return 1;
        }

        if (!is_array($manifest)) {
            $this->error('c2pa:verify: top-level manifest must be an object');

            return 1;
        }

        $failures = 0;

        $assertions = $manifest['assertions'] ?? [];
        $claimRefs = $manifest['claim']['assertions'] ?? [];
        if (!is_array($assertions) || !is_array($claimRefs)) {
            $this->error('c2pa:verify: manifest missing assertions / claim.assertions array');

            return 1;
        }

        $this->line('-- Re-hashing assertions --');
        foreach ($assertions as $a) {
            if (!is_array($a) || !isset($a['label'], $a['data'])) {
                $this->error('  ! assertion missing label/data');
                $failures++;

                continue;
            }
            $assertion = new Assertion((string) $a['label'], (array) $a['data'], (int) ($a['instance'] ?? 1));
            $hash = $assertion->hashHex();

            $matched = false;
            foreach ($claimRefs as $ref) {
                if (!is_array($ref)) {
                    continue;
                }
                if (($ref['url'] ?? null) === $assertion->uri()) {
                    if (($ref['hash'] ?? null) === $hash) {
                        $matched = true;
                    }

                    break;
                }
            }
            if ($matched) {
                $this->success($assertion->uri());
            } else {
                $this->error($assertion->uri() . ' (hash mismatch or missing in claim)');
                $failures++;
            }
        }

        $this->line('-- Verifying claim signature --');
        $publicKeyOverride = $this->option('public-key');
        $resolver = function (string $kid) use ($publicKeyOverride): ?string {
            if (is_string($publicKeyOverride) && $publicKeyOverride !== '') {
                if (!ctype_xdigit($publicKeyOverride) || strlen($publicKeyOverride) !== 64) {
                    return null;
                }

                return hex2bin($publicKeyOverride) ?: null;
            }

            return C2paService::resolvePublicKey($kid);
        };

        $sigOk = false;
        try {
            $sigOk = C2paSigner::verify($manifest, $resolver);
        } catch (\Throwable $e) {
            $this->error('signature verify threw: ' . $e->getMessage());
        }
        if ($sigOk) {
            $this->success('claim_signature verifies under kid=' . ($manifest['claim_signature']['kid'] ?? '?'));
        } else {
            $this->error('claim_signature did NOT verify');
            $failures++;
        }

        $this->line('-- Ingredient chain --');
        $ingredientCount = 0;
        foreach ($assertions as $a) {
            if (($a['label'] ?? '') === 'c2pa.ingredients') {
                foreach (($a['data']['ingredients'] ?? []) as $ingredient) {
                    $ingredientCount++;
                    $title = $ingredient['title'] ?? '(untitled)';
                    $h = $ingredient['hash'] ?? '?';
                    $this->line("  - {$title}  sha256={$h}");
                }
            }
        }
        if ($ingredientCount === 0) {
            $this->line('  (no ingredients declared)');
        }

        if ($failures === 0) {
            $this->newline();
            $this->success('c2pa:verify PASSED');

            return 0;
        }

        $this->newline();
        $this->error("c2pa:verify FAILED ({$failures} problem(s))");

        return 1;
    }
}
