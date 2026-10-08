<?php
/**
 * Ingest date check: year and year-month dates become full dates MySQL accepts
 * (event.start_date/end_date are DATE and the server runs in strict mode).
 *
 * Run: php ahgIngestPlugin/testing/ingest-dates-check.php
 */
foreach ([dirname(__DIR__, 3).'/atom-framework/vendor/autoload.php', '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $a) {
    if (file_exists($a)) { require $a; break; }
}
require_once dirname(__DIR__).'/lib/Services/IngestCommitService.php';
$c = '\AhgIngestPlugin\Services\IngestCommitService';
if (!class_exists($c)) { $c = 'IngestCommitService'; }
$cases = [['1950', false, '1950-01-01'], ['1950', true, '1950-12-31'], ['1920-05', false, '1920-05-01'], ['1920-02', true, '1920-02-29'],
    ['1901-3-7', false, '1901-03-07'], ['1950-13', true, null], ['circa 1950', false, null], ['2023-02-30', false, null]];
$fail = 0;
foreach ($cases as [$in, $end, $want]) {
    $got = $c::fullDate($in, $end);
    $ok = $got === $want; $fail += $ok ? 0 : 1;
    echo ($ok ? 'PASS ' : 'FAIL ')."{$in} ".($end ? 'end' : 'start').' => '.var_export($got, true)."\n";
}
echo $fail ? "\n{$fail} failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);
