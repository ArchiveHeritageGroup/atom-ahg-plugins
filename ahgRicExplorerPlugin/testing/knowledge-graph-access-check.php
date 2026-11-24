<?php

/**
 * Knowledge graph access (#150), on in-memory SQLite with the real
 * SearchAccessFilterService. Touches no real database and does not boot Symfony.
 *   php ahgRicExplorerPlugin/testing/knowledge-graph-access-check.php
 * Exits 1 on any failed expectation.
 */
$framework = getenv('ATOM_FRAMEWORK') ?: '/usr/share/nginx/archive/atom-framework';
require $framework.'/vendor/autoload.php';
require_once $framework.'/src/Services/Search/SearchAccessFilterService.php';
require_once dirname(__DIR__).'/lib/Services/KnowledgeGraphService.php';

use AtomExtensions\Services\Search\SearchAccessFilterService as Filter;
use Illuminate\Database\Capsule\Manager as DB;

$capsule = new DB();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
$capsule->setAsGlobal();
$pdo = DB::connection()->getPdo();
foreach ([
    'information_object (id INTEGER PRIMARY KEY, repository_id INT, lft INT, rgt INT)',
    'information_object_i18n (id INT, culture TEXT, title TEXT)',
    'actor_i18n (id INT, culture TEXT, authorized_form_of_name TEXT)',
    'term_i18n (id INT, culture TEXT, name TEXT)',
    'status (object_id INT, type_id INT, status_id INT)',
    'event (object_id INT, actor_id INT, type_id INT)',
    'object_term_relation (object_id INT, term_id INT)',
    'relation (subject_id INT, object_id INT, type_id INT)',
    'provenance_record (information_object_id INT, donor_id INT)',
    'acl_user_group (user_id INT, group_id INT)',
    'security_classification (id INT, level INT)',
    'object_security_classification (object_id INT, classification_id INT, active INT)',
    'user_security_clearance (user_id INT, classification_id INT, expires_at TEXT)',
    'rights_embargo (object_id INT, status TEXT, embargo_type TEXT, start_date TEXT, end_date TEXT)',
    'icip_access_restriction (information_object_id INT, start_date TEXT, end_date TEXT, applies_to_descendants INT)',
] as $table) {
    $pdo->exec('CREATE TABLE '.$table);
}

const PUB = 10;
const DRAFT = 11;
const EMBARGO = 12;
const CLASSIFIED = 13;
const ICIP = 14;
const NOSTATUS = 15;
const PUB2 = 16;
const ADMIN = 1;
const RESEARCHER = 2;

$labels = [PUB => 'Public', DRAFT => 'SECRET draft', EMBARGO => 'SECRET embargoed', CLASSIFIED => 'SECRET classified', ICIP => 'SECRET icip', NOSTATUS => 'SECRET no status', PUB2 => 'Public neighbour'];
foreach ($labels as $id => $title) {
    DB::table('information_object')->insert(['id' => $id, 'repository_id' => 500, 'lft' => $id * 10, 'rgt' => $id * 10 + 1]);
    DB::table('information_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'title' => $title]);
    if (NOSTATUS !== $id) {
        DB::table('status')->insert(['object_id' => $id, 'type_id' => 158, 'status_id' => DRAFT === $id ? 159 : 160]);
    }
    if (PUB !== $id) {
        DB::table('relation')->insert(['subject_id' => PUB, 'object_id' => $id, 'type_id' => 900]);
    }
}
DB::table('actor_i18n')->insert([
    ['id' => 500, 'culture' => 'en', 'authorized_form_of_name' => 'Repository'],
    ['id' => 501, 'culture' => 'en', 'authorized_form_of_name' => 'Creator'],
    ['id' => 502, 'culture' => 'en', 'authorized_form_of_name' => 'SECRET donor'],
]);
DB::table('term_i18n')->insert(['id' => 600, 'culture' => 'en', 'name' => 'Subject']);
DB::table('event')->insert(['object_id' => PUB, 'actor_id' => 501, 'type_id' => 900]);
DB::table('object_term_relation')->insert(['object_id' => PUB, 'term_id' => 600]);
DB::table('provenance_record')->insert(['information_object_id' => PUB, 'donor_id' => 502]);
DB::table('acl_user_group')->insert(['user_id' => ADMIN, 'group_id' => 100]);
DB::table('security_classification')->insert(['id' => 1, 'level' => 3]);
DB::table('object_security_classification')->insert(['object_id' => CLASSIFIED, 'classification_id' => 1, 'active' => 1]);
DB::table('rights_embargo')->insert(['object_id' => EMBARGO, 'status' => 'active', 'embargo_type' => 'full', 'start_date' => '2000-01-01', 'end_date' => null]);
DB::table('icip_access_restriction')->insert(['information_object_id' => ICIP, 'start_date' => null, 'end_date' => null, 'applies_to_descendants' => 0]);

$fails = 0;
$check = function (bool $ok, string $what) use (&$fails) {
    echo ($ok ? 'PASS ' : 'FAIL ').$what."\n";
    $fails += $ok ? 0 : 1;
};
$nodeIds = static fn (array $g) => array_column($g['nodes'], 'id');
$pickerIds = static fn (array $rows) => array_map(static fn ($r) => (int) $r->io_id, $rows);
$blocked = [DRAFT => 'draft', EMBARGO => 'embargoed', CLASSIFIED => 'classified', ICIP => 'ICIP', NOSTATUS => 'no-status'];

$researcher = new KnowledgeGraphService(RESEARCHER);
$picker = $pickerIds($researcher->listEntities());
$graph = $researcher->build(PUB);
$json = json_encode($graph);
sort($picker);
$check($picker === [PUB, PUB2], 'researcher picker holds only the public records');
foreach ($blocked as $id => $name) {
    $check(!in_array($id, $picker, true), "{$name} absent from picker");
    $check([] === $researcher->build($id)['nodes'], "{$name} as centre gives no graph (404)");
    $check(!in_array('io-'.$id, $nodeIds($graph), true), "{$name} absent as neighbour");
}
$check(!str_contains($json, 'SECRET'), 'no restricted label anywhere in the public graph');
$check(in_array('io-'.PUB2, $nodeIds($graph), true) && 1 === $graph['summary']['related'], 'public neighbour kept and counted');
foreach (['repo-500', 'actor-501', 'term-600'] as $node) {
    $check(in_array($node, $nodeIds($graph), true), "{$node} kept");
}
foreach ($graph['edges'] as $edge) {
    $check(in_array($edge['source'], $nodeIds($graph), true) && in_array($edge['target'], $nodeIds($graph), true), 'edge joins two kept nodes');
}
$check([] === (new KnowledgeGraphService(RESEARCHER))->build(999)['nodes'], 'missing record gives no graph');
$check([] === (new KnowledgeGraphService(RESEARCHER))->build(1)['nodes'], 'root gives no graph');

$limited = new KnowledgeGraphService(RESEARCHER);
$check($pickerIds($limited->listEntities(2)) === [PUB2, PUB], 'picker fills its limit past hidden rows');

$editor = new KnowledgeGraphService(RESEARCHER, static fn (int $id) => DRAFT === $id);
$editorGraph = $editor->build(PUB);
$check(in_array('io-'.DRAFT, $nodeIds($editorGraph), true), 'staff with viewDraft sees the draft');
$check(!in_array('io-'.NOSTATUS, $nodeIds($editorGraph), true), 'viewDraft denied on another record keeps it hidden');
$check(!in_array('io-'.EMBARGO, $nodeIds($editorGraph), true), 'viewDraft does not lift an embargo');
$check(in_array('donor-502', $nodeIds($editorGraph), true), 'staff sees the donor');

$admin = new KnowledgeGraphService(ADMIN, static fn (int $id) => true);
$check(7 === count($pickerIds($admin->listEntities())), 'administrator picker holds all seven');
$check(7 === count(array_filter($nodeIds($admin->build(PUB)), static fn ($n) => str_starts_with($n, 'io-'))), 'administrator graph holds all seven records');

// Neighbour step fails after the centre was allowed: centre only.
DB::statement('ALTER TABLE relation RENAME TO relation_off');
$broken = (new KnowledgeGraphService(RESEARCHER))->build(PUB);
$check(['io-'.PUB] === $nodeIds($broken) && [] === $broken['edges'], 'neighbour failure leaves only the allowed centre');
$check([] === (new KnowledgeGraphService(RESEARCHER))->build(DRAFT)['nodes'], 'neighbour failure still refuses a draft centre');
DB::statement('ALTER TABLE relation_off RENAME TO relation');

// The filter itself throws: nothing can be proven allowed. Keep this block last,
// because a registered source cannot be removed.
Filter::addRestrictionSource(static function () {
    throw new RuntimeException('filter down');
});
$failed = new KnowledgeGraphService(RESEARCHER);
$check([] === $failed->listEntities(), 'filter exception empties the picker');
$check([] === $failed->build(PUB)['nodes'], 'filter exception refuses even a public centre (404)');
$check([] === (new KnowledgeGraphService(RESEARCHER))->build(DRAFT)['nodes'], 'filter exception refuses a draft centre');

echo $fails ? "{$fails} FAILED\n" : "All checks passed\n";
exit($fails ? 1 : 0);
