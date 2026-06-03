<?php
/**
 * PSIS / AtoM-AHG - retention pruner for the AI inference chain.
 *
 * Nulls out payload_json on rows past the configured retention window so PII
 * does not linger forever, while preserving seq / prev_hash / entry_hash /
 * signature so the chain remains structurally verifiable indefinitely.
 *
 * Default retention: 7 years. Override per-run with --years or persistently
 * via the AtoM setting `ai_compliance_retention_years` (settings_i18n).
 *
 *   php symfony ai-compliance:prune
 *   php symfony ai-compliance:prune --years=10
 *   php symfony ai-compliance:prune --dry-run
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use Illuminate\Database\Capsule\Manager as DB;

class aiCompliancePruneTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('years', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override the configured retention window (years)', null),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report what would be pruned without writing'),
        ]);

        $this->namespace        = 'ai-compliance';
        $this->name             = 'prune';
        $this->briefDescription = 'Null payload_json on inference-log rows older than the retention window';
        $this->detailedDescription = <<<EOF
The [ai-compliance:prune|INFO] task nulls payload_json on inference-log rows
past the configured retention window. seq / prev_hash / entry_hash / signature
are preserved so the chain remains verifiable; only the input + output
fingerprints + PII-bearing payload are dropped.

Default retention is 7 years. Override per-run with --years or persistently
by setting the AtoM setting [ai_compliance_retention_years|INFO] in
ahg_settings (or settings_i18n with key ai_compliance_retention_years).

  [php symfony ai-compliance:prune|INFO]
  [php symfony ai-compliance:prune --dry-run|INFO]
  [php symfony ai-compliance:prune --years=10|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $this->bootFramework();

        $configured = $options['years'] !== null
            ? (float) $options['years']
            : $this->settingYears();

        $thresholdSecs = (int) round($configured * 365 * 24 * 3600);
        $threshold     = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('-' . $thresholdSecs . ' seconds');
        $thresholdSql  = $threshold->format('Y-m-d H:i:s.v');

        $eligible = DB::table('ai_inference_log')
            ->where('ts', '<', $thresholdSql)
            ->whereNull('payload_pruned_at');

        $count = (int) (clone $eligible)->count();

        $this->logBlock([
            sprintf('Retention window:  %s years', $configured),
            sprintf('Threshold:         %s', $threshold->format(DATE_ATOM)),
            sprintf('Rows to prune:     %d', $count),
        ], 'INFO');

        if ($count === 0) {
            $this->logSection('ai-compliance', 'Nothing to prune.');
            return 0;
        }

        if (!empty($options['dry-run'])) {
            $this->logSection('ai-compliance', 'Dry run; no rows touched.');
            return 0;
        }

        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $updated = $eligible->update([
            'payload_json'      => null,
            'payload_pruned_at' => $now,
        ]);

        $this->logSection('ai-compliance', sprintf(
            'Pruned %d rows (hash + signature + chain links preserved).',
            $updated
        ));
        return 0;
    }

    /**
     * Read the configured retention window in years from AtoM settings.
     * Falls back to 7 years if no setting row exists.
     */
    private function settingYears(): float
    {
        // Prefer ahg_settings if present (the AtoM-AHG settings plugin).
        try {
            if (DB::schema()->hasTable('ahg_settings')) {
                $row = DB::table('ahg_settings')
                    ->where('name', 'ai_compliance_retention_years')
                    ->first(['value']);
                if ($row !== null && $row->value !== null && $row->value !== '') {
                    return (float) $row->value;
                }
            }
        } catch (\Throwable) {
            // schema() may be unavailable depending on Capsule init; ignore + fall through.
        }

        // Stock AtoM settings_i18n
        try {
            $row = DB::table('settings_i18n')
                ->where('name', 'ai_compliance_retention_years')
                ->first(['value']);
            if ($row !== null && $row->value !== null && $row->value !== '') {
                return (float) $row->value;
            }
        } catch (\Throwable) {
        }

        return 7.0;
    }

    private function bootFramework(): void
    {
        $databaseManager = new sfDatabaseManager($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}
