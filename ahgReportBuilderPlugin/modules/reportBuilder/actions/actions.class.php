<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class reportBuilderActions extends AhgController
{
    protected ReportBuilderService $service;

    public function boot(): void
    {
        // Load framework bootstrap (Illuminate DB + PathResolver)
        $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        // Load root Composer autoloader for Dompdf, PhpSpreadsheet, etc.
        $rootAutoload = class_exists('\AtomFramework\Helpers\PathResolver')
            ? \AtomFramework\Helpers\PathResolver::getRootAutoloadPath()
            : $this->config('sf_root_dir') . '/vendor/autoload.php';
        if (file_exists($rootAutoload)) {
            require_once $rootAutoload;
        }

        $pluginDir = $this->config('sf_plugins_dir') . '/ahgReportBuilderPlugin/lib';
        require_once $pluginDir . '/DataSourceRegistry.php';
        require_once $pluginDir . '/ColumnDiscovery.php';
        require_once $pluginDir . '/ReportBuilderService.php';
        require_once $pluginDir . '/HtmlSanitizer.php';
        require_once $pluginDir . '/SectionService.php';

        $culture = $this->culture();
        $this->service = new ReportBuilderService($culture);
    }

    /**
     * Lazy-load a service class.
     */
    private function loadService(string $className): void
    {
        if (!class_exists($className, false)) {
            $pluginDir = $this->config('sf_plugins_dir') . '/ahgReportBuilderPlugin/lib';
            $file = $pluginDir . '/' . $className . '.php';
            if (!file_exists($file)) {
                $file = $pluginDir . '/Export/' . $className . '.php';
            }
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Check if user is authenticated and has admin/editor access.
     */
    protected function checkAdminAccess(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check for admin, editor, or contributor role (AtoM method)
        if (!$this->getUser()->isAdministrator() &&
            !$this->getUser()->hasCredential('editor') &&
            !$this->getUser()->hasCredential('contributor')) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    /**
     * Get the current user ID.
     */
    protected function getUserId(): ?int
    {
        return $this->getUser()->getAttribute('user_id');
    }

    /**
     * List all custom reports.
     */
    public function executeIndex($request)
    {
        $this->checkAdminAccess();

        $this->reports = $this->service->getReports($this->getUserId());
        $this->statistics = $this->service->getStatistics($this->getUserId());
        $this->dataSources = DataSourceRegistry::getAll();
    }

    /**
     * Create a new custom report.
     */
    public function executeCreate($request)
    {
        $this->checkAdminAccess();

        $this->dataSources = DataSourceRegistry::getAll();

        if ($request->isMethod('post')) {
            try {
                $dataSource = $request->getParameter('data_source');
                if (!DataSourceRegistry::exists($dataSource)) {
                    throw new InvalidArgumentException('Invalid data source selected');
                }

                $reportId = $this->service->createReport([
                    'name' => $request->getParameter('name'),
                    'description' => $request->getParameter('description'),
                    'user_id' => $this->getUserId(),
                    'data_source' => $dataSource,
                    'columns' => ['id'], // Start with just ID
                    'layout' => [
                        'blocks' => [
                            ['type' => 'table', 'id' => 'main-table'],
                        ],
                    ],
                ]);

                $this->getUser()->setFlash('success', 'Report created. Now configure your report.');
                $this->redirect("admin/report-builder/{$reportId}/edit");
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * Edit a custom report (designer view).
     */
    public function executeEdit($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $this->report = $this->service->getReport($id);

        if (!$this->report) {
            $this->forward404('Report not found');
        }

        // Check ownership or shared access
        if ($this->report->user_id !== $this->getUserId() &&
            !$this->report->is_shared &&
            !$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Access denied');
        }

        $this->dataSources = DataSourceRegistry::getAll();
        $this->dataSource = DataSourceRegistry::get($this->report->data_source);
        $this->columnsGrouped = ColumnDiscovery::getColumnsGrouped($this->report->data_source);
        $this->allColumns = ColumnDiscovery::getColumns($this->report->data_source);

        // Load sections for section-based editing
        $sectionService = new SectionService();
        $this->sections = $sectionService->getSections($id);

        // Load comments count
        try {
            $this->commentCount = DB::table('report_comment')
                ->where('report_id', $id)
                ->where('is_resolved', 0)
                ->count();
        } catch (\Exception $e) {
            $this->commentCount = 0;
        }
    }

    /**
     * Preview a custom report.
     */
    public function executePreview($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $this->report = $this->service->getReport($id);

        if (!$this->report) {
            $this->forward404('Report not found');
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = min(100, max(10, (int) $request->getParameter('limit', 50)));

        $this->results = $this->service->executeReport($id, [], $page, $limit);
        $this->allColumns = ColumnDiscovery::getColumns($this->report->data_source);
    }

    /**
     * View a shared/public report.
     */
    public function executeView($request)
    {
        $id = (int) $request->getParameter('id');
        $this->report = $this->service->getReport($id);

        if (!$this->report) {
            $this->forward404('Report not found');
        }

        // Check if report is viewable
        if (!$this->report->is_public) {
            if (!$this->getUser()->isAuthenticated()) {
                $this->redirect('user/login');
            }
            if (!$this->report->is_shared &&
                $this->report->user_id !== $this->getUserId()) {
                $this->forward404('Access denied');
            }
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = min(100, max(10, (int) $request->getParameter('limit', 50)));

        $this->results = $this->service->executeReport($id, [], $page, $limit);
        $this->allColumns = ColumnDiscovery::getColumns($this->report->data_source);
    }

    /**
     * Clone a custom report.
     */
    public function executeCloneReport($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');

        try {
            $newId = $this->service->cloneReport($id, $this->getUserId());
            $this->getUser()->setFlash('success', 'Report cloned successfully');
            $this->redirect("admin/report-builder/{$newId}/edit");
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
            $this->redirect('admin/report-builder');
        }
    }

    /**
     * Delete a custom report.
     */
    public function executeDelete($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $report = $this->service->getReport($id);

        if (!$report) {
            $this->forward404('Report not found');
        }

        // Check ownership
        if ($report->user_id !== $this->getUserId() &&
            !$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Access denied');
        }

        if ($request->isMethod('post') || $request->getParameter('confirm') === '1') {
            $this->service->deleteReport($id);
            $this->getUser()->setFlash('success', 'Report deleted');
        }

        $this->redirect('admin/report-builder');
    }

    /**
     * Export a custom report.
     */
    public function executeExport($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'csv');

        $report = $this->service->getReport($id);
        if (!$report) {
            $this->forward404('Report not found');
        }

        // Get all data (no pagination for export)
        $results = $this->service->executeReport($id, [], 1, 10000);
        $allColumns = ColumnDiscovery::getColumns($report->data_source);

        // Load sections for enhanced export
        $sectionService = new SectionService();
        $sections = $sectionService->getSections($id);

        switch ($format) {
            case 'csv':
                $this->exportCsv($report, $results, $allColumns);
                break;
            case 'xlsx':
                $this->exportXlsx($report, $results, $allColumns);
                break;
            case 'pdf':
                $this->loadService('PdfExporter');
                $exporter = new PdfExporter($report, $sections, $results, $allColumns, $this->culture());
                $exporter->generate();
                break;
            case 'docx':
                $this->loadService('WordExporter');
                $exporter = new WordExporter($report, $sections, $results, $allColumns, $this->culture());
                $exporter->generate();
                break;
            default:
                $this->forward404('Invalid export format');
        }

        return sfView::NONE;
    }

    /**
     * Export to CSV.
     */
    private function exportCsv(object $report, array $results, array $allColumns): void
    {
        $filename = $this->sanitizeFilename($report->name) . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Write header row
        $headers = [];
        foreach ($report->columns as $col) {
            $headers[] = $allColumns[$col]['label'] ?? $col;
        }
        fputcsv($output, $headers);

        // Write data rows
        foreach ($results['results'] as $row) {
            $rowData = [];
            foreach ($report->columns as $col) {
                $rowData[] = $row->{$col} ?? '';
            }
            fputcsv($output, $rowData);
        }

        fclose($output);
    }

    /**
     * Export to XLSX.
     */
    private function exportXlsx(object $report, array $results, array $allColumns): void
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->getUser()->setFlash('error', 'XLSX export requires PhpSpreadsheet library');
            $this->redirect("admin/report-builder/{$report->id}/preview");
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($report->name, 0, 31));

        // Write header row
        $col = 1;
        foreach ($report->columns as $column) {
            $sheet->setCellValue([$col, 1], $allColumns[$column]['label'] ?? $column);
            $col++;
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE0E0E0'],
            ],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

        // Write data rows
        $row = 2;
        foreach ($results['results'] as $item) {
            $col = 1;
            foreach ($report->columns as $column) {
                $value = $item->{$column} ?? '';
                $sheet->setCellValue([$col, $row], $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $filename = $this->sanitizeFilename($report->name) . '_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * Export to PDF.
     */
    private function exportPdf(object $report, array $results, array $allColumns): void
    {
        // Check if Dompdf is available
        if (!class_exists('Dompdf\Dompdf')) {
            $this->getUser()->setFlash('error', 'PDF export requires Dompdf library');
            $this->redirect("admin/report-builder/{$report->id}/preview");
        }

        $html = $this->generatePdfHtml($report, $results, $allColumns);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = $this->sanitizeFilename($report->name) . '_' . date('Y-m-d') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * Generate HTML for PDF export.
     */
    private function generatePdfHtml(object $report, array $results, array $allColumns): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h1 { font-size: 16px; margin-bottom: 5px; }
            .meta { color: #666; margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
            th { background: #f5f5f5; font-weight: bold; }
            tr:nth-child(even) { background: #fafafa; }
        </style></head><body>';

        $html .= '<h1>' . htmlspecialchars($report->name) . '</h1>';
        $html .= '<div class="meta">Generated: ' . date('Y-m-d H:i:s') . ' | Total: ' . $results['total'] . ' records</div>';

        $html .= '<table><thead><tr>';
        foreach ($report->columns as $col) {
            $html .= '<th>' . htmlspecialchars($allColumns[$col]['label'] ?? $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($results['results'] as $row) {
            $html .= '<tr>';
            foreach ($report->columns as $col) {
                $value = $row->{$col} ?? '';
                // Truncate long text
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }

    /**
     * Manage report schedules.
     */
    public function executeSchedule($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $this->report = $this->service->getReport($id);

        if (!$this->report) {
            $this->forward404('Report not found');
        }

        // Get existing schedules
        $this->schedules = DB::table('report_schedule')
            ->where('custom_report_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        if ($request->isMethod('post')) {
            try {
                $frequency = $request->getParameter('frequency');
                $now = new DateTime();

                // Calculate next run based on frequency
                $nextRun = $this->calculateNextRun($frequency, $request);

                DB::table('report_schedule')->insert([
                    'custom_report_id' => $id,
                    'frequency' => $frequency,
                    'day_of_week' => $request->getParameter('day_of_week'),
                    'day_of_month' => $request->getParameter('day_of_month'),
                    'time_of_day' => $request->getParameter('time_of_day', '08:00:00'),
                    'output_format' => $request->getParameter('output_format', 'pdf'),
                    'email_recipients' => $request->getParameter('email_recipients'),
                    'next_run' => $nextRun->format('Y-m-d H:i:s'),
                    'is_active' => 1,
                    'created_at' => $now->format('Y-m-d H:i:s'),
                ]);

                $this->getUser()->setFlash('success', 'Schedule created');
                $this->redirect("admin/report-builder/{$id}/schedule");
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * Delete a report schedule.
     */
    public function executeScheduleDelete($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $scheduleId = (int) $request->getParameter('scheduleId');

        if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
            DB::table('report_schedule')
                ->where('id', $scheduleId)
                ->where('custom_report_id', $id)
                ->delete();
        } else {
            $conn = \Propel::getConnection();
            $stmt = $conn->prepare('DELETE FROM report_schedule WHERE id = ? AND custom_report_id = ?');
            $stmt->execute([$scheduleId, $id]);
        }

        $this->getUser()->setFlash('success', 'Schedule deleted');
        $this->redirect("admin/report-builder/{$id}/schedule");
    }

    /**
     * View archived reports.
     */
    public function executeArchive($request)
    {
        $this->checkAdminAccess();

        $this->archives = DB::table('report_archive as a')
            ->leftJoin('custom_report as r', 'a.custom_report_id', '=', 'r.id')
            ->select('a.*', 'r.name as report_name')
            ->orderBy('a.generated_at', 'desc')
            ->limit(100)
            ->get()
            ->toArray();
    }

    // ===================
    // API Actions
    // ===================

    /**
     * API: Save report definition.
     */
    public function executeApiSave($request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['id']) && $data['id']) {
                // Update existing
                $this->service->updateReport((int) $data['id'], $data);
                $response = ['success' => true, 'id' => $data['id'], 'message' => 'Report updated'];
            } else {
                // Create new
                $data['user_id'] = $this->getUserId();
                $id = $this->service->createReport($data);
                $response = ['success' => true, 'id' => $id, 'message' => 'Report created'];
            }

            return $this->renderText(json_encode($response));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Get report data.
     */
    public function executeApiData($request)
    {
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['id'])) {
                // Execute saved report
                $page = $data['page'] ?? 1;
                $limit = $data['limit'] ?? 50;
                $filters = $data['filters'] ?? [];

                $results = $this->service->executeReport((int) $data['id'], $filters, $page, $limit);
            } else {
                // Execute ad-hoc report definition
                $results = $this->service->executeReportDefinition(
                    $data['data_source'],
                    $data['columns'],
                    $data['filters'] ?? [],
                    $data['sort_config'] ?? [],
                    $data['page'] ?? 1,
                    $data['limit'] ?? 50
                );
            }

            return $this->renderText(json_encode(['success' => true, 'data' => $results]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Get chart data for a report.
     */
    public function executeApiChartData($request)
    {
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $reportId = (int) ($data['id'] ?? 0);

            if (!$reportId) {
                throw new InvalidArgumentException('Report ID required');
            }

            $chartConfig = [
                'groupBy' => $data['groupBy'] ?? null,
                'aggregate' => $data['aggregate'] ?? 'count',
                'field' => $data['field'] ?? null,
            ];

            $chartData = $this->service->getChartData($reportId, $chartConfig);

            return $this->renderText(json_encode([
                'success' => true,
                'data' => $chartData,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * API: Get columns for a data source.
     */
    public function executeApiColumns($request)
    {
        $this->getResponse()->setContentType('application/json');

        $source = $request->getParameter('source');

        if (!DataSourceRegistry::exists($source)) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Invalid data source',
            ]));
        }

        $columns = ColumnDiscovery::getColumnsGrouped($source);

        return $this->renderText(json_encode([
            'success' => true,
            'columns' => $columns,
            'dataSource' => DataSourceRegistry::get($source),
        ]));
    }

    /**
     * API: Delete a report.
     */
    public function executeApiDelete($request)
    {
        $this->checkAdminAccess();

        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');
        $report = $this->service->getReport($id);

        if (!$report) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Report not found',
            ]));
        }

        // Check ownership
        if ($report->user_id !== $this->getUserId() &&
            !$this->getUser()->hasCredential('administrator')) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Access denied',
            ]));
        }

        $this->service->deleteReport($id);

        return $this->renderText(json_encode([
            'success' => true,
            'message' => 'Report deleted',
        ]));
    }

    // ===================
    // Dashboard Widget Actions
    // ===================

    /**
     * Render a dashboard widget.
     */
    public function executeWidget($request)
    {
        $widgetId = (int) $request->getParameter('id');

        $this->widget = DB::table('dashboard_widget as w')
            ->leftJoin('custom_report as r', 'w.custom_report_id', '=', 'r.id')
            ->select('w.*', 'r.name as report_name', 'r.data_source', 'r.columns', 'r.filters')
            ->where('w.id', $widgetId)
            ->where('w.is_active', 1)
            ->first();

        if (!$this->widget) {
            return sfView::NONE;
        }

        // Get widget data based on type
        switch ($this->widget->widget_type) {
            case 'count':
            case 'stat':
                $this->widgetData = $this->getWidgetCountData($this->widget);
                break;
            case 'chart':
                $this->widgetData = $this->getWidgetChartData($this->widget);
                break;
            case 'table':
                $this->widgetData = $this->getWidgetTableData($this->widget);
                break;
        }
    }

    /**
     * Get count/stat data for widget.
     */
    private function getWidgetCountData(object $widget): array
    {
        if (!$widget->custom_report_id) {
            return ['value' => 0];
        }

        $results = $this->service->executeReport($widget->custom_report_id, [], 1, 1);

        return [
            'value' => $results['total'] ?? 0,
        ];
    }

    /**
     * Get chart data for widget.
     */
    private function getWidgetChartData(object $widget): array
    {
        $config = json_decode($widget->config, true) ?: [];

        if (!$widget->custom_report_id) {
            return ['labels' => [], 'data' => []];
        }

        $chartConfig = [
            'groupBy' => $config['groupBy'] ?? null,
            'aggregate' => $config['aggregate'] ?? 'count',
        ];

        return $this->service->getChartData($widget->custom_report_id, $chartConfig);
    }

    /**
     * Get table data for widget.
     */
    private function getWidgetTableData(object $widget): array
    {
        $config = json_decode($widget->config, true) ?: [];
        $limit = $config['limit'] ?? 5;

        if (!$widget->custom_report_id) {
            return ['results' => [], 'columns' => []];
        }

        $results = $this->service->executeReport($widget->custom_report_id, [], 1, $limit);
        $columns = json_decode($widget->columns, true) ?: [];
        $allColumns = ColumnDiscovery::getColumns($widget->data_source);

        return [
            'results' => $results['results'] ?? [],
            'columns' => $columns,
            'allColumns' => $allColumns,
        ];
    }

    /**
     * API: Get widgets for current user.
     */
    public function executeApiWidgets($request)
    {
        $this->getResponse()->setContentType('application/json');

        $userId = $this->getUserId();
        if (!$userId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $widgets = DB::table('dashboard_widget as w')
            ->leftJoin('custom_report as r', 'w.custom_report_id', '=', 'r.id')
            ->select('w.*', 'r.name as report_name')
            ->where('w.user_id', $userId)
            ->where('w.is_active', 1)
            ->orderBy('w.position_y')
            ->orderBy('w.position_x')
            ->get()
            ->toArray();

        return $this->renderText(json_encode(['success' => true, 'widgets' => $widgets]));
    }

    /**
     * API: Save widget configuration.
     */
    public function executeApiWidgetSave($request)
    {
        $this->getResponse()->setContentType('application/json');

        $userId = $this->getUserId();
        if (!$userId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        try {
            $data = json_decode($request->getContent(), true);
            $now = date('Y-m-d H:i:s');

            if (isset($data['id']) && $data['id']) {
                // Update existing widget
                DB::table('dashboard_widget')
                    ->where('id', $data['id'])
                    ->where('user_id', $userId)
                    ->update([
                        'title' => $data['title'] ?? null,
                        'position_x' => $data['position_x'] ?? 0,
                        'position_y' => $data['position_y'] ?? 0,
                        'width' => $data['width'] ?? 4,
                        'height' => $data['height'] ?? 2,
                        'config' => json_encode($data['config'] ?? []),
                        'updated_at' => $now,
                    ]);
                $id = $data['id'];
            } else {
                // Create new widget
                $id = DB::table('dashboard_widget')->insertGetId([
                    'user_id' => $userId,
                    'custom_report_id' => $data['custom_report_id'] ?? null,
                    'widget_type' => $data['widget_type'] ?? 'stat',
                    'title' => $data['title'] ?? null,
                    'position_x' => $data['position_x'] ?? 0,
                    'position_y' => $data['position_y'] ?? 0,
                    'width' => $data['width'] ?? 4,
                    'height' => $data['height'] ?? 2,
                    'config' => json_encode($data['config'] ?? []),
                    'is_active' => 1,
                    'created_at' => $now,
                ]);
            }

            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Delete widget.
     */
    public function executeApiWidgetDelete($request)
    {
        $this->getResponse()->setContentType('application/json');

        $userId = $this->getUserId();
        if (!$userId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $id = (int) $request->getParameter('id');

        if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
            DB::table('dashboard_widget')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->delete();
        } else {
            $conn = \Propel::getConnection();
            $stmt = $conn->prepare('DELETE FROM dashboard_widget WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $userId]);
        }

        return $this->renderText(json_encode(['success' => true]));
    }

    // ===================
    // Section API Actions (Phase 1)
    // ===================

    /**
     * API: Save a report section.
     */
    public function executeApiSectionSave($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $sectionService = new SectionService();

            // Sanitize HTML content
            if (isset($data['content'])) {
                $data['content'] = HtmlSanitizer::sanitize($data['content']);
            }

            if (isset($data['id']) && $data['id']) {
                $sectionService->update((int) $data['id'], $data);
                $id = $data['id'];
            } else {
                $id = $sectionService->create($data);
            }

            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Delete a report section.
     */
    public function executeApiSectionDelete($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $id = (int) $request->getParameter('id');
            $sectionService = new SectionService();
            $sectionService->delete($id);

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Reorder report sections.
     */
    public function executeApiSectionReorder($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $reportId = (int) ($data['report_id'] ?? 0);
            $sectionIds = $data['section_ids'] ?? [];

            $sectionService = new SectionService();
            $sectionService->reorder($reportId, $sectionIds);

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ===================
    // Link API Actions (Phase 3)
    // ===================

    /**
     * API: Save a link.
     */
    public function executeApiLinkSave($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $this->loadService('LinkService');
            $linkService = new LinkService($this->culture());

            if (isset($data['id']) && $data['id']) {
                $linkService->update((int) $data['id'], $data);
                $id = $data['id'];
            } else {
                $id = $linkService->create($data);
            }

            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Delete a link.
     */
    public function executeApiLinkDelete($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $id = (int) $request->getParameter('id');
            $this->loadService('LinkService');
            $linkService = new LinkService($this->culture());
            $linkService->delete($id);

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Fetch OpenGraph metadata from a URL.
     */
    public function executeApiOgFetch($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $url = $data['url'] ?? '';

            $this->loadService('LinkService');
            $linkService = new LinkService($this->culture());
            $ogData = $linkService->fetchOpenGraph($url);

            return $this->renderText(json_encode(['success' => true, 'data' => $ogData]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Search AtoM entities for cross-references.
     */
    public function executeApiEntitySearch($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $query = $request->getParameter('q', '');
            $type = $request->getParameter('type', 'information_object');

            $this->loadService('LinkService');
            $linkService = new LinkService($this->culture());
            $results = $linkService->searchEntities($query, $type);

            return $this->renderText(json_encode(['success' => true, 'results' => $results]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ===================
    // Template Actions (Phase 4)
    // ===================

    /**
     * View template library.
     */
    public function executeTemplates($request)
    {
        $this->checkAdminAccess();

        $this->loadService('TemplateService');
        $templateService = new TemplateService();
        $this->templates = $templateService->getTemplates();
    }

    /**
     * API: Create report from template.
     */
    public function executeApiTemplateApply($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $templateId = (int) ($data['template_id'] ?? 0);
            $reportName = $data['name'] ?? 'New Report';

            $this->loadService('TemplateService');
            $templateService = new TemplateService();
            $template = $templateService->getTemplate($templateId);

            if (!$template) {
                throw new InvalidArgumentException('Template not found');
            }

            $structure = $template->structure;
            $dataSource = $structure['data_source'] ?? 'information_object';

            // Create report
            $reportId = $this->service->createReport([
                'name' => $reportName,
                'description' => $template->description,
                'user_id' => $this->getUserId(),
                'data_source' => $dataSource,
                'columns' => ['id'],
                'layout' => ['blocks' => []],
            ]);

            // Apply template sections
            $templateService->applyToReport($templateId, $reportId);

            return $this->renderText(json_encode(['success' => true, 'id' => $reportId]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Save report as template.
     */
    public function executeApiTemplateSave($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);

            $this->loadService('TemplateService');
            $templateService = new TemplateService();

            if (isset($data['report_id'])) {
                // Create template from existing report
                $id = $templateService->createFromReport(
                    (int) $data['report_id'],
                    $data['name'] ?? 'Custom Template',
                    $data['category'] ?? 'custom',
                    $data['scope'] ?? 'user',
                    $this->getUserId()
                );
            } else {
                // Create/update template directly
                if (isset($data['id']) && $data['id']) {
                    $templateService->update((int) $data['id'], $data);
                    $id = $data['id'];
                } else {
                    $data['created_by'] = $this->getUserId();
                    $id = $templateService->create($data);
                }
            }

            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Delete template.
     */
    public function executeApiTemplateDelete($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $id = (int) $request->getParameter('id');
            $this->loadService('TemplateService');
            $templateService = new TemplateService();
            $templateService->delete($id);

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ===================
    // Workflow Actions (Phase 5)
    // ===================

    /**
     * API: Change report status.
     */
    public function executeApiStatusChange($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $reportId = (int) ($data['report_id'] ?? 0);
            $newStatus = $data['status'] ?? '';

            $this->loadService('WorkflowService');
            $workflowService = new WorkflowService();
            $workflowService->transition($reportId, $newStatus, $this->getUserId());

            return $this->renderText(json_encode(['success' => true, 'status' => $newStatus]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Add a comment.
     */
    public function executeApiComment($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);

            $this->loadService('CommentService');
            $commentService = new CommentService();

            $action = $data['form_action'] ?? 'create';

            switch ($action) {
                case 'create':
                    $id = $commentService->create([
                        'report_id' => (int) ($data['report_id'] ?? 0),
                        'section_id' => isset($data['section_id']) ? (int) $data['section_id'] : null,
                        'user_id' => $this->getUserId(),
                        'content' => $data['content'] ?? '',
                    ]);

                    return $this->renderText(json_encode(['success' => true, 'id' => $id]));

                case 'resolve':
                    $commentService->resolve((int) $data['comment_id'], $this->getUserId());

                    return $this->renderText(json_encode(['success' => true]));

                case 'unresolve':
                    $commentService->unresolve((int) $data['comment_id']);

                    return $this->renderText(json_encode(['success' => true]));

                case 'delete':
                    $commentService->delete((int) $data['comment_id']);

                    return $this->renderText(json_encode(['success' => true]));

                case 'list':
                    $comments = $commentService->getComments(
                        (int) ($data['report_id'] ?? 0),
                        isset($data['section_id']) ? (int) $data['section_id'] : null
                    );

                    return $this->renderText(json_encode(['success' => true, 'comments' => $comments]));
            }

            return $this->renderText(json_encode(['success' => false, 'error' => 'Unknown action']));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Get version history.
     */
    public function executeApiVersions($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $reportId = (int) $request->getParameter('id', 0);

            if (!$reportId) {
                $data = json_decode($request->getContent(), true);
                $reportId = (int) ($data['report_id'] ?? 0);
            }

            $this->loadService('VersionService');
            $versionService = new VersionService();
            $versions = $versionService->getVersions($reportId);

            return $this->renderText(json_encode(['success' => true, 'versions' => $versions]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Create a version snapshot.
     */
    public function executeApiVersionCreate($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $reportId = (int) ($data['report_id'] ?? 0);
            $summary = $data['change_summary'] ?? null;

            $this->loadService('VersionService');
            $versionService = new VersionService();
            $versionId = $versionService->createVersion($reportId, $this->getUserId(), $summary);

            return $this->renderText(json_encode(['success' => true, 'version_id' => $versionId]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Restore a version.
     */
    public function executeApiVersionRestore($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $versionId = (int) ($data['version_id'] ?? 0);

            $this->loadService('VersionService');
            $versionService = new VersionService();
            $versionService->restoreVersion($versionId, $this->getUserId());

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * View version history page.
     */
    public function executeHistory($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $this->report = $this->service->getReport($id);

        if (!$this->report) {
            $this->forward404('Report not found');
        }

        $this->loadService('VersionService');
        $versionService = new VersionService();
        $this->versions = $versionService->getVersions($id);
    }

    // ===================
    // Query Actions (Phase 6)
    // ===================

    /**
     * Query builder page.
     */
    public function executeQuery($request)
    {
        $this->checkAdminAccess();

        $id = (int) $request->getParameter('id');
        $this->report = $this->service->getReport($id);

        if (!$this->report) {
            $this->forward404('Report not found');
        }

        $this->isAdmin = $this->getUser()->isAdministrator();
    }

    /**
     * API: Execute a query.
     */
    public function executeApiQueryExecute($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);

            $this->loadService('QueryBuilder');
            $queryBuilder = new QueryBuilder();

            if (($data['query_type'] ?? 'visual') === 'raw_sql') {
                // Admin-only raw SQL
                if (!$this->getUser()->isAdministrator()) {
                    throw new RuntimeException('Raw SQL execution requires administrator access');
                }
                $results = $queryBuilder->executeRawSql(
                    $data['sql'] ?? '',
                    $data['params'] ?? [],
                    $this->getUserId()
                );
            } else {
                $results = $queryBuilder->executeVisualQuery($data, $this->getUserId());
            }

            return $this->renderText(json_encode(['success' => true, 'data' => $results]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Save a query.
     */
    public function executeApiQuerySave($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $data['created_by'] = $this->getUserId();

            $this->loadService('QueryBuilder');
            $queryBuilder = new QueryBuilder();
            $id = $queryBuilder->saveQuery($data);

            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Validate a SQL query.
     */
    public function executeApiQueryValidate($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $sql = $data['sql'] ?? '';

            $this->loadService('QueryBuilder');
            $queryBuilder = new QueryBuilder();
            $issues = $queryBuilder->validateSql($sql);

            return $this->renderText(json_encode([
                'success' => true,
                'valid' => empty($issues),
                'issues' => $issues,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Get available tables for query builder.
     */
    public function executeApiQueryTables($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $this->loadService('QueryBuilder');
            $queryBuilder = new QueryBuilder();
            $tables = $queryBuilder->getAvailableTables();

            return $this->renderText(json_encode(['success' => true, 'tables' => $tables]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Get columns for a table.
     */
    public function executeApiQueryColumns($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $table = $request->getParameter('table', '');

            $this->loadService('QueryBuilder');
            $queryBuilder = new QueryBuilder();
            $columns = $queryBuilder->getTableColumns($table);

            return $this->renderText(json_encode(['success' => true, 'columns' => $columns]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ===================
    // Share Actions (Phase 7)
    // ===================

    /**
     * API: Create a share link.
     */
    public function executeApiShareCreate($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);

            $this->loadService('ShareService');
            $shareService = new ShareService();

            $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
            $emailRecipients = $data['email_recipients'] ?? null;

            $share = $shareService->createShare(
                (int) ($data['report_id'] ?? 0),
                $this->getUserId(),
                $expiresAt,
                $emailRecipients
            );

            return $this->renderText(json_encode([
                'success' => true,
                'share' => $share,
                'url' => $shareService->getShareUrl($share['token']),
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Deactivate a share.
     */
    public function executeApiShareDeactivate($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $id = (int) $request->getParameter('id');
            $this->loadService('ShareService');
            $shareService = new ShareService();
            $shareService->deactivateShare($id);

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * View a shared report (public access via token).
     */
    public function executeSharedView($request)
    {
        $token = $request->getParameter('token', '');

        $this->loadService('ShareService');
        $shareService = new ShareService();
        $share = $shareService->getShare($token);

        if (!$share) {
            $this->expired = true;
            $this->setTemplate('shareSuccess');

            return;
        }

        $this->share = $share;
        $this->report = $this->service->getReport($share->report_id);

        if (!$this->report) {
            $this->expired = true;
            $this->setTemplate('shareSuccess');

            return;
        }

        $this->expired = false;

        // Load sections
        $sectionService = new SectionService();
        $this->sections = $sectionService->getSections($share->report_id);

        // Load report data
        $this->reportData = $this->service->executeReport($share->report_id, [], 1, 100);
        $this->allColumns = ColumnDiscovery::getColumns($this->report->data_source);

        $this->setTemplate('shareSuccess');
    }

    // ===================
    // Attachment Actions (Phase 7)
    // ===================

    /**
     * API: Upload attachment.
     */
    public function executeApiAttachmentUpload($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $reportId = (int) $request->getParameter('report_id');
            $sectionId = $request->getParameter('section_id') ? (int) $request->getParameter('section_id') : null;

            if (empty($_FILES['file'])) {
                throw new InvalidArgumentException('No file uploaded');
            }

            $this->loadService('AttachmentService');
            $attachmentService = new AttachmentService();
            $attachment = $attachmentService->upload($reportId, $sectionId, $_FILES['file']);

            return $this->renderText(json_encode(['success' => true, 'attachment' => $attachment]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Delete attachment.
     */
    public function executeApiAttachmentDelete($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $id = (int) $request->getParameter('id');
            $this->loadService('AttachmentService');
            $attachmentService = new AttachmentService();
            $attachmentService->delete($id);

            return $this->renderText(json_encode(['success' => true]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * API: Get attachments for a report/section.
     */
    public function executeApiAttachments($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $reportId = (int) $request->getParameter('report_id');
            $sectionId = $request->getParameter('section_id') ? (int) $request->getParameter('section_id') : null;

            $this->loadService('AttachmentService');
            $attachmentService = new AttachmentService();
            $attachments = $attachmentService->getAttachments($reportId, $sectionId);

            return $this->renderText(json_encode(['success' => true, 'attachments' => $attachments]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ===================
    // Data Binding Actions (Phase 6)
    // ===================

    /**
     * API: Create a data snapshot.
     */
    public function executeApiSnapshot($request)
    {
        $this->checkAdminAccess();
        $this->getResponse()->setContentType('application/json');

        try {
            $data = json_decode($request->getContent(), true);
            $reportId = (int) ($data['report_id'] ?? 0);
            $action = $data['form_action'] ?? 'create';

            $this->loadService('DataBindingService');
            $dataBindingService = new DataBindingService($this->culture());

            if ($action === 'clear') {
                $dataBindingService->clearSnapshot($reportId);

                return $this->renderText(json_encode(['success' => true, 'mode' => 'live']));
            }

            $dataBindingService->createSnapshot($reportId);

            return $this->renderText(json_encode(['success' => true, 'mode' => 'snapshot']));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ===================
    // Helper Methods
    // ===================

    /**
     * Sanitize a string for use as a filename.
     */
    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);

        return trim($name, '_');
    }

    /**
     * Calculate the next run time for a schedule.
     */
    private function calculateNextRun(string $frequency, sfWebRequest $request): DateTime
    {
        $now = new DateTime();
        $time = $request->getParameter('time_of_day', '08:00:00');

        switch ($frequency) {
            case 'daily':
                $next = new DateTime('tomorrow ' . $time);
                break;

            case 'weekly':
                $dayOfWeek = (int) $request->getParameter('day_of_week', 1); // Default Monday
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $next = new DateTime('next ' . $days[$dayOfWeek] . ' ' . $time);
                break;

            case 'monthly':
                $dayOfMonth = (int) $request->getParameter('day_of_month', 1);
                $next = new DateTime('first day of next month ' . $time);
                $next->setDate((int) $next->format('Y'), (int) $next->format('m'), min($dayOfMonth, (int) $next->format('t')));
                break;

            case 'quarterly':
                $next = new DateTime('first day of +3 months ' . $time);
                break;

            default:
                $next = new DateTime('tomorrow ' . $time);
        }

        return $next;
    }
}
