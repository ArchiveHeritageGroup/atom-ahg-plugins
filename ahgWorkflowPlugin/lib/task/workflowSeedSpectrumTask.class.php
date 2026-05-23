<?php

/**
 * workflowSeedSpectrumTask - PSIS Symfony port of Heratio Spectrum#B
 *   php artisan workflow:seed-spectrum
 *
 * Usage:
 *   php symfony workflow:seed-spectrum                      # Install missing procedures (safe, idempotent)
 *   php symfony workflow:seed-spectrum --dry-run            # Preview without writing
 *   php symfony workflow:seed-spectrum --only=object_entry  # Install just specified procedure(s)
 *   php symfony workflow:seed-spectrum --overwrite          # RESET existing seeded steps (destructive of custom edits)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class workflowSeedSpectrumTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('overwrite', null, sfCommandOption::PARAMETER_NONE, 'Replace name/description AND delete-and-reinstall steps for existing Spectrum workflows.'),
            new sfCommandOption('only', null, sfCommandOption::PARAMETER_OPTIONAL | sfCommandOption::IS_ARRAY, 'Install only specific procedure codes (e.g. --only=object_entry --only=cataloguing)'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would change without writing anything'),
        ]);

        $this->namespace = 'workflow';
        $this->name = 'seed-spectrum';
        $this->briefDescription = 'Install the Spectrum 5.1 procedure starter pack — 21 workflows with paraphrased canonical steps.';
        $this->detailedDescription = $this->briefDescription;
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->bootDatabaseConnection($options);

        $jsonPath = sfConfig::get('sf_plugins_dir').'/ahgWorkflowPlugin/database/spectrum_procedures.json';
        if (!is_file($jsonPath)) {
            $this->logSection('seed-spectrum', "Seed file not found: $jsonPath");
            return 1;
        }

        $raw = json_decode((string) file_get_contents($jsonPath), true);
        if (!is_array($raw) || empty($raw['procedures'])) {
            $this->logSection('seed-spectrum', "Seed file is malformed (no 'procedures' key).");
            return 1;
        }

        $overwrite = !empty($options['overwrite']);
        $dryRun = !empty($options['dry-run']);
        $onlyCodes = array_filter((array) ($options['only'] ?? []));

        if ($dryRun) {
            $this->log('DRY RUN — no DB writes will be made.');
        }
        if ($overwrite) {
            $this->log('OVERWRITE mode — existing Spectrum workflow steps will be REPLACED. Hand-customised steps for those procedures will be lost.');
        }
        if (!empty($onlyCodes)) {
            $this->log('Limited to: '.implode(', ', $onlyCodes));
        }

        require_once dirname(__DIR__).'/Services/SpectrumProcedureCatalog.php';

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'invalid_code' => 0];
        $catalogCodes = SpectrumProcedureCatalog::codes();

        foreach ($raw['procedures'] as $code => $procedure) {
            if (!empty($onlyCodes) && !in_array($code, $onlyCodes, true)) {
                continue;
            }
            if (!in_array($code, $catalogCodes, true)) {
                $this->log("  x {$code}: not in catalog (skipping)");
                $stats['invalid_code']++;
                continue;
            }

            $result = $this->seedProcedure($code, $procedure, $overwrite, $dryRun);
            $stats[$result['action']]++;
            $this->log("  ".$result['icon']." {$code}: ".$result['message']);
        }

        $this->log('');
        $this->log(sprintf(
            'Done. Created: %d  Updated: %d  Skipped: %d  Invalid: %d',
            $stats['created'], $stats['updated'], $stats['skipped'], $stats['invalid_code']
        ));

        return 0;
    }

    /**
     * Bootstrap the Laravel Capsule connection — the atom-framework normally
     * wires this up but Symfony tasks need to nudge it explicitly.
     */
    private function bootDatabaseConnection(array $options): void
    {
        $configuration = ProjectConfiguration::getApplicationConfiguration($options['application'], $options['env'], true);
        sfContext::createInstance($configuration);
        // Capsule is wired by atom-framework's bootstrap; touching sfContext is enough.
    }

    /**
     * @return array{action:string, icon:string, message:string}
     */
    private function seedProcedure(string $code, array $procedure, bool $overwrite, bool $dryRun): array
    {
        $existing = DB::table('ahg_workflow')->where('spectrum_procedure', $code)->first();

        if ($existing === null) {
            if ($dryRun) {
                return ['action' => 'created', 'icon' => '+', 'message' => 'would CREATE workflow + '.count($procedure['steps'] ?? []).' steps'];
            }
            $result = DB::transaction(function () use ($code, $procedure) {
                $wfId = $this->createWorkflow($code, $procedure);
                $count = $this->insertSteps($wfId, $procedure['steps'] ?? []);
                return [$wfId, $count];
            });
            [$workflowId, $stepCount] = $result;
            return ['action' => 'created', 'icon' => '+', 'message' => "created workflow id={$workflowId} with {$stepCount} steps"];
        }

        if (!$overwrite) {
            return ['action' => 'skipped', 'icon' => '=', 'message' => "exists (id={$existing->id}), no --overwrite — skipping"];
        }

        if ($dryRun) {
            $existingStepCount = DB::table('ahg_workflow_step')->where('workflow_id', $existing->id)->count();
            return ['action' => 'updated', 'icon' => '~', 'message' => "would UPDATE workflow id={$existing->id} and REPLACE {$existingStepCount} existing steps with ".count($procedure['steps'] ?? []).' seed steps'];
        }

        DB::transaction(function () use ($existing, $procedure) {
            DB::table('ahg_workflow')->where('id', $existing->id)->update([
                'name'        => $procedure['name'] ?? $existing->name,
                'description' => $procedure['description'] ?? $existing->description,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
            DB::table('ahg_workflow_step')->where('workflow_id', $existing->id)->delete();
            $this->insertSteps((int) $existing->id, $procedure['steps'] ?? []);
        });

        return ['action' => 'updated', 'icon' => '~', 'message' => "updated workflow id={$existing->id} (steps replaced)"];
    }

    private function createWorkflow(string $code, array $procedure): int
    {
        $now = date('Y-m-d H:i:s');
        return (int) DB::table('ahg_workflow')->insertGetId([
            'name'                 => $procedure['name'] ?? "Spectrum: {$code}",
            'description'          => $procedure['description'] ?? null,
            'scope_type'           => 'global',
            'trigger_event'        => 'submit',
            'applies_to'           => 'information_object',
            'is_active'            => 1,
            'is_default'           => 0,
            'require_all_steps'    => 1,
            'allow_parallel'       => 0,
            'notification_enabled' => 1,
            'spectrum_procedure'   => $code,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
    }

    private function insertSteps(int $workflowId, array $steps): int
    {
        $now = date('Y-m-d H:i:s');
        $count = 0;
        foreach (array_values($steps) as $i => $step) {
            DB::table('ahg_workflow_step')->insert([
                'workflow_id'      => $workflowId,
                'name'             => $step['name'] ?? 'Step '.($i + 1),
                'description'      => $step['description'] ?? null,
                'step_order'       => $i + 1,
                'step_type'        => $step['step_type'] ?? 'review',
                'action_required'  => $step['action_required'] ?? 'approve_reject',
                'instructions'     => $step['instructions'] ?? null,
                'is_optional'      => $step['is_optional'] ?? 0,
                'is_active'        => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $count++;
        }
        return $count;
    }
}
