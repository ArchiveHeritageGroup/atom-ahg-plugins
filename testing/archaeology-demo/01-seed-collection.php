<?php
/**
 * Seed a demonstration archaeological collection on the archaeology instance.
 *
 * Models the structure proposed in the Wits discussion document:
 *
 *   Site -> Trench -> Context -> Find / Sample     (the excavation record)
 *   Fonds -> Series -> File                        (the site archive)
 *
 * joined on the site and context identifier, plus the controlled vocabularies
 * that make the assemblage analysable rather than merely inventoried.
 *
 * Object type is modelled as a first-class facet, matching how museum platforms
 * (eHive and similar) actually surface archaeological material, while context
 * type and bead series supply the excavation axis those platforms lack.
 *
 * ALL DATA IS FICTIONAL. The site does not exist.
 *
 * Usage: seed_archeology_demo.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);

const LOD_TAXONOMY = 34;   // Level of description
const ROOT_IO = 1;

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));

    return substr($s, 0, 250);
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

function newObject(string $class): int
{
    return DB::table('object')->insertGetId([
        'class_name' => $class, 'created_at' => now(), 'updated_at' => now(),
    ]);
}

function addSlug(int $id, string $text): void
{
    DB::table('slug')->insert(['object_id' => $id, 'slug' => uniqueSlug(slugify($text))]);
}

/** Create a taxonomy and return its id. */
function taxonomy(string $name, string $note = ''): int
{
    $existing = DB::table('taxonomy_i18n')->where('name', $name)->value('id');
    if ($existing) {
        return (int) $existing;
    }
    $id = newObject('QubitTaxonomy');
    DB::table('taxonomy')->insert(['id' => $id, 'parent_id' => 30, 'source_culture' => 'en']);
    DB::table('taxonomy_i18n')->insert(['id' => $id, 'culture' => 'en', 'name' => $name, 'note' => $note]);
    addSlug($id, $name);

    return $id;
}

/** Create a term in a taxonomy and return its id. */
function term(int $taxonomyId, string $name): int
{
    $existing = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
        ->where('t.taxonomy_id', $taxonomyId)->where('ti.name', $name)->value('t.id');
    if ($existing) {
        return (int) $existing;
    }
    $id = newObject('QubitTerm');
    DB::table('term')->insert(['id' => $id, 'taxonomy_id' => $taxonomyId, 'source_culture' => 'en']);
    DB::table('term_i18n')->insert(['id' => $id, 'culture' => 'en', 'name' => $name]);
    addSlug($id, $name);

    return $id;
}

/** Create an information object; returns its id. */
function description(array $d): int
{
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
    // Publication status lives in `status`, not on information_object.
    // type 158 = publication status, 160 = published.
    DB::table('status')->insert([
        'object_id' => $id, 'type_id' => 158, 'status_id' => 160, 'serial_number' => 0,
    ]);
    DB::table('information_object_i18n')->insert(array_filter([
        'id' => $id, 'culture' => 'en',
        'title' => $d['title'],
        'scope_and_content' => $d['scope'] ?? null,
        'extent_and_medium' => $d['extent'] ?? null,
        'physical_characteristics' => $d['physical'] ?? null,
        'archival_history' => $d['history'] ?? null,
        'acquisition' => $d['acquisition'] ?? null,
    ], static fn ($v) => null !== $v));
    addSlug($id, $d['identifier'] ? $d['identifier'] . ' ' . $d['title'] : $d['title']);

    // Object-term relations (object type, material, context type, bead series)
    foreach ($d['terms'] ?? [] as $termId) {
        $rel = newObject('QubitObjectTermRelation');
        DB::table('object_term_relation')->insert(['id' => $rel, 'object_id' => $id, 'term_id' => $termId]);
    }

    return $id;
}

if (!$apply) {
    echo "  DRY RUN - re-run with --apply\n";
    exit(0);
}

DB::connection()->beginTransaction();

try {
    // ---------------------------------------------------------------- levels
    // The excavation hierarchy the document proposes. Added to AtoM's existing
    // level-of-description taxonomy so they sit alongside Fonds/Series/File,
    // which the site archive still uses.
    $lvl = [];
    foreach (['Site', 'Trench', 'Context', 'Find', 'Sample'] as $n) {
        $lvl[$n] = term(LOD_TAXONOMY, $n);
    }
    $fonds = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
        ->where('t.taxonomy_id', LOD_TAXONOMY)->where('ti.name', 'Fonds')->value('t.id') ?: term(LOD_TAXONOMY, 'Fonds');
    $series = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
        ->where('t.taxonomy_id', LOD_TAXONOMY)->where('ti.name', 'Series')->value('t.id') ?: term(LOD_TAXONOMY, 'Series');
    $file = DB::table('term as t')->join('term_i18n as ti', 'ti.id', '=', 't.id')
        ->where('t.taxonomy_id', LOD_TAXONOMY)->where('ti.name', 'File')->value('t.id') ?: term(LOD_TAXONOMY, 'File');

    // ---------------------------------------------------- controlled vocabularies
    // Object type is the workhorse facet in museum platforms; context type and
    // bead series are the archaeological axis those platforms do not provide.
    $taxObjectType = taxonomy('Object type', 'Primary object-name facet. Align to Getty AAT or Nomenclature before production use.');
    $taxMaterial = taxonomy('Material', 'Physical material of the find.');
    $taxContextType = taxonomy('Context type', 'Stratigraphic character of an excavated context.');
    $taxBeadSeries = taxonomy('Bead series', 'Typological series for southern African glass trade beads. Placeholder terms - to be replaced with the series Wits uses.');

    $ot = [];
    foreach (['Bead', 'Potsherd', 'Vessel', 'Tool', 'Worked stone', 'Grindstone',
        'Ostrich eggshell fragment', 'Bone tool', 'Metal fragment', 'Slag',
        'Architectural fragment', 'Figurine', ] as $n) {
        $ot[$n] = term($taxObjectType, $n);
    }
    $mat = [];
    foreach (['Glass', 'Ceramic', 'Stone', 'Bone', 'Shell', 'Iron', 'Copper alloy', 'Charcoal'] as $n) {
        $mat[$n] = term($taxMaterial, $n);
    }
    $ctx = [];
    foreach (['Layer', 'Fill', 'Cut', 'Feature', 'Hearth', 'Occupation floor', 'Burial'] as $n) {
        $ctx[$n] = term($taxContextType, $n);
    }
    $bs = [];
    foreach (['Series A - Indo-Pacific drawn', 'Series B - wound', 'Series C - moulded', 'Unclassified'] as $n) {
        $bs[$n] = term($taxBeadSeries, $n);
    }

    // Phase is deliberately a VOCABULARY, not a hierarchy level.
    //
    // Site/trench/context is a spatial hierarchy: a context physically sits
    // inside one trench. Phase is an interpretive grouping that cuts ACROSS it -
    // a context in Trench 1 and another in Trench 2 routinely belong to the same
    // phase. Modelled as a tree level it could not express that, because each
    // context already has exactly one parent. As a term it can, and phasing can
    // be revised after excavation without restructuring the archive.
    $taxPhase = taxonomy('Phase', 'Interpretive chronological phase. Cuts across the spatial hierarchy, so it is applied as a term rather than a level.');

    // Axes observed in eHive's live archaeology facets (14,765 objects), added
    // here because they are what a working aggregation actually needs.
    //
    // Cultural sensitivity: eHive shows 14,762 of 14,765 objects "Not assessed".
    // That is the argument for screening BEFORE ingest rather than after - at
    // scale, "later" never arrives.
    $taxSensitivity = taxonomy('Cultural sensitivity', 'Screening status for culturally sensitive material, including ancestral remains and restricted knowledge. Assess before publication, not after.');
    $sens = [];
    foreach (['Not assessed', 'Open access', 'Awareness required', 'Restricted - consult community'] as $n) {
        $sens[$n] = term($taxSensitivity, $n);
    }

    // Rights: eHive shows 13,183 of 14,765 as "All rights reserved" - the default
    // is closed unless someone decides otherwise. Worth setting deliberately.
    $taxRights = taxonomy('Rights statement', 'RightsStatements.org / Creative Commons statement applied to the record and its images.');
    $rights = [];
    foreach (['All rights reserved', 'Attribution (CC BY)', 'Attribution - Non-commercial (CC BY-NC)',
        'Public domain', 'Rights status unknown', ] as $n) {
        $rights[$n] = term($taxRights, $n);
    }
    $ph = [];
    foreach (['Phase 1 - Later Stone Age occupation', 'Phase 2 - Early Iron Age',
        'Phase 3 - historical', 'Unphased', ] as $n) {
        $ph[$n] = term($taxPhase, $n);
    }

    // ---------------------------------------------------------------- repository
    // entity_type_id 131 = corporate body (132 is PERSON - a repository typed as
    // a person renders wrongly and breaks authority-record behaviour).
    $repoId = newObject('QubitRepository');
    // parent_id MUST be the actor root (not null): a null-parent repository is
    // treated as "the root" by the repository view action and 404s its own page.
    $actorRootId = (int) (DB::table('actor')->whereNull('parent_id')->min('id') ?: 3);
    DB::table('actor')->insert([
        'id' => $repoId, 'parent_id' => $actorRootId, 'entity_type_id' => 131, 'source_culture' => 'en',
    ]);
    DB::table('actor_i18n')->insert([
        'id' => $repoId, 'culture' => 'en',
        'authorized_form_of_name' => 'University of the Witwatersrand - Archaeology',
        'history' => 'School of Geography, Archaeology and Environmental Studies. Holds excavated archaeological material under permit from the South African Heritage Resources Agency.',
    ]);
    DB::table('repository')->insert(['id' => $repoId, 'identifier' => 'WITS-ARCH', 'source_culture' => 'en']);
    addSlug($repoId, 'University of the Witwatersrand Archaeology');

    // ------------------------------------------------------- excavation record
    $site = description([
        'identifier' => 'KRK', 'title' => 'Kranskop Shelter', 'level' => $lvl['Site'], 'repository' => $repoId,
        'scope' => 'DEMONSTRATION DATA - this site is fictional. A rock shelter with Later Stone Age and Iron Age occupation, excavated over two seasons. Held under SAHRA permit (National Heritage Resources Act 25 of 1999, section 35).',
        'history' => 'Excavated by the University of the Witwatersrand. Permit reference: SAHRA/DEMO/2026/001.',
    ]);

    $t1 = description(['identifier' => 'KRK-T1', 'title' => 'Trench 1', 'level' => $lvl['Trench'], 'parent' => $site, 'repository' => $repoId,
        'scope' => 'Two by two metre trench against the north wall of the shelter.']);
    $t2 = description(['identifier' => 'KRK-T2', 'title' => 'Trench 2', 'level' => $lvl['Trench'], 'parent' => $site, 'repository' => $repoId,
        'scope' => 'One by two metre extension to the east, opened in the second season.']);

    $c1001 = description(['identifier' => 'KRK-1001', 'title' => 'Context 1001 - upper ashy layer', 'level' => $lvl['Context'], 'parent' => $t1, 'repository' => $repoId,
        'terms' => [$ctx['Layer'], $ph['Phase 3 - historical']], 'scope' => 'Loose grey ash with charcoal inclusions. Overlies 1002.']);
    $c1002 = description(['identifier' => 'KRK-1002', 'title' => 'Context 1002 - occupation floor', 'level' => $lvl['Context'], 'parent' => $t1, 'repository' => $repoId,
        'terms' => [$ctx['Occupation floor'], $ph['Phase 2 - Early Iron Age']], 'scope' => 'Compacted surface with in-situ hearth. Sealed by 1001.']);
    $c2001 = description(['identifier' => 'KRK-2001', 'title' => 'Context 2001 - hearth fill', 'level' => $lvl['Context'], 'parent' => $t2, 'repository' => $repoId,
        'terms' => [$ctx['Hearth'], $ctx['Fill'], $ph['Phase 2 - Early Iron Age']], 'scope' => 'Charcoal-rich fill within a shallow cut. Sampled for radiocarbon dating.']);

    $finds = [
        ['KRK-SF214', 'Glass beads, small find 214', $c1001, [$ot['Bead'], $mat['Glass'], $bs['Series A - Indo-Pacific drawn']],
            '14 drawn glass beads, blue and blue-green.', 'Diameter 3-4 mm. Drawn, heat-rounded.'],
        ['KRK-SF215', 'Potsherds, small find 215', $c1001, [$ot['Potsherd'], $mat['Ceramic']],
            '23 body sherds, one decorated rim.', 'Sandy fabric, burnished exterior. Comb-stamped decoration on rim sherd.'],
        ['KRK-SF221', 'Glass bead assemblage, small find 221', $c1002, [$ot['Bead'], $mat['Glass'], $bs['Series B - wound']],
            '6 wound glass beads, yellow and green.', 'Diameter 5-7 mm. Wound, with visible spiral seam.'],
        ['KRK-SF224', 'Grindstone fragment, small find 224', $c1002, [$ot['Grindstone'], $mat['Stone']],
            'Lower grindstone, broken.', 'Coarse quartzite. Ground working surface, heavily worn.'],
        ['KRK-SF230', 'Clay figurine fragment, small find 230', $c2001, [$ot['Figurine'], $mat['Ceramic']],
            'Torso fragment of a modelled clay figurine.', 'Fine untempered clay. Surviving height 42 mm.'],
        ['KRK-SF231', 'Iron slag, small find 231', $c2001, [$ot['Slag'], $mat['Iron']],
            'Three fragments of tap slag.', 'Dense, vitrified upper surface.'],
        ['KRK-SF238', 'Ostrich eggshell beads, small find 238', $c1002, [$ot['Ostrich eggshell fragment'], $mat['Shell']],
            '31 ostrich eggshell beads and 4 blanks.', 'Diameter 4-6 mm, various stages of manufacture.'],
    ];
    $findIds = [];
    foreach ($finds as [$ident, $title, $parent, $terms, $extent, $physical]) {
        $findIds[] = description(['identifier' => $ident, 'title' => $title, 'level' => $lvl['Find'],
            'parent' => $parent, 'repository' => $repoId, 'terms' => $terms,
            'extent' => $extent, 'physical' => $physical]);
    }

    $samples = [
        ['KRK-S07', 'Charcoal sample 07', $c2001, [$mat['Charcoal']], 'Bulk charcoal from hearth fill, submitted for radiocarbon dating.'],
        ['KRK-S08', 'Sediment sample 08', $c1002, [], 'Bulk sediment from occupation floor, retained for micromorphology.'],
    ];
    foreach ($samples as [$ident, $title, $parent, $terms, $extent]) {
        description(['identifier' => $ident, 'title' => $title, 'level' => $lvl['Sample'],
            'parent' => $parent, 'repository' => $repoId, 'terms' => $terms, 'extent' => $extent]);
    }

    // ------------------------------------------------------------- site archive
    // Described archivally, because these ARE records - they accumulate through
    // the activity of excavating and they have a creator.
    $arch = description(['identifier' => 'KRK-ARCH', 'title' => 'Kranskop Shelter excavation archive', 'level' => $fonds, 'repository' => $repoId,
        'scope' => 'DEMONSTRATION DATA. The documentary archive generated by the excavation of Kranskop Shelter. Linked to the excavation record by site and context identifier.',
        'acquisition' => 'Deposited by the excavation director at the close of the second season.']);

    $sheets = description(['identifier' => 'KRK-ARCH-1', 'title' => 'Context sheets', 'level' => $series, 'parent' => $arch, 'repository' => $repoId,
        'scope' => 'Single-context recording sheets, one per stratigraphic unit.']);
    description(['identifier' => 'KRK-ARCH-1/1', 'title' => 'Context sheets, Trench 1', 'level' => $file, 'parent' => $sheets, 'repository' => $repoId,
        'extent' => '18 sheets']);
    description(['identifier' => 'KRK-ARCH-1/2', 'title' => 'Context sheets, Trench 2', 'level' => $file, 'parent' => $sheets, 'repository' => $repoId,
        'extent' => '11 sheets']);

    $nb = description(['identifier' => 'KRK-ARCH-2', 'title' => 'Field notebooks', 'level' => $series, 'parent' => $arch, 'repository' => $repoId,
        'scope' => 'Daily site notebooks kept by the excavation director.']);
    description(['identifier' => 'KRK-ARCH-2/1', 'title' => 'Field notebook, season 1', 'level' => $file, 'parent' => $nb, 'repository' => $repoId,
        'extent' => '1 volume, 96 pages']);

    $dr = description(['identifier' => 'KRK-ARCH-3', 'title' => 'Section drawings and plans', 'level' => $series, 'parent' => $arch, 'repository' => $repoId,
        'scope' => 'Measured section drawings and trench plans at 1:20.']);
    description(['identifier' => 'KRK-ARCH-3/1', 'title' => 'Trench 1 north-facing section', 'level' => $file, 'parent' => $dr, 'repository' => $repoId,
        'extent' => '1 drawing, permatrace']);

    $phot = description(['identifier' => 'KRK-ARCH-4', 'title' => 'Site photography', 'level' => $series, 'parent' => $arch, 'repository' => $repoId,
        'scope' => 'Digital site photography, including context and working shots.']);
    description(['identifier' => 'KRK-ARCH-4/1', 'title' => 'Trench 1 context photography', 'level' => $file, 'parent' => $phot, 'repository' => $repoId,
        'extent' => '214 digital images']);

    // ----------------------------------------------------------------- accession
    $accId = newObject('QubitAccession');
    // accession carries its own created_at/updated_at (no DB default).
    DB::table('accession')->insert([
        'id' => $accId, 'identifier' => 'ACC-2026-001', 'date' => '2026-03-15', 'source_culture' => 'en',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('accession_i18n')->insert([
        'id' => $accId, 'culture' => 'en',
        'title' => 'Kranskop Shelter, seasons 1 and 2',
        'scope_and_content' => 'DEMONSTRATION DATA. Excavated assemblage and site archive from Kranskop Shelter, transferred under SAHRA permit SAHRA/DEMO/2026/001.',
        'received_extent_units' => '9 boxes of finds, 1 box of site records',
    ]);
    addSlug($accId, 'ACC-2026-001 Kranskop Shelter');

    DB::connection()->commit();
    echo "  COMMITTED\n";
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo '  ROLLED BACK: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

printf("\n  descriptions : %d\n", DB::table('information_object')->count() - 1);
printf("  repositories : %d\n", DB::table('repository')->count());
printf("  accessions   : %d\n", DB::table('accession')->count());
printf("  taxonomies   : %d\n", DB::table('taxonomy')->count());
printf("  terms        : %d\n", DB::table('term')->count());

// Keep browse facets ("Narrow your results by:") current after this seed.
require __DIR__ . '/_refresh_facets.php';
refresh_demo_facets();
