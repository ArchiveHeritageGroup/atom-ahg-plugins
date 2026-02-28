<?php

use Illuminate\Database\Capsule\Manager as DB;

class IntegrityAlertService
{
    // ------------------------------------------------------------------
    // Threshold evaluation (Issue #190)
    // ------------------------------------------------------------------

    public function checkThresholds(array $runResult): void
    {
        $configs = DB::table('integrity_alert_config')
            ->where('is_enabled', 1)
            ->get();

        if ($configs->isEmpty()) {
            return;
        }

        foreach ($configs as $config) {
            try {
                $triggered = $this->evaluateAlert($config, $runResult);
                if ($triggered) {
                    $this->sendAlert($config, $runResult);
                    DB::table('integrity_alert_config')
                        ->where('id', $config->id)
                        ->update(['last_triggered_at' => date('Y-m-d H:i:s')]);
                }
            } catch (\Exception $e) {
                // Alert failures are non-fatal
            }
        }
    }

    protected function evaluateAlert(object $config, array $runResult): bool
    {
        $threshold = (float) $config->threshold_value;
        $value = null;

        switch ($config->alert_type) {
            case 'pass_rate_below':
                $counters = $runResult['counters'] ?? [];
                $scanned = $counters['objects_scanned'] ?? 0;
                $passed = $counters['objects_passed'] ?? 0;
                $value = $scanned > 0 ? round(($passed / $scanned) * 100, 1) : 100;
                break;

            case 'failure_count_above':
                $counters = $runResult['counters'] ?? [];
                $value = ($counters['objects_failed'] ?? 0)
                    + ($counters['objects_missing'] ?? 0)
                    + ($counters['objects_error'] ?? 0);
                break;

            case 'dead_letter_count_above':
                $value = DB::table('integrity_dead_letter')
                    ->whereIn('status', ['open', 'acknowledged', 'investigating'])
                    ->count();
                break;

            case 'backlog_above':
                require_once dirname(__FILE__) . '/IntegrityService.php';
                $service = new \IntegrityService();
                $value = $service->calculateBacklog();
                break;

            case 'run_failure':
                // Triggers on any non-completed run status
                $status = $runResult['status'] ?? '';

                return in_array($status, ['failed', 'timeout', 'partial']);

            default:
                return false;
        }

        if ($value === null) {
            return false;
        }

        return $this->compare($value, $config->comparison, $threshold);
    }

    protected function compare(float $value, string $comparison, float $threshold): bool
    {
        switch ($comparison) {
            case 'lt':
                return $value < $threshold;
            case 'lte':
                return $value <= $threshold;
            case 'gt':
                return $value > $threshold;
            case 'gte':
                return $value >= $threshold;
            case 'eq':
                return abs($value - $threshold) < 0.001;
            default:
                return false;
        }
    }

    // ------------------------------------------------------------------
    // Alert dispatch
    // ------------------------------------------------------------------

    public function sendAlert(object $config, array $context): void
    {
        $subject = $this->buildSubject($config, $context);
        $body = $this->buildBody($config, $context);

        if (!empty($config->email)) {
            $this->sendEmailAlert($config->email, $subject, $body);
        }

        if (!empty($config->webhook_url)) {
            $payload = [
                'alert_type' => $config->alert_type,
                'threshold_value' => $config->threshold_value,
                'comparison' => $config->comparison,
                'run_id' => $context['run_id'] ?? null,
                'status' => $context['status'] ?? null,
                'counters' => $context['counters'] ?? [],
                'hostname' => gethostname(),
                'timestamp' => date('c'),
            ];
            $this->sendWebhookAlert($config->webhook_url, $payload, $config->webhook_secret);
        }
    }

    protected function buildSubject(object $config, array $context): string
    {
        $host = gethostname() ?: 'server';
        $type = str_replace('_', ' ', $config->alert_type);

        return "[Integrity Alert] {$type} on {$host}";
    }

    protected function buildBody(object $config, array $context): string
    {
        $host = gethostname() ?: 'server';
        $runId = $context['run_id'] ?? 'N/A';
        $status = $context['status'] ?? 'N/A';
        $counters = $context['counters'] ?? [];
        $type = str_replace('_', ' ', $config->alert_type);
        $threshold = $config->threshold_value;
        $now = date('Y-m-d H:i:s');

        $lines = [
            "Integrity Alert: {$type}",
            "Threshold: {$config->comparison} {$threshold}",
            "Host: {$host}",
            "Time: {$now}",
            "",
            "Run ID: {$runId}",
            "Status: {$status}",
        ];

        if (!empty($counters)) {
            $lines[] = "Scanned: " . ($counters['objects_scanned'] ?? 0);
            $lines[] = "Passed: " . ($counters['objects_passed'] ?? 0);
            $lines[] = "Failed: " . ($counters['objects_failed'] ?? 0);
            $lines[] = "Missing: " . ($counters['objects_missing'] ?? 0);
            $lines[] = "Errors: " . ($counters['objects_error'] ?? 0);
        }

        return implode("\n", $lines);
    }

    public function sendEmailAlert(string $email, string $subject, string $body): void
    {
        try {
            $mailer = \sfContext::getInstance()->getMailer();
            $message = $mailer->compose(null, $email, $subject, $body);
            $mailer->send($message);
        } catch (\Exception $e) {
            // Email sending failure is non-fatal
        }
    }

    public function sendWebhookAlert(string $url, array $payload, ?string $secret = null): void
    {
        $json = json_encode($payload);
        $headers = [
            'Content-Type: application/json',
            'User-Agent: AtoM-Integrity/1.1',
        ];

        if ($secret) {
            $signature = hash_hmac('sha256', $json, $secret);
            $headers[] = 'X-Signature: sha256=' . $signature;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        @file_get_contents($url, false, $context);
    }

    // ------------------------------------------------------------------
    // Schedule notification (existing fields)
    // ------------------------------------------------------------------

    public function sendScheduleNotification(object $schedule, array $runResult): void
    {
        if (empty($schedule->notify_email)) {
            return;
        }

        $status = $runResult['status'] ?? '';
        $counters = $runResult['counters'] ?? [];
        $hasFailed = ($counters['objects_failed'] ?? 0) > 0
            || ($counters['objects_missing'] ?? 0) > 0;
        $hasMismatch = ($counters['objects_failed'] ?? 0) > 0;
        $isFailure = in_array($status, ['failed', 'timeout', 'partial']);

        $shouldNotify = false;
        if ($schedule->notify_on_failure && $isFailure) {
            $shouldNotify = true;
        }
        if ($schedule->notify_on_mismatch && $hasMismatch) {
            $shouldNotify = true;
        }

        if (!$shouldNotify) {
            return;
        }

        $host = gethostname() ?: 'server';
        $subject = "[Integrity] Schedule '{$schedule->name}' - {$status} on {$host}";
        $body = "Schedule: {$schedule->name}\n"
            . "Status: {$status}\n"
            . "Run ID: " . ($runResult['run_id'] ?? 'N/A') . "\n"
            . "Duration: " . ($runResult['duration_seconds'] ?? 'N/A') . "s\n\n"
            . "Scanned: " . ($counters['objects_scanned'] ?? 0) . "\n"
            . "Passed: " . ($counters['objects_passed'] ?? 0) . "\n"
            . "Failed: " . ($counters['objects_failed'] ?? 0) . "\n"
            . "Missing: " . ($counters['objects_missing'] ?? 0) . "\n"
            . "Errors: " . ($counters['objects_error'] ?? 0) . "\n";

        if ($runResult['error'] ?? null) {
            $body .= "\nError: {$runResult['error']}\n";
        }

        $this->sendEmailAlert($schedule->notify_email, $subject, $body);
    }

    // ------------------------------------------------------------------
    // Alert config CRUD
    // ------------------------------------------------------------------

    public function listAlertConfigs(): array
    {
        return DB::table('integrity_alert_config')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();
    }

    public function createAlertConfig(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('integrity_alert_config')->insertGetId(array_merge([
            'alert_type' => 'pass_rate_below',
            'threshold_value' => 95,
            'comparison' => 'lt',
            'is_enabled' => 1,
            'email' => null,
            'webhook_url' => null,
            'webhook_secret' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $data));
    }

    public function updateAlertConfig(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('integrity_alert_config')->where('id', $id)->update($data) > 0;
    }

    public function deleteAlertConfig(int $id): bool
    {
        return DB::table('integrity_alert_config')->where('id', $id)->delete() > 0;
    }
}
