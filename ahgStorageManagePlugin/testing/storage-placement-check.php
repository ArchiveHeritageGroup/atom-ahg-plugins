<?php

/**
 * Storage placement check: the physical storage form as the way into the
 * location tree (StoragePlacementService).
 *
 * Builds a THROWAWAY database as storage-movement-check.php does, then checks
 * that the form's Building ... Shelf levels find existing places without regard
 * to case, create missing ones only when allowed, skip empty levels, leave the
 * box alone when nothing changed, and log a move when something did. The
 * database is dropped at the end.
 *
 * Run:  php ahgStorageManagePlugin/testing/storage-placement-check.php /path/to/client.cnf
 */
// The framework autoloader, wherever this instance keeps it. Hardcoding one
// path means the check only runs on the machine it was written on.
$autoload = null;

foreach ([dirname(__DIR__, 3) . '/atom-framework/vendor/autoload.php',
          dirname(__DIR__, 4) . '/atom-framework/vendor/autoload.php',
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
require dirname(__DIR__) . '/lib/Services/StorageLocationService.php';
require dirname(__DIR__) . '/lib/Services/StorageMovementService.php';

use Illuminate\Database\Capsule\Manager as DB;

$scratch = 'scratch_storage_placement_check';
$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) {
    fwrite(STDERR, "usage: php {$argv[0]} /path/to/client.cnf\n");
    exit(2);
}

// The storage tables only. physical_object is stubbed below, because this check
// must not need an AtoM database to run.
$sql = file_get_contents(dirname(__DIR__) . '/database/install.sql');
$sql = substr($sql, strpos($sql, 'CREATE TABLE IF NOT EXISTS ahg_storage_location ('));

$db = new DB();
$base = ['driver' => 'mysql', 'host' => $ini['host'] ?? 'localhost', 'port' => $ini['port'] ?? 3306, 'username' => $ini['user'], 'password' => $ini['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database' => 'mysql'], 'admin');
$db->addConnection($base + ['database' => $scratch]);
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
require_once dirname(__DIR__) . '/lib/Services/StoragePlacementService.php';
$pl = new AhgStorageManage\Services\StoragePlacementService();

$fail = 0;
function ok($c, $m)
{
    global $fail;
    echo ($c ? 'PASS ' : 'FAIL ') . $m . "\n";
    if (!$c) {
        ++$fail;
    }
}
$count = function () { return DB::table('ahg_storage_location')->count(); };
$moves = function () { return DB::table('ahg_storage_movement')->count(); };

$b = $loc->createLocation(['name' => 'Main Building', 'location_type' => 'building']);
$r = $loc->createLocation(['name' => 'Room 3', 'location_type' => 'room', 'parent_id' => $b['id']]);

ok(AhgStorageManage\Services\StoragePlacementService::available(), 'tree reported available');
ok($pl->resolve(['building' => ' main building ', 'room' => 'ROOM 3'], false) === (int) $r['id'], 'existing places found regardless of case and spaces, floor skipped');
ok(false === $pl->resolve(['building' => 'Main Building', 'room' => 'Room 4'], false), 'missing place refused when creating is not allowed');
ok(2 === $count(), '...and nothing was created');
ok(null === $pl->resolve(['building' => '', 'shelf' => ' '], true), 'all levels empty resolves to nothing');

ok('moved' === $pl->saveFromForm(1, ['building' => 'Main Building', 'room' => 'Room 3', 'shelf' => 'Shelf A'], true), 'box placed, new shelf created');
$shelf = DB::table('ahg_storage_location')->where('name', 'Shelf A')->first();
ok($shelf && (int) $shelf->parent_id === (int) $r['id'] && 'shelf' === $shelf->location_type, 'new shelf sits under Room 3');
ok($mv->currentLocationOf(1) === (int) $shelf->id && 1 === $moves(), 'box is on the shelf, one move logged');
ok(['building' => 'Main Building', 'room' => 'Room 3', 'shelf' => 'Shelf A'] === $pl->levelsFor(1), 'levels read back from the tree for the form');

ok('unchanged' === $pl->saveFromForm(1, ['building' => 'main building', 'room' => 'room 3', 'shelf' => 'shelf a'], true), 'resave with the same places is no move');
ok(1 === $moves() && 3 === $count(), '...no move logged, no duplicate place');

$c = $loc->createLocation(['name' => 'Box C1', 'location_type' => 'container', 'parent_id' => $shelf->id]);
$mv->moveObject(1, (int) $c['id']);
ok('unchanged' === $pl->saveFromForm(1, ['building' => 'Main Building', 'room' => 'Room 3', 'shelf' => 'Shelf A'], true), 'box in a container below its shelf is not pulled up to the shelf');
ok($mv->currentLocationOf(1) === (int) $c['id'], '...it stays in the container');

ok('not_allowed' === $pl->saveFromForm(2, ['building' => 'Main Building', 'room' => 'Room 9'], false), 'non-editor cannot create Room 9');
ok(null === $mv->currentLocationOf(2), '...and box 2 stays unplaced');

ok('moved' === $pl->saveFromForm(2, ['building' => 'Main Building', 'room' => 'Room 3'], false), 'non-editor may still choose existing places');
ok($mv->currentLocationOf(2) === (int) $r['id'], '...box 2 is in Room 3');

echo $fail ? "\n{$fail} failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);
