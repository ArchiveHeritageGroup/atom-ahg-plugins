<?php

/**
 * Preservation triage check (PreservationTriageService).
 *
 * Builds a THROWAWAY database from the real CREATE TABLE statements in
 * ahgPreservationPlugin and ahgConditionPlugin install.sql, stubs the base AtoM
 * tables it reads, seeds a small holding with known problems and checks the
 * scores, reasons, ranking, filters, headline counts, forecast and CSV. The
 * database is dropped at the end. Nothing touches the instance database.
 *
 * Run:  php ahgPreservationPlugin/testing/preservation-triage-check.php /path/to/client.cnf
 */
$autoload = null;
foreach ([dirname(__DIR__, 3).'/atom-framework/vendor/autoload.php',
          dirname(__DIR__, 4).'/atom-framework/vendor/autoload.php',
          '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $candidate) {
    if (file_exists($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if (null === $autoload) {
    fwrite(STDERR, "cannot find atom-framework/vendor/autoload.php\n");
    exit(2);
}
require $autoload;
require dirname(__DIR__).'/lib/Services/PreservationTriageService.php';

use Illuminate\Database\Capsule\Manager as DB;

$scratch = 'scratch_preservation_triage_check';
$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) {
    fwrite(STDERR, "usage: php {$argv[0]} /path/to/client.cnf\n");
    exit(2);
}

$db = new DB();
$base = ['driver' => 'mysql', 'host' => $ini['host'] ?? 'localhost', 'port' => $ini['port'] ?? 3306, 'username' => $ini['user'], 'password' => $ini['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database' => 'mysql'], 'admin');
$db->addConnection($base + ['database' => $scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}");
DB::connection('admin')->statement("CREATE DATABASE {$scratch} CHARACTER SET utf8mb4");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}"); });

// The plugin tables, exactly as install.sql defines them.
function createFrom(string $file, array $tables): void
{
    $sql = file_get_contents($file);
    foreach ($tables as $t) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `?'.$t.'`?\s*\(.*?\)[^;]*;/s', $sql, $m)) {
            throw new RuntimeException("no CREATE TABLE for {$t} in {$file}");
        }
        DB::unprepared($m[0]);
    }
}
DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
$plugins = dirname(__DIR__, 2);
createFrom($plugins.'/ahgConditionPlugin/database/install.sql', ['condition_report', 'condition_damage', 'spectrum_condition_check', 'condition_assessment_schedule']);
createFrom(dirname(__DIR__).'/database/install.sql', ['preservation_checksum', 'preservation_fixity_check', 'preservation_format', 'preservation_object_format', 'preservation_format_obsolescence', 'preservation_virus_scan', 'preservation_event']);

// Stand-ins for the base AtoM tables read.
DB::unprepared('CREATE TABLE information_object (id INT PRIMARY KEY, identifier VARCHAR(1024), repository_id INT, parent_id INT, lft INT, rgt INT, source_culture VARCHAR(16) NOT NULL DEFAULT "en")');
DB::unprepared('CREATE TABLE information_object_i18n (id INT, culture VARCHAR(16), title VARCHAR(1024), PRIMARY KEY (id, culture))');
DB::unprepared('CREATE TABLE slug (object_id INT PRIMARY KEY, slug VARCHAR(255))');
DB::unprepared('CREATE TABLE digital_object (id INT PRIMARY KEY, object_id INT, parent_id INT)');
DB::unprepared('CREATE TABLE repository (id INT PRIMARY KEY)');
DB::unprepared('CREATE TABLE actor (id INT PRIMARY KEY, source_culture VARCHAR(16) NOT NULL DEFAULT "en")');
DB::unprepared('CREATE TABLE actor_i18n (id INT, culture VARCHAR(16), authorized_form_of_name VARCHAR(1024), PRIMARY KEY (id, culture))');

// A holding: root 1; collection 10 (repo 100) with 11-14; collection 20 (repo 200) with 21; 30 bare.
$ios = [
    [1, null, null, 1, 18, 'Root'],
    [10, 100, 1, 2, 11, 'Collection A'], [11, null, 10, 3, 4, 'Poor map'], [12, null, 10, 5, 6, 'Corrupt scan'],
    [13, null, 10, 7, 8, '=cmd|WordPerfect letter'], [14, null, 10, 9, 10, 'Critical bible'],
    [20, 200, 1, 12, 15, 'Collection B'], [21, null, 20, 13, 14, 'Infected audio'],
    [30, null, 1, 16, 17, 'Bare description'],
];
foreach ($ios as [$id, $repo, $parent, $l, $r, $title]) {
    DB::table('information_object')->insert(['id' => $id, 'identifier' => 'ID-'.$id, 'repository_id' => $repo, 'parent_id' => $parent, 'lft' => $l, 'rgt' => $r]);
    DB::table('information_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'title' => $title]);
    DB::table('slug')->insert(['object_id' => $id, 'slug' => 'rec-'.$id]);
}
foreach ([100 => 'Repo A', 200 => 'Repo B'] as $id => $name) {
    DB::table('repository')->insert(['id' => $id]);
    DB::table('actor')->insert(['id' => $id]);
    DB::table('actor_i18n')->insert(['id' => $id, 'culture' => 'en', 'authorized_form_of_name' => $name]);
}

// 11: poor, high, assessed 2019, two severe damage entries and one minor; a later pending Spectrum check is ignored.
$cr = DB::table('condition_report')->insertGetId(['information_object_id' => 11, 'assessment_date' => '2019-03-01', 'overall_rating' => 'poor', 'priority' => 'high']);
foreach (['severe', 'Severe', 'minor'] as $sev) {
    DB::table('condition_damage')->insert(['condition_report_id' => $cr, 'damage_type' => 'tear', 'severity' => $sev, 'is_active' => 1]);
}
DB::table('spectrum_condition_check')->insert(['object_id' => 11, 'check_date' => '2026-05-01', 'checked_by' => 'x', 'overall_condition' => 'pending', 'workflow_state' => 'scheduled']);

// 12: master + derivative; 3 failed checks on the derivative, a pass on the master; schedule overdue.
DB::table('digital_object')->insert([['id' => 1, 'object_id' => 12, 'parent_id' => null], ['id' => 2, 'object_id' => null, 'parent_id' => 1]]);
DB::table('preservation_checksum')->insert(['digital_object_id' => 1, 'algorithm' => 'sha256', 'checksum_value' => 'a', 'file_size' => 1, 'generated_at' => '2025-01-01']);
foreach (['fail', 'fail', 'missing'] as $st) {
    DB::table('preservation_fixity_check')->insert(['digital_object_id' => 2, 'algorithm' => 'sha256', 'expected_value' => 'a', 'actual_value' => 'a', 'status' => $st, 'checked_at' => '2025-06-01']);
}
DB::table('preservation_fixity_check')->insert(['digital_object_id' => 1, 'algorithm' => 'sha256', 'expected_value' => 'a', 'actual_value' => 'a', 'status' => 'pass', 'checked_at' => '2026-01-01']);
DB::table('condition_assessment_schedule')->insert(['object_id' => 12, 'next_due_date' => '2026-01-01', 'is_active' => 1]);

// 13: WordPerfect, registry says high, obsolescence says critical; no checksum.
DB::table('digital_object')->insert(['id' => 3, 'object_id' => 13, 'parent_id' => null]);
$fmt = DB::table('preservation_format')->insertGetId(['puid' => 'x-fmt/44', 'mime_type' => 'application/vnd.wordperfect', 'format_name' => 'WordPerfect', 'risk_level' => 'high']);
DB::table('preservation_object_format')->insert(['digital_object_id' => 3, 'format_id' => $fmt, 'puid' => 'x-fmt/44', 'format_name' => 'WordPerfect', 'identification_tool' => 'siegfried', 'identification_date' => '2026-01-01']);
DB::table('preservation_format_obsolescence')->insert(['format_id' => $fmt, 'puid' => 'x-fmt/44', 'current_risk_level' => 'critical']);

// 14: completed Spectrum check 2022-01-15, critical + urgent, next check 2025 (overdue).
DB::table('spectrum_condition_check')->insert(['object_id' => 14, 'check_date' => '2022-01-15', 'checked_by' => 'x', 'overall_condition' => 'critical', 'condition_rating' => 'critical', 'treatment_priority' => 'urgent', 'next_check_date' => '2025-01-01', 'workflow_state' => 'completed']);

// 21: two files, both checksummed, never fixity-checked; file 4 infected then cleaned, file 5 infected now.
DB::table('digital_object')->insert([['id' => 4, 'object_id' => 21, 'parent_id' => null], ['id' => 5, 'object_id' => 21, 'parent_id' => null]]);
foreach ([4, 5] as $d) {
    DB::table('preservation_checksum')->insert(['digital_object_id' => $d, 'algorithm' => 'sha256', 'checksum_value' => 'b', 'file_size' => 1, 'generated_at' => '2025-01-01']);
}
DB::table('preservation_virus_scan')->insert([
    ['digital_object_id' => 4, 'scan_engine' => 'clamav', 'file_path' => '/x', 'status' => 'infected', 'scanned_at' => '2025-01-01'],
    ['digital_object_id' => 4, 'scan_engine' => 'clamav', 'file_path' => '/x', 'status' => 'clean', 'scanned_at' => '2026-01-01'],
    ['digital_object_id' => 5, 'scan_engine' => 'clamav', 'file_path' => '/x', 'status' => 'infected', 'scanned_at' => '2026-02-01'],
]);

$fail = 0;
function ok($c, $m)
{
    global $fail;
    echo ($c ? 'ok   ' : 'FAIL ').$m."\n";
    $fail += $c ? 0 : 1;
}

$svc = new PreservationTriageService(5, '2026-10-08');
$all = $svc->build();
$by = array_column($all['rows'], null, 'id');
$has = fn ($id, $needle) => (bool) array_filter($by[$id]['reasons'] ?? [], fn ($r) => false !== strpos($r, $needle));

ok(array_column($all['rows'], 'id') === [12, 14, 11, 21, 13], 'ranking 12, 14, 11, 21, 13 (got '.implode(',', array_column($all['rows'], 'id')).')');
ok(70 === $by[12]['score'] && $has(12, '3 failed fixity checks') && $has(12, 'assessment overdue'), '12: 3 failed fixity (capped 60) + overdue schedule = 70');
ok(65 === $by[14]['score'] && $has(14, 'condition critical, assessed 2022') && $has(14, 'urgent'), '14: critical + urgent + overdue = 65');
ok(60 === $by[11]['score'] && $has(11, 'condition poor, assessed 2019') && $has(11, '2 active severe damage') && $has(11, 'last assessed 2019'), '11: poor + high + 2 severe damage + stale; pending check ignored = 60');
ok(55 === $by[21]['score'] && $has(21, 'threat') && $has(21, 'fixity never verified'), '21: latest scan per file decides (one infected) + never verified = 55');
ok(30 === $by[13]['score'] && $has(13, 'format at risk (critical): WordPerfect') && $has(13, 'no checksum'), '13: obsolescence raises WordPerfect to critical + no checksum = 30');
ok(!isset($by[30]) && 5 === $all['counts']['in_scope'], 'bare description is not in scope; 5 records in scope');
$cnt = $all['counts'];
ok([2, 2, 1, 1, 1, 1, 1, 1] === [$cnt['condition_poor'], $cnt['overdue'], $cnt['stale_assessment'], $cnt['fixity_failed'], $cnt['format_at_risk'], $cnt['infected'], $cnt['no_checksum'], $cnt['never_verified']], 'headline counts');
ok('Repo A' === $by[11]['repository'] && 'Collection A' === $by[11]['collection'], 'repository and collection inherited from the top-level record');
ok(1 === count($all['forecast']) && 14 === $all['forecast'][0]['id'] && '2027-01-15' === $all['forecast'][0]['due'] && 1 === $all['forecastByMonth']['2027-01'], 'forecast: 14 passes 5 years on 2027-01-15');

$b = $svc->build(['repository_id' => 200]);
ok([21] === array_column($b['rows'], 'id'), 'repository filter');
$a = $svc->build(['collection_id' => 10]);
ok([12, 14, 11, 13] === array_column($a['rows'], 'id'), 'collection filter');

$ten = (new PreservationTriageService(10, '2026-10-08'))->build();
ok(!array_filter((array_column($ten['rows'], null, 'id')[11]['reasons'] ?? []), fn ($r) => false !== strpos($r, 'last assessed')), 'threshold 10 years: 2019 assessment no longer stale');

$csv = PreservationTriageService::toCsv($all['rows'], fn ($s) => 'https://x/index.php/'.$s);
$lines = array_map('str_getcsv', array_filter(explode("\n", $csv)));
ok('Rank' === $lines[0][0] && '1' === $lines[1][0] && '70' === $lines[1][1], 'CSV header and first row');
ok(false !== strpos($csv, "'=cmd|WordPerfect letter") && false !== strpos($csv, 'https://x/index.php/rec-12'), 'CSV neutralises formula titles and links records');

// Missing table: drop one source and the build still runs, reporting it.
DB::unprepared('DROP TABLE preservation_virus_scan');
$m = (new PreservationTriageService(5, '2026-10-08'))->build();
ok(null === $m['sources']['preservation_virus_scan']['count'] && 0 === $m['counts']['infected'], 'missing table reported as not installed, rest still scored');

echo $fail ? "\n{$fail} FAILED\n" : "\nall passed\n";
exit($fail ? 1 : 0);
