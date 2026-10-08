<?php
/**
 * SortChildrenService check (#205). Builds a THROWAWAY database with a small
 * nested set, sorts it by identifier, title and date, and checks the order and
 * that the nested set stays valid. The AtoM database is never touched.
 *
 * Run: php ahgInformationObjectManagePlugin/testing/sort-children-check.php /path/to/client.cnf
 */
foreach ([dirname(__DIR__, 3).'/atom-framework/vendor/autoload.php', '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $a) {
    if (file_exists($a)) { require $a; break; }
}
require dirname(__DIR__).'/lib/Services/SortChildrenService.php';

use AhgInformationObjectManage\Services\SortChildrenService as Sorter;
use Illuminate\Database\Capsule\Manager as DB;

$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) { fwrite(STDERR, "usage: php {$argv[0]} client.cnf\n"); exit(2); }
$scratch = 'scratch_sort_children_check';
$db = new DB();
$base = ['driver' => 'mysql', 'host' => $ini['host'] ?? 'localhost', 'username' => $ini['user'], 'password' => $ini['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database' => 'mysql'], 'admin');
$db->addConnection($base + ['database' => $scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}");
DB::connection('admin')->statement("CREATE DATABASE {$scratch} CHARACTER SET utf8mb4");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}"); });
DB::unprepared("CREATE TABLE information_object (id INT PRIMARY KEY, parent_id INT, lft INT, rgt INT, identifier VARCHAR(50), source_culture VARCHAR(7) DEFAULT 'en')");
DB::unprepared("CREATE TABLE information_object_i18n (id INT, culture VARCHAR(7), title VARCHAR(255), PRIMARY KEY (id, culture))");
DB::unprepared("CREATE TABLE event (id INT AUTO_INCREMENT PRIMARY KEY, object_id INT, start_date DATE)");

// Outside (2-3), fonds 10 (4-19) with children 10/2/1/undated, outside (20-21).
// Fonds 10: c11 "10" (5-8, child c14 "b"), c12 "2" (9-10), c13 "1" (11-16, children c15 "z" 12-13, c16 "a" 14-15), c17 "" (17-18)
$rows = [[1, null, 1, 22, '', 'root'], [2, 1, 2, 3, 'X', 'outside before'], [10, 1, 4, 19, 'F', 'Fonds'],
  [11, 10, 5, 8, '10', 'Charlie'], [14, 11, 6, 7, 'b', 'inner'], [12, 10, 9, 10, '2', 'alpha'],
  [13, 10, 11, 16, '1', 'Bravo'], [15, 13, 12, 13, 'z', 'zulu'], [16, 13, 14, 15, 'a', 'able'], [17, 10, 17, 18, '', 'delta'],
  [3, 1, 20, 21, 'Y', 'outside after']];
foreach ($rows as [$id, $p, $l, $r, $ident, $t]) {
    DB::table('information_object')->insert(['id' => $id, 'parent_id' => $p, 'lft' => $l, 'rgt' => $r, 'identifier' => $ident]);
    DB::table('information_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'title' => $t]);
}
foreach ([11 => '1950-01-01', 12 => '1900-05-01', 13 => '1920-01-01'] as $o => $d) { DB::table('event')->insert(['object_id' => $o, 'start_date' => $d]); }

$fail = 0;
$ok = function ($c, $m) use (&$fail) { echo ($c ? 'PASS ' : 'FAIL ').$m."\n"; $fail += $c ? 0 : 1; };
$order = fn ($parent) => DB::table('information_object')->where('parent_id', $parent)->orderBy('lft')->pluck('id')->all();
$valid = function () {
    // every node strictly inside its parent, siblings disjoint, outside rows untouched
    $n = DB::table('information_object')->get()->keyBy('id');
    foreach ($n as $row) {
        if ($row->parent_id && !($n[$row->parent_id]->lft < $row->lft && $row->rgt < $n[$row->parent_id]->rgt)) return false;
        if ($row->rgt <= $row->lft) return false;
    }
    $all = []; foreach ($n as $row) { $all[] = $row->lft; $all[] = $row->rgt; }
    sort($all);

    return $all === range(1, 22) && 2 == $n[2]->lft && 20 == $n[3]->lft && 4 == $n[10]->lft && 19 == $n[10]->rgt;
};

$plan = Sorter::plan(10, 'identifier', 'asc', 'en');
$ok([17, 13, 12, 11] === array_column($plan['children'], 'id') && 7 === $plan['records'], 'plan: identifier in number order ("" first, then 1, 2, 10), whole branch counted');
$ok(5 === (int) DB::table('information_object')->where('id', 11)->value('lft') && [11, 12, 13, 17] === $order(10), 'plan writes nothing');
$seen = [];
$r = Sorter::apply(10, 'identifier', 'asc', 'en', function ($id, $lft) use (&$seen) { $seen[$id] = $lft; return true; });
$ok([17, 13, 12, 11] === $order(10) && [16, 15] === $order(13), 'apply: every level sorted (children of 1 now a, z)');
$ok($valid(), 'nested set valid; records outside the branch did not move');
$ok($r['moved'] === count($seen) && $r['moved'] > 0, 'search updated for each moved record');
$again = Sorter::apply(10, 'identifier', 'asc', 'en', fn () => true);
$ok(0 === $again['moved'], 'sorting again moves nothing');
Sorter::apply(10, 'title', 'asc', 'en', fn () => true);
$ok([12, 13, 11, 17] === $order(10), 'title: alpha, Bravo, Charlie, delta (case ignored)');
Sorter::apply(10, 'date', 'desc', 'en', fn () => true);
$ok([11, 13, 12, 17] === $order(10) && $valid(), 'date newest first, undated last; still valid');
DB::table('information_object')->where('id', 16)->update(['rgt' => 99]);
try { Sorter::apply(10, 'title', 'asc', 'en', fn () => true); $ok(false, 'broken tree refused'); } catch (\RuntimeException $e) { $ok(true, 'broken tree refused, nothing changed'); }
echo $fail ? "\n{$fail} failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);
