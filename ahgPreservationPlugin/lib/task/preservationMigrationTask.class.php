<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task for format migration planning and reporting.
 *
 * Provides CLI commands for:
 * - Listing available migration pathways
 * - Generating obsolescence reports
 * - Managing migration plans
 * - Refreshing obsolescence assessments
 */
class preservationMigrationTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),

            // Actions
            new sfCommandOption('pathways', null, sfCommandOption::PARAMETER_NONE, 'List all migration pathways'),
            new sfCommandOption('obsolescence', null, sfCommandOption::PARAMETER_NONE, 'Generate obsolescence report'),
            new sfCommandOption('plans', null, sfCommandOption::PARAMETER_NONE, 'List migration plans'),
            new sfCommandOption('assess', null, sfCommandOption::PARAMETER_OPTIONAL, 'Assess a specific format by PUID', null),
            new sfCommandOption('refresh', null, sfCommandOption::PARAMETER_NONE, 'Refresh obsolescence counts'),
            new sfCommandOption('tools', null, sfCommandOption::PARAMETER_NONE, 'Check available migration tools'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show migration statistics'),

            // Filters
            new sfCommandOption('source', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by source PUID'),
            new sfCommandOption('target', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by target PUID'),
            new sfCommandOption('tool', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by migration tool'),
            new sfCommandOption('risk', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by risk level (low, medium, high, critical)'),
            new sfCommandOption('urgency', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by migration urgency'),
            new sfCommandOption('recommended', null, sfCommandOption::PARAMETER_NONE, 'Show only recommended pathways'),

            // Plan management
            new sfCommandOption('plan-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific plan ID'),
            new sfCommandOption('plan-status', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter plans by status'),

            // Output
            new sfCommandOption('json', null, sfCommandOption::PARAMETER_NONE, 'Output as JSON'),
            new sfCommandOption('verbose', 'v', sfCommandOption::PARAMETER_NONE, 'Verbose output'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'migration';
        $this->briefDescription = 'Format migration planning and obsolescence reporting';
        $this->detailedDescription = <<<EOF
Manage format migration pathways and assess format obsolescence risk.

Examples:
  php symfony preservation:migration --pathways              # List all migration pathways
  php symfony preservation:migration --pathways --source=fmt/44   # Pathways from JPEG
  php symfony preservation:migration --pathways --recommended     # Only recommended pathways
  php symfony preservation:migration --obsolescence              # Generate obsolescence report
  php symfony preservation:migration --obsolescence --risk=critical # Critical formats only
  php symfony preservation:migration --assess=fmt/44             # Assess JPEG format
  php symfony preservation:migration --refresh                   # Refresh obsolescence counts
  php symfony preservation:migration --tools                     # Check available tools
  php symfony preservation:migration --plans                     # List migration plans
  php symfony preservation:migration --stats                     # Migration statistics
  php symfony preservation:migration --pathways --json           # JSON output

Migration Pathways:
  Pre-defined routes for converting files from one format to another.
  Each pathway specifies the tool, command, and quality impact.

Obsolescence Tracking:
  Monitors formats at risk of becoming obsolete and tracks
  the number of affected digital objects requiring migration.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once dirname(__DIR__).'/Services/MigrationPathwayService.php';
        require_once dirname(__DIR__).'/Services/MigrationPlanService.php';

        $pathwayService = new MigrationPathwayService();
        $planService = new MigrationPlanService();

        $isJson = isset($options['json']);
        $verbose = isset($options['verbose']);

        // Route to appropriate action
        if (isset($options['pathways'])) {
            $this->showPathways($pathwayService, $options, $isJson, $verbose);
        } elseif (isset($options['obsolescence'])) {
            $this->showObsolescence($pathwayService, $options, $isJson, $verbose);
        } elseif (!empty($options['assess'])) {
            $this->assessFormat($pathwayService, $options['assess'], $isJson);
        } elseif (isset($options['refresh'])) {
            $this->refreshCounts($pathwayService, $isJson);
        } elseif (isset($options['tools'])) {
            $this->checkTools($pathwayService, $isJson);
        } elseif (isset($options['plans'])) {
            $this->showPlans($planService, $options, $isJson, $verbose);
        } elseif (isset($options['stats'])) {
            $this->showStats($pathwayService, $planService, $isJson);
        } else {
            // Default: show summary
            $this->showSummary($pathwayService, $planService);
        }
    }

    /**
     * Display migration pathways.
     */
    protected function showPathways(MigrationPathwayService $service, array $options, bool $json, bool $verbose): void
    {
        $filters = [];

        if (!empty($options['source'])) {
            $filters['source_puid'] = $options['source'];
        }
        if (!empty($options['target'])) {
            $filters['target_puid'] = $options['target'];
        }
        if (!empty($options['tool'])) {
            $filters['tool'] = $options['tool'];
        }
        if (isset($options['recommended'])) {
            $filters['recommended_only'] = true;
        }

        $pathways = $service->getPathways($filters);

        if ($json) {
            echo json_encode($pathways, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('migration', 'Migration Pathways');
        $this->logSection('migration', str_repeat('-', 60));

        if (empty($pathways)) {
            $this->logSection('migration', 'No pathways found matching criteria.');

            return;
        }

        $this->logSection('migration', sprintf('Found %d pathway(s)', count($pathways)));
        $this->logSection('migration', '');

        foreach ($pathways as $p) {
            $rec = $p->is_recommended ? ' [RECOMMENDED]' : '';
            $auto = $p->is_automated ? '' : ' [MANUAL]';
            $this->logSection('migration', sprintf(
                '#%d: %s → %s%s%s',
                $p->id,
                $p->source_format_name ?? $p->source_puid,
                $p->target_format_name ?? $p->target_puid,
                $rec,
                $auto
            ));

            $this->logSection('migration', sprintf(
                '    Tool: %s | Quality: %s | Fidelity: %s',
                $p->migration_tool,
                $p->quality_impact,
                $p->fidelity_score ? $p->fidelity_score.'%' : 'N/A'
            ));

            if ($verbose && $p->migration_command) {
                $this->logSection('migration', "    Command: {$p->migration_command}");
            }
            if ($verbose && $p->notes) {
                $this->logSection('migration', "    Notes: {$p->notes}");
            }
            $this->logSection('migration', '');
        }
    }

    /**
     * Display obsolescence report.
     */
    protected function showObsolescence(MigrationPathwayService $service, array $options, bool $json, bool $verbose): void
    {
        $filters = [];

        if (!empty($options['risk'])) {
            $filters['risk_level'] = $options['risk'];
        }
        if (!empty($options['urgency'])) {
            $filters['urgency'] = $options['urgency'];
        }

        $report = $service->getObsolescenceReport($filters);

        if ($json) {
            echo json_encode($report, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('migration', 'Format Obsolescence Report');
        $this->logSection('migration', str_repeat('-', 60));

        if (empty($report)) {
            $this->logSection('migration', 'No formats flagged for obsolescence concern.');
            $this->logSection('migration', 'Run --refresh to update obsolescence tracking.');

            return;
        }

        $this->logSection('migration', sprintf('Found %d format(s) with obsolescence concerns', count($report)));
        $this->logSection('migration', '');

        // Group by urgency
        $byUrgency = [];
        foreach ($report as $item) {
            $byUrgency[$item->migration_urgency][] = $item;
        }

        foreach (['critical', 'high', 'medium', 'low', 'none'] as $urgency) {
            if (empty($byUrgency[$urgency])) {
                continue;
            }

            $urgencyLabel = strtoupper($urgency);
            $color = in_array($urgency, ['critical', 'high']) ? 'ERROR' : 'INFO';
            $this->logSection('migration', "=== {$urgencyLabel} URGENCY ===", null, $color);

            foreach ($byUrgency[$urgency] as $item) {
                $sizeFormatted = $this->formatBytes($item->storage_size_bytes ?? 0);

                $this->logSection('migration', sprintf(
                    '%s (%s) - %s',
                    $item->format_name,
                    $item->puid,
                    $item->mime_type
                ));

                $this->logSection('migration', sprintf(
                    '  Risk: %s | Objects: %d | Size: %s',
                    strtoupper($item->current_risk_level),
                    $item->affected_object_count,
                    $sizeFormatted
                ));

                if ($item->recommended_action) {
                    $this->logSection('migration', "  Action: {$item->recommended_action}");
                }

                if ($item->recommended_target_puid) {
                    $this->logSection('migration', sprintf(
                        '  Recommended: Migrate to %s using %s',
                        $item->recommended_target_puid,
                        $item->recommended_tool ?? 'available tool'
                    ));
                }

                $this->logSection('migration', '');
            }
        }

        // Show alerts summary
        $alerts = $service->getObsolescenceAlerts();
        if (!empty($alerts)) {
            $this->logSection('migration', '');
            $this->logSection('migration', '*** ATTENTION: '.count($alerts).' format(s) require immediate action! ***', null, 'ERROR');
        }
    }

    /**
     * Assess a specific format.
     */
    protected function assessFormat(MigrationPathwayService $service, string $puid, bool $json): void
    {
        $assessment = $service->assessFormat($puid);

        if ($json) {
            echo json_encode($assessment, JSON_PRETTY_PRINT)."\n";

            return;
        }

        if (isset($assessment['error'])) {
            $this->logSection('migration', "Error: {$assessment['error']}", null, 'ERROR');

            return;
        }

        $this->logSection('migration', 'Format Assessment');
        $this->logSection('migration', str_repeat('-', 40));
        $this->logSection('migration', "Format: {$assessment['format_name']}");
        $this->logSection('migration', "PUID: {$assessment['puid']}");
        $this->logSection('migration', "MIME: {$assessment['mime_type']}");
        $this->logSection('migration', '');

        $riskColor = in_array($assessment['risk_level'], ['critical', 'high']) ? 'ERROR' : 'INFO';
        $this->logSection('migration', sprintf('Risk Level: %s', strtoupper($assessment['risk_level'])), null, $riskColor);
        $this->logSection('migration', sprintf('Migration Urgency: %s', strtoupper($assessment['migration_urgency'])));
        $this->logSection('migration', sprintf('Is Preservation Format: %s', $assessment['is_preservation_format'] ? 'Yes' : 'No'));
        $this->logSection('migration', '');
        $this->logSection('migration', sprintf('Affected Objects: %d', $assessment['affected_objects']));
        $this->logSection('migration', sprintf('Storage Size: %s', $this->formatBytes($assessment['storage_bytes'])));
        $this->logSection('migration', sprintf('Available Pathways: %d', $assessment['available_pathways']));

        if ($assessment['recommended_pathway']) {
            $rp = $assessment['recommended_pathway'];
            $this->logSection('migration', '');
            $this->logSection('migration', 'Recommended Migration:');
            $this->logSection('migration', sprintf('  Target: %s (%s)', $rp->target_format_name ?? $rp->target_puid, $rp->target_puid));
            $this->logSection('migration', sprintf('  Tool: %s', $rp->migration_tool));
            $this->logSection('migration', sprintf('  Quality: %s | Fidelity: %s', $rp->quality_impact, $rp->fidelity_score ? $rp->fidelity_score.'%' : 'N/A'));
        }
    }

    /**
     * Refresh obsolescence counts.
     */
    protected function refreshCounts(MigrationPathwayService $service, bool $json): void
    {
        $this->logSection('migration', 'Refreshing obsolescence counts...');

        $result = $service->refreshObsolescenceCounts();

        if ($json) {
            echo json_encode($result, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('migration', sprintf('Updated: %d existing records', $result['updated']));
        $this->logSection('migration', sprintf('Added: %d new at-risk formats', $result['added']));
        $this->logSection('migration', sprintf('Assessed at: %s', $result['assessed_at']));
    }

    /**
     * Check available migration tools.
     */
    protected function checkTools(MigrationPathwayService $service, bool $json): void
    {
        $tools = ['imagemagick', 'ffmpeg', 'ghostscript', 'libreoffice'];
        $results = [];

        foreach ($tools as $tool) {
            $results[$tool] = $service->validateTool($tool);
        }

        if ($json) {
            echo json_encode($results, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('migration', 'Migration Tool Availability');
        $this->logSection('migration', str_repeat('-', 40));

        foreach ($results as $tool => $info) {
            $status = $info['available'] ? 'AVAILABLE' : 'NOT FOUND';
            $color = $info['available'] ? 'INFO' : 'ERROR';

            $this->logSection('migration', sprintf('%s: %s', ucfirst($tool), $status), null, $color);

            if ($info['available']) {
                $this->logSection('migration', "  Path: {$info['path']}");
                if ($info['version']) {
                    $version = strlen($info['version']) > 60 ? substr($info['version'], 0, 60).'...' : $info['version'];
                    $this->logSection('migration', "  Version: {$version}");
                }
            }
        }
    }

    /**
     * Show migration plans.
     */
    protected function showPlans(MigrationPlanService $service, array $options, bool $json, bool $verbose): void
    {
        $filters = [];

        if (!empty($options['plan-status'])) {
            $filters['status'] = $options['plan-status'];
        }

        $plans = $service->getPlans($filters);

        if ($json) {
            echo json_encode($plans, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('migration', 'Migration Plans');
        $this->logSection('migration', str_repeat('-', 60));

        if (empty($plans)) {
            $this->logSection('migration', 'No migration plans found.');

            return;
        }

        foreach ($plans as $plan) {
            $statusColor = match ($plan->status) {
                'in_progress' => 'INFO',
                'completed' => 'INFO',
                'failed', 'cancelled' => 'ERROR',
                default => null,
            };

            $this->logSection('migration', sprintf(
                '#%d: %s [%s]',
                $plan->id,
                $plan->name,
                strtoupper($plan->status)
            ), null, $statusColor);

            $this->logSection('migration', sprintf(
                '  %s → %s using %s',
                $plan->source_format_name ?? $plan->source_puid,
                $plan->target_format_name ?? $plan->target_puid,
                $plan->migration_tool ?? 'N/A'
            ));

            if ($plan->status === 'in_progress' || $plan->status === 'completed') {
                $progress = $service->getPlanProgress($plan->id);
                $this->logSection('migration', sprintf(
                    '  Progress: %d/%d (%.1f%%) | Success: %.1f%%',
                    $progress['status_breakdown']['completed'] ?? 0,
                    $progress['total_objects'],
                    $progress['percent_complete'],
                    $progress['success_rate']
                ));
            } else {
                $this->logSection('migration', sprintf('  Total Objects: %d', $plan->total_objects));
            }

            if ($verbose) {
                $this->logSection('migration', sprintf('  Created: %s', $plan->created_at));
                if ($plan->started_at) {
                    $this->logSection('migration', sprintf('  Started: %s', $plan->started_at));
                }
            }

            $this->logSection('migration', '');
        }
    }

    /**
     * Show overall migration statistics.
     */
    protected function showStats(MigrationPathwayService $pathwayService, MigrationPlanService $planService, bool $json): void
    {
        $pathwayStats = $pathwayService->getPathwayStats();
        $planStats = $planService->getOverallStats();

        $stats = [
            'pathways' => $pathwayStats,
            'plans' => $planStats,
        ];

        if ($json) {
            echo json_encode($stats, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('migration', 'Migration Statistics');
        $this->logSection('migration', str_repeat('-', 40));

        $this->logSection('migration', 'PATHWAYS:');
        $this->logSection('migration', sprintf('  Total Pathways: %d', $pathwayStats['total_pathways']));
        $this->logSection('migration', sprintf('  Recommended: %d', $pathwayStats['recommended_count']));
        $this->logSection('migration', sprintf('  Automated: %d', $pathwayStats['automated_count']));
        $this->logSection('migration', sprintf('  Formats with Pathways: %d', $pathwayStats['formats_with_pathways']));

        if ($pathwayStats['at_risk_without_pathways'] > 0) {
            $this->logSection('migration', sprintf(
                '  At-Risk without Pathways: %d',
                $pathwayStats['at_risk_without_pathways']
            ), null, 'ERROR');
        }

        $this->logSection('migration', '');
        $this->logSection('migration', 'By Tool:');
        foreach ($pathwayStats['by_tool'] as $tool => $count) {
            $this->logSection('migration', sprintf('  %s: %d', $tool, $count));
        }

        $this->logSection('migration', '');
        $this->logSection('migration', 'PLANS:');
        $this->logSection('migration', sprintf('  Total Plans: %d', $planStats['total_plans']));
        $this->logSection('migration', sprintf('  Objects Converted: %d', $planStats['total_objects_converted']));
        $this->logSection('migration', sprintf('  Success Rate: %.1f%%', $planStats['overall_success_rate']));

        if ($planStats['total_original_size'] > 0) {
            $this->logSection('migration', sprintf(
                '  Storage: %s → %s (%s)',
                $this->formatBytes($planStats['total_original_size']),
                $this->formatBytes($planStats['total_converted_size']),
                $planStats['storage_saved'] > 0 ? 'saved '.$this->formatBytes($planStats['storage_saved']) : 'increased '.$this->formatBytes(abs($planStats['storage_saved']))
            ));
        }

        if (!empty($planStats['plans_by_status'])) {
            $this->logSection('migration', '');
            $this->logSection('migration', 'Plans by Status:');
            foreach ($planStats['plans_by_status'] as $status => $count) {
                $this->logSection('migration', sprintf('  %s: %d', $status, $count));
            }
        }
    }

    /**
     * Show summary overview.
     */
    protected function showSummary(MigrationPathwayService $pathwayService, MigrationPlanService $planService): void
    {
        $this->logSection('migration', 'Format Migration Overview');
        $this->logSection('migration', str_repeat('=', 50));

        $pathwayStats = $pathwayService->getPathwayStats();
        $alerts = $pathwayService->getObsolescenceAlerts();
        $planStats = $planService->getOverallStats();

        $this->logSection('migration', sprintf('Available Pathways: %d (%d recommended)', $pathwayStats['total_pathways'], $pathwayStats['recommended_count']));
        $this->logSection('migration', sprintf('Migration Plans: %d total, %d active', $planStats['total_plans'], ($planStats['plans_by_status']['in_progress'] ?? 0) + ($planStats['plans_by_status']['approved'] ?? 0)));

        if (count($alerts) > 0) {
            $this->logSection('migration', '');
            $this->logSection('migration', sprintf('ALERTS: %d format(s) require attention!', count($alerts)), null, 'ERROR');
            $this->logSection('migration', 'Run --obsolescence for details.');
        }

        if ($pathwayStats['at_risk_without_pathways'] > 0) {
            $this->logSection('migration', sprintf(
                'WARNING: %d at-risk format(s) have no migration pathway defined.',
                $pathwayStats['at_risk_without_pathways']
            ), null, 'ERROR');
        }

        $this->logSection('migration', '');
        $this->logSection('migration', 'Commands:');
        $this->logSection('migration', '  --pathways     List migration pathways');
        $this->logSection('migration', '  --obsolescence Obsolescence report');
        $this->logSection('migration', '  --plans        Migration plans');
        $this->logSection('migration', '  --tools        Check available tools');
        $this->logSection('migration', '  --stats        Statistics');
        $this->logSection('migration', '  --refresh      Update counts');
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            ++$i;
        }

        return number_format($bytes, 2).' '.$units[$i];
    }
}
