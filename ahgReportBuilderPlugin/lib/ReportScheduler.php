<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Report Scheduler Service.
 *
 * Handles running scheduled reports and sending email notifications.
 */
class ReportScheduler
{
    private string $culture;
    private string $archivePath;
    private ReportBuilderService $reportService;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
        $this->archivePath = sfConfig::get('sf_upload_dir') . '/reports';
        $this->reportService = new ReportBuilderService($culture);

        // Ensure archive directory exists
        if (!is_dir($this->archivePath)) {
            mkdir($this->archivePath, 0755, true);
        }
    }

    /**
     * Run all due scheduled reports.
     *
     * @return array Results of each scheduled report
     */
    public function runDueReports(): array
    {
        $results = [];
        $now = new DateTime();

        // Get all active schedules that are due
        $schedules = DB::table('report_schedule as s')
            ->join('custom_report as r', 's.custom_report_id', '=', 'r.id')
            ->select('s.*', 'r.name as report_name', 'r.data_source', 'r.columns', 'r.filters')
            ->where('s.is_active', 1)
            ->where('s.next_run', '<=', $now->format('Y-m-d H:i:s'))
            ->get();

        foreach ($schedules as $schedule) {
            try {
                $result = $this->runSchedule($schedule);
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'report_name' => $schedule->report_name,
                    'success' => true,
                    'file' => $result['file_path'],
                    'email_sent' => $result['email_sent'],
                ];

                echo "[" . date('Y-m-d H:i:s') . "] SUCCESS: {$schedule->report_name} - {$result['file_path']}\n";
            } catch (Exception $e) {
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'report_name' => $schedule->report_name,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                echo "[" . date('Y-m-d H:i:s') . "] ERROR: {$schedule->report_name} - {$e->getMessage()}\n";
            }
        }

        return $results;
    }

    /**
     * Run a single schedule.
     *
     * @param object $schedule The schedule record
     *
     * @return array Result with file_path and email_sent
     */
    public function runSchedule(object $schedule): array
    {
        $now = new DateTime();

        // Generate the report
        $reportData = $this->reportService->executeReport(
            $schedule->custom_report_id,
            [],
            1,
            50000 // Max rows for scheduled reports
        );

        // Get column definitions
        $allColumns = ColumnDiscovery::getColumns(
            DataSourceRegistry::getDataSourceKey($schedule->data_source) ?? $schedule->data_source
        );

        // Generate file based on format
        $filename = $this->generateFilename($schedule->report_name, $schedule->output_format);
        $filePath = $this->archivePath . '/' . $filename;

        switch ($schedule->output_format) {
            case 'csv':
                $this->generateCsv($filePath, $schedule, $reportData, $allColumns);
                break;
            case 'xlsx':
                $this->generateXlsx($filePath, $schedule, $reportData, $allColumns);
                break;
            case 'pdf':
            default:
                $this->generatePdf($filePath, $schedule, $reportData, $allColumns);
                break;
        }

        // Save to archive
        $archiveId = DB::table('report_archive')->insertGetId([
            'custom_report_id' => $schedule->custom_report_id,
            'schedule_id' => $schedule->id,
            'file_path' => $filename,
            'file_format' => $schedule->output_format,
            'file_size' => filesize($filePath),
            'generated_at' => $now->format('Y-m-d H:i:s'),
            'parameters' => json_encode(['rows' => $reportData['total']]),
        ]);

        // Send email if configured
        $emailSent = false;
        if (!empty($schedule->email_recipients)) {
            $emailSent = $this->sendEmail($schedule, $filePath, $reportData);
        }

        // Update schedule
        $nextRun = $this->calculateNextRun($schedule);
        DB::table('report_schedule')
            ->where('id', $schedule->id)
            ->update([
                'last_run' => $now->format('Y-m-d H:i:s'),
                'next_run' => $nextRun->format('Y-m-d H:i:s'),
            ]);

        return [
            'file_path' => $filename,
            'archive_id' => $archiveId,
            'email_sent' => $emailSent,
        ];
    }

    /**
     * Generate a unique filename for the report.
     */
    private function generateFilename(string $reportName, string $format): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $reportName);
        $safeName = preg_replace('/_+/', '_', $safeName);
        $safeName = trim($safeName, '_');

        return $safeName . '_' . date('Y-m-d_His') . '.' . $format;
    }

    /**
     * Generate CSV file.
     */
    private function generateCsv(string $filePath, object $schedule, array $reportData, array $allColumns): void
    {
        $columns = json_decode($schedule->columns, true) ?: [];
        $output = fopen($filePath, 'w');

        // Write BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Header row
        $headers = [];
        foreach ($columns as $col) {
            $headers[] = $allColumns[$col]['label'] ?? $col;
        }
        fputcsv($output, $headers);

        // Data rows
        foreach ($reportData['results'] as $row) {
            $rowData = [];
            foreach ($columns as $col) {
                $rowData[] = $row->{$col} ?? '';
            }
            fputcsv($output, $rowData);
        }

        fclose($output);
    }

    /**
     * Generate XLSX file.
     */
    private function generateXlsx(string $filePath, object $schedule, array $reportData, array $allColumns): void
    {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new RuntimeException('PhpSpreadsheet library not available');
        }

        $columns = json_decode($schedule->columns, true) ?: [];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($schedule->report_name, 0, 31));

        // Header row
        $col = 1;
        foreach ($columns as $column) {
            $sheet->setCellValueByColumnAndRow($col, 1, $allColumns[$column]['label'] ?? $column);
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

        // Data rows
        $row = 2;
        foreach ($reportData['results'] as $item) {
            $col = 1;
            foreach ($columns as $column) {
                $value = $item->{$column} ?? '';
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    /**
     * Generate PDF file.
     */
    private function generatePdf(string $filePath, object $schedule, array $reportData, array $allColumns): void
    {
        if (!class_exists('Dompdf\Dompdf')) {
            throw new RuntimeException('Dompdf library not available');
        }

        $columns = json_decode($schedule->columns, true) ?: [];
        $html = $this->generatePdfHtml($schedule->report_name, $columns, $reportData, $allColumns);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        file_put_contents($filePath, $dompdf->output());
    }

    /**
     * Generate HTML for PDF.
     */
    private function generatePdfHtml(string $reportName, array $columns, array $reportData, array $allColumns): string
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

        $html .= '<h1>' . htmlspecialchars($reportName) . '</h1>';
        $html .= '<div class="meta">Generated: ' . date('Y-m-d H:i:s') . ' | Total: ' . $reportData['total'] . ' records</div>';

        $html .= '<table><thead><tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . htmlspecialchars($allColumns[$col]['label'] ?? $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($reportData['results'] as $row) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $value = $row->{$col} ?? '';
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
     * Send email with report attachment.
     */
    private function sendEmail(object $schedule, string $filePath, array $reportData): bool
    {
        $recipients = array_map('trim', explode(',', $schedule->email_recipients));
        $recipients = array_filter($recipients);

        if (empty($recipients)) {
            return false;
        }

        // Get SMTP settings
        $smtpHost = sfConfig::get('app_smtp_host', 'localhost');
        $smtpPort = sfConfig::get('app_smtp_port', 25);
        $smtpUser = sfConfig::get('app_smtp_user', '');
        $smtpPass = sfConfig::get('app_smtp_password', '');
        $fromEmail = sfConfig::get('app_smtp_from_email', 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $fromName = sfConfig::get('app_smtp_from_name', 'AtoM Report Builder');

        // Build email
        $subject = "Scheduled Report: {$schedule->report_name}";
        $body = "Your scheduled report '{$schedule->report_name}' has been generated.\n\n";
        $body .= "Report Details:\n";
        $body .= "- Total Records: {$reportData['total']}\n";
        $body .= "- Format: " . strtoupper($schedule->output_format) . "\n";
        $body .= "- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $body .= "The report is attached to this email.\n";

        try {
            // Try using PHPMailer if available
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendWithPhpMailer($recipients, $subject, $body, $filePath, $smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromEmail, $fromName);
            }

            // Fallback to mail() function
            return $this->sendWithMail($recipients, $subject, $body, $filePath, $fromEmail, $fromName);
        } catch (Exception $e) {
            error_log("Failed to send scheduled report email: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Send email using PHPMailer.
     */
    private function sendWithPhpMailer(array $recipients, string $subject, string $body, string $filePath, string $smtpHost, int $smtpPort, string $smtpUser, string $smtpPass, string $fromEmail, string $fromName): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = $smtpPort;

        if (!empty($smtpUser)) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
        }

        $mail->setFrom($fromEmail, $fromName);

        foreach ($recipients as $email) {
            $mail->addAddress($email);
        }

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->addAttachment($filePath);

        return $mail->send();
    }

    /**
     * Send email using PHP mail() function (fallback).
     */
    private function sendWithMail(array $recipients, string $subject, string $body, string $filePath, string $fromEmail, string $fromName): bool
    {
        $filename = basename($filePath);
        $fileContent = file_get_contents($filePath);
        $fileEncoded = chunk_split(base64_encode($fileContent));
        $boundary = md5(uniqid(time()));

        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body . "\r\n\r\n";

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= $fileEncoded . "\r\n";
        $message .= "--{$boundary}--";

        return mail(implode(',', $recipients), $subject, $message, $headers);
    }

    /**
     * Calculate the next run time for a schedule.
     */
    private function calculateNextRun(object $schedule): DateTime
    {
        $now = new DateTime();
        $time = $schedule->time_of_day;

        switch ($schedule->frequency) {
            case 'daily':
                $next = new DateTime('tomorrow ' . $time);
                break;

            case 'weekly':
                $dayOfWeek = (int) $schedule->day_of_week;
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $next = new DateTime('next ' . $days[$dayOfWeek] . ' ' . $time);
                break;

            case 'monthly':
                $dayOfMonth = (int) $schedule->day_of_month;
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

    /**
     * Get the archive path.
     */
    public function getArchivePath(): string
    {
        return $this->archivePath;
    }
}
