<?php

/**
 * Storage contents check (atom-ahg-plugins#193): the managed type list, the
 * capacity roll-up, containers inside containers, placing objects, and the
 * migration of the flat location fields into the tree.
 *
 * Builds a THROWAWAY database, loads the storage tables from
 * database/install.sql into it with stand-ins for the base AtoM tables, drives
 * the three services, and drops the database at the end.
 *
 * Run:  php ahgStorageManagePlugin/testing/storage-contents-check.php /path/to/client.cnf
 * The .cnf is a MySQL [client] file with user= and password= (and optionally
 * host= and port=) for an account that may CREATE and DROP a database. It never
 * touches the AtoM database.
 */
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
require dirname(__DIR__) . '/lib/Services/StorageFlatMigrationService.php';

use AhgStorageManage\Services\StorageFlatMigrationService;
use AhgStorageManage\Services\StorageLocationService;
use AhgStorageManage\Services\StorageMovementService;
use Illuminate\Database\Capsule\Manager as DB;

$scratch = 'scratch_storage_contents_check';
$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) {
    fwrite(STDERR, "usage: php {$argv[0]} /path/to/client.cnf\n");
    exit(2);
}

$install = file_get_contents(dirname(__DIR__) . '/database/install.sql');
$storage = substr($install, strpos($install, 'CREATE TABLE IF NOT EXISTS ahg_storage_location ('));
// The flat tables too, as install.sql makes them, since the migration reads them.
$flat = substr($install, strpos($install, 'CREATE TABLE IF NOT EXISTS `physical_object_extended`'));
$flat = substr($flat, 0, strpos($flat, 'CREATE TABLE IF NOT EXISTS ahg_storage_location ('));

$db = new DB();
$base = ['driver' => 'mysql', 'host' => $ini['host'] ?? 'localhost', 'port' => $ini['port'] ?? 3306, 'username' => $ini['user'], 'password' => $ini['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database' => 'mysql'], 'admin');
$db->addConnection($base + ['database' => $scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}");
DB::connection('admin')->statement("CREATE DATABASE {$scratch} CHARACTER SET utf8mb4");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}"); });

DB::unprepared('CREATE TABLE physical_object (id INT NOT NULL PRIMARY KEY);');
DB::unprepared('CREATE TABLE physical_object_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, name VARCHAR(255), location VARCHAR(1024), PRIMARY KEY (id, culture));');
DB::unprepared('CREATE TABLE ahg_dropdown (id INT AUTO_INCREMENT PRIMARY KEY, taxonomy VARCHAR(100) NOT NULL, taxonomy_label VARCHAR(255) NOT NULL, code VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, UNIQUE KEY uk_taxonomy_code (taxonomy, code));');
DB::unprepared($flat);
DB::unprepared($storage);

$fail = 0;
function ok($c, $m)
{
    global $fail;
    echo ($c ? 'PASS ' : 'FAIL ') . $m . "\n";
    if (!$c) {
        ++$fail;
    }
}
function refused(callable $do, string $needle, string $m)
{
    try {
        $do();
        ok(false, $m . ' (was allowed)');
    } catch (Exception $e) {
        ok(str_contains($e->getMessage(), $needle), $m . ': ' . $e->getMessage());
    }
}
function box(int $id, string $name, ?string $location = null, array $extended = [])
{
    DB::table('physical_object')->insert(['id' => $id]);
    DB::table('physical_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'name' => $name, 'location' => $location]);
    if ($extended) {
        DB::table('physical_object_extended')->insert(['physical_object_id' => $id] + $extended);
    }
}
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

// ── The type list is the managed one ────────────────────────────────────────
$loc = new StorageLocationService();
ok(9 === count($loc->types()) && 'Building' === $loc->types()['building'], 'install.sql seeds nine types, read with their labels');
ok(array_keys($loc->types())[0] === 'building' && array_keys($loc->types())[8] === 'storage_unit', 'types come in the order set');

DB::table('ahg_dropdown')->insert(['taxonomy' => 'storage_location_type', 'taxonomy_label' => 'Storage Location Type', 'code' => 'cabinet', 'label' => 'Plan cabinet', 'sort_order' => 75]);
$loc = new StorageLocationService();
$cabinet = $loc->createLocation(['name' => 'Cabinet 1', 'location_type' => 'cabinet']);
ok('cabinet' === $cabinet['location_type'], 'a type the archive added is accepted');
ok('Plan cabinet' === $loc->typeLabel('cabinet'), 'and shown by its label');
refused(function () use ($loc) { $loc->createLocation(['name' => 'X', 'location_type' => 'cupboard']); }, 'Invalid location type', 'a type not on the list is refused');

DB::table('ahg_dropdown')->where('taxonomy', 'storage_location_type')->where('code', 'cabinet')->update(['is_active' => 0]);
$loc = new StorageLocationService();
ok(!isset($loc->types()['cabinet']), 'a retired type is no longer offered');
refused(function () use ($loc) { $loc->createLocation(['name' => 'Cabinet 2', 'location_type' => 'cabinet']); }, 'Invalid location type', 'nor accepted on a new location');
$kept = $loc->updateLocation((int) $cabinet['id'], ['name' => 'Cabinet One', 'location_type' => 'cabinet']);
ok('cabinet' === $kept['location_type'] && 'Cabinet One' === $kept['name'], 'a location that already has it keeps it through an edit');
ok('Cabinet' === $loc->typeLabel('cabinet'), 'and it still reads as something');
$loc->deleteLocation((int) $cabinet['id']);

DB::table('ahg_dropdown')->where('taxonomy', 'storage_location_type')->update(['is_active' => 0]);
ok(StorageLocationService::TYPES === array_keys((new StorageLocationService())->types()), 'an empty list falls back to the shipped types');
DB::table('ahg_dropdown')->where('taxonomy', 'storage_location_type')->where('code', '!=', 'cabinet')->update(['is_active' => 1]);
DB::unprepared($storage);
ok(10 === DB::table('ahg_dropdown')->where('taxonomy', 'storage_location_type')->count(), 're-running install.sql adds no duplicate types');

// ── Containers inside containers, and the roll-up ───────────────────────────
$loc = new StorageLocationService();
$mv = new StorageMovementService();
foreach ([1 => 'Box 1', 2 => 'Box 2', 3 => 'Box 3', 4 => 'Box 4'] as $id => $name) {
    box($id, $name);
}

$room = $loc->createLocation(['name' => 'Strongroom B', 'location_type' => 'room', 'capacity_value' => 200, 'capacity_unit' => 'boxes']);
$bay = $loc->createLocation(['name' => 'Bay 1', 'location_type' => 'bay', 'parent_id' => $room['id'], 'capacity_value' => 12.5, 'capacity_unit' => 'linear_meters']);
$bay2 = $loc->createLocation(['name' => 'Bay 2', 'location_type' => 'bay', 'parent_id' => $room['id'], 'capacity_value' => 7.5, 'capacity_unit' => 'linear_meters']);
$pallet = $loc->createLocation(['name' => 'Pallet 7', 'location_type' => 'storage_unit', 'parent_id' => $bay['id'], 'capacity_value' => 40, 'capacity_unit' => 'boxes']);
$carton = $loc->createLocation(['name' => 'Carton A', 'location_type' => 'container', 'parent_id' => $pallet['id']]);

$unplaced = $mv->unplacedObjects();
ok(4 === $unplaced['total'] && 4 === count($unplaced['rows']), 'every object starts without a place');
ok(1 === $mv->unplacedObjects('Box 3')['total'], 'and can be found by name');
ok(0 === $mv->unplacedObjects('50%')['total'], 'a percent sign in the search is a percent sign');
ok(2 === count($mv->unplacedObjects('', 2)['rows']) && 4 === $mv->unplacedObjects('', 2)['total'], 'the list is capped and the total is not');

$mv->moveObjects([1, 2], (int) $carton['id'], ['note' => 'packed']);   // boxes in a carton
$mv->moveObject(3, (int) $room['id']);                                   // loose in the room
ok(1 === $mv->unplacedObjects()['total'], 'placed objects leave the unplaced list');

$under = $mv->objectsUnder((int) $room['id']);
ok(3 === count($under), 'the room holds three objects, counting what is in the carton on the pallet');
ok('Box 3' === $under[0]['name'] && 0 === (int) $under[0]['depth'], 'what is held directly comes first');
ok('Carton A' === $under[1]['location_name'] && 3 === (int) $under[1]['depth'], 'each object says which location it is actually in');
ok(2 === count($mv->objectsUnder((int) $pallet['id'])), 'the pallet holds the two boxes in its carton');
ok(0 === count($mv->objectsUnder((int) $bay2['id'])), 'an empty branch holds nothing');

$r = $loc->capacityRollup((int) $room['id']);
ok(200.0 === $r['own']['value'] && 'boxes' === $r['own']['unit'], 'own capacity is reported as declared');
ok(['boxes' => 40.0, 'linear_meters' => 20.0] === $r['beneath'], 'capacity beneath is summed per unit, never across units');
ok(3 === $r['declared_beneath'], 'and says how many locations declared one');
ok(1 === $r['objects_here'] && 2 === $r['objects_beneath'] && 3 === $r['objects_total'], 'objects are counted here, beneath and in all');
$r = $loc->capacityRollup((int) $carton['id']);
ok(null === $r['own'] && [] === $r['beneath'] && 2 === $r['objects_here'] && 0 === $r['objects_beneath'], 'a leaf with no capacity reports none, and its own objects');

// Moving the pallet moves what is on it: one log row, and the roll-up follows
$before = DB::table('ahg_storage_movement')->count();
$loc->updateLocation((int) $pallet['id'], ['parent_id' => (int) $bay2['id']]);
ok(DB::table('ahg_storage_movement')->count() === $before + 1, 'moving a pallet is one movement, not one per box');
ok(2 === count($mv->objectsUnder((int) $bay2['id'])) && 0 === count($mv->objectsUnder((int) $bay['id'])), 'the boxes went with the pallet');
ok(['boxes' => 40.0] === $loc->capacityRollup((int) $bay2['id'])['beneath'], 'and the capacity with it');
refused(function () use ($loc) { $loc->capacityRollup(999999); }, 'not found', 'a roll-up for no location is refused');
ok(agrees(), 'index agrees with log');

// ── The flat fields, carried into the tree ──────────────────────────────────
DB::unprepared('SET FOREIGN_KEY_CHECKS = 0');
foreach (['ahg_storage_movement', 'ahg_physical_object_location', 'ahg_storage_location_closure', 'ahg_storage_location', 'physical_object_extended', 'physical_object_i18n', 'physical_object'] as $table) {
    DB::table($table)->delete();
}
DB::unprepared('SET FOREIGN_KEY_CHECKS = 1');

box(10, 'Box 10', null, ['building' => 'Main Building', 'floor' => '1', 'room' => 'C3', 'shelf' => 'S4']);
box(11, 'Box 11', 'ignored when there are fields', ['building' => ' main building ', 'floor' => '1', 'room' => 'c3']);   // same room, typed differently
box(12, 'Box 12', null, ['building' => 'Main Building', 'floor' => '2']);
box(13, 'Box 13', 'Annex');                                  // free text only
box(14, 'Box 14');                                           // nothing recorded
box(15, 'Box 15', null, ['building' => '', 'floor' => '  ']); // blank fields are not fields
box(16, 'Box 16');                                           // in a strongroom
box(17, 'Box 17', null, ['building' => 'Main Building']);    // fields and a strongroom

DB::table('ahg_strongroom')->insert(['id' => 1, 'slug' => 'vault', 'name' => 'Vault', 'location_description' => 'Basement', 'capacity_value' => 300, 'capacity_unit' => 'boxes']);
DB::table('ahg_strongroom')->insert(['id' => 2, 'slug' => 'empty-room', 'name' => 'Empty Room']);
DB::table('ahg_physical_object_storage')->insert(['physical_object_id' => 16, 'strongroom_id' => 1]);
DB::table('ahg_physical_object_storage')->insert(['physical_object_id' => 17, 'strongroom_id' => 1]);

$mig = new StorageFlatMigrationService();
$plan = $mig->run(false);
ok(0 === DB::table('ahg_storage_location')->count() && 0 === DB::table('ahg_storage_movement')->count() && 0 === DB::table('ahg_physical_object_location')->count(), 'a dry run writes nothing');
ok(5 === count($plan['placed']), 'the dry run would place five objects');
ok(['Vault (room)', 'Empty Room (room)', 'Main Building (building)', 'Main Building > Floor 1 (floor)', 'Main Building > Floor 1 > Room C3 (room)', 'Main Building > Floor 1 > Room C3 > Shelf S4 (shelf)', 'Main Building > Floor 2 (floor)'] === $plan['locations_created'], 'and make seven locations, each once: two strongrooms, a building, two floors, a room and a shelf');
ok(3 === count($plan['skipped']), 'three are left alone');
$reasons = array_column($plan['skipped'], 'reason', 'object_id');
ok(str_contains($reasons[13], 'free text only: "Annex"'), 'free text is reported, not guessed at');
ok('no location recorded' === $reasons[14] && 'no location recorded' === $reasons[15], 'blank fields count as nothing recorded');

$done = $mig->run(true);
ok($done['placed'] == $plan['placed'] && $done['locations_created'] == $plan['locations_created'] && $done['skipped'] == $plan['skipped'], 'applying does exactly what the dry run said');
ok(7 === DB::table('ahg_storage_location')->count(), 'seven locations exist');
ok(1 === DB::table('ahg_storage_location')->where('location_type', 'building')->count(), '"Main Building" typed three ways is one building');
ok(1 === DB::table('ahg_storage_location')->where('location_type', 'room')->where('name', 'Room C3')->count(), '"C3" and "c3" are one room, named Room C3');

$path = static function (int $objectId) use ($loc, $mv) { return implode(' > ', array_column($loc->getLocationPath((int) $mv->currentLocationOf($objectId)), 'name')); };
ok('Main Building > Floor 1 > Room C3 > Shelf S4' === $path(10), 'an object lands in its innermost location');
ok('Main Building > Floor 1 > Room C3' === $path(11), 'and one with fewer fields stops higher up');
ok('Main Building > Floor 2' === $path(12), 'a second floor is its own branch');
ok('Vault' === $path(16), 'a strongroom assignment becomes a room');
ok('Main Building' === $path(17), 'structured fields win over a strongroom');
ok(str_contains(array_column($done['placed'], 'source', 'object_id')[17], 'also assigned to strongroom "Vault"'), 'and the report says so');
ok(null === $mv->currentLocationOf(13) && null === $mv->currentLocationOf(14), 'what was skipped is not placed');

$vault = DB::table('ahg_storage_location')->where('name', 'Vault')->first();
ok('room' === $vault->location_type && 300.0 === (float) $vault->capacity_value && 'Basement' === $vault->description, 'the strongroom brings its description and capacity');
ok(1 === DB::table('ahg_storage_location')->where('name', 'Empty Room')->count(), 'an empty strongroom still becomes a room');

$first = $mv->historyFor('physical_object', 10)[0];
ok(null === $first['from_location_id'] && str_contains((string) $first['note'], 'Migrated from the flat location fields') && StorageFlatMigrationService::ACTOR === $first['username'], 'each placement is a first placement in the log, marked as a migration');
ok(agrees(), 'index agrees with log after the migration');
$closure = DB::table('ahg_storage_location_closure')->orderBy('ancestor')->orderBy('descendant')->get()->all();
$loc->rebuildClosure();
ok(7 === DB::table('ahg_storage_location_closure')->where('depth', 0)->count() && $closure == DB::table('ahg_storage_location_closure')->orderBy('ancestor')->orderBy('descendant')->get()->all(), 'the closure was kept in step: a rebuild changes nothing');
ok('S4' === DB::table('physical_object_extended')->where('physical_object_id', 10)->value('shelf') && 'Annex' === DB::table('physical_object_i18n')->where('id', 13)->value('location'), 'the flat fields are left exactly as they were');

// Run again: nothing new
$moves = DB::table('ahg_storage_movement')->count();
$again = $mig->run(true);
ok(0 === count($again['placed']) && 0 === count($again['locations_created']), 'a second run places nothing and makes nothing');
ok(7 === DB::table('ahg_storage_location')->count() && $moves === DB::table('ahg_storage_movement')->count(), 'and writes nothing');

// A box catalogued afterwards is picked up, into the room that already exists
box(18, 'Box 18', null, ['building' => 'Main Building', 'floor' => '1', 'room' => 'C3']);
$later = $mig->run(true);
ok(1 === count($later['placed']) && 0 === count($later['locations_created']) && 'Main Building > Floor 1 > Room C3' === $path(18), 'a later box joins the existing room');

// Free text, when the archive says what it means
refused(function () use ($mig) { $mig->run(false, ['free_text_type' => 'cupboard']); }, 'Unknown location type', 'free text cannot be filed under a type that is not on the list');
$free = $mig->run(true, ['free_text_type' => 'building']);
ok(1 === count($free['placed']) && 'Annex' === $path(13), 'with a type given, free text becomes a root location');
ok('building' === DB::table('ahg_storage_location')->where('name', 'Annex')->value('location_type'), 'of that type');

// All or nothing
box(19, 'Box 19', null, ['building' => 'Second Building']);
DB::table('ahg_dropdown')->where('taxonomy', 'storage_location_type')->where('code', 'building')->update(['is_active' => 0]);
$locations = DB::table('ahg_storage_location')->count();
refused(function () { (new StorageFlatMigrationService())->run(true); }, 'Invalid location type', 'a migration that cannot finish says why');
ok($locations === DB::table('ahg_storage_location')->count() && null === $mv->currentLocationOf(19), 'and leaves nothing half done');
ok(agrees(), 'index agrees with log at the end');

echo $fail ? "\n{$fail} FAILED\n" : "\nALL PASS\n";
exit($fail ? 1 : 0);
