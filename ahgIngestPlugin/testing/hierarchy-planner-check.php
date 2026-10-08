<?php
// php ahgIngestPlugin/testing/hierarchy-planner-check.php - no database needed.
require __DIR__.'/../lib/Services/HierarchyPlanner.php';
use AhgIngestPlugin\Services\HierarchyPlanner as H;

$fail = 0;
function ok($cond, $msg) { global $fail; echo ($cond ? 'PASS ' : 'FAIL ').$msg."\n"; if (!$cond) { ++$fail; } }
function r($n, $legacy, $parent) { return (object) ['row_number' => $n, 'legacy_id' => $legacy, 'parent_id_ref' => $parent]; }
function nums($rows) { return implode(',', array_map(fn ($r) => $r->row_number, $rows)); }

// Already parents-first: unchanged.
ok('1,2,3,4' === nums(H::order([r(1, 'F', ''), r(2, 'S', 'F'), r(3, 'FL', 'S'), r(4, 'I', 'FL')])), 'parents-first file keeps its order');
// Children before parents: parents pulled forward, otherwise stable.
ok('3,2,1' === nums(H::order([r(1, 'I', 'FL'), r(2, 'FL', 'S'), r(3, 'S', '')])), 'reversed chain becomes parent-first');
ok('2,1,3,4' === nums(H::order([r(1, 'A1', 'A'), r(2, 'A', ''), r(3, 'B', ''), r(4, 'B1', 'B')])), 'only rows that must wait move');
// Parent outside the batch (existing slug) puts no constraint on order.
ok('1,2' === nums(H::order([r(1, 'X', 'existing-fonds'), r(2, 'Y', 'X')])), 'external parent ref is not a constraint');
// No legacyId at all: file order.
ok('1,2' === nums(H::order([r(1, null, ''), r(2, null, '')])), 'rows without legacyId keep file order');
// Every row is returned exactly once, even with a loop.
$loop = [r(1, 'A', 'B'), r(2, 'B', 'A'), r(3, 'C', '')];
ok(3 === count(H::order($loop)) && 3 === count(array_unique(array_map(fn ($x) => $x->row_number, H::order($loop)))), 'a loop still returns every row once');

// Problems.
$p = H::problems([r(1, 'A', 'A')]);
ok(isset($p[1]) && false !== strpos($p[1], 'own parent'), 'self-parent reported');
$p = H::problems($loop);
ok(isset($p[1], $p[2]) && !isset($p[3]), 'two-row loop reported on both rows, not on the bystander');
$p = H::problems([r(1, 'A', 'C'), r(2, 'B', 'A'), r(3, 'C', 'B'), r(4, 'D', 'A')]);
ok(isset($p[1], $p[2], $p[3]) && !isset($p[4]), 'three-row loop reported; a child hanging off the loop is not itself a loop');
ok([] === H::problems([r(1, 'F', ''), r(2, 'S', 'F'), r(3, 'I', 'S')]), 'clean tree has no problems');
// Arrays work as well as objects.
ok('2,1' === nums(array_map(fn ($a) => (object) $a, H::order([['row_number' => 1, 'legacy_id' => 'c', 'parent_id_ref' => 'p'], ['row_number' => 2, 'legacy_id' => 'p', 'parent_id_ref' => '']]))), 'array rows supported');

echo $fail ? "\n$fail failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);
