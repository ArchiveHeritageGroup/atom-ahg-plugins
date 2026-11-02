<?php
/**
 * Check for IngestService::cleanupWorkFiles() (catalog #319).
 * Runs against a throwaway folder and an in-memory SQLite database; touches
 * no site files and no site database.
 *
 *   php testing/ingest-cleanup-check.php
 */
require '/usr/share/nginx/archive/atom-framework/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

$root = sys_get_temp_dir().'/ingest-cleanup-check-'.getmypid();
@mkdir($root.'/var', 0700, true);

class sfConfig
{
    public static $root;
    public static function get($k, $d = null) { return 'sf_root_dir' === $k ? self::$root : $d; }
}
sfConfig::$root = $root;

$db = new DB();
$db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
$db->setAsGlobal();
DB::statement('CREATE TABLE ingest_job (id INTEGER PRIMARY KEY, session_id INTEGER, status TEXT)');
DB::statement('CREATE TABLE ahg_settings (id INTEGER PRIMARY KEY, setting_key TEXT, setting_value TEXT, setting_group TEXT)');
DB::table('ahg_settings')->insert(['setting_key' => 'ingest_work_dir', 'setting_value' => $root.'/work', 'setting_group' => 'ingest']);
DB::table('ingest_job')->insert([
    ['id' => 1, 'session_id' => 10, 'status' => 'completed'],
    ['id' => 2, 'session_id' => 20, 'status' => 'running'],
]);

require __DIR__.'/../ahgIngestPlugin/lib/Services/IngestService.php';
use AhgIngestPlugin\Services\IngestService;

// ingest_work_dir (set above) points the work folder at the temp folder.
$base = $root.'/work';
$old = time() - 40 * 86400;
$mk = function ($path, $mtime) { @mkdir(dirname($path), 0700, true); file_put_contents($path, 'x'); touch($path, $mtime); };

$mk("$base/10/old.csv", $old);              // finished session, old -> delete
$mk("$base/20/old.csv", $old);              // running job -> keep
$mk("$base/30/old.csv", $old);              // old but has a fresh file -> keep
$mk("$base/30/new.csv", time());
$mk("$base/job_1.log", $old);               // old log, finished -> delete
$mk("$base/job_2.log", $old);               // running job's log -> keep
$mk("$base/manifests/m_old.csv", $old);     // old manifest -> delete
$mk("$base/manifests/m_new.csv", time());   // new manifest -> keep
$mk("$base/aip/aip_10_x.zip", $old);        // package -> never touched
$mk("$base/sharepoint/f.txt", $old);        // not a session folder -> keep
touch("$base/10", $old);


$fail = 0;
$check = function ($ok, $what) use (&$fail) { echo ($ok ? 'PASS  ' : 'FAIL  ').$what."\n"; $fail += $ok ? 0 : 1; };

$dry = IngestService::cleanupWorkFiles(30, true);
$check(1 === $dry['sessions'] && 1 === $dry['logs'] && 1 === $dry['manifests'], 'dry run counts 1 session, 1 log, 1 manifest');
$check(file_exists("$base/10/old.csv"), 'dry run deletes nothing');

$r = IngestService::cleanupWorkFiles(30, false);
$check(!file_exists("$base/10"), 'old finished session folder deleted');
$check(file_exists("$base/20/old.csv"), 'session with a running job kept');
$check(file_exists("$base/30/new.csv") && file_exists("$base/30/old.csv"), 'session with a recent file kept whole');
$check(!file_exists("$base/job_1.log"), 'old finished job log deleted');
$check(file_exists("$base/job_2.log"), 'running job log kept');
$check(!file_exists("$base/manifests/m_old.csv") && file_exists("$base/manifests/m_new.csv"), 'only the old manifest deleted');
$check(file_exists("$base/aip/aip_10_x.zip"), 'AIP package untouched');
$check(file_exists("$base/sharepoint/f.txt"), 'non-session folder untouched');

$r2 = IngestService::cleanupWorkFiles(60, false);
$check(0 === $r2['sessions'] + $r2['logs'] + $r2['manifests'], 'nothing newer than the period is deleted');

exec('rm -rf '.escapeshellarg($root));
echo $fail ? "$fail FAILED\n" : "All checks passed\n";
exit($fail ? 1 : 0);
