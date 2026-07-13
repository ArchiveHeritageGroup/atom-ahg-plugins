<?php
/**
 * Entity create -> verify -> delete -> verify roundtrips on a THROWAWAY dataset.
 *
 * Exercises the framework WriteServices (the same write code the UI write actions
 * call) for entities that expose a clean service-level create AND delete, so every
 * record this creates is also removed - no orphans. Base-AtoM edit *forms* cannot
 * be driven headlessly (their theme JS intercepts submit), so this service-layer
 * roundtrip is the reliable way to prove real mutations succeed end to end.
 *
 * Run:  php atom-ahg-plugins/testing/entity-write-roundtrips.php
 * All test records are named "ZZ-TEST-*" and are deleted by the test; a final
 * sweep removes any that leaked from a mid-run failure.
 */
require '/usr/share/nginx/archive/atom-framework/bootstrap.php';

// Load the AtoM class environment in the CLI env (separate cache from prod - no
// web-cache recompile), the same safe pattern AtoM's own CLI import commands use,
// so the WriteServices' \QubitTerm / \QubitActor references resolve.
if (!class_exists('ProjectConfiguration', false)) {
    require_once '/usr/share/nginx/archive/config/ProjectConfiguration.class.php';
}
if (!class_exists('sfContext', false) || !\sfContext::hasInstance()) {
    $configuration = \ProjectConfiguration::getApplicationConfiguration('qubit', 'cli', false);
    \sfContext::createInstance($configuration);
}

use AtomFramework\Services\Write\WriteServiceFactory;
use AtomFramework\Services\Write\SectorRecordWriteService;
use Illuminate\Database\Capsule\Manager as DB;

$ts = (string) time();
$MARK = "ZZ-TEST-{$ts}";
$results = [];
$createdActorIds = [];
$createdTermIds = [];

function ok(&$r, string $entity, bool $pass, string $detail): void
{
    $r[] = ['entity' => $entity, 'pass' => $pass, 'detail' => $detail];
}

// ---- TERM (createTerm / deleteTerm) ---------------------------------------
try {
    $subjectId = (class_exists('QubitTaxonomy') && defined('QubitTaxonomy::SUBJECT_ID'))
        ? \QubitTaxonomy::SUBJECT_ID : 35;
    $svc = WriteServiceFactory::term();
    $name = "{$MARK}-Term";
    $term = $svc->createTerm($subjectId, $name);
    $id = (int) $term->id;
    $createdTermIds[] = $id;
    $exists = DB::table('term_i18n')->where('id', $id)->where('name', $name)->exists();
    $svc->deleteTerm($id);
    $gone = !DB::table('term')->where('id', $id)->exists()
        && !DB::table('term_i18n')->where('id', $id)->exists();
    if ($gone) { array_pop($createdTermIds); }
    ok($results, 'Term', $exists && $gone, "created id={$id}; existed=".($exists?'yes':'NO').", deleted=".($gone?'yes':'NO'));
} catch (\Throwable $e) {
    ok($results, 'Term', false, 'EXCEPTION: '.$e->getMessage());
}

// ---- DONOR (createDonor / deleteDonor) ------------------------------------
try {
    $svc = WriteServiceFactory::donor();
    $name = "{$MARK}-Donor";
    $id = (int) $svc->createDonor(['authorizedFormOfName' => $name]);
    $createdActorIds[] = $id;
    $exists = DB::table('donor')->where('id', $id)->exists()
        && DB::table('actor_i18n')->where('id', $id)->where('authorized_form_of_name', $name)->exists();
    $svc->deleteDonor($id);
    $gone = !DB::table('donor')->where('id', $id)->exists()
        && !DB::table('actor')->where('id', $id)->exists();
    if ($gone) { array_pop($createdActorIds); }
    ok($results, 'Donor', $exists && $gone, "created id={$id}; existed=".($exists?'yes':'NO').", deleted=".($gone?'yes':'NO'));
} catch (\Throwable $e) {
    ok($results, 'Donor', false, 'EXCEPTION: '.$e->getMessage());
}

// ---- RIGHTS HOLDER (createRightsHolder / deleteRightsHolder) ---------------
try {
    $svc = WriteServiceFactory::rightsHolder();
    $name = "{$MARK}-RightsHolder";
    $id = (int) $svc->createRightsHolder(['authorizedFormOfName' => $name]);
    $createdActorIds[] = $id;
    $exists = DB::table('rights_holder')->where('id', $id)->exists()
        && DB::table('actor_i18n')->where('id', $id)->where('authorized_form_of_name', $name)->exists();
    $svc->deleteRightsHolder($id);
    $gone = !DB::table('rights_holder')->where('id', $id)->exists()
        && !DB::table('actor')->where('id', $id)->exists();
    if ($gone) { array_pop($createdActorIds); }
    ok($results, 'RightsHolder', $exists && $gone, "created id={$id}; existed=".($exists?'yes':'NO').", deleted=".($gone?'yes':'NO'));
} catch (\Throwable $e) {
    ok($results, 'RightsHolder', false, 'EXCEPTION: '.$e->getMessage());
}

// ---- ACTOR (createActor / QubitActor delete) ------------------------------
try {
    $svc = WriteServiceFactory::actor();
    $name = "{$MARK}-Actor";
    $id = (int) $svc->createActor(['authorizedFormOfName' => $name]);
    $createdActorIds[] = $id;
    $exists = DB::table('actor')->where('id', $id)->exists()
        && DB::table('actor_i18n')->where('id', $id)->where('authorized_form_of_name', $name)->exists();
    \QubitActor::getById($id)->delete();
    $gone = !DB::table('actor')->where('id', $id)->exists();
    if ($gone) { array_pop($createdActorIds); }
    ok($results, 'Actor', $exists && $gone, "created id={$id}; existed=".($exists?'yes':'NO').", deleted=".($gone?'yes':'NO'));
} catch (\Throwable $e) {
    ok($results, 'Actor', false, 'EXCEPTION: '.$e->getMessage());
}

// ---- INFORMATION OBJECT (createInformationObject / QubitInformationObject delete)
$createdIoIds = [];
try {
    $svc = WriteServiceFactory::informationObject();
    $name = "{$MARK}-IO";
    $id = (int) $svc->createInformationObject(['title' => $name]);
    $createdIoIds[] = $id;
    $exists = DB::table('information_object')->where('id', $id)->exists()
        && DB::table('information_object_i18n')->where('id', $id)->where('title', $name)->exists();
    \QubitInformationObject::getById($id)->delete();
    $gone = !DB::table('information_object')->where('id', $id)->exists();
    if ($gone) { array_pop($createdIoIds); }
    ok($results, 'InformationObject', $exists && $gone, "created id={$id}; existed=".($exists?'yes':'NO').", deleted=".($gone?'yes':'NO'));
} catch (\Throwable $e) {
    ok($results, 'InformationObject', false, 'EXCEPTION: '.$e->getMessage());
}

// ---- GLAM SECTORS (IO typed by source_standard / QubitInformationObject delete)
// Library / Museum / Gallery / DAM records are Information Objects distinguished
// by source_standard; the sector extension tables (library_item, dam_iptc_metadata,
// etc.) are populated by the sector edit forms, not by a create service, so this
// covers the sector-typed base record create/delete.
// Full CRUD via SectorRecordWriteService: create with a sector extension field,
// verify it, update it, verify the change, delete, verify BOTH the extension and
// the information object are gone.
$sectorTests = [
    'library' => ['label' => 'Library', 'field' => 'call_number', 'create' => ['call_number' => 'ZZ-CN-1'],           'update' => ['call_number' => 'ZZ-CN-2']],
    'dam'     => ['label' => 'DAM',     'field' => 'headline',    'create' => ['headline' => 'ZZ-HL-1'],              'update' => ['headline' => 'ZZ-HL-2']],
    'museum'  => ['label' => 'Museum',  'field' => 'work_type',   'create' => ['work_type' => 'ZZ-WT-1', 'materials' => ['bronze']], 'update' => ['work_type' => 'ZZ-WT-2']],
    'gallery' => ['label' => 'Gallery', 'field' => 'work_type',   'create' => ['work_type' => 'ZZ-WT-1'],             'update' => ['work_type' => 'ZZ-WT-2']],
];
$sectorSvc = new SectorRecordWriteService();
foreach ($sectorTests as $sector => $t) {
    $f = $t['field'];
    try {
        $id = $sectorSvc->create($sector, "{$MARK}-{$t['label']}", $t['create']);
        $createdIoIds[] = $id;
        $c = $sectorSvc->read($id, $sector);
        $createdOk = is_array($c) && (($c[$f] ?? null) === $t['create'][$f]);
        $sectorSvc->update($id, $sector, $t['update']);
        $u = $sectorSvc->read($id, $sector);
        $updatedOk = is_array($u) && (($u[$f] ?? null) === $t['update'][$f]);
        $sectorSvc->delete($id, $sector);
        $extGone = null === $sectorSvc->read($id, $sector);
        $ioGone = !DB::table('information_object')->where('id', $id)->exists();
        if ($ioGone) { array_pop($createdIoIds); }
        ok($results, "Sector:{$t['label']}", $createdOk && $updatedOk && $extGone && $ioGone,
            "id={$id}; create ".($createdOk?'ok':'FAIL').", update ".($updatedOk?'ok':'FAIL').", ext-deleted ".($extGone?'ok':'FAIL').", io-deleted ".($ioGone?'ok':'FAIL'));
    } catch (\Throwable $e) {
        ok($results, "Sector:{$t['label']}", false, 'EXCEPTION: '.$e->getMessage());
    }
}

// ---- Safety sweep: remove any ZZ-TEST-* records that leaked -----------------
foreach (array_unique($createdIoIds) as $iid) {
    try { \QubitInformationObject::getById($iid)?->delete(); } catch (\Throwable $e) {}
}
$leakIos = DB::table('information_object_i18n')->where('title', 'like', 'ZZ-TEST-%')->pluck('id')->all();
foreach ($leakIos as $iid) { try { \QubitInformationObject::getById($iid)?->delete(); } catch (\Throwable $e) {} }
$leakActors = DB::table('actor_i18n')->where('authorized_form_of_name', 'like', 'ZZ-TEST-%')->pluck('id')->all();
foreach (array_unique(array_merge($createdActorIds, $leakActors)) as $aid) {
    foreach (['donor','rights_holder','actor_i18n','actor','object'] as $t) {
        try { DB::table($t)->where('id', $aid)->delete(); } catch (\Throwable $e) {}
    }
}
$leakTerms = DB::table('term_i18n')->where('name', 'like', 'ZZ-TEST-%')->pluck('id')->all();
foreach (array_unique(array_merge($createdTermIds, $leakTerms)) as $tid) {
    foreach (['term_i18n','term','object'] as $t) {
        try { DB::table($t)->where('id', $tid)->delete(); } catch (\Throwable $e) {}
    }
}
$sweptA = count($leakActors); $sweptT = count($leakTerms);

// ---- Report ---------------------------------------------------------------
$pass = 0; $fail = 0;
echo "\nEntity create/delete roundtrips (throwaway data, mark {$MARK}):\n";
foreach ($results as $r) {
    echo ($r['pass'] ? "  PASS  " : "  FAIL  ").str_pad($r['entity'], 14)." {$r['detail']}\n";
    $r['pass'] ? $pass++ : $fail++;
}
echo "\n  {$pass} passed, {$fail} failed. Cleanup sweep removed ".($sweptA)." stray actor(s), ".($sweptT)." stray term(s).\n";
exit($fail === 0 ? 0 : 1);
