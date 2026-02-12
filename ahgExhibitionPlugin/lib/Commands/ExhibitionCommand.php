<?php

namespace AtomFramework\Console\Commands\Museum;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for exhibition management.
 *
 * Provides commands for managing exhibitions, objects, storylines,
 * and generating reports.
 */
class ExhibitionCommand extends BaseCommand
{
    protected string $name = 'museum:exhibition';
    protected string $description = 'Manage museum exhibitions';
    protected string $detailedDescription = <<<'EOF'
    Manages museum exhibitions including creation, status tracking, and reporting.

    Examples:
      php bin/atom museum:exhibition --list                    List all exhibitions
      php bin/atom museum:exhibition --current                 Show open exhibitions
      php bin/atom museum:exhibition --upcoming                Show upcoming exhibitions
      php bin/atom museum:exhibition --show=1                  Show exhibition details
      php bin/atom museum:exhibition --object-list=1           Generate object list
      php bin/atom museum:exhibition --statistics              Show statistics
      php bin/atom museum:exhibition --install-schema          Install database tables

    Status transitions:
      php bin/atom museum:exhibition --exhibition-id=1 --status=planning

    Exhibition types: permanent, temporary, traveling, online, pop_up
    Statuses: concept, planning, preparation, installation, open, closing, closed, archived
    EOF;

    protected function configure(): void
    {
        // Actions
        $this->addOption('list', null, 'List exhibitions');
        $this->addOption('show', null, 'Show exhibition details (ID or slug)');
        $this->addOption('create', null, 'Create new exhibition (interactive)');
        $this->addOption('status', null, 'Change exhibition status');
        $this->addOption('statistics', null, 'Show exhibition statistics');
        $this->addOption('object-list', null, 'Generate object list for exhibition');
        $this->addOption('upcoming', null, 'Show upcoming exhibitions');
        $this->addOption('current', null, 'Show currently open exhibitions');
        $this->addOption('overdue', null, 'Show exhibitions past closing date');
        $this->addOption('install-schema', null, 'Install exhibition database schema');

        // Filters
        $this->addOption('exhibition-id', null, 'Exhibition ID');
        $this->addOption('year', null, 'Filter by year');
        $this->addOption('type', null, 'Filter by type');

        // Output
        $this->addOption('format', null, 'Output format (table, json, csv)', 'table');
    }

    protected function handle(): int
    {
        // Install schema
        if ($this->hasOption('install-schema')) {
            $this->installSchema();

            return 0;
        }

        // Initialize service
        $servicePath = $this->getPluginsRoot() . '/ahgExhibitionPlugin/lib/Services/ExhibitionService.php';
        require_once $servicePath;
        $service = new \arMuseumMetadataPlugin\Services\Exhibition\ExhibitionService(DB::connection());

        // Statistics
        if ($this->hasOption('statistics')) {
            $this->showStatistics($service);

            return 0;
        }

        // Current exhibitions
        if ($this->hasOption('current')) {
            $this->showCurrentExhibitions($service);

            return 0;
        }

        // Upcoming exhibitions
        if ($this->hasOption('upcoming')) {
            $this->showUpcomingExhibitions($service);

            return 0;
        }

        // Overdue exhibitions
        if ($this->hasOption('overdue')) {
            $this->showOverdueExhibitions($service);

            return 0;
        }

        // Show specific exhibition
        $show = $this->option('show');
        if ($show !== null) {
            $this->showExhibition($service, $show, $this->option('format'));

            return 0;
        }

        // Generate object list
        $objectList = $this->option('object-list');
        if ($objectList !== null) {
            $this->generateObjectList($service, (int) $objectList, $this->option('format'));

            return 0;
        }

        // Change status
        $status = $this->option('status');
        $exhibitionId = $this->option('exhibition-id');
        if ($status !== null && $exhibitionId !== null) {
            $this->changeStatus($service, (int) $exhibitionId, $status);

            return 0;
        }

        // Create exhibition
        if ($this->hasOption('create')) {
            $this->createExhibition($service);

            return 0;
        }

        // Default: list exhibitions
        $this->listExhibitions($service);

        return 0;
    }

    /**
     * Install exhibition schema.
     */
    private function installSchema(): void
    {
        $this->info('Installing exhibition database schema...');

        $schemaFile = $this->getPluginsRoot() . '/ahgExhibitionPlugin/data/exhibition_schema.sql';

        if (!file_exists($schemaFile)) {
            $this->error('Schema file not found: ' . $schemaFile);

            return;
        }

        $sql = file_get_contents($schemaFile);

        // Split into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn ($s) => !empty($s) && !str_starts_with($s, '--')
        );

        $executed = 0;
        $errors = 0;

        foreach ($statements as $statement) {
            if (empty(trim($statement))) {
                continue;
            }

            try {
                DB::statement($statement);
                ++$executed;
            } catch (\Exception $e) {
                // Ignore "already exists" errors
                if (!str_contains($e->getMessage(), 'already exists')) {
                    $this->error('Error: ' . $e->getMessage());
                    ++$errors;
                }
            }
        }

        $this->success("Schema installation complete: {$executed} statements executed");

        if ($errors > 0) {
            $this->error("{$errors} errors encountered");
        }
    }

    /**
     * List exhibitions.
     */
    private function listExhibitions($service): void
    {
        $this->bold('Exhibitions');
        $this->newline();

        $filters = [];
        $year = $this->option('year');
        $type = $this->option('type');

        if ($year !== null) {
            $filters['year'] = $year;
        }
        if ($type !== null) {
            $filters['exhibition_type'] = $type;
        }

        $result = $service->search($filters, 100, 0);

        if (empty($result['results'])) {
            $this->info('No exhibitions found');

            return;
        }

        $this->line(sprintf('Found %d exhibition(s)', $result['total']));
        $this->newline();

        // Table header
        $this->line(sprintf(
            '%-4s %-30s %-12s %-12s %-12s %-12s',
            'ID', 'Title', 'Type', 'Status', 'Opens', 'Closes'
        ));
        $this->line(str_repeat('-', 90));

        foreach ($result['results'] as $exhibition) {
            $this->line(sprintf(
                '%-4d %-30s %-12s %-12s %-12s %-12s',
                $exhibition['id'],
                mb_substr($exhibition['title'], 0, 28),
                $exhibition['exhibition_type'],
                $exhibition['status'],
                $exhibition['opening_date'] ?? '-',
                $exhibition['closing_date'] ?? '-'
            ));
        }
    }

    /**
     * Show exhibition details.
     */
    private function showExhibition($service, $idOrSlug, string $format): void
    {
        if (is_numeric($idOrSlug)) {
            $exhibition = $service->get((int) $idOrSlug, true);
        } else {
            $exhibition = $service->getBySlug($idOrSlug);
        }

        if (!$exhibition) {
            $this->error('Exhibition not found');

            return;
        }

        if ('json' === $format) {
            echo json_encode($exhibition, JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Exhibition Details');
        $this->newline();
        $this->line(sprintf('ID:          %d', $exhibition['id']));
        $this->line(sprintf('Title:       %s', $exhibition['title']));
        $this->line(sprintf('Subtitle:    %s', $exhibition['subtitle'] ?? '-'));
        $this->line(sprintf('Slug:        %s', $exhibition['slug']));
        $this->line(sprintf('Type:        %s', $exhibition['type_label']));
        $this->line(sprintf('Status:      %s', $exhibition['status_info']['label'] ?? $exhibition['status']));
        $this->newline();
        $this->info('Dates:');
        $this->line(sprintf('  Opens:     %s', $exhibition['opening_date'] ?? '-'));
        $this->line(sprintf('  Closes:    %s', $exhibition['closing_date'] ?? '-'));

        if ($exhibition['timing']['days_until_opening'] !== null) {
            $this->line(sprintf('  Days until opening: %d', $exhibition['timing']['days_until_opening']));
        }
        if ($exhibition['timing']['days_until_closing'] !== null) {
            $this->line(sprintf('  Days until closing: %d', $exhibition['timing']['days_until_closing']));
        }

        $this->newline();
        $this->line(sprintf('Venue:       %s', $exhibition['venue_name'] ?? '-'));
        $this->line(sprintf('Curator:     %s', $exhibition['curator_name'] ?? '-'));

        if (!empty($exhibition['statistics'])) {
            $stats = $exhibition['statistics'];
            $this->newline();
            $this->info('Statistics:');
            $this->line(sprintf('  Objects:       %d', $stats['object_count']));
            $this->line(sprintf('  Sections:      %d', $stats['section_count']));
            $this->line(sprintf('  Storylines:    %d', $stats['storyline_count']));
            $this->line(sprintf('  Events:        %d', $stats['event_count']));
            $this->line(sprintf('  Insurance:     %s %s',
                number_format($stats['total_insurance_value'], 2),
                $exhibition['budget_currency'] ?? 'ZAR'
            ));
        }

        if (!empty($exhibition['objects'])) {
            $this->newline();
            $this->info('Objects (' . count($exhibition['objects']) . '):');
            foreach (array_slice($exhibition['objects'], 0, 10) as $obj) {
                $this->line(sprintf('  - [%s] %s (%s)',
                    $obj['identifier'] ?? '-',
                    mb_substr($obj['object_title'] ?? 'Untitled', 0, 40),
                    $obj['status_label']
                ));
            }
            if (count($exhibition['objects']) > 10) {
                $this->line('  ... and ' . (count($exhibition['objects']) - 10) . ' more');
            }
        }

        // Show valid transitions
        $validTransitions = $service->getValidTransitions($exhibition['status']);
        if (!empty($validTransitions)) {
            $this->newline();
            $this->info('Valid status transitions: ' . implode(', ', $validTransitions));
        }
    }

    /**
     * Generate object list for exhibition.
     */
    private function generateObjectList($service, int $exhibitionId, string $format): void
    {
        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->error('Exhibition not found');

            return;
        }

        $objects = $service->generateObjectList($exhibitionId);

        if (empty($objects)) {
            $this->info('No objects in exhibition');

            return;
        }

        if ('csv' === $format) {
            $this->outputCsv($objects, $exhibition['title']);

            return;
        }

        if ('json' === $format) {
            echo json_encode(['exhibition' => $exhibition['title'], 'objects' => $objects], JSON_PRETTY_PRINT) . "\n";

            return;
        }

        $this->bold('Object List: ' . $exhibition['title']);
        $this->newline();
        $this->line(sprintf(
            '%-15s %-35s %-15s %-10s',
            'Identifier', 'Title', 'Section', 'Status'
        ));
        $this->line(str_repeat('-', 80));

        foreach ($objects as $obj) {
            $this->line(sprintf(
                '%-15s %-35s %-15s %-10s',
                $obj['identifier'] ?? '-',
                mb_substr($obj['object_title'] ?? 'Untitled', 0, 33),
                mb_substr($obj['section_title'] ?? '-', 0, 13),
                $obj['status']
            ));
        }

        $this->newline();
        $this->line(sprintf('Total: %d objects', count($objects)));
    }

    /**
     * Change exhibition status.
     */
    private function changeStatus($service, int $exhibitionId, string $newStatus): void
    {
        try {
            $service->transitionStatus($exhibitionId, $newStatus, 1, 'CLI status change');
            $this->success("Status changed to: {$newStatus}");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Show current exhibitions.
     */
    private function showCurrentExhibitions($service): void
    {
        $this->bold('Currently Open Exhibitions');
        $this->newline();

        $result = $service->search(['status' => 'open'], 50, 0);

        if (empty($result['results'])) {
            $this->info('No exhibitions currently open');

            return;
        }

        foreach ($result['results'] as $exhibition) {
            $this->line(sprintf(
                '[%d] %s',
                $exhibition['id'],
                $exhibition['title']
            ));
            $this->line(sprintf(
                '    Venue: %s | Closes: %s',
                $exhibition['venue_name'] ?? 'Unknown',
                $exhibition['closing_date'] ?? 'TBD'
            ));
        }

        $this->newline();
        $this->line(sprintf('Total: %d open exhibitions', $result['total']));
    }

    /**
     * Show upcoming exhibitions.
     */
    private function showUpcomingExhibitions($service): void
    {
        $this->bold('Upcoming Exhibitions');
        $this->newline();

        $result = $service->search(['upcoming' => true], 50, 0);

        if (empty($result['results'])) {
            $this->info('No upcoming exhibitions');

            return;
        }

        foreach ($result['results'] as $exhibition) {
            $daysUntil = $exhibition['opening_date']
                ? (new \DateTime($exhibition['opening_date']))->diff(new \DateTime())->days
                : null;

            $this->line(sprintf(
                '[%d] %s',
                $exhibition['id'],
                $exhibition['title']
            ));
            $this->line(sprintf(
                '    Status: %s | Opens: %s%s',
                $exhibition['status'],
                $exhibition['opening_date'] ?? 'TBD',
                $daysUntil !== null ? " ({$daysUntil} days)" : ''
            ));
        }

        $this->newline();
        $this->line(sprintf('Total: %d upcoming exhibitions', $result['total']));
    }

    /**
     * Show overdue exhibitions.
     */
    private function showOverdueExhibitions($service): void
    {
        $this->bold('Overdue Exhibitions (past closing date but not closed)');
        $this->newline();

        $overdue = DB::table('exhibition')
            ->where('closing_date', '<', date('Y-m-d'))
            ->whereNotIn('status', ['closed', 'archived', 'canceled'])
            ->orderBy('closing_date')
            ->get();

        if ($overdue->isEmpty()) {
            $this->success('No overdue exhibitions');

            return;
        }

        foreach ($overdue as $exhibition) {
            $daysOverdue = (new \DateTime($exhibition->closing_date))->diff(new \DateTime())->days;

            $this->error(sprintf(
                '[%d] %s - %d days overdue',
                $exhibition->id,
                $exhibition->title,
                $daysOverdue
            ));
            $this->line(sprintf(
                '    Status: %s | Was due: %s',
                $exhibition->status,
                $exhibition->closing_date
            ));
        }

        $this->newline();
        $this->error(sprintf('Total: %d overdue exhibitions', $overdue->count()));
    }

    /**
     * Show statistics.
     */
    private function showStatistics($service): void
    {
        $stats = $service->getStatistics();

        $this->bold('Exhibition Statistics');
        $this->newline();

        $this->line(sprintf('Total Exhibitions:      %d', $stats['total_exhibitions']));
        $this->line(sprintf('Currently Open:         %d', $stats['current_exhibitions']));
        $this->line(sprintf('Upcoming:               %d', $stats['upcoming_exhibitions']));
        $this->line(sprintf('Objects on Display:     %d', $stats['total_objects_on_display']));
        $this->line(sprintf('Total Insurance Value:  %s', number_format($stats['total_insurance_value'], 2)));

        if (!empty($stats['by_status'])) {
            $this->newline();
            $this->info('By Status:');
            foreach ($stats['by_status'] as $status => $count) {
                $this->line(sprintf('  %-15s: %d', $status, $count));
            }
        }

        if (!empty($stats['by_type'])) {
            $this->newline();
            $this->info('By Type:');
            foreach ($stats['by_type'] as $type => $count) {
                $this->line(sprintf('  %-15s: %d', $type, $count));
            }
        }
    }

    /**
     * Create exhibition interactively.
     */
    private function createExhibition($service): void
    {
        $this->bold('Create New Exhibition');
        $this->newline();

        // For CLI, we'll create with minimal data - full form in UI
        $this->info('Interactive creation not fully implemented.');
        $this->line('Please use the web interface or provide data via API.');
        $this->newline();
        $this->line('Example API call:');
        $this->line('  POST /api/museum/exhibition');
        $this->line('  {');
        $this->line('    "title": "Exhibition Title",');
        $this->line('    "exhibition_type": "temporary",');
        $this->line('    "opening_date": "2024-06-01",');
        $this->line('    "closing_date": "2024-09-30"');
        $this->line('  }');
    }

    /**
     * Output CSV.
     */
    private function outputCsv(array $data, string $title): void
    {
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Exhibition: ' . $title]);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);

        // Headers
        fputcsv($output, ['Identifier', 'Title', 'Section', 'Position', 'Status', 'Insurance Value', 'Loan Required', 'Lender']);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['identifier'] ?? '',
                $row['object_title'] ?? '',
                $row['section_title'] ?? '',
                $row['display_position'] ?? '',
                $row['status'] ?? '',
                $row['insurance_value'] ?? '',
                $row['requires_loan'] ? 'Yes' : 'No',
                $row['lender_institution'] ?? '',
            ]);
        }

        fclose($output);
    }
}
