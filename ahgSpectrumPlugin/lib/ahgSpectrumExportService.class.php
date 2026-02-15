<?php

/**
 * Spectrum Export Service
 *
 * Generates PDF and CSV exports of Spectrum procedure histories
 * for audits, inspections, and compliance reporting.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgSpectrumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgSpectrumExportService
{
    protected $eventService;

    public function __construct()
    {
        $this->eventService = new ahgSpectrumEventService();
    }

    /**
     * Export object procedure history as CSV
     */
    public function exportObjectHistoryCSV($objectId, $procedureId = null): string
    {
        $events = $this->eventService->getObjectEvents($objectId, $procedureId, 10000);
        $object = $this->getInformationObjectData($objectId);

        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, [
            'Object Identifier',
            'Object Title',
            'Procedure',
            'Event Type',
            'Status From',
            'Status To',
            'User',
            'Assigned To',
            'Due Date',
            'Completed Date',
            'Location',
            'Notes',
            'Event Date/Time',
        ]);

        // Data rows
        foreach ($events as $event) {
            $procedureName = ahgSpectrumEventService::$procedures[$event['procedure_id']]['name'] ?? $event['procedure_id'];
            $eventTypeName = ahgSpectrumEventService::$eventTypeLabels[$event['event_type']] ?? $event['event_type'];
            $statusFromName = ahgSpectrumEventService::$statusLabels[$event['status_from']]['label'] ?? $event['status_from'];
            $statusToName = ahgSpectrumEventService::$statusLabels[$event['status_to']]['label'] ?? $event['status_to'];

            fputcsv($output, [
                $object ? $object->identifier : '',
                $object ? $object->title : '',
                $procedureName,
                $eventTypeName,
                $statusFromName,
                $statusToName,
                $event['user_name'] ?? '',
                $event['assigned_to_name'] ?? '',
                $event['due_date'] ?? '',
                $event['completed_date'] ?? '',
                $event['location'] ?? '',
                $event['notes'] ?? '',
                $event['created_at'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export repository-wide procedure history as CSV
     */
    public function exportRepositoryHistoryCSV($repositoryId = null, $procedureId = null, $dateFrom = null, $dateTo = null): string
    {
        $query = DB::table('spectrum_event as e')
            ->join('information_object as io', 'e.object_id', '=', 'io.id')
            ->leftJoin('user as u', 'e.user_id', '=', 'u.id')
            ->leftJoin('user as a', 'e.assigned_to_id', '=', 'a.id')
            ->select(
                'e.*',
                'io.identifier as object_identifier',
                'u.username as user_name',
                'a.username as assigned_to_name'
            );

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        if ($procedureId) {
            $query->where('e.procedure_id', $procedureId);
        }

        if ($dateFrom) {
            $query->where('e.created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $query->where('e.created_at', '<=', $dateTo . ' 23:59:59');
        }

        $events = $query->orderByDesc('e.created_at')->get();

        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, [
            'Object Identifier',
            'Procedure',
            'Spectrum Ref',
            'Event Type',
            'Status From',
            'Status To',
            'User',
            'Assigned To',
            'Due Date',
            'Completed Date',
            'Location',
            'Notes',
            'Event Date/Time',
        ]);

        // Data rows
        foreach ($events as $event) {
            $procedure = ahgSpectrumEventService::$procedures[$event->procedure_id] ?? [];
            $procedureName = $procedure['name'] ?? $event->procedure_id;
            $spectrumRef = $procedure['spectrum_ref'] ?? '';
            $eventTypeName = ahgSpectrumEventService::$eventTypeLabels[$event->event_type] ?? $event->event_type;
            $statusFromName = ahgSpectrumEventService::$statusLabels[$event->status_from]['label'] ?? $event->status_from;
            $statusToName = ahgSpectrumEventService::$statusLabels[$event->status_to]['label'] ?? $event->status_to;

            fputcsv($output, [
                $event->object_identifier,
                $procedureName,
                $spectrumRef,
                $eventTypeName,
                $statusFromName,
                $statusToName,
                $event->user_name ?? '',
                $event->assigned_to_name ?? '',
                $event->due_date ?? '',
                $event->completed_date ?? '',
                $event->location ?? '',
                $event->notes ?? '',
                $event->created_at,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export procedure statistics as CSV
     */
    public function exportStatisticsCSV($repositoryId = null, $dateFrom = null, $dateTo = null): string
    {
        $stats = $this->eventService->getProcedureStatistics($repositoryId, $dateFrom, $dateTo);

        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, [
            'Procedure',
            'Spectrum Reference',
            'Category',
            'Total Objects',
            'Completed',
            'In Progress',
            'Pending Review',
            'Overdue',
            'Completion Rate (%)',
        ]);

        // Data rows
        foreach ($stats as $procedureId => $stat) {
            $completionRate = $stat['total_objects'] > 0
                ? round(($stat['completed'] / $stat['total_objects']) * 100, 1)
                : 0;

            fputcsv($output, [
                $stat['name'],
                $stat['spectrum_ref'],
                ucfirst(str_replace('_', ' ', $stat['category'])),
                $stat['total_objects'],
                $stat['completed'],
                $stat['in_progress'],
                $stat['pending_review'],
                $stat['overdue'],
                $completionRate,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export overdue procedures as CSV
     */
    public function exportOverdueCSV($repositoryId = null): string
    {
        $overdue = $this->eventService->getOverdueProcedures($repositoryId);

        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, [
            'Object Identifier',
            'Procedure',
            'Due Date',
            'Days Overdue',
            'Assigned To',
        ]);

        // Data rows
        foreach ($overdue as $item) {
            $procedureName = ahgSpectrumEventService::$procedures[$item['procedure_id']]['name'] ?? $item['procedure_id'];

            fputcsv($output, [
                $item['object_identifier'],
                $procedureName,
                $item['due_date'],
                $item['days_overdue'],
                $item['assigned_to_name'] ?? 'Unassigned',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate PDF procedure history for an object
     */
    public function generateObjectHistoryPDF($objectId, $procedureId = null): array
    {
        $events = $this->eventService->getObjectEvents($objectId, $procedureId, 10000);
        $object = $this->getInformationObjectData($objectId);
        $statuses = $this->eventService->getAllProcedureStatuses($objectId);
        $progress = $this->eventService->calculateObjectProgress($objectId);

        $userId = null;
        if (class_exists('sfContext') && sfContext::hasInstance()) {
            $userId = sfContext::getInstance()->getUser()->getAttribute('user_id');
        }

        $html = $this->renderPDFTemplate('object_history', [
            'object' => $object,
            'events' => $events,
            'statuses' => $statuses,
            'progress' => $progress,
            'procedureId' => $procedureId,
            'generatedAt' => date('Y-m-d H:i:s'),
            'generatedBy' => $userId,
        ]);

        return $this->htmlToPDF($html, sprintf(
            'spectrum_history_%s.pdf',
            $object ? $object->identifier : $objectId
        ));
    }

    /**
     * Generate PDF audit report for repository
     */
    public function generateAuditReportPDF($repositoryId = null, $dateFrom = null, $dateTo = null): array
    {
        $stats = $this->eventService->getProcedureStatistics($repositoryId, $dateFrom, $dateTo);
        $overdue = $this->eventService->getOverdueProcedures($repositoryId);
        $recentEvents = $this->eventService->getRecentEvents($repositoryId, null, 50);

        $repository = $repositoryId ? $this->getRepositoryData($repositoryId) : null;

        $html = $this->renderPDFTemplate('audit_report', [
            'repository' => $repository,
            'stats' => $stats,
            'overdue' => $overdue,
            'recentEvents' => $recentEvents,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'generatedAt' => date('Y-m-d H:i:s'),
        ]);

        return $this->htmlToPDF($html, sprintf('spectrum_audit_%s.pdf', date('Y-m-d')));
    }

    /**
     * Get information object data
     */
    protected function getInformationObjectData(int $objectId): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select('io.*', 'i18n.title')
            ->first();
    }

    /**
     * Get repository data
     */
    protected function getRepositoryData(int $repositoryId): ?object
    {
        return DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->where('r.id', $repositoryId)
            ->select('r.*', 'ai.authorized_form_of_name')
            ->first();
    }

    /**
     * Render PDF template
     */
    protected function renderPDFTemplate($template, $vars): string
    {
        extract($vars);

        ob_start();

        switch ($template) {
            case 'object_history':
                $this->renderObjectHistoryTemplate($object, $events, $statuses, $progress, $procedureId, $generatedAt);
                break;
            case 'audit_report':
                $this->renderAuditReportTemplate($repository, $stats, $overdue, $recentEvents, $dateFrom, $dateTo, $generatedAt);
                break;
        }

        return ob_get_clean();
    }

    /**
     * Render object history PDF template
     */
    protected function renderObjectHistoryTemplate($object, $events, $statuses, $progress, $procedureId, $generatedAt): void
    {
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Spectrum Procedure History</title>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
        .header { background: #2c3e50; color: #fff; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .header .subtitle { font-size: 11pt; opacity: 0.8; }
        .section { margin: 20px; page-break-inside: avoid; }
        .section h2 { font-size: 14pt; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; margin-bottom: 15px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 150px; padding: 5px; font-weight: bold; background: #f5f5f5; }
        .info-value { display: table-cell; padding: 5px; }
        .progress-bar { background: #ecf0f1; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { background: #27ae60; height: 100%; }
        .status-grid { display: table; width: 100%; border-collapse: collapse; margin: 10px 0; }
        .status-row { display: table-row; }
        .status-row:nth-child(even) { background: #f9f9f9; }
        .status-cell { display: table-cell; padding: 8px; border: 1px solid #ddd; }
        .status-header { font-weight: bold; background: #2c3e50; color: #fff; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9pt; color: #fff; }
        .status-completed { background: #27ae60; }
        .status-in_progress { background: #3498db; }
        .status-pending_review { background: #f39c12; }
        .status-overdue { background: #e74c3c; }
        .status-not_started { background: #95a5a6; }
        .event-list { margin: 10px 0; }
        .event-item { padding: 10px; border-left: 3px solid #3498db; margin-bottom: 10px; background: #f9f9f9; }
        .event-header { font-weight: bold; margin-bottom: 5px; }
        .event-meta { font-size: 9pt; color: #7f8c8d; }
        .event-notes { margin-top: 5px; font-style: italic; }
        .footer { position: fixed; bottom: 0; width: 100%; padding: 10px 20px; font-size: 8pt; color: #7f8c8d; border-top: 1px solid #ddd; }
        @page { margin: 20mm 15mm 25mm 15mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Spectrum Procedure History</h1>
        <div class="subtitle">
            <?php if ($object): ?>
                <?php echo htmlspecialchars($object->identifier); ?> - <?php echo htmlspecialchars($object->title ?? ''); ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>Object Information</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Identifier</div>
                <div class="info-value"><?php echo $object ? htmlspecialchars($object->identifier) : 'N/A'; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Title</div>
                <div class="info-value"><?php echo $object ? htmlspecialchars($object->title ?? '') : 'N/A'; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Report Generated</div>
                <div class="info-value"><?php echo $generatedAt; ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Overall Progress</h2>
        <p>Completed: <?php echo $progress['completed']; ?> of <?php echo $progress['total']; ?> procedures (<?php echo $progress['percentage']; ?>%)</p>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress['percentage']; ?>%"></div>
        </div>
        <?php if ($progress['overdue'] > 0): ?>
            <p style="color: #e74c3c;"><strong><?php echo $progress['overdue']; ?> procedure(s) overdue</strong></p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Procedure Status Summary</h2>
        <div class="status-grid">
            <div class="status-row">
                <div class="status-cell status-header">Procedure</div>
                <div class="status-cell status-header">Status</div>
                <div class="status-cell status-header">Due Date</div>
                <div class="status-cell status-header">Last Update</div>
            </div>
            <?php foreach ($statuses as $procId => $status): ?>
                <?php if ($procedureId && $procId !== $procedureId) continue; ?>
                <div class="status-row">
                    <div class="status-cell"><?php echo htmlspecialchars($status['name']); ?></div>
                    <div class="status-cell">
                        <span class="status-badge status-<?php echo $status['current_status']; ?>">
                            <?php echo ahgSpectrumEventService::$statusLabels[$status['current_status']]['label'] ?? $status['current_status']; ?>
                        </span>
                    </div>
                    <div class="status-cell"><?php echo $status['due_date'] ?? '-'; ?></div>
                    <div class="status-cell"><?php echo $status['last_update'] ? date('Y-m-d', strtotime($status['last_update'])) : '-'; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section">
        <h2>Event History</h2>
        <div class="event-list">
            <?php foreach ($events as $event): ?>
                <div class="event-item">
                    <div class="event-header">
                        <?php echo ahgSpectrumEventService::$eventTypeLabels[$event['event_type']] ?? $event['event_type']; ?>
                        - <?php echo ahgSpectrumEventService::$procedures[$event['procedure_id']]['name'] ?? $event['procedure_id']; ?>
                    </div>
                    <div class="event-meta">
                        <?php echo $event['created_at']; ?>
                        <?php if (!empty($event['user_name'])): ?> | By: <?php echo htmlspecialchars($event['user_name']); ?><?php endif; ?>
                        <?php if (!empty($event['status_from']) && !empty($event['status_to'])): ?>
                            | Status: <?php echo ahgSpectrumEventService::$statusLabels[$event['status_from']]['label'] ?? $event['status_from']; ?>
                            → <?php echo ahgSpectrumEventService::$statusLabels[$event['status_to']]['label'] ?? $event['status_to']; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($event['notes'])): ?>
                        <div class="event-notes"><?php echo htmlspecialchars($event['notes']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($events)): ?>
                <p>No events recorded.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        Spectrum 5.1 Procedure History Report | Generated: <?php echo $generatedAt; ?> | ahgSpectrumPlugin for AtoM
    </div>
</body>
</html>
        <?php
    }

    /**
     * Render audit report PDF template
     */
    protected function renderAuditReportTemplate($repository, $stats, $overdue, $recentEvents, $dateFrom, $dateTo, $generatedAt): void
    {
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Spectrum Audit Report</title>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
        .header { background: #2c3e50; color: #fff; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .header .subtitle { font-size: 11pt; opacity: 0.8; }
        .section { margin: 20px; page-break-inside: avoid; }
        .section h2 { font-size: 14pt; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; margin-bottom: 15px; }
        .stats-grid { display: table; width: 100%; border-collapse: collapse; }
        .stats-row { display: table-row; }
        .stats-row:nth-child(even) { background: #f9f9f9; }
        .stats-cell { display: table-cell; padding: 8px; border: 1px solid #ddd; text-align: center; }
        .stats-header { font-weight: bold; background: #2c3e50; color: #fff; }
        .stats-name { text-align: left; }
        .overdue-item { padding: 8px; background: #fdf2f2; border-left: 3px solid #e74c3c; margin-bottom: 8px; }
        .summary-box { background: #e8f6f3; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary-stat { display: inline-block; margin-right: 30px; }
        .summary-stat .number { font-size: 24pt; font-weight: bold; color: #27ae60; }
        .summary-stat .label { font-size: 9pt; color: #7f8c8d; }
        .footer { position: fixed; bottom: 0; width: 100%; padding: 10px 20px; font-size: 8pt; color: #7f8c8d; border-top: 1px solid #ddd; }
        @page { margin: 20mm 15mm 25mm 15mm; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Spectrum Audit Report</h1>
        <div class="subtitle">
            <?php if ($repository): ?>
                <?php echo htmlspecialchars($repository->authorized_form_of_name ?? ''); ?>
            <?php else: ?>
                All Repositories
            <?php endif; ?>
            <?php if ($dateFrom || $dateTo): ?>
                | Period: <?php echo $dateFrom ?? 'Start'; ?> to <?php echo $dateTo ?? 'Present'; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>Executive Summary</h2>
        <div class="summary-box">
            <?php
            $totalCompleted = array_sum(array_column($stats, 'completed'));
            $totalInProgress = array_sum(array_column($stats, 'in_progress'));
            $totalOverdue = count($overdue);
            ?>
            <div class="summary-stat">
                <div class="number"><?php echo $totalCompleted; ?></div>
                <div class="label">Completed Procedures</div>
            </div>
            <div class="summary-stat">
                <div class="number" style="color: #3498db;"><?php echo $totalInProgress; ?></div>
                <div class="label">In Progress</div>
            </div>
            <div class="summary-stat">
                <div class="number" style="color: #e74c3c;"><?php echo $totalOverdue; ?></div>
                <div class="label">Overdue</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Procedure Statistics</h2>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stats-cell stats-header stats-name">Procedure</div>
                <div class="stats-cell stats-header">Ref</div>
                <div class="stats-cell stats-header">Total</div>
                <div class="stats-cell stats-header">Completed</div>
                <div class="stats-cell stats-header">In Progress</div>
                <div class="stats-cell stats-header">Overdue</div>
                <div class="stats-cell stats-header">Rate</div>
            </div>
            <?php foreach ($stats as $procId => $stat): ?>
                <?php if ($stat['total_objects'] == 0) continue; ?>
                <div class="stats-row">
                    <div class="stats-cell stats-name"><?php echo htmlspecialchars($stat['name']); ?></div>
                    <div class="stats-cell"><?php echo $stat['spectrum_ref']; ?></div>
                    <div class="stats-cell"><?php echo $stat['total_objects']; ?></div>
                    <div class="stats-cell"><?php echo $stat['completed']; ?></div>
                    <div class="stats-cell"><?php echo $stat['in_progress']; ?></div>
                    <div class="stats-cell" style="<?php echo $stat['overdue'] > 0 ? 'color: #e74c3c; font-weight: bold;' : ''; ?>">
                        <?php echo $stat['overdue']; ?>
                    </div>
                    <div class="stats-cell">
                        <?php echo $stat['total_objects'] > 0 ? round(($stat['completed'] / $stat['total_objects']) * 100) : 0; ?>%
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($overdue)): ?>
    <div class="section">
        <h2>Overdue Procedures (<?php echo count($overdue); ?>)</h2>
        <?php foreach (array_slice($overdue, 0, 20) as $item): ?>
            <div class="overdue-item">
                <strong><?php echo htmlspecialchars($item['object_identifier']); ?></strong>
                - <?php echo ahgSpectrumEventService::$procedures[$item['procedure_id']]['name'] ?? $item['procedure_id']; ?>
                <br>
                <small>Due: <?php echo $item['due_date']; ?> (<?php echo $item['days_overdue']; ?> days overdue)
                | Assigned: <?php echo $item['assigned_to_name'] ?? 'Unassigned'; ?></small>
            </div>
        <?php endforeach; ?>
        <?php if (count($overdue) > 20): ?>
            <p><em>... and <?php echo count($overdue) - 20; ?> more overdue items</em></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>Recent Activity</h2>
        <?php foreach (array_slice($recentEvents, 0, 15) as $event): ?>
            <div style="padding: 5px 0; border-bottom: 1px solid #eee;">
                <strong><?php echo htmlspecialchars($event['object_identifier']); ?></strong>
                - <?php echo ahgSpectrumEventService::$eventTypeLabels[$event['event_type']] ?? $event['event_type']; ?>
                (<?php echo ahgSpectrumEventService::$procedures[$event['procedure_id']]['name'] ?? $event['procedure_id']; ?>)
                <br>
                <small><?php echo $event['created_at']; ?> | <?php echo $event['user_name'] ?? 'System'; ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        Spectrum 5.1 Audit Report | Generated: <?php echo $generatedAt; ?> | ahgSpectrumPlugin for AtoM
    </div>
</body>
</html>
        <?php
    }

    /**
     * Convert HTML to PDF using available method
     */
    protected function htmlToPDF($html, $filename): array
    {
        // Try dompdf first (most common)
        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return [
                'content' => $dompdf->output(),
                'filename' => $filename,
                'mime_type' => 'application/pdf',
            ];
        }

        // Try TCPDF
        if (class_exists('TCPDF')) {
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('ahgSpectrumPlugin');
            $pdf->SetTitle('Spectrum Report');
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            return [
                'content' => $pdf->Output('', 'S'),
                'filename' => $filename,
                'mime_type' => 'application/pdf',
            ];
        }

        // Try wkhtmltopdf via command line
        $wkhtmltopdf = '/usr/local/bin/wkhtmltopdf';
        if (!file_exists($wkhtmltopdf)) {
            $wkhtmltopdf = '/usr/bin/wkhtmltopdf';
        }

        if (file_exists($wkhtmltopdf)) {
            $tempHtml = tempnam(sys_get_temp_dir(), 'spectrum_') . '.html';
            $tempPdf = tempnam(sys_get_temp_dir(), 'spectrum_') . '.pdf';

            file_put_contents($tempHtml, $html);
            exec(sprintf(
                '%s --quiet %s %s 2>&1',
                escapeshellcmd($wkhtmltopdf),
                escapeshellarg($tempHtml),
                escapeshellarg($tempPdf)
            ), $output, $returnCode);

            if ($returnCode === 0 && file_exists($tempPdf)) {
                $content = file_get_contents($tempPdf);
                unlink($tempHtml);
                unlink($tempPdf);
                return [
                    'content' => $content,
                    'filename' => $filename,
                    'mime_type' => 'application/pdf',
                ];
            }

            @unlink($tempHtml);
            @unlink($tempPdf);
        }

        // Fallback: return HTML
        return [
            'content' => $html,
            'filename' => str_replace('.pdf', '.html', $filename),
            'mime_type' => 'text/html',
        ];
    }

    /**
     * Generate condition report PDF
     */
    public function generateConditionReportPDF(int $objectId): array
    {
        $object = $this->getInformationObjectData($objectId);
        $conditionChecks = $this->getConditionChecks($objectId);

        $html = $this->renderConditionReportTemplate($object, $conditionChecks);

        return $this->htmlToPDF($html, sprintf(
            'condition_report_%s.pdf',
            $object ? $object->identifier : $objectId
        ));
    }

    /**
     * Get condition checks for an object
     */
    protected function getConditionChecks(int $objectId): array
    {
        return DB::table('spectrum_event')
            ->where('object_id', $objectId)
            ->where('procedure_id', 'condition_check')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($row) {
                return (array) $row;
            })
            ->toArray();
    }

    /**
     * Render condition report template
     */
    protected function renderConditionReportTemplate($object, $conditionChecks): string
    {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Condition Report</title>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        .header { background: #2c3e50; color: #fff; padding: 20px; }
        .section { margin: 20px; }
        .section h2 { color: #2c3e50; border-bottom: 2px solid #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Condition Report</h1>
        <p><?php echo $object ? htmlspecialchars($object->identifier . ' - ' . ($object->title ?? '')) : 'Unknown Object'; ?></p>
    </div>

    <div class="section">
        <h2>Condition History</h2>
        <?php if (empty($conditionChecks)): ?>
            <p>No condition checks recorded.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
                <?php foreach ($conditionChecks as $check): ?>
                    <tr>
                        <td><?php echo $check['created_at']; ?></td>
                        <td><?php echo $check['status_to'] ?? '-'; ?></td>
                        <td><?php echo htmlspecialchars($check['notes'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <div class="section">
        <p><small>Generated: <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}