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

        $culture = $this->culture();
        $this->service = new ReportBuilderService($culture);
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
            QubitAcl::forwardUnauthorized();
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

        switch ($format) {
            case 'csv':
                $this->exportCsv($report, $results, $allColumns);
                break;
            case 'xlsx':
                $this->exportXlsx($report, $results, $allColumns);
                break;
            case 'pdf':
                $this->exportPdf($report, $results, $allColumns);
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

        DB::table('report_schedule')
            ->where('id', $scheduleId)
            ->where('custom_report_id', $id)
            ->delete();

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

        DB::table('dashboard_widget')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return $this->renderText(json_encode(['success' => true]));
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
