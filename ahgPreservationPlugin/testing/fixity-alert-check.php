<?php

/**
 * Fixity alert check (#234). Offline: in-memory SQLite, a temp directory and a
 * stub mailer. Runs the real scheduled workflow over one good, one changed and
 * one missing file. No live database, no Symfony boot, no email.
 *
 * Run:  php ahgPreservationPlugin/testing/fixity-alert-check.php
 */

namespace AhgCore\Services {
    class EmailService
    {
        public static array $sent = [];

        public static function addresses(?string $list): array
        {
            return array_values(array_filter(preg_split('/[\s,;]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        }

        public static function send(string $to, string $subject, string $body): bool
        {
            self::$sent[] = compact('to', 'subject', 'body');

            return true;
        }
    }
}

namespace {
    use AhgCore\Services\EmailService;
    use Illuminate\Database\Capsule\Manager as DB;

    class sfConfig
    {
        public static array $values = ['app_siteBaseUrl' => 'https://site.example'];

        public static function get($key, $default = null)
        {
            return self::$values[$key] ?? $default;
        }
    }

    require '/usr/share/nginx/archive/atom-framework/vendor/autoload.php';

    require dirname(__DIR__).'/lib/PreservationService.php';

    $root = sys_get_temp_dir().'/fixity-alert-check-'.getmypid();
    mkdir($root.'/f', 0700, true);
    register_shutdown_function(function () use ($root) {
        array_map('unlink', glob($root.'/f/*'));
        rmdir($root.'/f');
        rmdir($root);
    });
    sfConfig::$values['sf_root_dir'] = $root;

    $db = new DB();
    $db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $db->setAsGlobal();
    foreach ([
        'CREATE TABLE digital_object (id INTEGER PRIMARY KEY, object_id INT, parent_id INT, path TEXT, name TEXT, usage_id INT)',
        'CREATE TABLE information_object (id INTEGER PRIMARY KEY)',
        'CREATE TABLE information_object_i18n (id INT, culture TEXT, title TEXT)',
        'CREATE TABLE slug (object_id INT, slug TEXT)',
        'CREATE TABLE preservation_checksum (id INTEGER PRIMARY KEY, digital_object_id INT, algorithm TEXT, checksum_value TEXT, generated_at TEXT, verified_at TEXT, verification_status TEXT)',
        'CREATE TABLE preservation_fixity_check (id INTEGER PRIMARY KEY, digital_object_id INT, checksum_id INT, algorithm TEXT, expected_value TEXT, actual_value TEXT, status TEXT, error_message TEXT, checked_at TEXT, checked_by TEXT, duration_ms INT, created_at TEXT)',
        'CREATE TABLE preservation_event (id INTEGER PRIMARY KEY, digital_object_id INT, information_object_id INT, event_type TEXT, event_datetime TEXT, event_detail TEXT, event_outcome TEXT, event_outcome_detail TEXT, linking_agent_type TEXT, linking_agent_value TEXT, created_at TEXT)',
        'CREATE TABLE preservation_workflow_schedule (id INTEGER PRIMARY KEY, name TEXT, workflow_type TEXT, is_enabled INT, cron_expression TEXT, batch_limit INT, options TEXT, last_run_at TEXT, last_run_status TEXT, last_run_processed INT, last_run_duration_ms INT, next_run_at TEXT, total_runs INT DEFAULT 0, total_processed INT DEFAULT 0, notify_on_failure INT, notify_email TEXT, updated_at TEXT)',
        'CREATE TABLE preservation_workflow_run (id INTEGER PRIMARY KEY, schedule_id INT, workflow_type TEXT, status TEXT, started_at TEXT, completed_at TEXT, duration_ms INT, objects_processed INT, objects_succeeded INT, objects_failed INT, objects_skipped INT, error_message TEXT, summary TEXT, triggered_by TEXT, triggered_by_user TEXT, created_at TEXT)',
    ] as $sql) {
        DB::statement($sql);
    }

    file_put_contents($root.'/f/good.tif', 'good');
    file_put_contents($root.'/f/changed.tif', 'tampered');
    $old = '2026-01-01 00:00:00';
    foreach ([1 => ['good.tif', 'good'], 2 => ['changed.tif', 'original'], 3 => ['gone.tif', 'gone']] as $id => [$name, $content]) {
        DB::table('digital_object')->insert(['id' => $id, 'object_id' => 100 + $id, 'path' => '/f/', 'name' => $name, 'usage_id' => 140]);
        DB::table('information_object')->insert(['id' => 100 + $id]);
        DB::table('information_object_i18n')->insert(['id' => 100 + $id, 'culture' => 'en', 'title' => "Record {$id}"]);
        DB::table('slug')->insert(['object_id' => 100 + $id, 'slug' => "record-{$id}"]);
        DB::table('preservation_checksum')->insert(['id' => $id, 'digital_object_id' => $id, 'algorithm' => 'sha256', 'checksum_value' => hash('sha256', $content), 'generated_at' => '2025-12-01 00:00:00', 'verified_at' => $old, 'verification_status' => 'valid']);
    }
    // Object 2 last verified good on a known date; object 3 never has.
    DB::table('preservation_fixity_check')->insert(['digital_object_id' => 2, 'checksum_id' => 2, 'algorithm' => 'sha256', 'expected_value' => 'x', 'status' => 'pass', 'checked_at' => '2026-03-04 05:06:07']);

    DB::table('preservation_workflow_schedule')->insert(['id' => 1, 'name' => 'Daily Fixity Check', 'workflow_type' => 'fixity_check', 'is_enabled' => 1, 'cron_expression' => '0 2 * * *', 'batch_limit' => 10, 'options' => '{"min_age_days": 7}', 'notify_on_failure' => 1, 'notify_email' => 'a@example.test, b@example.test']);

    $failed = 0;
    $check = function (string $label, bool $ok) use (&$failed) {
        echo($ok ? 'ok   ' : 'FAIL ').$label."\n";
        $failed += $ok ? 0 : 1;
    };

    $service = new PreservationService();
    $result = $service->executeWorkflow(1, 'scheduler');
    $body = EmailService::$sent[0]['body'] ?? '';

    $check('run completes as partial', 'partial' === $result['status']);
    $check('one digest per recipient', 2 === count(EmailService::$sent));
    $check('alert outcome recorded', 2 === ($result['results']['summary']['alert']['emailed'] ?? null));
    $check('names changed file', str_contains($body, 'File: changed.tif'));
    $check('names missing file', str_contains($body, 'File: gone.tif'));
    $check('good file not listed', !str_contains($body, 'good.tif'));
    $check('names record with link', str_contains($body, 'Record: Record 2 - https://site.example/index.php/record-2'));
    $check('last good date from history', str_contains($body, 'Last good checksum: 2026-03-04 05:06:07'));
    $check('never-verified file says so', str_contains($body, 'never re-verified (checksum recorded 2025-12-01'));
    $check('links filtered report', str_contains($body, 'https://site.example/index.php/admin/preservation/fixity-log?status=failures'));
    $check('report lists the two current failures', 2 === count($service->getFixityLog(100, 'failures')));
    $check('failure count matches', 2 === $service->countCurrentFixityFailures());

    // Restored file leaves the report.
    file_put_contents($root.'/f/changed.tif', 'original');
    sleep(1);
    $service->verifyFixity(2, 'sha256', 'test');
    $check('restored file drops off report', 1 === $service->countCurrentFixityFailures());

    // Respects the opt-out, and a clean run sends nothing.
    EmailService::$sent = [];
    $alerts = new FixityAlertService();
    $details = [3 => ['sha256' => ['status' => 'missing']]];
    $alerts->notify((object) ['name' => 'x', 'notify_on_failure' => 0, 'notify_email' => 'a@example.test'], ['details' => $details]);
    $alerts->notify((object) ['name' => 'x', 'notify_on_failure' => 1, 'notify_email' => 'a@example.test'], ['details' => []]);
    $check('no mail when opted out or clean', [] === EmailService::$sent);
    $none = @$alerts->notify((object) ['name' => 'x', 'notify_on_failure' => 1, 'notify_email' => ''], ['details' => $details]);
    $check('no recipient is reported, not fatal', str_contains((string) $none['note'], 'no notification email'));

    // Storage outage warning, and newline injection from a file name.
    $row = ['status' => 'missing', 'algorithm' => 'sha256', 'title' => null, 'slug' => null, 'last_good' => null, 'baseline' => null];
    [, $outage] = FixityAlertService::compose([$row + ['file' => "a\nBcc: x"], $row + ['file' => 'b']], 2, ['total' => 2, 'passed' => 0], 'x', '');
    $check('all-missing warns about storage', str_contains($outage, 'storage is mounted'));
    $check('control characters stripped', str_contains($outage, 'File: a Bcc: x'));

    echo $failed ? "{$failed} check(s) FAILED\n" : "All checks passed\n";

    exit($failed ? 1 : 0);
}
