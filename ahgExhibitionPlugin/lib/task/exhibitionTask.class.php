<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task for exhibition management.
 *
 * Provides commands for managing exhibitions, objects, storylines,
 * and generating reports.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class exhibitionTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),

            // Actions
            new sfCommandOption('list', null, sfCommandOption::PARAMETER_NONE, 'List exhibitions'),
            new sfCommandOption('show', null, sfCommandOption::PARAMETER_OPTIONAL, 'Show exhibition details (ID or slug)'),
            new sfCommandOption('create', null, sfCommandOption::PARAMETER_NONE, 'Create new exhibition (interactive)'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_OPTIONAL, 'Change exhibition status'),
            new sfCommandOption('statistics', null, sfCommandOption::PARAMETER_NONE, 'Show exhibition statistics'),
            new sfCommandOption('object-list', null, sfCommandOption::PARAMETER_OPTIONAL, 'Generate object list for exhibition'),
            new sfCommandOption('upcoming', null, sfCommandOption::PARAMETER_NONE, 'Show upcoming exhibitions'),
            new sfCommandOption('current', null, sfCommandOption::PARAMETER_NONE, 'Show currently open exhibitions'),
            new sfCommandOption('overdue', null, sfCommandOption::PARAMETER_NONE, 'Show exhibitions past closing date'),
            new sfCommandOption('install-schema', null, sfCommandOption::PARAMETER_NONE, 'Install exhibition database schema'),

            // Filters
            new sfCommandOption('exhibition-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Exhibition ID'),
            new sfCommandOption('year', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by year'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by type'),

            // Output
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output format (table, json, csv)', 'table'),
        ]);

        $this->namespace = 'museum';
        $this->name = 'exhibition';
        $this->briefDescription = 'Manage museum exhibitions';
        $this->detailedDescription = <<<EOF
Manages museum exhibitions including creation, status tracking, and reporting.

Examples:
  php symfony museum:exhibition --list                    # List all exhibitions
  php symfony museum:exhibition --current                 # Show open exhibitions
  php symfony museum:exhibition --upcoming                # Show upcoming exhibitions
  php symfony museum:exhibition --show=1                  # Show exhibition details
  php symfony museum:exhibition --object-list=1           # Generate object list
  php symfony museum:exhibition --statistics              # Show statistics
  php symfony museum:exhibition --install-schema          # Install database tables

Status transitions:
  php symfony museum:exhibition --exhibition-id=1 --status=planning

Exhibition types: permanent, temporary, traveling, online, pop_up
Statuses: concept, planning, preparation, installation, open, closing, closed, archived
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        \AhgCore\Core\AhgDb::init();

        // Install schema
        if ($options['install-schema']) {
            $this->installSchema();

            return;
        }

        // Initialize service
        require_once dirname(__DIR__).'/Services/Exhibition/ExhibitionService.php';
        $service = new \arMuseumMetadataPlugin\Services\Exhibition\ExhibitionService(DB::connection());

        // Statistics
        if ($options['statistics']) {
            $this->showStatistics($service);

            return;
        }

        // Current exhibitions
        if ($options['current']) {
            $this->showCurrentExhibitions($service);

            return;
        }

        // Upcoming exhibitions
        if ($options['upcoming']) {
            $this->showUpcomingExhibitions($service);

            return;
        }

        // Overdue exhibitions
        if ($options['overdue']) {
            $this->showOverdueExhibitions($service);

            return;
        }

        // Show specific exhibition
        if (!empty($options['show'])) {
            $this->showExhibition($service, $options['show'], $options['format']);

            return;
        }

        // Generate object list
        if (!empty($options['object-list'])) {
            $this->generateObjectList($service, (int) $options['object-list'], $options['format']);

            return;
        }

        // Change status
        if (!empty($options['status']) && !empty($options['exhibition-id'])) {
            $this->changeStatus($service, (int) $options['exhibition-id'], $options['status']);

            return;
        }

        // Create exhibition
        if ($options['create']) {
            $this->createExhibition($service);

            return;
        }

        // Default: list exhibitions
        $this->listExhibitions($service, $options);
    }

    /**
     * Install exhibition schema.
     */
    protected function installSchema()
    {
        $this->logSection('exhibition', 'Installing exhibition database schema...');

        $schemaFile = dirname(__DIR__, 2).'/data/exhibition_schema.sql';

        if (!file_exists($schemaFile)) {
            $this->logSection('exhibition', 'Schema file not found: '.$schemaFile, null, 'ERROR');

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
                    $this->logSection('exhibition', 'Error: '.$e->getMessage(), null, 'ERROR');
                    ++$errors;
                }
            }
        }

        $this->logSection('exhibition', "Schema installation complete: {$executed} statements executed");

        if ($errors > 0) {
            $this->logSection('exhibition', "{$errors} errors encountered", null, 'ERROR');
        }
    }

    /**
     * List exhibitions.
     */
    protected function listExhibitions($service, array $options)
    {
        $this->logSection('exhibition', 'Exhibitions');
        $this->logSection('exhibition', '');

        $filters = [];
        if (!empty($options['year'])) {
            $filters['year'] = $options['year'];
        }
        if (!empty($options['type'])) {
            $filters['exhibition_type'] = $options['type'];
        }

        $result = $service->search($filters, 100, 0);

        if (empty($result['results'])) {
            $this->logSection('exhibition', 'No exhibitions found');

            return;
        }

        $this->logSection('exhibition', sprintf('Found %d exhibition(s)', $result['total']));
        $this->logSection('exhibition', '');

        // Table header
        $this->logSection('exhibition', sprintf(
            '%-4s %-30s %-12s %-12s %-12s %-12s',
            'ID', 'Title', 'Type', 'Status', 'Opens', 'Closes'
        ));
        $this->logSection('exhibition', str_repeat('-', 90));

        foreach ($result['results'] as $exhibition) {
            $this->logSection('exhibition', sprintf(
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
    protected function showExhibition($service, $idOrSlug, string $format)
    {
        if (is_numeric($idOrSlug)) {
            $exhibition = $service->get((int) $idOrSlug, true);
        } else {
            $exhibition = $service->getBySlug($idOrSlug);
        }

        if (!$exhibition) {
            $this->logSection('exhibition', 'Exhibition not found', null, 'ERROR');

            return;
        }

        if ('json' === $format) {
            echo json_encode($exhibition, JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('exhibition', 'Exhibition Details');
        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf('ID:          %d', $exhibition['id']));
        $this->logSection('exhibition', sprintf('Title:       %s', $exhibition['title']));
        $this->logSection('exhibition', sprintf('Subtitle:    %s', $exhibition['subtitle'] ?? '-'));
        $this->logSection('exhibition', sprintf('Slug:        %s', $exhibition['slug']));
        $this->logSection('exhibition', sprintf('Type:        %s', $exhibition['type_label']));
        $this->logSection('exhibition', sprintf('Status:      %s', $exhibition['status_info']['label'] ?? $exhibition['status']));
        $this->logSection('exhibition', '');
        $this->logSection('exhibition', 'Dates:');
        $this->logSection('exhibition', sprintf('  Opens:     %s', $exhibition['opening_date'] ?? '-'));
        $this->logSection('exhibition', sprintf('  Closes:    %s', $exhibition['closing_date'] ?? '-'));

        if ($exhibition['timing']['days_until_opening'] !== null) {
            $this->logSection('exhibition', sprintf('  Days until opening: %d', $exhibition['timing']['days_until_opening']));
        }
        if ($exhibition['timing']['days_until_closing'] !== null) {
            $this->logSection('exhibition', sprintf('  Days until closing: %d', $exhibition['timing']['days_until_closing']));
        }

        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf('Venue:       %s', $exhibition['venue_name'] ?? '-'));
        $this->logSection('exhibition', sprintf('Curator:     %s', $exhibition['curator_name'] ?? '-'));

        if (!empty($exhibition['statistics'])) {
            $stats = $exhibition['statistics'];
            $this->logSection('exhibition', '');
            $this->logSection('exhibition', 'Statistics:');
            $this->logSection('exhibition', sprintf('  Objects:       %d', $stats['object_count']));
            $this->logSection('exhibition', sprintf('  Sections:      %d', $stats['section_count']));
            $this->logSection('exhibition', sprintf('  Storylines:    %d', $stats['storyline_count']));
            $this->logSection('exhibition', sprintf('  Events:        %d', $stats['event_count']));
            $this->logSection('exhibition', sprintf('  Insurance:     %s %s',
                number_format($stats['total_insurance_value'], 2),
                $exhibition['budget_currency'] ?? 'ZAR'
            ));
        }

        if (!empty($exhibition['objects'])) {
            $this->logSection('exhibition', '');
            $this->logSection('exhibition', 'Objects ('.count($exhibition['objects']).'):');
            foreach (array_slice($exhibition['objects'], 0, 10) as $obj) {
                $this->logSection('exhibition', sprintf('  - [%s] %s (%s)',
                    $obj['identifier'] ?? '-',
                    mb_substr($obj['object_title'] ?? 'Untitled', 0, 40),
                    $obj['status_label']
                ));
            }
            if (count($exhibition['objects']) > 10) {
                $this->logSection('exhibition', '  ... and '.(count($exhibition['objects']) - 10).' more');
            }
        }

        // Show valid transitions
        $validTransitions = $service->getValidTransitions($exhibition['status']);
        if (!empty($validTransitions)) {
            $this->logSection('exhibition', '');
            $this->logSection('exhibition', 'Valid status transitions: '.implode(', ', $validTransitions));
        }
    }

    /**
     * Generate object list for exhibition.
     */
    protected function generateObjectList($service, int $exhibitionId, string $format)
    {
        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->logSection('exhibition', 'Exhibition not found', null, 'ERROR');

            return;
        }

        $objects = $service->generateObjectList($exhibitionId);

        if (empty($objects)) {
            $this->logSection('exhibition', 'No objects in exhibition');

            return;
        }

        if ('csv' === $format) {
            $this->outputCsv($objects, $exhibition['title']);

            return;
        }

        if ('json' === $format) {
            echo json_encode(['exhibition' => $exhibition['title'], 'objects' => $objects], JSON_PRETTY_PRINT)."\n";

            return;
        }

        $this->logSection('exhibition', 'Object List: '.$exhibition['title']);
        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf(
            '%-15s %-35s %-15s %-10s',
            'Identifier', 'Title', 'Section', 'Status'
        ));
        $this->logSection('exhibition', str_repeat('-', 80));

        foreach ($objects as $obj) {
            $this->logSection('exhibition', sprintf(
                '%-15s %-35s %-15s %-10s',
                $obj['identifier'] ?? '-',
                mb_substr($obj['object_title'] ?? 'Untitled', 0, 33),
                mb_substr($obj['section_title'] ?? '-', 0, 13),
                $obj['status']
            ));
        }

        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf('Total: %d objects', count($objects)));
    }

    /**
     * Change exhibition status.
     */
    protected function changeStatus($service, int $exhibitionId, string $newStatus)
    {
        try {
            $service->transitionStatus($exhibitionId, $newStatus, 1, 'CLI status change');
            $this->logSection('exhibition', "Status changed to: {$newStatus}", null, 'INFO');
        } catch (\Exception $e) {
            $this->logSection('exhibition', 'Error: '.$e->getMessage(), null, 'ERROR');
        }
    }

    /**
     * Show current exhibitions.
     */
    protected function showCurrentExhibitions($service)
    {
        $this->logSection('exhibition', 'Currently Open Exhibitions');
        $this->logSection('exhibition', '');

        $result = $service->search(['status' => 'open'], 50, 0);

        if (empty($result['results'])) {
            $this->logSection('exhibition', 'No exhibitions currently open');

            return;
        }

        foreach ($result['results'] as $exhibition) {
            $this->logSection('exhibition', sprintf(
                '[%d] %s',
                $exhibition['id'],
                $exhibition['title']
            ));
            $this->logSection('exhibition', sprintf(
                '    Venue: %s | Closes: %s',
                $exhibition['venue_name'] ?? 'Unknown',
                $exhibition['closing_date'] ?? 'TBD'
            ));
        }

        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf('Total: %d open exhibitions', $result['total']));
    }

    /**
     * Show upcoming exhibitions.
     */
    protected function showUpcomingExhibitions($service)
    {
        $this->logSection('exhibition', 'Upcoming Exhibitions');
        $this->logSection('exhibition', '');

        $result = $service->search(['upcoming' => true], 50, 0);

        if (empty($result['results'])) {
            $this->logSection('exhibition', 'No upcoming exhibitions');

            return;
        }

        foreach ($result['results'] as $exhibition) {
            $daysUntil = $exhibition['opening_date']
                ? (new \DateTime($exhibition['opening_date']))->diff(new \DateTime())->days
                : null;

            $this->logSection('exhibition', sprintf(
                '[%d] %s',
                $exhibition['id'],
                $exhibition['title']
            ));
            $this->logSection('exhibition', sprintf(
                '    Status: %s | Opens: %s%s',
                $exhibition['status'],
                $exhibition['opening_date'] ?? 'TBD',
                $daysUntil !== null ? " ({$daysUntil} days)" : ''
            ));
        }

        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf('Total: %d upcoming exhibitions', $result['total']));
    }

    /**
     * Show overdue exhibitions.
     */
    protected function showOverdueExhibitions($service)
    {
        $this->logSection('exhibition', 'Overdue Exhibitions (past closing date but not closed)');
        $this->logSection('exhibition', '');

        $overdue = DB::table('exhibition')
            ->where('closing_date', '<', date('Y-m-d'))
            ->whereNotIn('status', ['closed', 'archived', 'canceled'])
            ->orderBy('closing_date')
            ->get();

        if ($overdue->isEmpty()) {
            $this->logSection('exhibition', 'No overdue exhibitions', null, 'INFO');

            return;
        }

        foreach ($overdue as $exhibition) {
            $daysOverdue = (new \DateTime($exhibition->closing_date))->diff(new \DateTime())->days;

            $this->logSection('exhibition', sprintf(
                '[%d] %s - %d days overdue',
                $exhibition->id,
                $exhibition->title,
                $daysOverdue
            ), null, 'ERROR');
            $this->logSection('exhibition', sprintf(
                '    Status: %s | Was due: %s',
                $exhibition->status,
                $exhibition->closing_date
            ));
        }

        $this->logSection('exhibition', '');
        $this->logSection('exhibition', sprintf('Total: %d overdue exhibitions', $overdue->count()), null, 'ERROR');
    }

    /**
     * Show statistics.
     */
    protected function showStatistics($service)
    {
        $stats = $service->getStatistics();

        $this->logSection('exhibition', 'Exhibition Statistics');
        $this->logSection('exhibition', '');

        $this->logSection('exhibition', sprintf('Total Exhibitions:      %d', $stats['total_exhibitions']));
        $this->logSection('exhibition', sprintf('Currently Open:         %d', $stats['current_exhibitions']));
        $this->logSection('exhibition', sprintf('Upcoming:               %d', $stats['upcoming_exhibitions']));
        $this->logSection('exhibition', sprintf('Objects on Display:     %d', $stats['total_objects_on_display']));
        $this->logSection('exhibition', sprintf('Total Insurance Value:  %s', number_format($stats['total_insurance_value'], 2)));

        if (!empty($stats['by_status'])) {
            $this->logSection('exhibition', '');
            $this->logSection('exhibition', 'By Status:');
            foreach ($stats['by_status'] as $status => $count) {
                $this->logSection('exhibition', sprintf('  %-15s: %d', $status, $count));
            }
        }

        if (!empty($stats['by_type'])) {
            $this->logSection('exhibition', '');
            $this->logSection('exhibition', 'By Type:');
            foreach ($stats['by_type'] as $type => $count) {
                $this->logSection('exhibition', sprintf('  %-15s: %d', $type, $count));
            }
        }
    }

    /**
     * Create exhibition interactively.
     */
    protected function createExhibition($service)
    {
        $this->logSection('exhibition', 'Create New Exhibition');
        $this->logSection('exhibition', '');

        // For CLI, we'll create with minimal data - full form in UI
        $this->logSection('exhibition', 'Interactive creation not fully implemented.');
        $this->logSection('exhibition', 'Please use the web interface or provide data via API.');
        $this->logSection('exhibition', '');
        $this->logSection('exhibition', 'Example API call:');
        $this->logSection('exhibition', '  POST /api/museum/exhibition');
        $this->logSection('exhibition', '  {');
        $this->logSection('exhibition', '    "title": "Exhibition Title",');
        $this->logSection('exhibition', '    "exhibition_type": "temporary",');
        $this->logSection('exhibition', '    "opening_date": "2024-06-01",');
        $this->logSection('exhibition', '    "closing_date": "2024-09-30"');
        $this->logSection('exhibition', '  }');
    }

    /**
     * Output CSV.
     */
    protected function outputCsv(array $data, string $title)
    {
        $filename = 'exhibition_objects_'.date('Y-m-d').'.csv';

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Exhibition: '.$title]);
        fputcsv($output, ['Generated: '.date('Y-m-d H:i:s')]);
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
