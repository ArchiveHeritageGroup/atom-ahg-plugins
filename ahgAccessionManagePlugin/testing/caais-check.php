<?php

/**
 * CAAIS profile check (CaaisProfileService, atom-ahg-plugins#199 and #203).
 *
 * Builds a THROWAWAY database holding Heratio's CAAIS tables
 * (fixtures/install_caais.sql, a copy of Heratio's
 * database/install_caais.sql) and its dropdown seed, plus the smallest stubs
 * of the base AtoM tables the profile reads. Then checks that the form input
 * is cleaned the way Heratio's validator would, that save/get round-trip, that
 * a save without the profile section leaves the repeatable elements alone,
 * and that the JSON, CSV and XML exports carry what was saved. The database
 * is dropped at the end.
 *
 * If Heratio's install_caais.sql changes, copy it over the fixture: the point
 * of this check is that AtoM writes rows Heratio can read.
 *
 * Run:  php ahgAccessionManagePlugin/testing/caais-check.php /path/to/client.cnf
 */
// The framework autoloader, wherever this instance keeps it.
$autoload = null;

foreach ([dirname(__DIR__, 3) . '/atom-framework/vendor/autoload.php',
          dirname(__DIR__, 4) . '/atom-framework/vendor/autoload.php',
          '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $candidate) {
    if (file_exists($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if (null === $autoload) {
    fwrite(STDERR, "cannot find atom-framework/vendor/autoload.php\n");
    exit(2);
}
require $autoload;
require dirname(__DIR__) . '/lib/Services/CaaisProfileService.php';

use AhgAccessionManage\Services\CaaisProfileService;
use Illuminate\Database\Capsule\Manager as DB;

$scratch = 'scratch_caais_check';
$ini = parse_ini_file($argv[1] ?? '', true)['client'] ?? null;
if (!$ini) {
    fwrite(STDERR, "usage: php {$argv[0]} /path/to/client.cnf\n");
    exit(2);
}

$db = new DB();
$base = ['driver' => 'mysql', 'host' => $ini['host'] ?? 'localhost', 'port' => $ini['port'] ?? 3306, 'username' => $ini['user'], 'password' => $ini['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database' => 'mysql'], 'admin');
$db->addConnection($base + ['database' => $scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}");
DB::connection('admin')->statement("CREATE DATABASE {$scratch} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}"); });

// Stand-ins for the base AtoM tables, only the columns the profile reads.
DB::unprepared(<<<'SQL'
CREATE TABLE object (id INT NOT NULL PRIMARY KEY, created_at DATETIME NULL, updated_at DATETIME NULL) ENGINE=InnoDB;
CREATE TABLE slug (id INT AUTO_INCREMENT PRIMARY KEY, object_id INT NOT NULL, slug VARCHAR(255) NOT NULL) ENGINE=InnoDB;
CREATE TABLE accession (id INT NOT NULL PRIMARY KEY, identifier VARCHAR(255), date DATE NULL, acquisition_type_id INT NULL, processing_priority_id INT NULL, processing_status_id INT NULL, resource_type_id INT NULL, source_culture VARCHAR(16) NOT NULL DEFAULT 'en') ENGINE=InnoDB;
CREATE TABLE accession_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, title VARCHAR(255), scope_and_content TEXT, appraisal TEXT, archival_history TEXT, location_information TEXT, physical_characteristics TEXT, processing_notes TEXT, received_extent_units TEXT, source_of_acquisition TEXT, PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE actor (id INT NOT NULL PRIMARY KEY, source_culture VARCHAR(16) NOT NULL DEFAULT 'en') ENGINE=InnoDB;
CREATE TABLE actor_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, authorized_form_of_name VARCHAR(1024), PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE repository (id INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
CREATE TABLE relation (id INT NOT NULL PRIMARY KEY, subject_id INT NOT NULL, object_id INT NOT NULL, type_id INT NOT NULL) ENGINE=InnoDB;
CREATE TABLE contact_information (id INT NOT NULL PRIMARY KEY, actor_id INT NOT NULL, primary_contact TINYINT(1) DEFAULT 0, contact_person VARCHAR(1024), street_address TEXT, email VARCHAR(255), telephone VARCHAR(255), postal_code VARCHAR(255), country_code VARCHAR(255)) ENGINE=InnoDB;
CREATE TABLE contact_information_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, city VARCHAR(1024), region VARCHAR(1024), PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE event (id INT NOT NULL PRIMARY KEY, object_id INT NOT NULL, type_id INT NULL, start_date DATE NULL, end_date DATE NULL) ENGINE=InnoDB;
CREATE TABLE event_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, date VARCHAR(1024), PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE term_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, name VARCHAR(1024), PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE other_name (id INT NOT NULL PRIMARY KEY, object_id INT NOT NULL, type_id INT NULL) ENGINE=InnoDB;
CREATE TABLE other_name_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, name VARCHAR(1024), PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE accession_event (id INT NOT NULL PRIMARY KEY, type_id INT NULL, accession_id INT NOT NULL, date DATE NULL) ENGINE=InnoDB;
CREATE TABLE accession_event_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, agent VARCHAR(255), PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE note (id INT NOT NULL PRIMARY KEY, object_id INT NOT NULL) ENGINE=InnoDB;
CREATE TABLE note_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, content TEXT, PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE rights (id INT NOT NULL PRIMARY KEY, start_date DATE NULL, end_date DATE NULL, basis_id INT NULL) ENGINE=InnoDB;
CREATE TABLE rights_i18n (id INT NOT NULL, culture VARCHAR(16) NOT NULL, rights_note TEXT, PRIMARY KEY (id, culture)) ENGINE=InnoDB;
CREATE TABLE user (id INT NOT NULL PRIMARY KEY, username VARCHAR(255)) ENGINE=InnoDB;
CREATE TABLE ahg_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT) ENGINE=InnoDB;
CREATE TABLE ahg_dropdown (id INT AUTO_INCREMENT PRIMARY KEY, taxonomy VARCHAR(100) NOT NULL, taxonomy_label VARCHAR(255) NOT NULL, taxonomy_section VARCHAR(50) NULL, code VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, sort_order INT DEFAULT 0, is_default TINYINT(1) DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at DATETIME NULL, updated_at DATETIME NULL, UNIQUE KEY uk_taxonomy_code (taxonomy, code)) ENGINE=InnoDB;
SQL);
DB::unprepared(file_get_contents(__DIR__ . '/fixtures/install_caais.sql'));
DB::unprepared(file_get_contents(__DIR__ . '/fixtures/caais_seed_dropdowns.sql'));

$fail = 0;
function ok($c, $m)
{
    global $fail;
    echo ($c ? 'PASS ' : 'FAIL ') . $m . "\n";
    if (!$c) {
        ++$fail;
    }
}

// Two repositories: one named in English, one only in its French source culture.
DB::table('actor')->insert([['id' => 10, 'source_culture' => 'en'], ['id' => 11, 'source_culture' => 'fr'], ['id' => 20, 'source_culture' => 'en'], ['id' => 21, 'source_culture' => 'en']]);
DB::table('repository')->insert([['id' => 10], ['id' => 11]]);
DB::table('actor_i18n')->insert([
    ['id' => 10, 'culture' => 'en', 'authorized_form_of_name' => 'Northern Archive'],
    ['id' => 11, 'culture' => 'fr', 'authorized_form_of_name' => 'Archives du Sud'],
    ['id' => 20, 'culture' => 'en', 'authorized_form_of_name' => 'Jane Donor'],
    ['id' => 21, 'culture' => 'en', 'authorized_form_of_name' => 'Secret Donor'],
]);
DB::table('user')->insert(['id' => 7, 'username' => 'archivist']);
DB::table('term_i18n')->insert([['id' => 300, 'culture' => 'en', 'name' => 'Gift'], ['id' => 301, 'culture' => 'en', 'name' => 'In progress'], ['id' => 302, 'culture' => 'en', 'name' => 'Copyright']]);

foreach ([1 => 'ACC-2026-001', 2 => 'ACC-2026-002'] as $id => $ident) {
    DB::table('object')->insert(['id' => $id]);
    DB::table('slug')->insert(['object_id' => $id, 'slug' => strtolower($ident)]);
    DB::table('accession')->insert(['id' => $id, 'identifier' => $ident, 'date' => '2026-09-01', 'acquisition_type_id' => 300, 'processing_status_id' => 301]);
}
DB::table('accession_i18n')->insert([
    ['id' => 1, 'culture' => 'en', 'title' => 'Smith family papers', 'scope_and_content' => 'Letters and diaries', 'location_information' => 'Strongroom 2', 'received_extent_units' => '2 boxes', 'archival_history' => null, 'appraisal' => null, 'physical_characteristics' => null, 'processing_notes' => null, 'source_of_acquisition' => null],
    ['id' => 2, 'culture' => 'en', 'title' => '=HYPERLINK("x") | pipes', 'scope_and_content' => null, 'location_information' => null, 'received_extent_units' => '3 boxes', 'archival_history' => null, 'appraisal' => null, 'physical_characteristics' => 'Mould on box 2', 'processing_notes' => null, 'source_of_acquisition' => 'Estate of J. Brown'],
]);
// Accession 1: two donors, one with a contact; a dated event; a right.
DB::table('relation')->insert([['id' => 500, 'subject_id' => 1, 'object_id' => 20, 'type_id' => 169], ['id' => 501, 'subject_id' => 1, 'object_id' => 21, 'type_id' => 169], ['id' => 502, 'subject_id' => 1, 'object_id' => 600, 'type_id' => 168]]);
DB::table('contact_information')->insert(['id' => 700, 'actor_id' => 20, 'primary_contact' => 1, 'contact_person' => 'Jane Donor', 'email' => 'jane@example.org', 'telephone' => '012 345', 'street_address' => null, 'postal_code' => null, 'country_code' => 'ZA']);
DB::table('contact_information_i18n')->insert(['id' => 700, 'culture' => 'en', 'city' => 'Pretoria', 'region' => null]);
DB::table('event')->insert(['id' => 800, 'object_id' => 1, 'start_date' => '1920-01-01', 'end_date' => '1950-12-31']);
DB::table('rights')->insert(['id' => 600, 'start_date' => '2026-01-01', 'end_date' => null, 'basis_id' => 302]);
DB::table('rights_i18n')->insert(['id' => 600, 'culture' => 'en', 'rights_note' => 'Donor retains copyright']);

$s = new CaaisProfileService('en');

ok($s->installed(), 'CAAIS tables detected');
ok(!$s->enabled(), 'profile off without the setting');
DB::table('ahg_settings')->insert(['setting_key' => CaaisProfileService::SETTING, 'setting_value' => 'true']);
ok($s->enabled(), 'profile on with accession_caais_enabled=true');
ok(isset($s->choices()['event_type']['physical_transfer'], $s->choices()['confidentiality']['not_public']), 'dropdown codes read from the seed');
ok([11 => 'Archives du Sud', 10 => 'Northern Archive'] === $s->repositoryOptions(), 'repository picker lists both by name, the French one by its source-culture name');
ok([['id' => 20, 'name' => 'Jane Donor'], ['id' => 21, 'name' => 'Secret Donor']] === $s->sources(1), 'linked donors offered as sources');

// The form as posted: valid rows, a blank template row, rows Heratio would
// refuse, and the two suggested mandatory events, one untouched.
$posted = [
    '_profile' => '1',
    'repository_id' => '10',
    'rules_or_conventions' => ' Canadian Archival Accession Information Standard 1.0 ',
    'sources' => ['20' => '', '21' => 'not_public', '999' => 'not_public'],
    'extents' => [
        ['extent_type' => 'extent_received', 'quantity' => '12.5', 'is_estimate' => '1', 'unit' => 'linear_metres', 'content_type' => '', 'carrier_type' => '', 'digital_file_formats' => '', 'note' => 'Mostly letters'],
        ['extent_type' => '', 'quantity' => '', 'is_estimate' => '0', 'unit' => '', 'content_type' => '', 'carrier_type' => '', 'digital_file_formats' => '', 'note' => ''],
        ['extent_type' => 'extent_received', 'quantity' => '3', 'is_estimate' => '0', 'unit' => '', 'note' => ''],
        ['extent_type' => 'no_such_type', 'quantity' => '', 'note' => 'x'],
    ],
    'languages' => [['language' => 'en', 'note' => 'with partial French translation'], ['language' => '', 'note' => '']],
    'preservation' => [['requirement_type' => 'physical_condition', 'requirement_value' => 'Fragile bindings', 'note' => ''], ['requirement_type' => 'physical_condition', 'requirement_value' => '', 'note' => 'orphan note']],
    'events' => [
        ['_suggested' => '1', 'event_type' => 'physical_transfer', 'event_date' => '2026-09-01', 'agent' => 'Courier', 'note' => ''],
        ['_suggested' => '1', 'event_type' => 'legal_transfer', 'event_date' => '', 'agent' => '', 'note' => ''],
        ['event_type' => 'deed_of_gift_signed', 'event_date' => '2026/08/30', 'agent' => '', 'note' => ''],
        ['event_type' => 'checksums_created', 'event_date' => 'not a date', 'agent' => '', 'note' => ''],
    ],
];
[$clean, $errors] = $s->clean($posted);
ok(10 === $clean['repository_id'], 'repository id accepted');
ok([21 => 'not_public'] === $clean['sources'], 'confidentiality kept for a real actor, unknown actor and "none" dropped');
ok(1 === count($clean['extents']) && 1 === count($clean['languages']) && 1 === count($clean['preservation']), 'blank template rows and refused rows dropped');
ok(2 === count($clean['events']) && 'physical_transfer' === $clean['events'][0]['event_type'] && '2026-08-30' === $clean['events'][1]['event_date'], 'untouched suggested event dropped, filled one kept, date normalised');
ok(4 === count($errors), 'four refusals reported (quantity without unit, unknown type, requirement without value, bad date): ' . count($errors));

$s->save(1, $clean);
$s->recordRevision(1, 'created', 7);
$p = $s->get(1);
ok(10 === (int) $p['repository_id'] && 'Northern Archive' === $p['repository_name'], 'repository round-trips (#203)');
ok('Canadian Archival Accession Information Standard 1.0' === $p['rules_or_conventions'], 'rules or conventions trimmed and saved');
ok([21 => 'not_public'] === $p['sources'], 'source confidentiality round-trips');
ok('12.500' === (string) $p['extents'][0]['quantity'] && 1 === (int) $p['extents'][0]['is_estimate'] && 'linear_metres' === $p['extents'][0]['unit'] && null === $p['extents'][0]['content_type'], 'extent round-trips, blanks stored as NULL');
ok('12.5' === CaaisProfileService::formatQuantity($p['extents'][0]['quantity']), 'quantity formatted for display');
ok('physical_transfer' === $p['events'][0]['event_type'] && '2026-09-01' === $p['events'][0]['event_date'] && 'Courier' === $p['events'][0]['agent'] && 0 === (int) $p['events'][0]['sort_order'], 'event round-trips in form order');
ok(1 === count($p['revisions']) && 'created' === $p['revisions'][0]['revision_type'] && 'archivist' === $p['revisions'][0]['agent'] && 7 === (int) $p['revisions'][0]['user_id'], 'creation revision recorded with the user name snapshot');

// Saving without the profile section (profile switched off) keeps the rows.
[$c2] = $s->clean(['repository_id' => '11']);
$s->save(1, $c2);
$s->recordRevision(1, 'revised', 7);
$p = $s->get(1);
ok(11 === (int) $p['repository_id'] && 1 === count($p['extents']) && 2 === count($p['events']) && 'Canadian Archival Accession Information Standard 1.0' === $p['rules_or_conventions'], 'save without the profile section changes the repository only');

// A repository that does not exist is reported and leaves the stored one.
[$c3, $e3] = $s->clean(['repository_id' => '424242']);
$s->save(1, $c3);
ok(1 === count($e3) && 11 === (int) $s->get(1)['repository_id'], 'unknown repository refused, stored repository kept');

// Clearing the repository is allowed.
[$c4] = $s->clean(['repository_id' => '']);
$s->save(2, $c4);
ok(null === $s->get(2)['repository_id'] && DB::table('accession_caais')->where('accession_id', 2)->exists(), 'empty repository saves as NULL');
$s->save(1, $s->clean(['repository_id' => '10'])[0]);

// Export: Heratio's record shape, key for key.
$r = $s->exportRecord(1);
$keys = [
    'identity' => ['repository', 'identifiers', 'accession_title', 'archival_unit', 'acquisition_method', 'disposition_authority', 'status'],
    'source' => ['source_of_material', 'preliminary_custodial_history'],
    'materials' => ['date_of_material', 'extent_statement', 'preliminary_scope_and_content', 'language_of_material'],
    'management' => ['storage_location', 'rights', 'preservation_requirements', 'appraisal', 'associated_documentation'],
    'events' => null,
    'general' => ['general_note'],
    'control' => ['rules_or_conventions', 'date_of_creation_or_revision', 'language_of_accession_record'],
    'conformance' => ['missing_mandatory'],
];
$shape = true;
foreach ($keys as $k => $sub) {
    $shape = $shape && array_key_exists($k, $r) && (null === $sub || $sub === array_keys($r[$k]));
}
ok($shape && array_keys($keys) === array_keys($r), 'record has exactly Heratio\'s sections and keys, in order');
ok('Northern Archive' === $r['identity']['repository'] && 'Gift' === $r['identity']['acquisition_method'] && 'In progress' === $r['identity']['status'], 'identity: repository and terms resolved');
ok(2 === count($r['source']['source_of_material']) && 'Not for public access' === $r['source']['source_of_material'][1]['source_confidentiality'], 'both sources in the full record, confidentiality labelled');
ok('Jane Donor, Pretoria, ZA, 012 345, jane@example.org' === $r['source']['source_of_material'][0]['source_contact_information'], 'contact information joined as Heratio does');
ok('ca. 12.5 Linear metres' === $r['materials']['extent_statement'][0]['quantity_and_unit_of_measure'], 'extent quantity statement');
ok('1920-01-01 - 1950-12-31' === $r['materials']['date_of_material'], 'date of material from the event start and end');
ok(['English - with partial French translation'] === $r['materials']['language_of_material'], 'language with its statement');
ok('Copyright' === $r['management']['rights'][0]['rights_type'] && '2026-01-01' === $r['management']['rights'][0]['rights_value'], 'rights carried');
$types = array_column($r['events'], 'event_type');
ok(['Physical transfer', 'Deed of gift signed', 'Accession date'] === $types, 'CAAIS events then the accession date as an event');
ok(['Record created', 'Record revised'] === array_column($r['control']['date_of_creation_or_revision'], 'creation_or_revision_type'), 'revisions exported');
ok([] === $r['conformance']['missing_mandatory'], 'accession 1 meets every mandatory element');

$ext = $s->exportRecord(1, true);
ok(1 === count($ext['source']['source_of_material']) && 'Jane Donor' === $ext['source']['source_of_material'][0]['source_name'] && [] === $ext['conformance']['missing_mandatory'], 'for sharing: confidential source withheld, still counted for conformance');

$r2 = $s->exportRecord(2);
ok('Estate of J. Brown' === $r2['source']['source_of_material'][0]['source_name'] && 'Immediate source of acquisition' === $r2['source']['source_of_material'][0]['source_role'], 'no donor: immediate source of acquisition used');
ok('3 boxes' === $r2['materials']['extent_statement'][0]['extent_note'] && 'Physical condition' === $r2['management']['preservation_requirements'][0]['preservation_requirement_type'], 'free-text extent and physical condition used as fallbacks');
ok(['3.1 - Date of material', '5.1 - Event: physical transfer', '7.2 - Date of creation (record created)'] === $r2['conformance']['missing_mandatory'], 'accession 2 reports its missing mandatory elements');
ok(null === $s->exportRecord(999), 'unknown accession exports nothing');

$env = $s->envelope([$r, $r2]);
ok(['standard', 'standard_version', 'standard_publisher', 'serialisation', 'generated_at', 'crosswalk', 'records'] === array_keys($env) && 'heratio-caais-json/1' === $env['serialisation'] && ['element' => '1.1', 'source' => 'accession_caais.repository_id -> repository authorised name'] === $env['crosswalk']['repository'], 'envelope matches Heratio\'s');
$json = json_encode($env, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
ok(is_string($json) && json_decode($json, true)['records'][0]['identity']['identifiers'][0]['value'] === 'ACC-2026-001', 'JSON encodes and reads back');

$csv = CaaisProfileService::toCsv([$r, $r2]);
$fh = fopen('php://temp', 'r+');
fwrite($fh, $csv);
rewind($fh);
$rows = [];
while (false !== ($line = fgetcsv($fh))) {
    $rows[] = $line;
}
$h = array_flip($rows[0]);
ok(3 === count($rows) && count($rows[0]) === count(CaaisProfileService::CSV_COLUMNS), 'CSV: header plus one row per accession');
ok('Jane Donor | Secret Donor' === $rows[1][$h['source_names']] && ' | Not for public access' === $rows[1][$h['source_confidentiality']], 'CSV: repeatable values joined with " | ", positions kept');
ok('Physical transfer | Deed of gift signed | Accession date' === $rows[1][$h['event_types']] && '2026-09-01 | 2026-08-30 | 2026-09-01' === $rows[1][$h['event_dates']], 'CSV: event columns line up');
ok("'=HYPERLINK(\"x\") | pipes" === $rows[2][$h['accession_title']], 'CSV: formula neutralised; a lone value is not pipe-escaped');
ok('3.1 - Date of material | 5.1 - Event: physical transfer | 7.2 - Date of creation (record created)' === $rows[2][$h['missing_mandatory']], 'CSV: plain lists joined');
$esc = CaaisProfileService::toCsv([['identity' => ['identifiers' => [['type' => 'a|b', 'value' => 'c\\d']]]]]);
ok(str_contains($esc, 'a\\|b') && str_contains($esc, 'c\\\\d'), 'CSV: a "|" or "\\" inside a list value is backslash-escaped');

$xml = $s->toXml($env);
$dom = new DOMDocument();
ok(@$dom->loadXML($xml), 'XML is well-formed');
$x = new DOMXPath($dom);
ok('ahg-caais-xml/1' === $dom->documentElement->getAttribute('serialisation') && 'caais_export' === $dom->documentElement->nodeName, 'XML root and serialisation');
ok(2 === $x->query('/caais_export/records/record')->length && 'ACC-2026-001' === $x->evaluate('string(/caais_export/records/record[1]/identity/identifiers/identifier[1]/value)'), 'XML: records and identifiers');
ok('Not for public access' === $x->evaluate('string(//record[1]/source/source_of_material/source[2]/source_confidentiality)') && 3 === $x->query('//record[1]/events/event')->length, 'XML: sources and events');
ok('3.4' === $x->evaluate('string(/caais_export/crosswalk/map[@key="language_of_material"]/@element)') && 20 === $x->query('/caais_export/crosswalk/map')->length, 'XML: crosswalk maps');
ok('Letters and diaries' === $x->evaluate('string(//record[1]/materials/preliminary_scope_and_content/value)') && 0 === $x->query('//record[1]/identity/archival_unit/*')->length, 'XML: plain lists as <value>, empty list as empty element');

// The row cap Heratio enforces with max:100.
[$c5, $e5] = $s->clean(['_profile' => '1', 'extents' => array_fill(0, 120, ['extent_type' => 'extent_received', 'quantity' => '1', 'unit' => 'linear_metres'])]);
ok(100 === count($c5['extents']) && 1 === count($e5), 'more than 100 rows: capped and reported');

echo $fail ? "\n{$fail} failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);
