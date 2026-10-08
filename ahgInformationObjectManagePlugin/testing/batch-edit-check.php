<?php
/**
 * BatchEditService check (#204): the pure planning logic only - slug cleaning,
 * title rename rules, date normalising and which records change. No database,
 * no Propel, nothing written anywhere.
 *
 * Run: php ahgInformationObjectManagePlugin/testing/batch-edit-check.php
 */
require dirname(__DIR__).'/lib/Services/BatchEditService.php';

use AhgInformationObjectManage\Services\BatchEditService as B;

$fail = 0;
$n = 0;
function check(string $label, $got, $want): void
{
    global $fail, $n;
    ++$n;
    if ($got !== $want) {
        ++$fail;
        echo "FAIL {$label}\n  got:  ".var_export($got, true)."\n  want: ".var_export($want, true)."\n";
    }
}

// Slugs
check('slugs array', B::normaliseSlugs([' a-1 ', 'b', 'a-1', '', 5, 'x y', 'c/d']), ['a-1', 'b']);
check('slugs json', B::normaliseSlugs('["f1","f2","f1"]'), ['f1', 'f2']);

// Rename
check('replace case-sensitive', B::renameTitle('Letter to letter', ['find' => 'letter', 'replace' => 'note']), 'Letter to note');
check('replace ignore case', B::renameTitle('Letter to letter', ['find' => 'letter', 'replace' => 'note', 'ci' => true]), 'note to note');
check('replace utf8 ignore case', B::renameTitle('ÉCOLE école', ['find' => 'école', 'replace' => 'skool', 'ci' => true]), 'skool skool');
check('replace with $ and \\', B::renameTitle('Price X', ['find' => 'x', 'replace' => '$1 \\0', 'ci' => true]), 'Price $1 \\0');
check('find is not a regex', B::renameTitle('a.b axb', ['find' => '.', 'replace' => '-']), 'a-b axb');
check('prefix + suffix', B::renameTitle('Minutes', ['prefix' => 'Box 1 - ', 'suffix' => ' (copy)']), 'Box 1 - Minutes (copy)');
check('prefix not doubled', B::renameTitle('Box 1 - Minutes', ['prefix' => 'Box 1 - ']), 'Box 1 - Minutes');
check('suffix not doubled', B::renameTitle('Minutes (copy)', ['suffix' => ' (copy)']), 'Minutes (copy)');
check('replace then prefix', B::renameTitle('Old name', ['find' => 'Old', 'replace' => 'New', 'prefix' => 'A: ']), 'A: New name');

// Dates
check('date year start', B::normaliseDate('1950'), '1950-01-01');
check('date year end', B::normaliseDate('1950', true), '1950-12-31');
check('date month end leap', B::normaliseDate('2024-02', true), '2024-02-29');
check('date compact', B::normaliseDate('19500315'), '1950-03-15');
check('date full', B::normaliseDate('1950-03-15', true), '1950-03-15');
check('date empty', B::normaliseDate('  '), null);
check('date bad month', B::normaliseDate('1950-13'), false);
check('date bad day', B::normaliseDate('1950-02-30'), false);
check('date text', B::normaliseDate('circa 1950'), false);

// Plan
$labels = [
    'level' => [1 => 'Fonds', 2 => 'File'],
    'repository' => [7 => 'Archive A', 8 => 'Archive B'],
    'status' => [159 => 'Draft', 160 => 'Published'],
    'language' => ['en' => 'English', 'af' => 'Afrikaans', 'zu' => 'Zulu'],
    'eventType' => [111 => 'Creation'],
];
$record = [
    'title' => 'Minutes 1950', 'levelId' => 2, 'repositoryId' => 7, 'pubStatusId' => 159,
    'accessConditions' => 'Open', 'reproductionConditions' => null, 'languages' => ['en'],
    'terms' => ['subject' => [10 => 'Mining'], 'place' => [], 'genre' => []],
    'creators' => [50 => 'Smith, J.'],
    'events' => [['typeId' => 111, 'actorId' => null, 'date' => '1950', 'start' => '1950-01-01', 'end' => '1950-12-31']],
];
$fields = function (array $changes) { return array_column($changes, 'field'); };

$same = ['levelId' => 2, 'repositoryId' => 7, 'pubStatusId' => 159, 'accessConditions' => 'Open',
    'languages' => ['mode' => 'add', 'codes' => ['en']], 'terms' => ['subject' => [10 => 'Mining']],
    'creators' => [50 => 'Smith, J.'], 'event' => ['typeId' => 111, 'date' => '1950', 'start' => '1950-01-01', 'end' => '1950-12-31'],
    'rename' => ['find' => 'nothing-here', 'replace' => 'x']];
check('values already set -> no change', B::plan($record, $same, $labels), []);
check('hasOperations empty', B::hasOperations(['terms' => ['subject' => []], 'creators' => []]), false);
check('hasOperations terms', B::hasOperations(['terms' => ['place' => [3 => 'Pretoria']]]), true);

$ops = ['levelId' => 1, 'repositoryId' => 7, 'pubStatusId' => 160, 'reproductionConditions' => 'Ask first',
    'languages' => ['mode' => 'add', 'codes' => ['af', 'en']],
    'terms' => ['subject' => [10 => 'Mining', 11 => 'Labour'], 'place' => [20 => 'Pretoria']],
    'creators' => [50 => 'Smith, J.', 51 => 'Jones, K.'],
    'event' => ['typeId' => 111, 'date' => '', 'start' => '1960-01-01', 'end' => ''],
    'rename' => ['find' => '1950', 'replace' => '1951']];
$plan = B::plan($record, $ops, $labels);
check('changed fields', $fields($plan), ['title', 'levelId', 'pubStatusId', 'reproductionConditions', 'languages', 'subject', 'place', 'event', 'creators']);
$by = array_column($plan, null, 'field');
check('title before/after', [$by['title']['before'], $by['title']['after']], ['Minutes 1950', 'Minutes 1951']);
check('level names', [$by['levelId']['before'], $by['levelId']['after'], $by['levelId']['value']], ['File', 'Fonds', 1]);
check('languages added', $by['languages']['value'], ['en', 'af']);
check('languages shown', $by['languages']['after'], 'English; Afrikaans');
check('only new subject added', $by['subject']['value'], [11]);
check('subject after', $by['subject']['after'], 'Mining; Labour');
check('only new creator added', $by['creators']['value'], [51]);
check('event description', $by['event']['after'], 'Creation: (1960-01-01)');

$replace = B::plan($record, ['languages' => ['mode' => 'replace', 'codes' => ['zu']]], $labels);
check('languages replaced', $replace[0]['value'], ['zu']);

$empty = B::plan($record, ['rename' => ['find' => 'Minutes 1950', 'replace' => ' ']], $labels);
check('empty new title is refused', $empty[0]['value'], false);

$noTitle = $record;
$noTitle['title'] = null;
check('no title in this language -> no rename', B::plan($noTitle, ['rename' => ['prefix' => 'X ']], $labels), []);

echo ($fail ? "{$fail} of {$n} checks FAILED" : "All {$n} checks passed")."\n";
exit($fail ? 1 : 0);
