<?php
/**
 * Seed demonstration researchers, their research permits, and a custom
 * Report Builder report over them.
 *
 * Three things the request asked for, which belong together: a permit is
 * captured against a researcher, so both must exist; and the custom report is
 * only meaningful once there is data for it to summarise.
 *
 *   research_researcher   - who is studying the collection
 *   naz_research_permit   - the permit under which they study it
 *   report_definition +   - a custom SQL report: permits by researcher, topic,
 *   report_query            status and validity
 *
 * ALL DATA IS FICTIONAL.
 *
 * Idempotent: seeded rows are keyed on stable identifiers and skipped if present.
 *
 * Usage: 08-researchers-permits-report.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);
$now = date('Y-m-d H:i:s');

// researcher_type ids seen on this install
$type = fn (string $name) => DB::table('research_researcher_type')->where('name', $name)->value('id');

$researchers = [
    [
        'email' => 'demo.mahlangu@example.ac.za',
        'title' => 'Ms', 'first' => 'Thabisa', 'last' => 'Mahlangu',
        'type' => 'Postgraduate Student', 'institution' => 'University of the Witwatersrand',
        'department' => 'Archaeology', 'position' => 'PhD candidate',
        'interests' => 'Southern African glass trade beads; Indian Ocean exchange networks.',
        'project' => 'Chronology of glass bead assemblages from Iron Age sites in Limpopo.',
    ],
    [
        'email' => 'demo.okafor@example.ac.za',
        'title' => 'Dr', 'first' => 'Chidi', 'last' => 'Okafor',
        'type' => 'Visiting Scholar', 'institution' => 'University of Ibadan',
        'department' => 'Archaeology and Anthropology', 'position' => 'Senior Lecturer',
        'interests' => 'Iron metallurgy and slag analysis in sub-Saharan Africa.',
        'project' => 'Comparative study of Iron Age smelting debris.',
    ],
    [
        'email' => 'demo.vanzyl@example.ac.za',
        'title' => 'Mr', 'first' => 'Willem', 'last' => 'van Zyl',
        'type' => 'Academic Staff', 'institution' => 'University of the Witwatersrand',
        'department' => 'Archaeology', 'position' => 'Curator',
        'interests' => 'Ceramic typology and fabric analysis.',
        'project' => 'Fabric-based seriation of Late Iron Age ceramics.',
    ],
];

// permit per researcher: [number, topic, purpose, start, end, status, access]
$permits = [
    ['SAHRA/RP/2026/031', 'Glass bead chronology, Kranskop and Mothibi\'s Kraal assemblages',
     'Doctoral research: typological and compositional study of glass beads to refine trade-contact dating.',
     '2026-02-01', '2026-12-31', 'approved', 'Kranskop Shelter; Mothibi\'s Kraal glass finds'],
    ['SAHRA/RP/2026/044', 'Iron slag characterisation from Iron Age contexts',
     'Visiting research: metallographic sampling of tap slag for smelting-technology comparison.',
     '2026-05-15', '2026-08-15', 'approved', 'Iron slag finds across all sites'],
    ['SAHRA/RP/2026/052', 'Ceramic fabric seriation, Blaauwbosch and Mothibi\'s Kraal',
     'Curatorial research: fabric and firing analysis of the ceramic assemblages.',
     '2026-06-01', '2027-05-31', 'pending', 'Ceramic finds, all sites'],
];

if (!$apply) {
    printf("  researchers to seed : %d\n  permits to seed     : %d\n  custom report       : 1 (Research permits)\n",
        count($researchers), count($permits));
    echo "\n  DRY RUN - re-run with --apply\n";
    exit(0);
}

DB::connection()->beginTransaction();
try {
    // ---- researchers ----
    $ids = [];
    foreach ($researchers as $r) {
        $existing = DB::table('research_researcher')->where('email', $r['email'])->value('id');
        if ($existing) { $ids[] = (int) $existing; continue; }
        $ids[] = DB::table('research_researcher')->insertGetId([
            'user_id' => 0, 'title' => $r['title'], 'first_name' => $r['first'], 'last_name' => $r['last'],
            'email' => $r['email'], 'affiliation_type' => 'academic',
            'institution' => $r['institution'], 'department' => $r['department'], 'position' => $r['position'],
            'research_interests' => $r['interests'], 'current_project' => $r['project'],
            'researcher_type_id' => $type($r['type']),
            'preferred_language' => 'en', 'timezone' => 'Africa/Johannesburg',
            'status' => 'approved', 'approved_at' => $now, 'expires_at' => '2027-12-31',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    // ---- permit-register researchers (naz_research_permit.researcher_id is
    //      FK-bound to naz_researcher, so a register entry is created per person,
    //      mirroring the research_researcher record by email) ----
    $regIds = [];
    foreach ($researchers as $j => $r) {
        $existing = DB::table('naz_researcher')->where('email', $r['email'])->value('id');
        if ($existing) { $regIds[$j] = (int) $existing; continue; }
        $regIds[$j] = DB::table('naz_researcher')->insertGetId([
            'first_name' => $r['first'], 'last_name' => $r['last'], 'email' => $r['email'],
            'institution' => $r['institution'], 'registration_date' => $now,
            'created_by' => 448, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    // ---- permits (one per researcher) ----
    $permitCount = 0;
    foreach ($permits as $i => $p) {
        [$num, $topic, $purpose, $start, $end, $status, $access] = $p;
        if (DB::table('naz_research_permit')->where('permit_number', $num)->exists()) { continue; }
        DB::table('naz_research_permit')->insert([
            'permit_number' => $num, 'researcher_id' => $regIds[$i],
            'permit_type' => 'research_access', 'research_topic' => $topic, 'research_purpose' => $purpose,
            'start_date' => $start, 'end_date' => $end, 'status' => $status,
            'collections_access' => json_encode(array_map('trim', explode(';', $access))), 'fee_amount' => 0, 'fee_currency' => 'ZAR', 'fee_paid' => 0,
            'created_by' => 448, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $permitCount++;
    }

    // ---- enable Report Builder ----
    DB::table('atom_plugin')->where('name', 'ahgReportBuilderPlugin')->update(['is_enabled' => 1]);

    // ---- custom report: Research permits ----
    $reportCode = 'demo_research_permits';
    $reportId = DB::table('report_definition')->where('code', $reportCode)->value('id');
    if (!$reportId) {
        $reportId = DB::table('report_definition')->insertGetId([
            'code' => $reportCode,
            'name' => 'Research permits',
            'description' => 'Active and pending research permits, the researcher, topic, validity and collections access. Custom SQL report.',
            'category' => 'research',
            'sector' => 'archive,museum',
            'report_class' => '',                 // empty = custom SQL query report, not a built-in class
            'parameters' => json_encode(new stdClass()),
            'output_formats' => 'html,pdf,csv,xlsx',
            'is_active' => 1, 'sort_order' => 500,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    // The SQL the report runs. Read-only SELECT over the permit + researcher tables.
    $sql = "SELECT p.permit_number AS `Permit`,\n"
        . "       CONCAT(r.first_name, ' ', r.last_name) AS `Researcher`,\n"
        . "       r.institution AS `Institution`,\n"
        . "       p.research_topic AS `Topic`,\n"
        . "       p.status AS `Status`,\n"
        . "       p.start_date AS `From`,\n"
        . "       p.end_date AS `To`,\n"
        . "       p.collections_access AS `Access`\n"
        . "FROM naz_research_permit p\n"
        . "JOIN naz_researcher r ON r.id = p.researcher_id\n"
        . "ORDER BY p.status, p.end_date";

    if (!DB::table('report_query')->where('report_id', $reportId)->where('name', 'Permit register')->exists()) {
        DB::table('report_query')->insert([
            'report_id' => $reportId, 'section_id' => null, 'name' => 'Permit register',
            'query_text' => $sql, 'query_type' => 'sql', 'row_limit' => 500, 'timeout_seconds' => 30,
            'created_by' => 448, 'is_shared' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    DB::connection()->commit();
    printf("  researchers: %d  permits: %d  report_definition id=%d (+ SQL query)\n",
        count($ids), $permitCount, $reportId);
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo '  ROLLED BACK: ' . $e->getMessage() . "\n";
    exit(1);
}

// Keep browse facets ("Narrow your results by:") current after this seed.
require __DIR__ . '/_refresh_facets.php';
refresh_demo_facets();
