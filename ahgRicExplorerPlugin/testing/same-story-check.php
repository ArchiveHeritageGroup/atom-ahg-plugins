<?php

/**
 * Same-story and duplicate-leaf matching on planted texts (#196). No database.
 *   php ahgRicExplorerPlugin/testing/same-story-check.php
 * Exits 1 on any failed expectation.
 */
require_once dirname(__DIR__).'/lib/Services/SameStoryService.php';

use AhgRicExplorer\Services\SameStoryService as S;

$words = explode(' ', 'the column marched north at dawn towards the river crossing where the enemy guns had been seen on the ridge above the drift and the general ordered the cavalry forward to scout the line of advance while the infantry waited in the shelter of the kopje until the artillery could be brought up through the heavy sand');
$text = function (int $seed, int $n = 60) use ($words) {
    mt_srand($seed);
    $out = [];
    for ($i = 0; $i < $n; ++$i) {
        $out[] = $words[mt_rand(0, count($words) - 1)];
    }

    return implode(' ', $out);
};

$story = $text(1, 80);
$retold = implode(' ', array_slice(explode(' ', $story), 0, 50)).' '.$text(99, 30); // shares its first 50 words
$stock = 'from our own correspondent by telegraph this morning in the usual way';
$texts = [
    10 => $story, 11 => $retold,                              // same story, different pages
    20 => $text(3), 21 => $text(4), 22 => $text(5),           // page 2
    30 => $text(3), 31 => $text(4), 32 => $text(5),           // page 3: the same leaf again
    40 => 'too short to compare',
];
// The stock phrase in 25 otherwise unrelated texts must link none of them.
for ($i = 0; $i < 25; ++$i) {
    $texts[100 + $i] = $stock.' '.$text(500 + $i);
}
$parent = [10 => 1, 11 => 2, 20 => 2, 21 => 2, 22 => 2, 30 => 3, 31 => 3, 32 => 3, 40 => 4] + array_fill(100, 25, 5);

$r = S::match($texts, $parent);
$kind = [];
foreach ($r['links'] as $l) {
    $kind["{$l['a']}:{$l['b']}"] = $l['kind'];
}
$checks = [
    'short text left out' => 33 === $r['texts'],
    'same story found' => ($kind['10:11'] ?? null) === 'same_story',
    'same leaf: 3 links' => 3 === count(array_filter($kind, fn ($k) => 'same_leaf' === $k)),
    'same leaf: pages 2 and 3' => [['a' => 2, 'b' => 3, 'items' => 3]] === $r['leaves'],
    'stock phrase links nothing' => !array_filter(array_keys($kind), fn ($k) => (int) explode(':', $k)[0] >= 100),
    'nothing else linked' => 4 === count($r['links']),
];
$failed = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? 'ok   ' : 'FAIL ').$name."\n";
    $failed += $ok ? 0 : 1;
}
exit($failed ? 1 : 0);
