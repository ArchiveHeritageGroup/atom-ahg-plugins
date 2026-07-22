<?php
/**
 * Physical storage for the archaeology demo.
 *
 * Two layers, because AtoM and the AHG stack each provide one:
 *
 *   physical_object + relation(type 147)      - AtoM's native container link,
 *                                               what the Storage location page
 *                                               and holdings reports read.
 *   information_object_physical_location      - the AHG detail row: shelf, row,
 *                                               box, barcode, extent, condition.
 *
 * Also clears the 11 rows orphaned by the content wipe - they point at
 * information objects that no longer exist.
 *
 * Usage: seed_storage.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);
$now = date('Y-m-d H:i:s');

const HAS_PHYSICAL_OBJECT = 147;   // QubitTerm::HAS_PHYSICAL_OBJECT_ID
const BOX_TYPE = 223;              // Physical Object Type taxonomy (48) -> Box

// box identifier => [location, [find identifiers], shelf, row]
$boxes = [
    'KRK/BOX/01' => ['Repository store, bay 3', ['KRK-SF214', 'KRK-SF215', 'KRK-SF221'], 'A', '2'],
    'KRK/BOX/02' => ['Repository store, bay 3', ['KRK-SF224', 'KRK-SF231'], 'A', '3'],
    'KRK/BOX/03' => ['Repository store, bay 4', ['KRK-SF230', 'KRK-SF238'], 'B', '1'],
    'KRK/BOX/04' => ['Cold store, cabinet 1', ['KRK-S07', 'KRK-S08'], 'C', '1'],
];

function slugify(string $s): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($s)), '-');
}

function uniqueSlug(string $base): string
{
    $slug = $base;
    $i = 2;
    while (DB::table('slug')->where('slug', $slug)->exists()) {
        $slug = $base . '-' . $i++;
    }

    return $slug;
}

if (!$apply) {
    printf("  boxes to create : %d\n", count($boxes));
    printf("  finds to locate : %d\n", array_sum(array_map(fn ($b) => count($b[1]), $boxes)));
    printf("  orphans to clear: %d\n",
        DB::table('information_object_physical_location as l')
            ->leftJoin('information_object as io', 'io.id', '=', 'l.information_object_id')
            ->whereNull('io.id')->count());
    echo "\n  DRY RUN - re-run with --apply\n";
    exit(0);
}

DB::connection()->beginTransaction();
try {
    // 1. clear orphans from the wipe
    $orphanIds = DB::table('information_object_physical_location as l')
        ->leftJoin('information_object as io', 'io.id', '=', 'l.information_object_id')
        ->whereNull('io.id')->pluck('l.id')->all();
    $cleared = $orphanIds ? DB::table('information_object_physical_location')->whereIn('id', $orphanIds)->delete() : 0;

    $createdBoxes = 0;
    $linked = 0;
    $located = 0;

    foreach ($boxes as $boxName => [$location, $finds, $shelf, $row]) {
        // 2. the container itself
        $poId = DB::table('object')->insertGetId([
            'class_name' => 'QubitPhysicalObject', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('physical_object')->insert([
            'id' => $poId, 'type_id' => BOX_TYPE, 'source_culture' => 'en',
        ]);
        DB::table('physical_object_i18n')->insert([
            'id' => $poId, 'culture' => 'en', 'name' => $boxName, 'location' => $location,
        ]);
        DB::table('slug')->insert(['object_id' => $poId, 'slug' => uniqueSlug(slugify($boxName))]);
        ++$createdBoxes;

        foreach ($finds as $pos => $ident) {
            $ioId = DB::table('information_object')->where('identifier', $ident)->value('id');
            if (!$ioId) {
                throw new RuntimeException("find not found: {$ident}");
            }

            // 3. AtoM's native link: relation(subject=IO, object=container, type 147)
            $relId = DB::table('object')->insertGetId([
                'class_name' => 'QubitRelation', 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('relation')->insert([
                'id' => $relId, 'subject_id' => $ioId, 'object_id' => $poId,
                'type_id' => HAS_PHYSICAL_OBJECT, 'source_culture' => 'en',
            ]);
            ++$linked;

            // 4. the AHG detail row - where in the box, and in what state
            DB::table('information_object_physical_location')->insert([
                'information_object_id' => $ioId,
                'physical_object_id' => $poId,
                'shelf' => $shelf,
                'row' => $row,
                'position' => (string) ($pos + 1),
                'box_number' => $boxName,
                'barcode' => 'WITS-' . str_replace(['/', '-'], '', $boxName) . '-' . str_pad((string) ($pos + 1), 2, '0', STR_PAD_LEFT),
                'extent_value' => '1',
                'extent_unit' => 'bag',
                'condition_status' => 'stable',
                'access_status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            ++$located;
        }
    }

    DB::connection()->commit();
    printf("  orphans cleared: %d\n  boxes created  : %d\n  native links   : %d\n  location rows  : %d\n",
        $cleared, $createdBoxes, $linked, $located);
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo '  ROLLED BACK: ' . $e->getMessage() . "\n";
    exit(1);
}

// Keep browse facets ("Narrow your results by:") current after this seed.
require __DIR__ . '/_refresh_facets.php';
refresh_demo_facets();
