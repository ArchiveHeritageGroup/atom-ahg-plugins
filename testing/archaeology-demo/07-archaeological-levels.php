<?php
/**
 * Assign archaeological level-of-description terms to the excavation records.
 *
 * The instance uses two hierarchies. The EXCAVATION record maps naturally onto
 * archaeological levels; the SITE ARCHIVE is genuinely archival and keeps
 * Fonds/Series/File. Series and File are each used by both, so the taxonomy
 * terms cannot simply be renamed - that would turn the archive's series into
 * "Site". Instead, distinct terms are created and only the excavation records
 * are re-levelled. Archive records (identifier contains -ARCH) are left alone.
 *
 *   Series      -> Site       Subseries -> Trench     File -> Context
 *   Item (find) -> Find       Item (sample, -S..) -> Sample
 *
 * Find and Sample already exist (created by migrate-levels up). Site, Trench and
 * Context are created here through the full term chain.
 *
 * Export note: custom levels serialise to EAD as `otherlevel`. If SAHRIS or ADS
 * ingest is planned, confirm they accept otherlevel, or export the archive tree
 * (which keeps standard levels) for exchange.
 *
 * Usage: 07-archaeological-levels.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);

function levelTerm(string $name): int
{
    $id = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
        ->where('t.taxonomy_id', 34)->where('ti.name', $name)->value('t.id');
    if ($id) {
        return (int) $id;
    }
    $now = date('Y-m-d H:i:s');
    $id = DB::table('object')->insertGetId(['class_name' => 'QubitTerm', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('term')->insert(['id' => $id, 'taxonomy_id' => 34, 'source_culture' => 'en']);
    DB::table('term_i18n')->insert(['id' => $id, 'culture' => 'en', 'name' => $name]);
    $base = strtolower($name);
    $slug = $base; $i = 2;
    while (DB::table('slug')->where('slug', $slug)->exists()) { $slug = $base . '-' . $i++; }
    DB::table('slug')->insert(['object_id' => $id, 'slug' => $slug]);

    return $id;
}

// current level term ids
$cur = [];
foreach (['Series', 'Subseries', 'File', 'Item'] as $n) {
    $cur[$n] = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
        ->where('t.taxonomy_id', 34)->where('ti.name', $n)->value('t.id');
}

// build the plan: excavation records only (identifier NOT containing -ARCH)
$plan = [];   // io id => [from level name, to level name]
$rows = DB::table('information_object as io')
    ->join('term_i18n as lt', function ($j) { $j->on('lt.id', '=', 'io.level_of_description_id')->where('lt.culture', '=', 'en'); })
    ->where('io.id', '!=', 1)
    ->whereIn('io.level_of_description_id', array_values(array_filter($cur)))
    ->select('io.id', 'io.identifier', 'lt.name as level')->get();

foreach ($rows as $r) {
    if (false !== strpos((string) $r->identifier, '-ARCH')) {
        continue;   // site archive - leave archival
    }
    $to = match ($r->level) {
        'Series' => 'Site',
        'Subseries' => 'Trench',
        'File' => 'Context',
        // a sample identifier is -S<digits>; a find is -SF<digits>
        'Item' => preg_match('/-S\d/', (string) $r->identifier) ? 'Sample' : 'Find',
        default => null,
    };
    if ($to) {
        $plan[$r->id] = [$r->level, $to];
    }
}

$summary = [];
foreach ($plan as [$from, $to]) { $summary["{$from} -> {$to}"] = ($summary["{$from} -> {$to}"] ?? 0) + 1; }

echo "  excavation records to re-level:\n";
foreach ($summary as $move => $n) { printf("    %-22s %d\n", $move, $n); }
printf("  total: %d  (archive records left on standard ISAD levels)\n", count($plan));

if (!$apply) {
    echo "\n  DRY RUN - re-run with --apply\n";
    exit(0);
}

DB::connection()->beginTransaction();
try {
    $target = [
        'Site' => levelTerm('Site'), 'Trench' => levelTerm('Trench'),
        'Context' => levelTerm('Context'), 'Find' => levelTerm('Find'), 'Sample' => levelTerm('Sample'),
    ];

    $moved = 0;
    foreach ($plan as $ioId => [$from, $to]) {
        DB::table('information_object')->where('id', $ioId)->update(['level_of_description_id' => $target[$to]]);
        ++$moved;
    }

    // no excavation record may still sit on a renamed-away archival level
    $stray = DB::table('information_object as io')
        ->join('term_i18n as lt', function ($j) { $j->on('lt.id', '=', 'io.level_of_description_id')->where('lt.culture', '=', 'en'); })
        ->where('io.identifier', 'not like', '%-ARCH%')
        ->whereIn('lt.name', ['Subseries', 'Item'])
        ->count();
    if ($stray > 0) {
        throw new RuntimeException("{$stray} excavation record(s) still on Subseries/Item");
    }

    DB::connection()->commit();
    printf("\n  COMMITTED: %d record(s) re-levelled\n", $moved);
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo "\n  ROLLED BACK: " . $e->getMessage() . "\n";
    exit(1);
}
