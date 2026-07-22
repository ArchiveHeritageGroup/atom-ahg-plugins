<?php
/**
 * Clear CONTENT from the archaeology instance, ready for a demo seed.
 *
 * Deletes: descriptions (except the root), accessions, digital objects,
 * repositories, physical storage, and the workflow-state rows that point at
 * them. AtoM's `object` table cascades to every subtype, so removing the object
 * row removes the record and its notes, properties, events and relations.
 *
 * KEEPS: users, terms, taxonomies, settings, and the i18n work.
 *
 * Usage: clear_archeology_content.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);

// Users must survive. They are actors, and actor rows cascade from `object`,
// so every actor that backs a user has to be excluded explicitly.
$userIds = DB::table('user')->pluck('id')->all();

$sets = [
    'descriptions (excl. root)' => DB::table('information_object')->where('id', '!=', 1)->pluck('id')->all(),
    'accessions' => DB::table('accession')->pluck('id')->all(),
    'digital objects' => DB::table('digital_object')->pluck('id')->all(),
    'repositories' => DB::table('repository')->pluck('id')->all(),
    'physical storage' => DB::table('physical_object')->pluck('id')->all(),
];

// Content actors: every actor that is neither a user nor already counted as a
// repository. Repositories are listed separately above for reporting clarity.
$sets['content actors'] = DB::table('actor')
    ->whereNotIn('id', $userIds)
    ->whereNotIn('id', $sets['repositories'])
    ->where('id', '!=', 3)          // AtoM's root actor
    ->pluck('id')->all();

$ids = [];
foreach ($sets as $label => $list) {
    printf("  %-26s %d\n", $label, count($list));
    $ids = array_merge($ids, $list);
}
$ids = array_values(array_unique(array_map('intval', $ids)));

// Never delete the roots or anything backing a user.
$protected = array_merge($userIds, [1, 3]);
$ids = array_values(array_diff($ids, $protected));

printf("\n  object rows to delete: %d\n", count($ids));
printf("  protected (users + roots): %s\n", implode(',', $protected));

$wf = DB::table('spectrum_workflow_state')->count();
printf("  workflow-state rows to clear: %d\n", $wf);

if (!$apply) {
    echo "\n  DRY RUN - nothing deleted. Re-run with --apply\n";
    exit(0);
}

DB::connection()->beginTransaction();
try {
    DB::table('spectrum_workflow_state')->delete();
    // Chunk the delete so a large id list does not build a monster statement.
    $deleted = 0;
    foreach (array_chunk($ids, 500) as $chunk) {
        $deleted += DB::table('object')->whereIn('id', $chunk)->delete();
    }

    // Users must still be intact, or something cascaded that should not have.
    $usersLeft = DB::table('user')->count();
    if ($usersLeft !== count($userIds)) {
        throw new RuntimeException("user count changed ({$usersLeft} vs " . count($userIds) . ') - rolling back');
    }

    DB::connection()->commit();
    printf("\n  COMMITTED: %d object rows deleted\n", $deleted);
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo "\n  ROLLED BACK: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n  after:\n";
foreach ([
    'information_object' => 'descriptions',
    'accession' => 'accessions',
    'digital_object' => 'digital objects',
    'repository' => 'repositories',
    'actor' => 'actors',
    'user' => 'users',
    'term' => 'terms',
    'setting' => 'settings',
] as $t => $label) {
    printf("    %-18s %d\n", $label, DB::table($t)->count());
}
