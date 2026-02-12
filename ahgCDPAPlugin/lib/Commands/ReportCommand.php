<?php

namespace AtomFramework\Console\Commands\Cdpa;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate CDPA compliance reports for POTRAZ submission.
 */
class ReportCommand extends BaseCommand
{
    protected string $name = 'cdpa:report';
    protected string $description = 'Generate CDPA reports';
    protected string $detailedDescription = <<<'EOF'
    Generate CDPA compliance reports for POTRAZ submission.

    Report types:
      summary    - Overall compliance status
      requests   - Data subject requests log
      breaches   - Breach incident register
      processing - Processing activities register

    Examples:
      php bin/atom cdpa:report --type=summary
      php bin/atom cdpa:report --type=requests --format=csv --output=requests.csv
      php bin/atom cdpa:report --type=breaches --format=json
    EOF;

    protected function configure(): void
    {
        $this->addOption('format', null, 'Output format (text, csv, json)', 'text');
        $this->addOption('type', 't', 'Report type (summary, requests, breaches, processing)', 'summary');
        $this->addOption('output', 'o', 'Output file path');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgCDPAPlugin/lib/Services/CDPAService.php';
        if (!file_exists($serviceFile)) {
            $this->error("CDPAService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgCDPAPlugin\Services\CDPAService();
        $format = $this->option('format', 'text');
        $type = $this->option('type', 'summary');
        $outputPath = $this->option('output');

        switch ($type) {
            case 'summary':
                $data = $this->generateSummaryReport($service);

                break;

            case 'requests':
                $data = $this->generateRequestsReport();

                break;

            case 'breaches':
                $data = $this->generateBreachesReport();

                break;

            case 'processing':
                $data = $this->generateProcessingReport();

                break;

            default:
                $this->error("Unknown report type: {$type}");

                return 1;
        }

        $output = $this->formatOutput($data, $format);

        if ($outputPath) {
            file_put_contents($outputPath, $output);
            $this->success("Report saved to: {$outputPath}");
        } else {
            $this->line($output);
        }

        return 0;
    }

    private function generateSummaryReport($service): array
    {
        $stats = $service->getDashboardStats();
        $compliance = $service->getComplianceStatus();

        return [
            'report_type' => 'CDPA Compliance Summary',
            'generated_at' => date('Y-m-d H:i:s'),
            'compliance_status' => $compliance['status'],
            'issues' => $compliance['issues'],
            'warnings' => $compliance['warnings'],
            'license' => $stats['license'] ? [
                'number' => $stats['license']->license_number,
                'tier' => $stats['license']->tier,
                'expiry' => $stats['license']->expiry_date,
                'status' => $stats['license_status'],
            ] : null,
            'dpo' => $stats['dpo'] ? [
                'name' => $stats['dpo']->name,
                'appointed' => $stats['dpo']->appointment_date,
                'form_dp2' => $stats['dpo']->form_dp2_submitted ? 'Submitted' : 'Not Submitted',
            ] : null,
            'statistics' => [
                'pending_requests' => $stats['requests']['pending'],
                'overdue_requests' => $stats['requests']['overdue'],
                'open_breaches' => $stats['breaches']['open'],
                'processing_activities' => $stats['processing_activities'],
                'active_consents' => $stats['consent']['active'],
            ],
        ];
    }

    private function generateRequestsReport(): array
    {
        $requests = DB::table('cdpa_data_subject_request')
            ->orderBy('request_date', 'desc')
            ->get();

        return [
            'report_type' => 'Data Subject Requests Register',
            'generated_at' => date('Y-m-d H:i:s'),
            'total_count' => $requests->count(),
            'requests' => $requests->map(function ($r) {
                return [
                    'reference' => $r->reference_number,
                    'type' => $r->request_type,
                    'data_subject' => $r->data_subject_name,
                    'request_date' => $r->request_date,
                    'due_date' => $r->due_date,
                    'status' => $r->status,
                    'completed_date' => $r->completed_date,
                ];
            })->toArray(),
        ];
    }

    private function generateBreachesReport(): array
    {
        $breaches = DB::table('cdpa_breach')
            ->orderBy('incident_date', 'desc')
            ->get();

        return [
            'report_type' => 'Data Breach Register',
            'generated_at' => date('Y-m-d H:i:s'),
            'total_count' => $breaches->count(),
            'breaches' => $breaches->map(function ($b) {
                return [
                    'reference' => $b->reference_number,
                    'incident_date' => $b->incident_date,
                    'type' => $b->breach_type,
                    'severity' => $b->severity,
                    'records_affected' => $b->records_affected,
                    'potraz_notified' => $b->potraz_notified ? 'Yes' : 'No',
                    'subjects_notified' => $b->subjects_notified ? 'Yes' : 'No',
                    'status' => $b->status,
                ];
            })->toArray(),
        ];
    }

    private function generateProcessingReport(): array
    {
        $activities = DB::table('cdpa_processing_activity')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return [
            'report_type' => 'Processing Activities Register',
            'generated_at' => date('Y-m-d H:i:s'),
            'total_count' => $activities->count(),
            'activities' => $activities->map(function ($a) {
                return [
                    'name' => $a->name,
                    'category' => $a->category,
                    'purpose' => $a->purpose,
                    'legal_basis' => $a->legal_basis,
                    'storage_location' => $a->storage_location,
                    'retention_period' => $a->retention_period,
                    'cross_border' => $a->cross_border ? 'Yes' : 'No',
                    'children_data' => $a->children_data ? 'Yes' : 'No',
                    'biometric_data' => $a->biometric_data ? 'Yes' : 'No',
                ];
            })->toArray(),
        ];
    }

    private function formatOutput(array $data, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            case 'csv':
                return $this->arrayToCsv($data);

            default:
                return $this->arrayToText($data);
        }
    }

    private function arrayToCsv(array $data): string
    {
        $lines = [];

        // Get items array (requests, breaches, or activities)
        $items = $data['requests'] ?? $data['breaches'] ?? $data['activities'] ?? [];

        if (empty($items)) {
            return "No data available\n";
        }

        // Header
        $lines[] = implode(',', array_keys($items[0]));

        // Data rows
        foreach ($items as $item) {
            $values = array_map(function ($v) {
                if (is_array($v)) {
                    $v = json_encode($v);
                }

                return '"' . str_replace('"', '""', $v ?? '') . '"';
            }, array_values($item));
            $lines[] = implode(',', $values);
        }

        return implode("\n", $lines);
    }

    private function arrayToText(array $data, int $indent = 0): string
    {
        $output = '';
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $output .= "{$prefix}{$key}:\n";
                $output .= $this->arrayToText($value, $indent + 1);
            } else {
                $output .= "{$prefix}{$key}: {$value}\n";
            }
        }

        return $output;
    }
}
