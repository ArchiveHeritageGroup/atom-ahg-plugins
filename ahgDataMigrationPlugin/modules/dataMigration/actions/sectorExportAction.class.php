<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Sector-specific export action.
 *
 * Exports records from the database using sector-specific exporter.
 */
class sectorExportAction extends AhgController
{
    public function execute($request)
    {
        // Check user authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $sector = $request->getParameter('sector', 'archive');
        $format = $request->getParameter('format', 'csv');

        // Initialize response variables
        $this->sector = $sector;
        $this->error = null;
        $this->records = [];

        // Validate sector
        $validSectors = ['archive', 'museum', 'library', 'gallery', 'dam'];
        if (!in_array($sector, $validSectors, true)) {
            $this->error = "Invalid sector: {$sector}";

            return sfView::SUCCESS;
        }

        // Handle form submission
        if ($request->isMethod('post')) {
            try {
                // Get selected record IDs
                $recordIds = $request->getParameter('record_ids', []);
                if (is_string($recordIds)) {
                    $recordIds = array_filter(array_map('intval', explode(',', $recordIds)));
                }

                // Get filter criteria if no specific IDs
                $filters = [];
                if (empty($recordIds)) {
                    $filters = [
                        'repository' => $request->getParameter('repository'),
                        'level' => $request->getParameter('level'),
                        'date_from' => $request->getParameter('date_from'),
                        'date_to' => $request->getParameter('date_to'),
                        'limit' => (int) $request->getParameter('limit', 1000),
                    ];
                }

                // Create exporter
                $exporter = $this->createExporter($sector);

                if (null === $exporter) {
                    throw new RuntimeException("No exporter available for sector: {$sector}");
                }

                // Export data
                if (!empty($recordIds)) {
                    $csvContent = $exporter->exportFromDatabase($recordIds);
                } else {
                    $recordIds = $this->queryRecordIds($filters);
                    $csvContent = $exporter->exportFromDatabase($recordIds);
                }

                // Generate filename
                $filename = $sector.'_export_'.date('Y-m-d_His').'.csv';

                // Send as download
                $this->getResponse()->setContentType('text/csv; charset=utf-8');
                $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
                $this->getResponse()->setHttpHeader('Cache-Control', 'no-cache, must-revalidate');

                // Add BOM for Excel compatibility
                return $this->renderText("\xEF\xBB\xBF".$csvContent);
            } catch (Exception $e) {
                $this->error = $e->getMessage();
            }
        }

        // Load available repositories for filter UI
        $this->repositories = $this->getRepositories();

        return sfView::SUCCESS;
    }

    /**
     * Create an exporter for the specified sector.
     *
     * @param string $sector
     *
     * @return \ahgDataMigrationPlugin\Exporters\BaseExporter|null
     */
    protected function createExporter(string $sector)
    {
        $exporterMap = [
            'archive' => \ahgDataMigrationPlugin\Exporters\ArchivesExporter::class,
            'museum' => \ahgDataMigrationPlugin\Exporters\MuseumExporter::class,
            'library' => \ahgDataMigrationPlugin\Exporters\LibraryExporter::class,
            'gallery' => \ahgDataMigrationPlugin\Exporters\GalleryExporter::class,
            'dam' => \ahgDataMigrationPlugin\Exporters\DamExporter::class,
        ];

        $className = $exporterMap[$sector] ?? null;

        if ($className && class_exists($className)) {
            return new $className();
        }

        return null;
    }

    /**
     * Query record IDs based on filters.
     *
     * @param array $filters
     *
     * @return array<int>
     */
    protected function queryRecordIds(array $filters): array
    {
        $query = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->where('id', '!=', \QubitInformationObject::ROOT_ID)
            ->select('id')
        ;

        // Apply repository filter
        if (!empty($filters['repository'])) {
            $query->where('repository_id', $filters['repository']);
        }

        // Apply level filter
        if (!empty($filters['level'])) {
            $query->where('level_of_description_id', $filters['level']);
        }

        // Apply limit
        $limit = min($filters['limit'] ?? 1000, 10000);
        $query->limit($limit);

        return $query->pluck('id')->toArray();
    }

    /**
     * Get available repositories.
     *
     * @return array
     */
    protected function getRepositories(): array
    {
        return \Illuminate\Database\Capsule\Manager::table('actor as a')
            ->join('actor_i18n as ai', 'a.id', '=', 'ai.id')
            ->where('a.class_name', 'QubitRepository')
            ->where('ai.culture', 'en')
            ->orderBy('ai.authorized_form_of_name')
            ->select('a.id', 'ai.authorized_form_of_name as name')
            ->get()
            ->toArray()
        ;
    }
}
