<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Tells the preservation contacts what a scheduled fixity sweep found.
 *
 * One digest per run, sent to the schedule's notify_email list. The in-app side
 * is the "Fixity failures" menu entry in ahgPreservationPluginConfiguration.
 */
class FixityAlertService
{
    public const MAX_LISTED = 50;
    public const REPORT_PATH = '/index.php/admin/preservation/fixity-log?status=failures';

    private const PROBLEMS = [
        'missing' => 'File missing from storage',
        'fail' => 'Checksum changed',
        'error' => 'File could not be read',
    ];

    /**
     * Never throws: a failed alert must not turn a completed sweep into a failed run.
     *
     * @return array{failures: int, emailed: int, note: ?string}
     */
    public function notify(object $schedule, array $batchResult): array
    {
        $failures = self::failuresFrom($batchResult['details'] ?? []);
        $outcome = ['failures' => count($failures), 'emailed' => 0, 'note' => null];

        if (empty($failures) || empty($schedule->notify_on_failure)) {
            return $outcome;
        }

        try {
            if (!class_exists('AhgCore\Services\EmailService')) {
                $outcome['note'] = 'email service unavailable';
            } elseif (empty($recipients = \AhgCore\Services\EmailService::addresses($schedule->notify_email ?? ''))) {
                $outcome['note'] = 'no notification email configured on this schedule';
            } else {
                [$subject, $body] = self::compose(
                    $this->enrich(array_slice($failures, 0, self::MAX_LISTED)),
                    count($failures),
                    $batchResult,
                    (string) $schedule->name,
                    $this->baseUrl()
                );

                foreach ($recipients as $recipient) {
                    if (\AhgCore\Services\EmailService::send($recipient, $subject, $body)) {
                        ++$outcome['emailed'];
                    }
                }

                if ($outcome['emailed'] < count($recipients)) {
                    $outcome['note'] = 'email not sent to every recipient - check SMTP settings';
                }
            }
        } catch (\Throwable $e) {
            $outcome['note'] = 'alert failed: '.$e->getMessage();
        }

        if (null !== $outcome['note']) {
            error_log('FixityAlertService: '.$outcome['note']);
        }

        return $outcome;
    }

    /**
     * The failed checks in a batch result's details, one row per file and algorithm.
     * Objects whose check threw carry no algorithm rows; the digest counts those.
     */
    public static function failuresFrom(array $details): array
    {
        $failures = [];
        foreach ($details as $objectId => $results) {
            foreach ((array) $results as $algorithm => $result) {
                if (is_array($result) && isset(self::PROBLEMS[$result['status'] ?? ''])) {
                    $failures[] = ['object_id' => (int) $objectId, 'algorithm' => $algorithm, 'status' => $result['status']];
                }
            }
        }

        return $failures;
    }

    /**
     * Adds file name, record and the last date each checksum verified as good.
     */
    private function enrich(array $failures): array
    {
        $ids = array_values(array_unique(array_column($failures, 'object_id')));

        $objects = DB::table('digital_object as do')
            ->leftJoin('digital_object as parent', 'do.parent_id', '=', 'parent.id')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('i18n.id', '=', DB::raw('COALESCE(do.object_id, parent.object_id)'))
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'slug.object_id', '=', DB::raw('COALESCE(do.object_id, parent.object_id)'))
            ->whereIn('do.id', $ids)
            ->select('do.id', 'do.name', 'i18n.title', 'slug.slug')
            ->get()
            ->keyBy('id');

        $lastGood = [];
        foreach (DB::table('preservation_fixity_check')
            ->whereIn('digital_object_id', $ids)
            ->where('status', 'pass')
            ->groupBy('digital_object_id', 'algorithm')
            ->selectRaw('digital_object_id, algorithm, MAX(checked_at) as last_good')
            ->get() as $row) {
            $lastGood[$row->digital_object_id.'/'.$row->algorithm] = $row->last_good;
        }

        $baseline = [];
        foreach (DB::table('preservation_checksum')->whereIn('digital_object_id', $ids)->get() as $row) {
            $baseline[$row->digital_object_id.'/'.$row->algorithm] = $row->generated_at;
        }

        foreach ($failures as &$failure) {
            $key = $failure['object_id'].'/'.$failure['algorithm'];
            $object = $objects[$failure['object_id']] ?? null;
            $failure['file'] = $object->name ?? 'Unknown file';
            $failure['title'] = $object->title ?? null;
            $failure['slug'] = $object->slug ?? null;
            $failure['last_good'] = $lastGood[$key] ?? null;
            $failure['baseline'] = $baseline[$key] ?? null;
        }

        return $failures;
    }

    /**
     * @return array{0: string, 1: string} subject and plain-text body
     */
    public static function compose(array $rows, int $total, array $batchResult, string $scheduleName, string $baseUrl): array
    {
        $clean = static fn ($text) => trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $text));

        $lines = [
            'The scheduled fixity check "'.$clean($scheduleName).'" found '.$total.' failed file check(s).',
            'Checked: '.(int) ($batchResult['total'] ?? 0).' - passed: '.(int) ($batchResult['passed'] ?? 0),
            'Failures report: '.$baseUrl.self::REPORT_PATH,
            '',
        ];

        // A storage mount going away looks exactly like every file being deleted.
        $checked = (int) ($batchResult['total'] ?? 0);
        if ($checked > 1 && $total >= $checked && 0 === count(array_filter($rows, fn ($r) => 'missing' !== $r['status']))) {
            $lines[] = 'Every file checked is missing. Confirm the storage is mounted before treating this as data loss.';
            $lines[] = '';
        }

        foreach ($rows as $row) {
            $record = $clean($row['title'] ?? '') ?: 'No linked record';
            if (!empty($row['slug'])) {
                $record .= ' - '.$baseUrl.'/index.php/'.rawurlencode($row['slug']);
            }

            if (!empty($row['last_good'])) {
                $good = $row['last_good'];
            } elseif (!empty($row['baseline'])) {
                $good = 'never re-verified (checksum recorded '.$row['baseline'].')';
            } else {
                $good = 'unknown';
            }

            $lines[] = 'File: '.$clean($row['file']);
            $lines[] = 'Record: '.$record;
            $lines[] = 'Problem: '.self::PROBLEMS[$row['status']].' ('.strtoupper($row['algorithm']).')';
            $lines[] = 'Last good checksum: '.$good;
            $lines[] = '';
        }

        if ($total > count($rows)) {
            $lines[] = 'And '.($total - count($rows)).' more - see the failures report.';
        }
        if (!empty($batchResult['errors'])) {
            $lines[] = (int) $batchResult['errors'].' further object(s) could not be checked at all.';
        }

        return ["Fixity alert: {$total} file check(s) failed", implode("\n", $lines)];
    }

    private function baseUrl(): string
    {
        $url = class_exists('AhgCore\Core\AhgConfig')
            ? \AhgCore\Core\AhgConfig::getSiteBaseUrl()
            : (string) \sfConfig::get('app_siteBaseUrl', '');

        // AhgConfig's last resort is localhost, which is a dead link in an email.
        return 'http://localhost' === $url ? '' : rtrim($url, '/');
    }
}
