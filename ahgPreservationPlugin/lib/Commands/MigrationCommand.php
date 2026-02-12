<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class MigrationCommand extends BaseCommand
{
    protected string $name = 'preservation:migration';
    protected string $description = 'Format migration planning and obsolescence reporting';
    protected string $detailedDescription = <<<'EOF'
Manage format migration pathways and assess format obsolescence risk.

Examples:
  php bin/atom preservation:migration --pathways              # List all migration pathways
  php bin/atom preservation:migration --pathways --source=fmt/44   # Pathways from JPEG
  php bin/atom preservation:migration --pathways --recommended     # Only recommended pathways
  php bin/atom preservation:migration --obsolescence              # Generate obsolescence report
  php bin/atom preservation:migration --obsolescence --risk=critical # Critical formats only
  php bin/atom preservation:migration --assess=fmt/44             # Assess JPEG format
  php bin/atom preservation:migration --refresh                   # Refresh obsolescence counts
  php bin/atom preservation:migration --tools                     # Check available tools
  php bin/atom preservation:migration --plans                     # List migration plans
  php bin/atom preservation:migration --stats                     # Migration statistics
  php bin/atom preservation:migration --pathways --json           # JSON output

Migration Pathways:
  Pre-defined routes for converting files from one format to another.
  Each pathway specifies the tool, command, and quality impact.

Obsolescence Tracking:
  Monitors formats at risk of becoming obsolete and tracks
  the number of affected digital objects requiring migration.
EOF;

    protected function configure(): void
    {
        // Actions
        $this->addOption('pathways', null, 'List all migration pathways');
        $this->addOption('obsolescence', null, 'Generate obsolescence report');
        $this->addOption('plans', null, 'List migration plans');
        $this->addOption('assess', null, 'Assess a specific format by PUID');
        $this->addOption('refresh', null, 'Refresh obsolescence counts');
        $this->addOption('tools', null, 'Check available migration tools');
        $this->addOption('stats', null, 'Show migration statistics');

        // Filters
        $this->addOption('source', null, 'Filter by source PUID');
        $this->addOption('target', null, 'Filter by target PUID');
        $this->addOption('tool', null, 'Filter by migration tool');
        $this->addOption('risk', null, 'Filter by risk level (low, medium, high, critical)');
        $this->addOption('urgency', null, 'Filter by migration urgency');
        $this->addOption('recommended', null, 'Show only recommended pathways');

        // Plan management
        $this->addOption('plan-id', null, 'Specific plan ID');
        $this->addOption('plan-status', null, 'Filter plans by status');

        // Output
        $this->addOption('json', null, 'Output as JSON');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/Services/MigrationPathwayService.php';
        require_once dirname(__DIR__) . '/Services/MigrationPlanService.php';

        $pathwayService = new \MigrationPathwayService();
        $planService = new \MigrationPlanService();

        $isJson = $this->hasOption('json');

        // Route to appropriate action
        if ($this->hasOption('pathways')) {
            $this->showPathways($pathwayService, $isJson);
        } elseif ($this->hasOption('obsolescence')) {
            $this->showObsolescence($pathwayService, $isJson);
        } elseif ($this->hasOption('assess')) {
            $this->assessFormat($pathwayService, $this->option('assess'), $isJson);
        } elseif ($this->hasOption('refresh')) {
            $this->refreshCounts($pathwayService, $isJson);
        } elseif ($this->hasOption('tools')) {
            $this->checkTools($pathwayService, $isJson);
        } elseif ($this->hasOption('plans')) {
            $this->showPlans($planService, $isJson);
        } elseif ($this->hasOption('stats')) {
            $this->showStats($pathwayService, $planService, $isJson);
        } else {
            // Default: show summary
            $this->showSummary($pathwayService, $planService);
        }

        return 0;
    }

    /**
     * Display migration pathways.
     */
    private function showPathways(\MigrationPathwayService $service, bool $json): void
    {
        $filters = [];

        if ($this->hasOption('source')) {
            $filters['source_puid'] = $this->option('source');
        }
        if ($this->hasOption('target')) {
            $filters['target_puid'] = $this->option('target');
        }
        if ($this->hasOption('tool')) {
            $filters['tool'] = $this->option('tool');
        }
        if ($this->hasOption('recommended')) {
            $filters['recommended_only'] = true;
        }

        $pathways = $service->getPathways($filters);

        if ($json) {
            echo json_encode($pathways, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Migration Pathways');
        $this->line(str_repeat('-', 60));

        if (empty($pathways)) {
            $this->line('No pathways found matching criteria.');

            return;
        }

        $this->line(sprintf('Found %d pathway(s)', count($pathways)));
        $this->newline();

        foreach ($pathways as $p) {
            $rec = $p->is_recommended ? ' [RECOMMENDED]' : '';
            $auto = $p->is_automated ? '' : ' [MANUAL]';
            $this->info(sprintf(
                '#%d: %s → %s%s%s',
                $p->id,
                $p->source_format_name ?? $p->source_puid,
                $p->target_format_name ?? $p->target_puid,
                $rec,
                $auto
            ));

            $this->line(sprintf(
                '    Tool: %s | Quality: %s | Fidelity: %s',
                $p->migration_tool,
                $p->quality_impact,
                $p->fidelity_score ? $p->fidelity_score . '%' : 'N/A'
            ));

            if ($this->verbose && $p->migration_command) {
                $this->line("    Command: {$p->migration_command}");
            }
            if ($this->verbose && $p->notes) {
                $this->line("    Notes: {$p->notes}");
            }
            $this->newline();
        }
    }

    /**
     * Display obsolescence report.
     */
    private function showObsolescence(\MigrationPathwayService $service, bool $json): void
    {
        $filters = [];

        if ($this->hasOption('risk')) {
            $filters['risk_level'] = $this->option('risk');
        }
        if ($this->hasOption('urgency')) {
            $filters['urgency'] = $this->option('urgency');
        }

        $report = $service->getObsolescenceReport($filters);

        if ($json) {
            echo json_encode($report, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Format Obsolescence Report');
        $this->line(str_repeat('-', 60));

        if (empty($report)) {
            $this->line('No formats flagged for obsolescence concern.');
            $this->line('Run --refresh to update obsolescence tracking.');

            return;
        }

        $this->line(sprintf('Found %d format(s) with obsolescence concerns', count($report)));
        $this->newline();

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
            $method = in_array($urgency, ['critical', 'high']) ? 'error' : 'info';
            $this->$method("=== {$urgencyLabel} URGENCY ===");

            foreach ($byUrgency[$urgency] as $item) {
                $sizeFormatted = $this->formatBytes($item->storage_size_bytes ?? 0);

                $this->info(sprintf(
                    '%s (%s) - %s',
                    $item->format_name,
                    $item->puid,
                    $item->mime_type
                ));

                $this->line(sprintf(
                    '  Risk: %s | Objects: %d | Size: %s',
                    strtoupper($item->current_risk_level),
                    $item->affected_object_count,
                    $sizeFormatted
                ));

                if ($item->recommended_action) {
                    $this->line("  Action: {$item->recommended_action}");
                }

                if ($item->recommended_target_puid) {
                    $this->line(sprintf(
                        '  Recommended: Migrate to %s using %s',
                        $item->recommended_target_puid,
                        $item->recommended_tool ?? 'available tool'
                    ));
                }

                $this->newline();
            }
        }

        // Show alerts summary
        $alerts = $service->getObsolescenceAlerts();
        if (!empty($alerts)) {
            $this->newline();
            $this->error('*** ATTENTION: ' . count($alerts) . ' format(s) require immediate action! ***');
        }
    }

    /**
     * Assess a specific format.
     */
    private function assessFormat(\MigrationPathwayService $service, string $puid, bool $json): void
    {
        $assessment = $service->assessFormat($puid);

        if ($json) {
            echo json_encode($assessment, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        if (isset($assessment['error'])) {
            $this->error("Error: {$assessment['error']}");

            return;
        }

        $this->bold('Format Assessment');
        $this->line(str_repeat('-', 40));
        $this->line("Format: {$assessment['format_name']}");
        $this->line("PUID: {$assessment['puid']}");
        $this->line("MIME: {$assessment['mime_type']}");
        $this->newline();

        $riskMethod = in_array($assessment['risk_level'], ['critical', 'high']) ? 'error' : 'info';
        $this->$riskMethod(sprintf('Risk Level: %s', strtoupper($assessment['risk_level'])));
        $this->line(sprintf('Migration Urgency: %s', strtoupper($assessment['migration_urgency'])));
        $this->line(sprintf('Is Preservation Format: %s', $assessment['is_preservation_format'] ? 'Yes' : 'No'));
        $this->newline();
        $this->line(sprintf('Affected Objects: %d', $assessment['affected_objects']));
        $this->line(sprintf('Storage Size: %s', $this->formatBytes($assessment['storage_bytes'])));
        $this->line(sprintf('Available Pathways: %d', $assessment['available_pathways']));

        if ($assessment['recommended_pathway']) {
            $rp = $assessment['recommended_pathway'];
            $this->newline();
            $this->info('Recommended Migration:');
            $this->line(sprintf('  Target: %s (%s)', $rp->target_format_name ?? $rp->target_puid, $rp->target_puid));
            $this->line(sprintf('  Tool: %s', $rp->migration_tool));
            $this->line(sprintf('  Quality: %s | Fidelity: %s', $rp->quality_impact, $rp->fidelity_score ? $rp->fidelity_score . '%' : 'N/A'));
        }
    }

    /**
     * Refresh obsolescence counts.
     */
    private function refreshCounts(\MigrationPathwayService $service, bool $json): void
    {
        $this->info('Refreshing obsolescence counts...');

        $result = $service->refreshObsolescenceCounts();

        if ($json) {
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->line(sprintf('Updated: %d existing records', $result['updated']));
        $this->line(sprintf('Added: %d new at-risk formats', $result['added']));
        $this->line(sprintf('Assessed at: %s', $result['assessed_at']));
    }

    /**
     * Check available migration tools.
     */
    private function checkTools(\MigrationPathwayService $service, bool $json): void
    {
        $tools = ['imagemagick', 'ffmpeg', 'ghostscript', 'libreoffice'];
        $results = [];

        foreach ($tools as $tool) {
            $results[$tool] = $service->validateTool($tool);
        }

        if ($json) {
            echo json_encode($results, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Migration Tool Availability');
        $this->line(str_repeat('-', 40));

        foreach ($results as $tool => $info) {
            $status = $info['available'] ? 'AVAILABLE' : 'NOT FOUND';
            $method = $info['available'] ? 'success' : 'error';

            $this->$method(sprintf('%s: %s', ucfirst($tool), $status));

            if ($info['available']) {
                $this->line("  Path: {$info['path']}");
                if ($info['version']) {
                    $version = strlen($info['version']) > 60 ? substr($info['version'], 0, 60) . '...' : $info['version'];
                    $this->line("  Version: {$version}");
                }
            }
        }
    }

    /**
     * Show migration plans.
     */
    private function showPlans(\MigrationPlanService $service, bool $json): void
    {
        $filters = [];

        if ($this->hasOption('plan-status')) {
            $filters['status'] = $this->option('plan-status');
        }

        $plans = $service->getPlans($filters);

        if ($json) {
            echo json_encode($plans, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Migration Plans');
        $this->line(str_repeat('-', 60));

        if (empty($plans)) {
            $this->line('No migration plans found.');

            return;
        }

        foreach ($plans as $plan) {
            $statusMethod = match ($plan->status) {
                'in_progress', 'completed' => 'info',
                'failed', 'cancelled' => 'error',
                default => 'line',
            };

            $this->$statusMethod(sprintf(
                '#%d: %s [%s]',
                $plan->id,
                $plan->name,
                strtoupper($plan->status)
            ));

            $this->line(sprintf(
                '  %s → %s using %s',
                $plan->source_format_name ?? $plan->source_puid,
                $plan->target_format_name ?? $plan->target_puid,
                $plan->migration_tool ?? 'N/A'
            ));

            if ($plan->status === 'in_progress' || $plan->status === 'completed') {
                $progress = $service->getPlanProgress($plan->id);
                $this->line(sprintf(
                    '  Progress: %d/%d (%.1f%%) | Success: %.1f%%',
                    $progress['status_breakdown']['completed'] ?? 0,
                    $progress['total_objects'],
                    $progress['percent_complete'],
                    $progress['success_rate']
                ));
            } else {
                $this->line(sprintf('  Total Objects: %d', $plan->total_objects));
            }

            if ($this->verbose) {
                $this->line(sprintf('  Created: %s', $plan->created_at));
                if ($plan->started_at) {
                    $this->line(sprintf('  Started: %s', $plan->started_at));
                }
            }

            $this->newline();
        }
    }

    /**
     * Show overall migration statistics.
     */
    private function showStats(\MigrationPathwayService $pathwayService, \MigrationPlanService $planService, bool $json): void
    {
        $pathwayStats = $pathwayService->getPathwayStats();
        $planStats = $planService->getOverallStats();

        $stats = [
            'pathways' => $pathwayStats,
            'plans' => $planStats,
        ];

        if ($json) {
            echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Migration Statistics');
        $this->line(str_repeat('-', 40));

        $this->info('PATHWAYS:');
        $this->line(sprintf('  Total Pathways: %d', $pathwayStats['total_pathways']));
        $this->line(sprintf('  Recommended: %d', $pathwayStats['recommended_count']));
        $this->line(sprintf('  Automated: %d', $pathwayStats['automated_count']));
        $this->line(sprintf('  Formats with Pathways: %d', $pathwayStats['formats_with_pathways']));

        if ($pathwayStats['at_risk_without_pathways'] > 0) {
            $this->error(sprintf(
                '  At-Risk without Pathways: %d',
                $pathwayStats['at_risk_without_pathways']
            ));
        }

        $this->newline();
        $this->info('By Tool:');
        foreach ($pathwayStats['by_tool'] as $tool => $count) {
            $this->line(sprintf('  %s: %d', $tool, $count));
        }

        $this->newline();
        $this->info('PLANS:');
        $this->line(sprintf('  Total Plans: %d', $planStats['total_plans']));
        $this->line(sprintf('  Objects Converted: %d', $planStats['total_objects_converted']));
        $this->line(sprintf('  Success Rate: %.1f%%', $planStats['overall_success_rate']));

        if ($planStats['total_original_size'] > 0) {
            $this->line(sprintf(
                '  Storage: %s → %s (%s)',
                $this->formatBytes($planStats['total_original_size']),
                $this->formatBytes($planStats['total_converted_size']),
                $planStats['storage_saved'] > 0 ? 'saved ' . $this->formatBytes($planStats['storage_saved']) : 'increased ' . $this->formatBytes(abs($planStats['storage_saved']))
            ));
        }

        if (!empty($planStats['plans_by_status'])) {
            $this->newline();
            $this->info('Plans by Status:');
            foreach ($planStats['plans_by_status'] as $status => $count) {
                $this->line(sprintf('  %s: %d', $status, $count));
            }
        }
    }

    /**
     * Show summary overview.
     */
    private function showSummary(\MigrationPathwayService $pathwayService, \MigrationPlanService $planService): void
    {
        $this->bold('Format Migration Overview');
        $this->line(str_repeat('=', 50));

        $pathwayStats = $pathwayService->getPathwayStats();
        $alerts = $pathwayService->getObsolescenceAlerts();
        $planStats = $planService->getOverallStats();

        $this->line(sprintf('Available Pathways: %d (%d recommended)', $pathwayStats['total_pathways'], $pathwayStats['recommended_count']));
        $this->line(sprintf('Migration Plans: %d total, %d active', $planStats['total_plans'], ($planStats['plans_by_status']['in_progress'] ?? 0) + ($planStats['plans_by_status']['approved'] ?? 0)));

        if (count($alerts) > 0) {
            $this->newline();
            $this->error(sprintf('ALERTS: %d format(s) require attention!', count($alerts)));
            $this->line('Run --obsolescence for details.');
        }

        if ($pathwayStats['at_risk_without_pathways'] > 0) {
            $this->error(sprintf(
                'WARNING: %d at-risk format(s) have no migration pathway defined.',
                $pathwayStats['at_risk_without_pathways']
            ));
        }

        $this->newline();
        $this->info('Commands:');
        $this->line('  --pathways     List migration pathways');
        $this->line('  --obsolescence Obsolescence report');
        $this->line('  --plans        Migration plans');
        $this->line('  --tools        Check available tools');
        $this->line('  --stats        Statistics');
        $this->line('  --refresh      Update counts');
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

        return number_format($bytes, 2) . ' ' . $units[$i];
    }
}
