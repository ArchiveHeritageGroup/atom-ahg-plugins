<?php
/**
 * Storage movement log check (atom-ahg-plugins#193).
 *
 * Builds a THROWAWAY database, loads the storage tables from
 * database/install.sql into it, then drives StorageMovementService and
 * StorageLocationService through first placement, moves, removal from storage,
 * a bulk move, a location move and the deletion rules. After each step it
 * checks that the append-only log and the current-location index agree: the
 * index must equal the latest movement per object, or browse and history are
 * telling the archive two different stories. The database is dropped at the end.
 *
 * Run:  php ahgStorageManagePlugin/testing/storage-movement-check.php /path/to/client.cnf
 * The .cnf is a MySQL [client] file with user= and password= for an account
 * that may CREATE and DROP a database. It never touches the AtoM database.
 */
// The framework autoloader, wherever this instance keeps it. Hardcoding one
// path means the check only runs on the machine it was written on.
$autoload = null;

foreach ([dirname(__DIR__, 3).'/atom-framework/vendor/autoload.php',
          dirname(__DIR__, 4).'/atom-framework/vendor/autoload.php',
          '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $candidate) {
    if (file_exists($candidate)) { $autoload = $candidate; break; }
}

if (null === $autoload) { fwrite(STDERR, "cannot find atom-framework/vendor/autoload.php\n"); exit(2); }
require $autoload;
require dirname(__DIR__).'/lib/Services/StorageLocationService.php';
require dirname(__DIR__).'/lib/Services/StorageMovementService.php';

use Illuminate\Database\Capsule\Manager as DB;

$scratch = 'scratch_storage_movement_check';
$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) { fwrite(STDERR, "usage: php {$argv[0]} /path/to/client.cnf\n"); exit(2); }

// The storage tables only. physical_object is stubbed below, because this check
// must not need an AtoM database to run.
$sql = file_get_contents(dirname(__DIR__).'/database/install.sql');
$sql = substr($sql, strpos($sql, 'CREATE TABLE IF NOT EXISTS ahg_storage_location ('));

$db = new DB();
$base = ['driver'=>'mysql','host'=>$ini['host'] ?? 'localhost','port'=>$ini['port'] ?? 3306,'username'=>$ini['user'],'password'=>$ini['password'],'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database'=>'mysql'], 'admin');
$db->addConnection($base + ['database'=>$scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}");
DB::connection('admin')->statement("CREATE DATABASE {$scratch} CHARACTER SET utf8mb4");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}"); });

// Stand-ins for the two base AtoM tables the storage tables point at.
DB::unprepared('CREATE TABLE physical_object (id INT NOT NULL PRIMARY KEY);');
DB::unprepared('CREATE TABLE physical_object_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, name VARCHAR(255), PRIMARY KEY (id, culture));');
// ahg_dropdown belongs to ahgCorePlugin; install.sql seeds the location types
// into it, so the scratch database needs the table to exist.
DB::unprepared('CREATE TABLE ahg_dropdown (id INT AUTO_INCREMENT PRIMARY KEY, taxonomy VARCHAR(100) NOT NULL, taxonomy_label VARCHAR(255) NOT NULL, code VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, UNIQUE KEY uk_taxonomy_code (taxonomy, code));');
DB::unprepared($sql);

foreach ([1 => 'Box 1', 2 => 'Box 2', 3 => 'Box 3'] as $id => $name) {
    DB::table('physical_object')->insert(['id' => $id]);
    DB::table('physical_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'name' => $name]);
}

$loc = new AhgStorageManage\Services\StorageLocationService();
$mv = new AhgStorageManage\Services\StorageMovementService();

$fail = 0;
function ok($c, $m) { global $fail; echo ($c ? "PASS " : "FAIL ").$m."\n"; if (!$c) { ++$fail; } }

/** The current-location index must equal the latest movement per object. */
function agrees(): bool
{
    $latest = [];

    foreach (DB::table('ahg_storage_movement')->where('subject_type', 'physical_object')->orderBy('id')->get()->all() as $row) {
        $latest[(int) $row->subject_id] = null === $row->to_location_id ? null : (int) $row->to_location_id;
    }

    $index = [];

    foreach (DB::table('ahg_physical_object_location')->get()->all() as $row) {
        $index[(int) $row->physical_object_id] = (int) $row->location_id;
    }

    ksort($latest);
    ksort($index);

    return array_filter($latest, static function ($v) { return null !== $v; }) === $index;
}

$roomA = $loc->createLocation(['name' => 'Room A', 'location_type' => 'room']);
$roomB = $loc->createLocation(['name' => 'Room B', 'location_type' => 'room']);
$shelf = $loc->createLocation(['name' => 'Shelf 1', 'location_type' => 'shelf', 'parent_id' => $roomA['id']]);

// First placement
$m1 = $mv->moveObject(1, (int) $shelf['id'], ['note' => 'accessioned']);
ok(null === $m1['from_location_id'] && (int) $m1['to_location_id'] === (int) $shelf['id'], 'first placement has a null from');
ok($mv->currentLocationOf(1) === (int) $shelf['id'], 'current location set');
ok(agrees(), 'index agrees with log after first placement');

// A move records where it came from, and the snapshots
$m2 = $mv->moveObject(1, (int) $roomB['id'], ['note' => 'reading room request']);
ok((int) $m2['from_location_id'] === (int) $shelf['id'] && 'Shelf 1' === $m2['from_location_name'], 'move records from, with a name snapshot');
ok('Box 1' === $m2['subject_name'], 'subject name snapshot');
ok(2 === count($mv->historyFor('physical_object', 1)), 'history has both moves');
ok(agrees(), 'index agrees with log after a move');

// Moving to where it already is is refused, rather than logged as a non-move
try { $mv->moveObject(1, (int) $roomB['id']); ok(false, 'no-op move refused'); }
catch (Exception $e) { ok(str_contains($e->getMessage(), 'already'), 'no-op move refused: '.$e->getMessage()); }

// Removal from storage
$m3 = $mv->moveObject(1, null, ['note' => 'sent for conservation']);
ok(null === $m3['to_location_id'], 'removal has a null to');
ok(null === $mv->currentLocationOf(1), 'index row cleared on removal');
ok(agrees(), 'index agrees with log after removal');

// Bulk move: one row per subject, one batch id
$batch = $mv->moveObjects([1, 2, 3], (int) $shelf['id'], ['note' => 'shelved together']);
ok(3 === count($batch), 'bulk move wrote one row per object');
$batchId = $batch[0]['batch_id'];
ok(null !== $batchId && 3 === count($mv->batch($batchId)), 'batch id groups the rows');
ok(1 === count(array_unique(array_column($batch, 'batch_id'))), 'one batch id for the whole move');
ok(3 === count($mv->objectsIn((int) $shelf['id'])), 'three objects in the shelf');
ok(agrees(), 'index agrees with log after a bulk move');

// Objects already in the destination are skipped, not logged as moves
$again = $mv->moveObjects([1, 2, 3], (int) $shelf['id']);
ok(0 === count($again), 'objects already there are skipped');
ok(agrees(), 'index still agrees');

// A location move is logged once, for the thing that moved
$before = DB::table('ahg_storage_movement')->count();
$loc->updateLocation((int) $shelf['id'], ['parent_id' => (int) $roomB['id']]);
$locMoves = $mv->historyFor('storage_location', (int) $shelf['id']);
ok(1 === count($locMoves), 'location move logged once');
ok((int) $locMoves[0]['from_location_id'] === (int) $roomA['id'] && (int) $locMoves[0]['to_location_id'] === (int) $roomB['id'], 'location move records both parents');
ok(DB::table('ahg_storage_movement')->count() === $before + 1, 'no per-object fan-out for a location move');
ok(3 === count($mv->objectsIn((int) $shelf['id'])), 'objects moved with the shelf, without extra rows');

// A rename is not a move
$before = DB::table('ahg_storage_movement')->count();
$loc->updateLocation((int) $shelf['id'], ['name' => 'Shelf One']);
ok(DB::table('ahg_storage_movement')->count() === $before, 'a rename logs nothing');

// History outlives a rename, because the row carries a snapshot
ok('Shelf 1' === $mv->historyFor('physical_object', 2)[0]['to_location_name'], 'history reads after the location was renamed');

// Deletion rules
try { $loc->deleteLocation((int) $shelf['id']); ok(false, 'delete refused while it holds objects'); }
catch (Exception $e) { ok(str_contains($e->getMessage(), 'physical objects'), 'delete refused while it holds objects'); }

$mv->moveObjects([1, 2, 3], (int) $roomA['id']);
try { $loc->deleteLocation((int) $shelf['id']); ok(false, 'delete refused while it has history'); }
catch (Exception $e) { ok(str_contains($e->getMessage(), 'movement log'), 'delete refused while it has history: keeps the record'); }

// An unused location still deletes
$spare = $loc->createLocation(['name' => 'Spare', 'location_type' => 'room']);
ok($loc->deleteLocation((int) $spare['id']), 'an unused location still deletes');

// Nothing in the log was ever updated: ids are contiguous and append-only
$rows = DB::table('ahg_storage_movement')->orderBy('id')->get()->all();
ok(count($rows) === DB::table('ahg_storage_movement')->count(), 'log rows all present');
ok(agrees(), 'index agrees with log at the end');

echo $fail ? "\n{$fail} FAILED\n" : "\nALL PASS\n";
exit($fail ? 1 : 0);
