<?php

use Illuminate\Database\Capsule\Manager as DB;

class IntegrityScheduler
{
    public function getDueSchedules(): array
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('integrity_schedule')
            ->where('is_enabled', 1)
            ->where(function ($q) use ($now) {
                $q->whereNotNull('next_run_at')
                  ->where('next_run_at', '<=', $now);
            })
            ->orderBy('next_run_at')
            ->get()
            ->values()
            ->all();
    }

    public function runDueSchedules(): array
    {
        $results = [];

        require_once dirname(__FILE__) . '/IntegrityService.php';
        $service = new \IntegrityService();

        $dueSchedules = $this->getDueSchedules();

        foreach ($dueSchedules as $schedule) {
            try {
                $result = $service->executeBatchVerification($schedule->id, 'scheduler');
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'schedule_name' => $schedule->name,
                    'success' => true,
                    'result' => $result,
                ];

                // Compute and set next run
                $nextRun = $this->computeNextRun($schedule->frequency, $schedule->cron_expression);
                DB::table('integrity_schedule')
                    ->where('id', $schedule->id)
                    ->update(['next_run_at' => $nextRun]);

            } catch (\Exception $e) {
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'schedule_name' => $schedule->name,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function computeNextRun(string $frequency, ?string $cronExpression = null): string
    {
        $now = new \DateTime();

        if ($cronExpression) {
            return $this->nextFromCron($cronExpression, $now);
        }

        switch ($frequency) {
            case 'daily':
                $now->modify('+1 day');
                $now->setTime(2, 0, 0); // 02:00
                break;

            case 'weekly':
                $now->modify('next Monday');
                $now->setTime(2, 0, 0);
                break;

            case 'monthly':
                $now->modify('first day of next month');
                $now->setTime(2, 0, 0);
                break;

            case 'ad_hoc':
            default:
                // No automatic next run
                return '';
        }

        return $now->format('Y-m-d H:i:s');
    }

    protected function nextFromCron(string $cron, \DateTime $from): string
    {
        // Simple cron parser for common patterns: "M H D Mo DoW"
        $parts = preg_split('/\s+/', trim($cron));
        if (count($parts) !== 5) {
            // Invalid cron — fall back to +1 day
            $from->modify('+1 day');

            return $from->format('Y-m-d H:i:s');
        }

        $minute = $parts[0] === '*' ? 0 : (int) $parts[0];
        $hour = $parts[1] === '*' ? 2 : (int) $parts[1];

        // For basic daily/weekly/monthly patterns
        if ($parts[2] === '*' && $parts[3] === '*' && $parts[4] === '*') {
            // Daily at H:M
            $next = clone $from;
            $next->setTime($hour, $minute, 0);
            if ($next <= $from) {
                $next->modify('+1 day');
            }

            return $next->format('Y-m-d H:i:s');
        }

        if ($parts[4] !== '*' && $parts[2] === '*') {
            // Weekly on specific day
            $dayMap = ['0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday',
                '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday'];
            $dayName = $dayMap[$parts[4]] ?? 'Monday';
            $next = clone $from;
            $next->modify("next {$dayName}");
            $next->setTime($hour, $minute, 0);

            return $next->format('Y-m-d H:i:s');
        }

        if ($parts[2] !== '*') {
            // Monthly on specific day
            $day = (int) $parts[2];
            $next = clone $from;
            $next->modify('first day of next month');
            $next->setDate((int) $next->format('Y'), (int) $next->format('m'), min($day, (int) $next->format('t')));
            $next->setTime($hour, $minute, 0);

            return $next->format('Y-m-d H:i:s');
        }

        // Fallback
        $from->modify('+1 day');

        return $from->format('Y-m-d H:i:s');
    }
}
