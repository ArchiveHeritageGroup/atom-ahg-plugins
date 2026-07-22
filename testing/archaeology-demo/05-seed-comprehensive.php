<?php
/**
 * Comprehensive archaeology demonstration data.
 *
 * Extends the Kranskop Shelter set with two further sites so the instance has
 * enough volume and variety to exercise the things a single site cannot:
 * pagination, facet counts, multi-site browse, creator authorities, date ranges,
 * publication status, and cross-site phase comparison.
 *
 * Adds, per site: an excavation archive, trenches, stratigraphic contexts,
 * finds and samples, an accession, storage boxes, creation events with dated
 * ranges and named excavators, access points, and typed custom field values.
 *
 * ALL DATA IS FICTIONAL. None of these sites exist.
 *
 * Idempotent at site level: a site whose identifier already exists is skipped.
 *
 * Usage: 05-seed-comprehensive.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);

const ROOT_IO = 1;
const PUB_STATUS_TYPE = 158;
const PUB_PUBLISHED = 160;
const PUB_DRAFT = 159;
const CREATION_EVENT = 111;
const NAME_ACCESS_POINT = 161;
const HAS_PHYSICAL_OBJECT = 147;
const BOX_TYPE = 223;
const CORPORATE_BODY = 131;
const PERSON = 132;

function now(): string { return date('Y-m-d H:i:s'); }

function slugify(string $s): string {
    return substr(trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($s)), '-'), 0, 240);
}

function uniqueSlug(string $base): string {
    $slug = $base; $i = 2;
    while (DB::table('slug')->where('slug', $slug)->exists()) { $slug = $base . '-' . $i++; }
    return $slug;
}

function newObject(string $class): int {
    return DB::table('object')->insertGetId(['class_name' => $class, 'created_at' => now(), 'updated_at' => now()]);
}

function termId(string $taxonomyName, string $termName): ?int {
    return DB::table('term as t')
        ->join('term_i18n as ti', function ($j) { $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', 'en'); })
        ->join('taxonomy_i18n as tx', function ($j) { $j->on('tx.id', '=', 't.taxonomy_id')->where('tx.culture', '=', 'en'); })
        ->where('tx.name', $taxonomyName)->where('ti.name', $termName)->value('t.id');
}

function levelId(string $name): ?int {
    return DB::table('term as t')->join('term_i18n as ti', function ($j) { $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', 'en'); })
        ->where('t.taxonomy_id', 34)->where('ti.name', $name)->value('t.id');
}

function actorId(string $name, string $history = '', int $entityType = PERSON): int {
    $existing = DB::table('actor_i18n')->where('authorized_form_of_name', $name)->value('id');
    if ($existing) { return (int) $existing; }
    $id = newObject('QubitActor');
    DB::table('actor')->insert(['id' => $id, 'parent_id' => null, 'entity_type_id' => $entityType, 'source_culture' => 'en']);
    DB::table('actor_i18n')->insert(['id' => $id, 'culture' => 'en', 'authorized_form_of_name' => $name, 'history' => $history]);
    DB::table('slug')->insert(['object_id' => $id, 'slug' => uniqueSlug(slugify($name))]);
    return $id;
}

/** Create a description. */
function description(array $d): int {
    $id = newObject('QubitInformationObject');
    DB::table('information_object')->insert([
        'id' => $id,
        'parent_id' => $d['parent'] ?? ROOT_IO,
        'identifier' => $d['identifier'] ?? null,
        'level_of_description_id' => $d['level'] ?? null,
        'repository_id' => $d['repository'] ?? null,
        'source_culture' => 'en',
        'lft' => 0, 'rgt' => 0,
    ]);
    DB::table('status')->insert([
        'object_id' => $id, 'type_id' => PUB_STATUS_TYPE,
        'status_id' => $d['draft'] ?? false ? PUB_DRAFT : PUB_PUBLISHED, 'serial_number' => 0,
    ]);
    DB::table('information_object_i18n')->insert(array_filter([
        'id' => $id, 'culture' => 'en',
        'title' => $d['title'],
        'scope_and_content' => $d['scope'] ?? null,
        'extent_and_medium' => $d['extent'] ?? null,
        'physical_characteristics' => $d['physical'] ?? null,
        'archival_history' => $d['history'] ?? null,
        'acquisition' => $d['acquisition'] ?? null,
        'access_conditions' => $d['access'] ?? null,
    ], static fn ($v) => null !== $v));
    DB::table('slug')->insert(['object_id' => $id, 'slug' => uniqueSlug(slugify(($d['identifier'] ?? '') . ' ' . $d['title']))]);

    foreach (array_filter($d['terms'] ?? []) as $t) {
        $rel = newObject('QubitObjectTermRelation');
        DB::table('object_term_relation')->insert(['id' => $rel, 'object_id' => $id, 'term_id' => $t]);
    }

    // Creation event: dated range plus the excavator, so date facets and
    // creator authorities have something to work with.
    if (!empty($d['event'])) {
        [$display, $start, $end, $actor] = $d['event'];
        $eid = newObject('QubitEvent');
        DB::table('event')->insert([
            'id' => $eid, 'object_id' => $id, 'type_id' => CREATION_EVENT,
            'actor_id' => $actor, 'start_date' => $start, 'end_date' => $end, 'source_culture' => 'en',
        ]);
        DB::table('event_i18n')->insert(['id' => $eid, 'culture' => 'en', 'date' => $display]);
    }

    foreach (array_filter($d['nameAccessPoints'] ?? []) as $aid) {
        if (DB::table('event')->where('object_id', $id)->where('actor_id', $aid)->exists()) { continue; }
        $rel = newObject('QubitRelation');
        DB::table('relation')->insert([
            'id' => $rel, 'subject_id' => $id, 'object_id' => $aid,
            'type_id' => NAME_ACCESS_POINT, 'source_culture' => 'en',
        ]);
    }

    return $id;
}

function customValue(int $objectId, string $fieldKey, $value): void {
    $def = DB::table('custom_field_definition')->where('field_key', $fieldKey)->first();
    if (!$def || null === $value || '' === $value) { return; }
    $row = ['field_definition_id' => $def->id, 'object_id' => $objectId, 'sequence' => 0,
            'created_at' => now(), 'updated_at' => now(),
            'value_text' => null, 'value_number' => null, 'value_date' => null,
            'value_boolean' => null, 'value_dropdown' => null];
    match ($def->field_type) {
        'number' => $row['value_number'] = $value,
        'dropdown' => $row['value_dropdown'] = $value,
        default => $row['value_text'] = $value,
    };
    DB::table('custom_field_value')->insert($row);
}

// ---------------------------------------------------------------------------
// The two additional sites
// ---------------------------------------------------------------------------
$sites = [
    [
        'id' => 'MTK', 'title' => "Mothibi's Kraal",
        'scope' => 'DEMONSTRATION DATA - this site is fictional. A Late Iron Age settlement with stone-walled enclosures, excavated over three seasons. Held under SAHRA permit (National Heritage Resources Act 25 of 1999, section 35).',
        'permit' => 'SAHRA/DEMO/2024/017',
        'director' => "Dr Naledi Mokoena",
        'directorHistory' => 'Archaeologist specialising in Late Iron Age settlement patterns. Excavation director, seasons 1-3.',
        'accession' => ['ACC-2024-017', '2024-11-08', '14 boxes of finds, 2 boxes of site records'],
        'phases' => ['Phase 2 - Early Iron Age', 'Phase 3 - historical'],
        'trenches' => [
            ['A', 'Enclosure 1, central', [
                ['3001', 'Occupation floor', 'Occupation floor', 'Phase 2 - Early Iron Age'],
                ['3002', 'Ash midden', 'Layer', 'Phase 2 - Early Iron Age'],
                ['3003', 'Cattle byre fill', 'Fill', 'Phase 2 - Early Iron Age'],
            ]],
            ['B', 'Enclosure 3, entrance', [
                ['3101', 'Stone wall collapse', 'Layer', 'Phase 3 - historical'],
                ['3102', 'Threshold deposit', 'Layer', 'Phase 2 - Early Iron Age'],
            ]],
            ['C', 'Midden, south slope', [
                ['3201', 'Upper midden', 'Layer', 'Phase 3 - historical'],
                ['3202', 'Lower midden', 'Layer', 'Phase 2 - Early Iron Age'],
            ]],
        ],
    ],
    [
        'id' => 'BBF', 'title' => 'Blaauwbosch Farm',
        'scope' => 'DEMONSTRATION DATA - this site is fictional. A nineteenth-century farmstead with associated labourers\' quarters. Rescue excavation ahead of development.',
        'permit' => 'SAHRA/DEMO/2025/044',
        'director' => 'Prof. Andile Dlamini',
        'directorHistory' => 'Historical archaeologist. Directed the Blaauwbosch rescue excavation.',
        'accession' => ['ACC-2025-044', '2025-06-30', '9 boxes of finds, 1 box of site records'],
        'phases' => ['Phase 3 - historical'],
        'trenches' => [
            ['1', 'Farmhouse, kitchen wing', [
                ['4001', 'Demolition rubble', 'Layer', 'Phase 3 - historical'],
                ['4002', 'Kitchen floor', 'Occupation floor', 'Phase 3 - historical'],
                ['4003', 'Hearth', 'Hearth', 'Phase 3 - historical'],
            ]],
            ['2', "Labourers' quarters", [
                ['4101', 'Floor deposit', 'Occupation floor', 'Phase 3 - historical'],
                ['4102', 'Refuse pit fill', 'Fill', 'Phase 3 - historical'],
            ]],
        ],
    ],
];

// Find templates - drawn per context so the assemblage varies realistically.
$findTypes = [
    ['Potsherds', 'Potsherd', 'Ceramic', null, 'sherds',
     ['ceramic_sherd_count' => null, 'fabric' => 'quartz_tempered', 'firing_atmosphere' => 'oxidised_reduced_core',
      'surface_treatment' => 'burnished', 'wall_thickness_mm' => 7]],
    ['Glass beads', 'Bead', 'Glass', 'Series A - Indo-Pacific drawn', 'beads',
     ['diameter_min_mm' => 3, 'diameter_max_mm' => 4.5, 'perforation_mm' => 1.2,
      'manufacture_technique' => 'drawn', 'opacity' => 'opaque', 'bead_series' => 'series_a', 'colour' => 'blue']],
    ['Ostrich eggshell beads', 'Ostrich eggshell fragment', 'Shell', 'Ostrich eggshell', 'beads',
     ['diameter_min_mm' => 4, 'diameter_max_mm' => 6, 'perforation_mm' => 2,
      'manufacture_technique' => 'ground', 'bead_series' => 'oes', 'colour' => 'cream']],
    ['Iron slag', 'Slag', 'Iron', null, 'lumps', ['weight_g' => 610]],
    ['Grindstone fragment', 'Grindstone', 'Stone', null, 'cobble',
     ['lithic_type' => 'grindstone', 'raw_material' => 'quartzite', 'length_mm' => 220, 'width_mm' => 160, 'thickness_mm' => 60]],
    ['Stone flakes', 'Tool', 'Stone', null, 'lumps',
     ['lithic_type' => 'flake', 'raw_material' => 'chert', 'cortex' => '1_25']],
    ['Bone fragments', 'Bone tool', 'Bone', null, 'lumps', ['weight_g' => 240]],
    ['Copper ornament', 'Metal fragment', 'Copper alloy', null, 'figurine',
     ['manufacture_technique' => 'cast', 'length_mm' => 48, 'weight_g' => 12]],
    ['Glass bottle fragments', 'Vessel', 'Glass', null, 'sherds',
     ['manufacture_technique' => 'moulded', 'opacity' => 'translucent', 'colour' => 'green']],
    ['Clay pipe fragments', 'Potsherd', 'Ceramic', null, 'sherds',
     ['fabric' => 'fine_untempered', 'firing_atmosphere' => 'oxidised']],
];

if (!$apply) {
    $n = 0;
    foreach ($sites as $s) { foreach ($s['trenches'] as $t) { $n += count($t[2]); } }
    printf("  sites to add    : %d\n  contexts        : %d\n  finds (approx)  : %d\n",
        count($sites), $n, $n * 3);
    echo "\n  DRY RUN - re-run with --apply\n";
    exit(0);
}

$repoId = DB::table('repository')->value('id');
$lvl = ['Fonds' => levelId('Fonds'), 'Series' => levelId('Series'), 'Subseries' => levelId('Subseries'),
        'File' => levelId('File'), 'Item' => levelId('Item')];
foreach ($lvl as $k => $v) { if (!$v) { exit("  missing level term: {$k}\n"); } }

$counts = ['sites' => 0, 'descriptions' => 0, 'finds' => 0, 'actors' => 0, 'boxes' => 0, 'values' => 0, 'events' => 0];

DB::connection()->beginTransaction();
try {
    // Specialists appear as name access points across several sites.
    $specialists = [
        actorId('Dr Thandiwe Nkosi', 'Ceramic specialist. Analysed the Iron Age assemblages.'),
        actorId('Dr Pieter van Wyk', 'Faunal analyst.'),
        actorId('Ms Refilwe Sithole', 'Bead and glass specialist.'),
    ];
    $counts['actors'] += 3;

    foreach ($sites as $site) {
        if (DB::table('information_object')->where('identifier', $site['id'])->exists()) {
            printf("  %s already present, skipped\n", $site['id']);
            continue;
        }

        $director = actorId($site['director'], $site['directorHistory']);
        $counts['actors']++;

        $siteId = description([
            'identifier' => $site['id'], 'title' => $site['title'], 'level' => $lvl['Series'],
            'repository' => $repoId, 'scope' => $site['scope'],
            'history' => 'Excavated by the University of the Witwatersrand. Permit reference: ' . $site['permit'] . '.',
            'event' => [substr($site['accession'][1], 0, 4), $site['accession'][1], $site['accession'][1], $director],
            'nameAccessPoints' => $specialists,
        ]);
        $counts['sites']++; $counts['descriptions']++; $counts['events']++;

        $findSeq = 100;
        $boxSeq = 1;
        foreach ($site['trenches'] as [$tName, $tDesc, $contexts]) {
            $trenchId = description([
                'identifier' => $site['id'] . '-T' . $tName, 'title' => 'Trench ' . $tName,
                'level' => $lvl['Subseries'], 'parent' => $siteId, 'repository' => $repoId, 'scope' => $tDesc . '.',
            ]);
            $counts['descriptions']++;

            // one box per trench
            $poId = newObject('QubitPhysicalObject');
            DB::table('physical_object')->insert(['id' => $poId, 'type_id' => BOX_TYPE, 'source_culture' => 'en']);
            $boxName = $site['id'] . '/BOX/' . str_pad((string) $boxSeq++, 2, '0', STR_PAD_LEFT);
            DB::table('physical_object_i18n')->insert(['id' => $poId, 'culture' => 'en',
                'name' => $boxName, 'location' => 'Repository store, bay ' . (1 + ($counts['boxes'] % 5))]);
            DB::table('slug')->insert(['object_id' => $poId, 'slug' => uniqueSlug(slugify($boxName))]);
            $counts['boxes']++;

            foreach ($contexts as [$ctxNum, $ctxTitle, $ctxType, $phase]) {
                $ctxId = description([
                    'identifier' => $site['id'] . '-' . $ctxNum,
                    'title' => 'Context ' . $ctxNum . ' - ' . strtolower($ctxTitle),
                    'level' => $lvl['File'], 'parent' => $trenchId, 'repository' => $repoId,
                    'scope' => ucfirst($ctxTitle) . '. Recorded on a single-context sheet.',
                    'terms' => [termId('Context type', $ctxType), termId('Phase', $phase)],
                ]);
                $counts['descriptions']++;

                // 3 finds per context, rotating through the templates
                for ($k = 0; $k < 3; $k++) {
                    $t = $findTypes[($findSeq + $k) % count($findTypes)];
                    [$label, $objType, $material, $series, , $fields] = $t;
                    $count = 3 + (($findSeq + $k) % 28);
                    $ident = sprintf('%s-SF%03d', $site['id'], $findSeq + $k);

                    $findId = description([
                        'identifier' => $ident,
                        'title' => $label . ', small find ' . ($findSeq + $k),
                        'level' => $lvl['Item'], 'parent' => $ctxId, 'repository' => $repoId,
                        'extent' => $count . ' items. ' . $label . '.',
                        'physical' => 'Stable. Recorded during post-excavation processing.',
                        'terms' => [termId('Object type', $objType), termId('Material', $material),
                                    $series ? termId('Bead series', $series) : null,
                                    termId('Cultural sensitivity', 'Open access'),
                                    termId('Rights statement', 'Attribution (CC BY)')],
                        'nameAccessPoints' => [$specialists[($findSeq + $k) % 3]],
                    ]);
                    $counts['descriptions']++; $counts['finds']++;

                    foreach ($fields as $fk => $fv) {
                        customValue($findId, $fk, null === $fv ? $count : $fv);
                        $counts['values']++;
                    }
                    customValue($findId, 'count', $count);
                    $counts['values']++;

                    // storage link
                    $rel = newObject('QubitRelation');
                    DB::table('relation')->insert(['id' => $rel, 'subject_id' => $findId, 'object_id' => $poId,
                        'type_id' => HAS_PHYSICAL_OBJECT, 'source_culture' => 'en']);
                    DB::table('information_object_physical_location')->insert([
                        'information_object_id' => $findId, 'physical_object_id' => $poId,
                        'box_number' => $boxName, 'position' => (string) (($findSeq + $k) % 20 + 1),
                        'barcode' => 'WITS-' . str_replace('/', '', $boxName) . '-' . ($findSeq + $k),
                        'extent_value' => '1', 'extent_unit' => 'bag',
                        'condition_status' => 'stable', 'access_status' => 'available',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $findSeq += 3;
            }
        }

        // site archive
        $archId = description([
            'identifier' => $site['id'] . '-ARCH', 'title' => $site['title'] . ' excavation archive',
            'level' => $lvl['Fonds'], 'repository' => $repoId,
            'scope' => 'DEMONSTRATION DATA. Documentary archive generated by the excavation. Linked to the excavation record by site and context identifier.',
            'acquisition' => 'Deposited by the excavation director at the close of the final season.',
            'event' => [substr($site['accession'][1], 0, 4), $site['accession'][1], $site['accession'][1], $director],
        ]);
        $counts['descriptions']++; $counts['events']++;

        foreach ([['1', 'Context sheets', 'single-context recording sheets'],
                  ['2', 'Field notebooks', 'daily site notebooks'],
                  ['3', 'Section drawings and plans', 'measured drawings at 1:20'],
                  ['4', 'Site photography', 'digital site and context photography'],
                  ['5', 'Specialist reports', 'ceramic, faunal and bead analyses']] as [$n, $t, $d]) {
            $sid = description([
                'identifier' => $site['id'] . '-ARCH-' . $n, 'title' => $t, 'level' => $lvl['Series'],
                'parent' => $archId, 'repository' => $repoId, 'scope' => ucfirst($d) . '.',
            ]);
            $counts['descriptions']++;
            // one file per series, and mark the specialist reports draft to
            // demonstrate publication status in browse
            description([
                'identifier' => $site['id'] . '-ARCH-' . $n . '/1', 'title' => $t . ', season 1',
                'level' => $lvl['File'], 'parent' => $sid, 'repository' => $repoId,
                'extent' => (10 + (int) $n * 7) . ' items',
                'draft' => ('5' === $n),
                'access' => ('5' === $n) ? 'Embargoed until publication of the final report.' : null,
            ]);
            $counts['descriptions']++;
        }

        // accession
        [$accIdent, $accDate, $accExtent] = $site['accession'];
        $accId = newObject('QubitAccession');
        DB::table('accession')->insert(['id' => $accId, 'identifier' => $accIdent, 'date' => $accDate,
            'source_culture' => 'en', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('accession_i18n')->insert(['id' => $accId, 'culture' => 'en',
            'title' => $site['title'] . ', excavated assemblage and archive',
            'scope_and_content' => 'DEMONSTRATION DATA. Transferred under SAHRA permit ' . $site['permit'] . '.',
            'received_extent_units' => $accExtent]);
        DB::table('slug')->insert(['object_id' => $accId, 'slug' => uniqueSlug(slugify($accIdent . ' ' . $site['title']))]);
    }

    DB::connection()->commit();
    foreach ($counts as $k => $v) { printf("  %-14s %d\n", $k, $v); }
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo '  ROLLED BACK: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
