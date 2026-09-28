<?php
/**
 * Storage-location closure check (atom-ahg-plugins#193, option 2).
 *
 * Builds a THROWAWAY database, loads the ahg_storage_location section of
 * database/install.sql into it, then drives StorageLocationService through
 * create, subtree reads, path, move, move-to-root, cycle refusal, delete, and
 * checks after each step that the incrementally maintained closure equals a
 * full rebuildClosure() from parent_id. Finally it checks that the install.sql
 * backfill produces the same closure. The database is dropped at the end.
 *
 * Run:  php ahgStorageManagePlugin/testing/storage-location-closure-check.php /path/to/client.cnf
 * The .cnf is a MySQL [client] file with user= and password= for an account
 * that may CREATE and DROP a database. It never touches the AtoM database.
 */
// The framework autoloader, wherever this instance keeps it.
$autoload = null;

foreach ([dirname(__DIR__, 3).'/atom-framework/vendor/autoload.php',
          dirname(__DIR__, 4).'/atom-framework/vendor/autoload.php',
          '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $candidate) {
    if (file_exists($candidate)) { $autoload = $candidate; break; }
}

if (null === $autoload) { fwrite(STDERR, "cannot find atom-framework/vendor/autoload.php\n"); exit(2); }
require $autoload;
require dirname(__DIR__).'/lib/Services/StorageLocationService.php';
// A location move is logged, so the service reaches for this one too.
require dirname(__DIR__).'/lib/Services/StorageMovementService.php';
use Illuminate\Database\Capsule\Manager as DB;

$scratch = 'scratch_storage_location_check';
$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) { fwrite(STDERR, "usage: php {$argv[0]} /path/to/client.cnf
"); exit(2); }
$sql = file_get_contents(dirname(__DIR__).'/database/install.sql');
$sql = substr($sql, strpos($sql, 'CREATE TABLE IF NOT EXISTS ahg_storage_location ('));

$db = new DB;
$base = ['driver'=>'mysql','host'=>'localhost','username'=>$ini['user'],'password'=>$ini['password'],'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database'=>'mysql'], 'admin');
$db->addConnection($base + ['database'=>$scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS $scratch");
DB::connection('admin')->statement("CREATE DATABASE $scratch CHARACTER SET utf8mb4");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS $scratch"); });
// Stand-ins for the base AtoM tables the storage tables point at: the movement
// log and the current-location index carry foreign keys to physical_object, so
// the slice of install.sql loaded here will not create without them.
DB::unprepared('CREATE TABLE physical_object (id INT NOT NULL PRIMARY KEY);');
DB::unprepared('CREATE TABLE physical_object_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, name VARCHAR(255), PRIMARY KEY (id, culture));');
DB::unprepared($sql);
$s = new AhgStorageManage\Services\StorageLocationService();
$fail=0; function ok($c,$m){global $fail; echo ($c?"PASS ":"FAIL ").$m."\n"; if(!$c)$fail++;}
function snap(){ return array_map(fn($r)=>"$r->ancestor>$r->descendant:$r->depth", DB::table('ahg_storage_location_closure')->orderBy('ancestor')->orderBy('descendant')->get()->all()); }
$b=$s->createLocation(['name'=>'Building A','location_type'=>'building']);
$f=$s->createLocation(['name'=>'Floor 1','location_type'=>'floor','parent_id'=>$b['id']]);
$r=$s->createLocation(['name'=>'Room 3','location_type'=>'room','parent_id'=>$f['id']]);
$sh=$s->createLocation(['name'=>'Shelf A','location_type'=>'shelf','parent_id'=>$r['id']]);
$b2=$s->createLocation(['name'=>'Building B','location_type'=>'building']);
ok(count($s->getDescendants($b['id']))===3,'descendants of Building A = 3');
ok(array_column($s->getLocationPath($sh['id']),'name')===['Building A','Floor 1','Room 3','Shelf A'],'path root to leaf');
ok((int)$s->getLocationById($sh['id'])['level']===3,'shelf level 3');
$before=snap(); $n=$s->rebuildClosure(); ok($before===snap(),"rebuild matches incremental ($n rows)");
$s->updateLocation($r['id'],['parent_id'=>$b2['id']]);
ok(array_column($s->getLocationPath($sh['id']),'name')===['Building B','Room 3','Shelf A'],'move subtree: path updated');
ok((int)$s->getLocationById($sh['id'])['level']===2 && (int)$s->getLocationById($r['id'])['level']===1,'levels recomputed after move');
ok(count($s->getDescendants($b['id']))===1,'old ancestor lost the moved subtree');
$before=snap(); $s->rebuildClosure(); ok($before===snap(),'rebuild matches after move');
try{$s->updateLocation($b2['id'],['parent_id'=>$sh['id']]);ok(false,'cycle refused');}catch(Exception $e){ok(str_contains($e->getMessage(),'own ancestor'),'cycle refused: '.$e->getMessage());}
try{$s->updateLocation($b2['id'],['parent_id'=>$b2['id']]);ok(false,'self-parent refused');}catch(Exception $e){ok(true,'self-parent refused');}
$s->updateLocation($r['id'],['parent_id'=>'']);
ok($s->getLocationById($r['id'])['parent_id']===null && (int)$s->getLocationById($sh['id'])['level']===1,'move to root');
$before=snap(); $s->updateLocation($sh['id'],['name'=>'Shelf A renamed','parent_id'=>$r['id']]);
ok(snap()===$before,'rename with unchanged parent: no closure churn');
$t=$s->getLocationTree(null); ok(count($t)===3,'full tree has 3 roots');
$sub=$s->getLocationTree($r['id']); ok(count($sub)===1 && $sub[0]['name']==='Shelf A renamed','subtree read from closure');
try{$s->deleteLocation($r['id']);ok(false,'delete parent refused');}catch(Exception $e){ok(true,'delete parent with children refused');}
$s->deleteLocation($sh['id']); ok(DB::table('ahg_storage_location_closure')->where('descendant',$sh['id'])->orWhere('ancestor',$sh['id'])->count()===0,'delete clears closure rows');
$before=snap(); $s->rebuildClosure(); ok($before===snap(),'rebuild matches at end');
// install.sql backfill on pre-existing rows (closure emptied, rows kept)
DB::table('ahg_storage_location_closure')->delete();
$x=$s->createLocation(['name'=>'Deep','location_type'=>'container','parent_id'=>$r['id']]);
DB::table('ahg_storage_location_closure')->delete();
DB::unprepared($sql);
$bf=snap(); $s->rebuildClosure(); ok($bf===snap(),'install.sql backfill equals rebuild ('.count($bf).' rows)');
echo $fail? "\n$fail FAILED\n" : "\nALL PASS\n";
exit($fail ? 1 : 0);
