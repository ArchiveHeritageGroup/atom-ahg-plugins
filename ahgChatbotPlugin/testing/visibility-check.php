<?php
/**
 * Ask the Archive - visibility check. Read only; run on any install:
 *   php atom-ahg-plugins/ahgChatbotPlugin/testing/visibility-check.php
 * Exit code 0 = all checks pass. Run it before every release of this plugin.
 */
define('ATOM_ROOT', realpath(__DIR__.'/../../..'));
chdir(ATOM_ROOT);
require ATOM_ROOT.'/atom-framework/bootstrap.php';
foreach (['ChatbotVectorIndex', 'ChatbotRetriever', 'ChatbotService'] as $c) {
    require __DIR__.'/../lib/Services/'.$c.'.php';
}

use AhgChatbotPlugin\Services\ChatbotRetriever as R;
use AhgChatbotPlugin\Services\ChatbotService as C;
use Illuminate\Database\Capsule\Manager as DB;

$fails = 0;
$check = function (bool $ok, string $what) use (&$fails) {
    echo ($ok ? 'PASS ' : 'FAIL ').$what."\n";
    $fails += $ok ? 0 : 1;
};

$published = fn () => DB::table('status')->where('type_id', 158)->where('status_id', 160)->where('object_id', '>', 1);
$pub = $published()->value('object_id');
$draft = DB::table('status')->where('type_id', 158)->where('status_id', '<>', 160)->where('object_id', '>', 1)->value('object_id');

if ($pub) {
    $check(R::visibleOnly([(int) $pub], null) === [(int) $pub] || in_array((int) $pub,
        AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()->getRestrictedObjectIds(null)), 'a published record passes (unless restricted)');
}
if ($draft) {
    $check(R::visibleOnly([(int) $draft], null) === [], "draft {$draft} is filtered out");
    $slug = DB::table('slug')->where('object_id', $draft)->value('slug');
    $check(!in_array((int) $draft, array_map(fn ($r) => (int) $r->id, R::retrieve('zzzqqq', 'en', null, $slug)), true), 'a draft page record never reaches the context');
}
$check(R::visibleOnly([PHP_INT_MAX], null) === [], 'an id that does not exist is filtered out');

$restricted = AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()->getRestrictedObjectIds(null);
if ($restricted) {
    $check(R::visibleOnly(array_map('intval', array_slice($restricted, 0, 50)), null) === [], 'restricted records are filtered out');
} else {
    echo "SKIP no restricted records on this install\n";
}

foreach (['photograph', 'archive', 'letter'] as $q) {
    $ids = array_map(fn ($r) => (int) $r->id, R::retrieve($q, 'en', null));
    $check($ids === [] || $published()->whereIn('object_id', $ids)->count() === count($ids), "every hit for '{$q}' is published");
    $check([] === array_intersect($ids, array_map('intval', $restricted)), "no restricted hit for '{$q}'");
}

$m = C::mask('jan@example.com 0823371406 8001015009087 1952');
$check(!str_contains($m, '@') && !str_contains($m, '0823371406') && !str_contains($m, '8001015009087') && str_contains($m, '1952'), 'personal details masked, year kept');
$check(null !== C::throttle('', str_repeat('x', C::MAX_MESSAGE_CHARS + 1)), 'over-long question refused');

echo $fails ? "\n{$fails} FAILED\n" : "\nAll checks passed\n";
exit($fails ? 1 : 0);
