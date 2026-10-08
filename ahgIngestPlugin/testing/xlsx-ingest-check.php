<?php

/**
 * Excel ingest + grid entry check (issue #208).
 *
 * Part A (always): builds .xlsx and .xls workbooks in a temporary directory -
 * Afrikaans, isiZulu, Tshivenda and French text, a CJK cell, date and
 * date-time cells, a formula cell, float noise, a zero-padded identifier and a
 * second worksheet - converts them with XlsxConverter and checks the CSV the
 * ingest wizard reads back, through IngestService::detectCsvFormat().
 *
 * Part B (only when a client.cnf is given): builds a THROWAWAY database with
 * the plugin's own ingest tables (plus stand-ins for term / term_i18n), then
 * checks IngestService::processUpload() + parseRows() + autoMapColumns() for a
 * workbook, and GridEntryService normalise / validate / stage. The database is
 * dropped at the end. Never point this at a live database name; it creates its
 * own.
 *
 * Run:  php ahgIngestPlugin/testing/xlsx-ingest-check.php [/path/to/client.cnf]
 */
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
require_once dirname(__DIR__) . '/lib/Services/XlsxConverter.php';
require_once dirname(__DIR__) . '/lib/Services/IngestService.php';
require_once dirname(__DIR__) . '/lib/Services/GridEntryService.php';

use AhgIngestPlugin\Services\GridEntryService;
use AhgIngestPlugin\Services\IngestService;
use AhgIngestPlugin\Services\XlsxConverter;
use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$fail = 0;
function ok($cond, $msg)
{
    global $fail;
    echo ($cond ? 'ok   ' : 'FAIL ') . $msg . "\n";
    if (!$cond) {
        ++$fail;
    }
}

$tmp = sys_get_temp_dir() . '/xlsx-ingest-check-' . getmypid();
mkdir($tmp, 0700, true);
register_shutdown_function(function () use ($tmp) {
    foreach (glob($tmp . '/*') as $f) {
        @unlink($f);
    }
    @rmdir($tmp);
});

// ---- Build the workbook --------------------------------------------------
$book = new Spreadsheet();
$ws = $book->getActiveSheet();
$ws->setTitle('Beskrywings');
$ws->fromArray(['identifier', 'title', 'creationDatesStart', 'recorded', 'extent', 'total', 'scopeAndContent', ''], null, 'A1');
$rows = [
    ['00042', 'Ons het die brûe oor die rivier gebou; geëet, môre, sê', '1923-04-05', null, 1.5, '=E2*2', 'Afrikaans: ê ë ô û ï'],
    ['00043', 'Umlando wesizwe samaZulu - izincwadi zikaNkosi', '1909-01-22', null, 0.1, '=0.1+0.2', 'isiZulu; Tshivenda ṱ ḓ ṅ ḽ ṋ'],
    ['00044', 'Élève à l\'école; garçon, cœur, Noël', '1944-06-06', null, 1234567, '=B4&" (FR)"', 'Français'],
    ['00045', '南アフリカの文書館 档案馆 기록', '2001-12-31', null, 3, '=SUM(E2:E5)', 'CJK'],
];
$ws->fromArray($rows, null, 'A2');
// identifiers as numbers with a zero-padded format, like Excel users keep them
foreach ([2 => 42, 3 => 43, 4 => 44, 5 => 45] as $r => $n) {
    $ws->setCellValue('A' . $r, $n);
    $ws->getStyle('A' . $r)->getNumberFormat()->setFormatCode('00000');
}
// real Excel dates (serial numbers + date format), not text
foreach ([2 => '1923-04-05', 3 => '1909-01-22', 4 => '1944-06-06', 5 => '2001-12-31'] as $r => $d) {
    $ws->setCellValue('C' . $r, XlDate::PHPToExcel(new DateTime($d, new DateTimeZone('UTC'))));
    $ws->getStyle('C' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
}
$ws->setCellValue('D2', XlDate::PHPToExcel(new DateTime('2024-02-29 14:30:00', new DateTimeZone('UTC'))));
$ws->getStyle('D2')->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm');
// an empty row in the middle and formatting-only trailing cells are dropped
$ws->getStyle('A8:H9')->getFont()->setBold(true);
$ws->setCellValue('B10', 'Laaste ry na leë ry');
// Excel cannot hold dates before 1900 as dates; they arrive as text.
$ws->setCellValueExplicit('C10', '1879', PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

$second = $book->createSheet();
$second->setTitle('Notas');
$second->fromArray([['note'], ['tweede blad']], null, 'A1');

$xlsx = $tmp . '/check.xlsx';
(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($xlsx);
$xls = $tmp . '/check.xls';
(new PhpOffice\PhpSpreadsheet\Writer\Xls($book))->save($xls);

// ---- Part A: conversion -------------------------------------------------
echo "Part A - conversion\n";
$sheets = XlsxConverter::listSheets($xlsx);
ok(2 === count($sheets) && 'Beskrywings' === $sheets[0]['name'] && 'Notas' === $sheets[1]['name'], 'listSheets returns both sheets in order');
ok(XlsxConverter::isSpreadsheet('a.XLSX') && XlsxConverter::isSpreadsheet('b.xls') && !XlsxConverter::isSpreadsheet('c.csv'), 'isSpreadsheet by extension');

foreach (['xlsx' => $xlsx, 'xls' => $xls] as $kind => $file) {
    $csv = $tmp . '/out-' . $kind . '.csv';
    $res = XlsxConverter::toCsv($file, $csv, 0);
    ok(5 === $res['row_count'], "$kind: 5 data rows (blank row skipped), got {$res['row_count']}");
    ok(['identifier', 'title', 'creationDatesStart', 'recorded', 'extent', 'total', 'scopeAndContent'] === $res['headers'], "$kind: headers, trailing empty column dropped");

    $raw = file_get_contents($csv);
    ok(mb_check_encoding($raw, 'UTF-8') && "\xEF\xBB\xBF" !== substr($raw, 0, 3), "$kind: CSV is valid UTF-8 without BOM");

    $det = (new IngestService())->detectCsvFormat($csv, ',');
    ok(',' === $det['delimiter'] && 5 === $det['row_count'] && $det['headers'] === $res['headers'], "$kind: detectCsvFormat reads it back");

    $fh = fopen($csv, 'r');
    fgetcsv($fh);
    $data = [];
    while (($c = fgetcsv($fh)) !== false) {
        $data[] = array_combine($res['headers'], $c);
    }
    fclose($fh);

    ok('00042' === $data[0]['identifier'], "$kind: zero-padded identifier kept (got {$data[0]['identifier']})");
    ok('Ons het die brûe oor die rivier gebou; geëet, môre, sê' === $data[0]['title'], "$kind: Afrikaans diacritics");
    ok('isiZulu; Tshivenda ṱ ḓ ṅ ḽ ṋ' === $data[1]['scopeAndContent'], "$kind: Tshivenda dotted letters");
    ok('Élève à l\'école; garçon, cœur, Noël' === $data[2]['title'], "$kind: French diacritics and ligature");
    ok('南アフリカの文書館 档案馆 기록' === $data[3]['title'], "$kind: CJK text");
    ok('1923-04-05' === $data[0]['creationDatesStart'] && '1909-01-22' === $data[1]['creationDatesStart'], "$kind: date cells as ISO (got {$data[0]['creationDatesStart']}, {$data[1]['creationDatesStart']})");
    ok('2024-02-29 14:30:00' === $data[0]['recorded'], "$kind: date-time cell as ISO (got {$data[0]['recorded']})");
    ok('3' === $data[0]['total'], "$kind: formula =E2*2 gives 3 (got {$data[0]['total']})");
    ok('0.3' === $data[1]['total'], "$kind: formula =0.1+0.2 gives 0.3, no float noise (got {$data[1]['total']})");
    ok('Élève à l\'école; garçon, cœur, Noël (FR)' === $data[2]['total'], "$kind: string formula value (got {$data[2]['total']})");
    ok('1234567' === $data[2]['extent'] && '1.5' === $data[0]['extent'], "$kind: plain numbers (got {$data[2]['extent']}, {$data[0]['extent']})");
    ok('Laaste ry na leë ry' === $data[4]['title'], "$kind: row after a blank row kept");
    ok('1879' === $data[4]['creationDatesStart'], "$kind: pre-1900 date kept as the text Excel stores it (got {$data[4]['creationDatesStart']})");
}

$res = XlsxConverter::toCsv($xlsx, $tmp . '/notas.csv', 'Notas');
ok(1 === $res['row_count'] && ['note'] === $res['headers'], 'second sheet chosen by name');
try {
    XlsxConverter::toCsv($xlsx, $tmp . '/none.csv', 5);
    ok(false, 'missing sheet index throws');
} catch (RuntimeException $e) {
    ok(true, 'missing sheet index throws');
}
file_put_contents($tmp . '/broken.xlsx', 'not a workbook');
try {
    XlsxConverter::listSheets($tmp . '/broken.xlsx');
    ok(false, 'corrupt workbook throws');
} catch (Throwable $e) {
    ok(true, 'corrupt workbook throws');
}
ok('0.3' === XlsxConverter::numberText(0.1 + 0.2) && '-2.5' === XlsxConverter::numberText(-2.5) && '0' === XlsxConverter::numberText(-0.0), 'numberText edge values');

// ---- Part B: throwaway database -----------------------------------------
$cnf = $argv[1] ?? null;
if (!$cnf) {
    echo "Part B skipped (no client.cnf given)\n";
    echo $fail ? "\n{$fail} FAILED\n" : "\nall passed\n";
    exit($fail ? 1 : 0);
}
echo "Part B - throwaway database\n";
$ini = parse_ini_file($cnf, true)['client'] ?? null;
if (!$ini) {
    fwrite(STDERR, "cannot read {$cnf}\n");
    exit(2);
}
$scratch = 'scratch_xlsx_ingest_check';
$db = new DB();
$base = ['driver' => 'mysql', 'host' => $ini['host'] ?? 'localhost', 'port' => $ini['port'] ?? 3306, 'username' => $ini['user'], 'password' => $ini['password'], 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'];
$db->addConnection($base + ['database' => 'mysql'], 'admin');
$db->addConnection($base + ['database' => $scratch]);
$db->setAsGlobal();
DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}");
DB::connection('admin')->statement("CREATE DATABASE {$scratch} CHARACTER SET utf8mb4");
register_shutdown_function(function () use ($scratch) { DB::connection('admin')->statement("DROP DATABASE IF EXISTS {$scratch}"); });

DB::unprepared(file_get_contents(dirname(__DIR__) . '/database/install.sql'));
// Stand-ins for the base tables GridEntryService reads level names from.
DB::unprepared('CREATE TABLE term (id INT PRIMARY KEY, taxonomy_id INT, lft INT, source_culture VARCHAR(16));');
DB::unprepared('CREATE TABLE term_i18n (id INT, culture VARCHAR(16), name VARCHAR(255), PRIMARY KEY (id, culture));');
foreach ([1 => 'Fonds', 2 => 'Series', 3 => 'File', 4 => 'Item'] as $id => $name) {
    DB::table('term')->insert(['id' => $id, 'taxonomy_id' => 34, 'lft' => $id, 'source_culture' => 'en']);
    DB::table('term_i18n')->insert(['id' => $id, 'culture' => 'en', 'name' => $name]);
}
DB::table('term_i18n')->insert(['id' => 4, 'culture' => 'af', 'name' => 'Item (af)']);

$svc = new IngestService();
$sid = $svc->createSession(7, ['title' => 'xlsx check']);
$upload = $tmp . '/ingest_upload.xlsx';
copy($xlsx, $upload);
$fileId = $svc->processUpload($sid, ['original_name' => 'Katalogus.xlsx', 'stored_path' => $upload, 'file_size' => filesize($upload), 'mime_type' => 'application/octet-stream', 'sheet' => 0]);
$files = DB::table('ingest_file')->where('session_id', $sid)->orderBy('id')->get()->all();
ok(2 === count($files) && 'xlsx' === $files[0]->file_type && 'csv' === $files[1]->file_type && (int) $files[1]->id === $fileId, 'processUpload keeps the workbook and registers the converted CSV');
ok(',' === $files[1]->delimiter && 'UTF-8' === $files[1]->encoding && 5 === (int) $files[1]->row_count, 'converted CSV metadata');
ok(false !== strpos($files[1]->original_name, 'Beskrywings'), 'CSV entry names the worksheet');
ok(5 === $svc->parseRows($sid), 'parseRows reads 5 rows from the converted workbook');
$first = DB::table('ingest_row')->where('session_id', $sid)->orderBy('row_number')->first();
$d = json_decode($first->data, true);
ok('Ons het die brûe oor die rivier gebou; geëet, môre, sê' === $first->title && '1923-04-05' === $d['creationDatesStart'], 'ingest_row title + ISO date stored intact');
$cjk = DB::table('ingest_row')->where('session_id', $sid)->where('row_number', 4)->value('title');
ok('南アフリカの文書館 档案馆 기록' === $cjk, 'CJK title stored intact in ingest_row');
$maps = $svc->autoMapColumns($sid);
$targets = array_column($maps, 'target_field', 'source_column');
ok('identifier' === $targets['identifier'] && 'title' === $targets['title'] && 'creationDatesStart' === $targets['creationDatesStart'], 'autoMapColumns maps the workbook headers');

// Grid entry
$grid = new GridEntryService($svc);
ok(['Fonds', 'Series', 'File', 'Item'] === $grid->levelNames('en') && in_array('Item (af)', $grid->levelNames('af'), true), 'levelNames per culture with fallback');
$input = [
    ['title' => ' Lêer een ', 'levelOfDescription' => 'file', 'creationDatesStart' => '1950', 'creationDatesEnd' => '1960-12'],
    ['title' => '', 'identifier' => ''],
    ['title' => 'Ítem twee', 'creationDatesStart' => '1999-02-30'],
    ['identifier' => 'X-3', 'levelOfDescription' => 'Bogus'],
    ['title' => 'Vier', 'creationDatesStart' => '2001', 'creationDatesEnd' => '1999'],
];
$rows = $grid->normalise($input);
ok([0, 2, 3, 4] === array_keys($rows), 'normalise drops empty rows and keeps grid positions');
ok('Lêer een' === $rows[0]['title'] && '1950 - 1960-12' === $rows[0]['creationDates'], 'normalise trims and derives the display date');
$errors = $grid->validate($rows, $grid->levelNames('en'));
ok('File' === $rows[0]['levelOfDescription'] && !isset($errors[0]), 'level matched without case and rewritten to the term name');
ok(isset($errors[2]['creationDatesStart']), 'impossible date rejected');
ok(isset($errors[3]['title'], $errors[3]['levelOfDescription']), 'missing title and unknown level rejected');
ok(isset($errors[4]['creationDatesEnd']), 'end before start rejected');

$good = $grid->normalise([
    ['identifier' => 'G-1', 'title' => 'Inkomende briewe', 'levelOfDescription' => 'Series', 'creationDatesStart' => '1901-01-01', 'creationDatesEnd' => '1910', 'extentAndMedium' => '2 boxes', 'scopeAndContent' => "Briewe\r\nmet twee reëls"],
    ['identifier' => 'G-2', 'title' => '書簡集'],
]);
ok([] === $grid->validate($good, $grid->levelNames('en')), 'valid grid rows pass');
[$gsid, $stats] = $grid->stage(7, 123, 'Toets-fonds', $good);
$session = $svc->getSession($gsid);
ok('existing' === $session->parent_placement && 123 === (int) $session->parent_id && 'commit' === $session->status && 0 === (int) $session->process_virus_scan, 'grid session targets the parent and waits for commit');
ok(2 === $stats['total'] && 2 === $stats['valid'] && 0 === $stats['errors'], 'wizard validation passes the staged rows');
$r1 = DB::table('ingest_row')->where('session_id', $gsid)->orderBy('row_number')->first();
$e1 = json_decode($r1->enriched_data, true);
ok('Inkomende briewe' === $e1['title'] && 'Series' === $e1['levelOfDescription'] && '1901-01-01 - 1910' === $e1['creationDates'] && "Briewe\nmet twee reëls" === $e1['scopeAndContent'] && 'Draft' === $e1['publicationStatus'], 'enriched row carries the AtoM fields the commit job reads');
ok('書簡集' === DB::table('ingest_row')->where('session_id', $gsid)->where('row_number', 2)->value('title'), 'CJK title staged intact');

echo $fail ? "\n{$fail} FAILED\n" : "\nall passed\n";
exit($fail ? 1 : 0);
