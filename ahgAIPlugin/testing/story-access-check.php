<?php
/**
 * Story access check (#155 slice A). Offline: an in-memory SQLite database, the
 * real SearchAccessFilterService and a fake AI gateway. No Symfony, MySQL,
 * network or GPU.
 *
 *   php ahgAIPlugin/testing/story-access-check.php
 *
 * ATOM_FRAMEWORK overrides the framework path. Exit code 0 = all checks pass.
 */

namespace AtomFramework\Services\AI {
    class AiGatewayClient
    {
        public static array $prompts = [];

        public static function fromSettings(): self
        {
            return new self();
        }

        public function generate(string $prompt): array
        {
            self::$prompts[] = $prompt;

            return ['success' => true, 'text' => 'A story.'];
        }
    }
}

namespace AtomFramework\Http\Controllers {
    #[\AllowDynamicProperties]
    class AhgController
    {
        public bool $asStaff = false;

        public function forward404(): void
        {
            throw new \DomainException('404');
        }

        public function getUser(): object
        {
            return new class($this->asStaff) {
                public function __construct(private bool $staff)
                {
                }

                public function isAuthenticated(): bool
                {
                    return $this->staff;
                }

                public function hasCredential($c): bool
                {
                    return $this->staff;
                }
            };
        }

        public function getResponse(): object
        {
            return new class {
                public function setTitle($t): void
                {
                }
            };
        }
    }
}

namespace {
    use AtomExtensions\Services\Search\SearchAccessFilterService as Filter;
    use AtomFramework\Services\AI\AiGatewayClient as Gateway;
    use Illuminate\Database\Capsule\Manager as DB;

    $fw = getenv('ATOM_FRAMEWORK') ?: '/usr/share/nginx/archive/atom-framework';
    require $fw.'/vendor/autoload.php';
    require_once $fw.'/src/Services/Search/SearchAccessFilterService.php';
    require_once __DIR__.'/../lib/Services/StorytellingService.php';

    ini_set('error_log', '/dev/null');
    date_default_timezone_set('Africa/Johannesburg');

    $capsule = new DB();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setAsGlobal();
    $pdo = DB::connection()->getPdo();
    $pdo->sqliteCreateFunction('MONTH', fn ($d) => (int) substr((string) $d, 5, 2));
    $pdo->sqliteCreateFunction('DAY', fn ($d) => (int) substr((string) $d, 8, 2));
    $pdo->sqliteCreateFunction('YEAR', fn ($d) => (int) substr((string) $d, 0, 4));

    foreach ([
        'information_object (id INTEGER PRIMARY KEY, source_culture TEXT, lft INT, rgt INT, parent_id INT)',
        'information_object_i18n (id INT, culture TEXT, title TEXT, scope_and_content TEXT)',
        'status (object_id INT, type_id INT, status_id INT)',
        'slug (object_id INT, slug TEXT)',
        'event (object_id INT, start_date TEXT)',
        'ahg_story (id INTEGER PRIMARY KEY, slug TEXT, title TEXT, theme TEXT, body TEXT, object_ids TEXT, sources_json TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
        'security_classification (id INT, level INT)',
        'object_security_classification (object_id INT, classification_id INT, active INT)',
        'object_rights_holder (object_id INT, donor_id INT)',
        'donor_agreement (id INT, donor_id INT)',
        'donor_agreement_restriction (donor_agreement_id INT, restriction_type TEXT, start_date TEXT, end_date TEXT)',
        'rights_embargo (object_id INT, status TEXT, embargo_type TEXT, start_date TEXT, end_date TEXT)',
        'icip_access_restriction (information_object_id INT, applies_to_descendants INT, start_date TEXT, end_date TEXT)',
    ] as $t) {
        DB::statement('CREATE TABLE '.$t);
    }

    $records = [10 => 'public', 11 => 'embargoed', 12 => 'classified', 13 => 'donor', 14 => 'icip', 15 => 'draft', 16 => 'later'];
    foreach ($records as $id => $name) {
        DB::table('information_object')->insert(['id' => $id, 'source_culture' => 'en', 'lft' => $id * 10, 'rgt' => $id * 10 + 1, 'parent_id' => 1]);
        DB::table('information_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'title' => "Riverboat {$name}", 'scope_and_content' => "Scope {$name}"]);
        DB::table('status')->insert(['object_id' => $id, 'type_id' => 158, 'status_id' => 'draft' === $name ? 159 : 160]);
        DB::table('slug')->insert(['object_id' => $id, 'slug' => "r-{$id}"]);
        DB::table('event')->insert(['object_id' => $id, 'start_date' => '1900-'.date('m-d')]);
    }
    DB::table('rights_embargo')->insert(['object_id' => 11, 'status' => 'active', 'embargo_type' => 'full', 'start_date' => '2000-01-01', 'end_date' => null]);
    DB::table('security_classification')->insert(['id' => 1, 'level' => 3]);
    DB::table('object_security_classification')->insert(['object_id' => 12, 'classification_id' => 1, 'active' => 1]);
    DB::table('object_rights_holder')->insert(['object_id' => 13, 'donor_id' => 1]);
    DB::table('donor_agreement')->insert(['id' => 1, 'donor_id' => 1]);
    DB::table('donor_agreement_restriction')->insert(['donor_agreement_id' => 1, 'restriction_type' => 'closure', 'start_date' => null, 'end_date' => null]);
    DB::table('icip_access_restriction')->insert(['information_object_id' => 14, 'applies_to_descendants' => 0, 'start_date' => null, 'end_date' => null]);

    $fails = 0;
    $check = function (bool $ok, string $what) use (&$fails) {
        echo ($ok ? 'PASS ' : 'FAIL ').$what."\n";
        $fails += $ok ? 0 : 1;
    };
    $day = new \DateTimeImmutable('2026-11-22 10:00:00', new \DateTimeZone('Africa/Johannesburg'));
    $svc = new StorytellingService($day);
    $types = [11 => 'embargoed', 12 => 'classified', 13 => 'donor', 14 => 'ICIP', 15 => 'draft'];
    $publicStory = fn (array $ids) => (object) ['object_ids' => json_encode($ids), 'status' => 'published'];

    // The filter and the fixtures agree before anything else is trusted.
    $list = array_map('intval', Filter::getInstance()->getRestrictedObjectIds(null, true));
    sort($list);
    $check([11, 12, 13, 14] === $list, 'fixture: anonymous list is the four restricted records');

    $hits = array_column($svc->search('Riverboat'), 'id');
    sort($hits);
    $check([10, 16] === $hits, 'search offers only public records');

    foreach ($types as $id => $name) {
        Gateway::$prompts = [];
        $r = $svc->generate('', [$id, 10]);
        $check([10] === array_column($r['objects'], 'id') && 1 === count(Gateway::$prompts) && !str_contains(Gateway::$prompts[0], "Riverboat {$records[$id]}"), "generate: picked {$name} record not sent to the model");

        Gateway::$prompts = [];
        $r = $svc->generate('Riverboat');
        $check(!in_array($id, array_column($r['objects'], 'id'), true) && 1 === count(Gateway::$prompts) && !str_contains(Gateway::$prompts[0], "Riverboat {$records[$id]}"), "generate: {$name} record not a theme candidate");

        $r = $svc->save(['title' => "T {$name}", 'body' => 'x', 'status' => 'published', 'object_ids' => [10, $id]]);
        $check(!$r['ok'] && 0 === DB::table('ahg_story')->where('title', "T {$name}")->count(), "save: {$name} record refused, nothing stored");

        $check([] !== $svc->withheldReasons($publicStory([10, $id])), "render: story linking {$name} record is withheld");
        $check([10] === array_column($svc->storyRecords($publicStory([10, $id])), 'id'), "render: {$name} record not listed");
    }

    Gateway::$prompts = [];
    $r = $svc->onThisDay();
    $otd = array_column($r['objects'], 'id');
    sort($otd);
    $check([10, 16] === $otd && 1 === count(Gateway::$prompts), 'on this day uses only public records');

    // Restricted after publishing.
    $saved = $svc->save(['title' => 'Later', 'body' => 'Quotes the record.', 'status' => 'published', 'object_ids' => [16],
        'sources' => [['type' => 'record', 'label' => 'Riverboat later'], ['type' => 'record', 'label' => 'Riverboat embargoed']]]);
    $check($saved['ok'], 'a story from public records saves');
    $story = $svc->find($saved['id']);
    $check(1 === count($svc->storySources($story)), 'a credit naming an unlinked record is dropped');
    $check([] === $svc->withheldReasons($story), 'public before the restriction');
    DB::table('rights_embargo')->insert(['object_id' => 16, 'status' => 'active', 'embargo_type' => 'full', 'start_date' => '2000-01-01', 'end_date' => null]);
    $why = $svc->withheldReasons($story);
    $check(1 === count($why) && str_contains($why[0], 'record 16') && str_contains($why[0], 'restricted'), 'restricted later: withheld, and staff are told which record');
    $check('Quotes the record.' === $svc->find($saved['id'])->body, 'stored text unchanged');
    $r = $svc->save(['id' => $saved['id'], 'title' => 'Later', 'body' => 'Quotes the record.', 'status' => 'published', 'object_ids' => []]);
    $check(!$r['ok'] && '[16]' === $svc->find($saved['id'])->object_ids, 'unticking the restricted record cannot republish the story');
    DB::table('rights_embargo')->where('object_id', 16)->delete();
    $check([] === $svc->withheldReasons($story), 'restriction lifted: public again');

    DB::table('status')->where('object_id', 16)->update(['status_id' => 159]);
    $check(str_contains($svc->withheldReasons($story)[0] ?? '', 'not published'), 'unpublished later: withheld');
    DB::table('status')->where('object_id', 16)->update(['status_id' => 160]);
    $check([] !== $svc->withheldReasons((object) ['object_ids' => '{broken']), 'unreadable link list: withheld');
    $check([] !== $svc->withheldReasons($publicStory([99999])), 'deleted record: withheld');

    // Filter failure.
    Filter::addRestrictionSource(function () {
        throw new \RuntimeException('filter down');
    });
    Gateway::$prompts = [];
    $check([] === $svc->search('Riverboat'), 'filter failure: search returns nothing');
    $r = $svc->generate('Riverboat', [10], 'Background.');
    $check(!$r['ok'] && [] === $r['objects'] && [] === Gateway::$prompts, 'filter failure: generate refused, model not called');
    $r = $svc->generate('', [], 'Background only.');
    $check(!$r['ok'] && [] === Gateway::$prompts, 'filter failure: background-only generate refused');
    $r = $svc->onThisDay();
    $check(!$r['ok'] && [] === Gateway::$prompts, 'filter failure: on this day refused, model not called');
    $before = DB::table('ahg_story')->count();
    $r = $svc->save(['title' => 'During failure', 'body' => 'x', 'status' => 'published', 'object_ids' => [10]]);
    $check(!$r['ok'] && DB::table('ahg_story')->count() === $before, 'filter failure: save refused');
    $r = $svc->save(['title' => 'During failure', 'body' => 'x', 'status' => 'published', 'object_ids' => []]);
    $check(!$r['ok'] && DB::table('ahg_story')->count() === $before, 'filter failure: save with no records refused');
    $check([] !== $svc->withheldReasons($story) && [] === $svc->storyRecords($story), 'filter failure: render withheld, no records');
    $check([] !== $svc->withheldReasons($publicStory([])), 'filter failure: story with no records withheld');
    $prop = new \ReflectionProperty(Filter::class, 'sources');
    $prop->setValue(null, []);

    // The real show action: 404 for the public, the reason for staff.
    require_once __DIR__.'/../modules/story/actions/actions.class.php';
    $show = function (string $slug, bool $staff) {
        $a = new storyActions();
        $a->asStaff = $staff;
        $req = new class($slug) {
            public function __construct(private string $slug)
            {
            }

            public function getParameter($k, $d = null)
            {
                return 'slug' === $k ? $this->slug : $d;
            }
        };

        try {
            $a->executeShow($req);

            return $a;
        } catch (\DomainException $e) {
            return null;
        }
    };
    $slug = $svc->find($saved['id'])->slug;
    $check(null !== $show($slug, false), 'show: a clean published story is public');
    DB::table('rights_embargo')->insert(['object_id' => 16, 'status' => 'active', 'embargo_type' => 'full', 'start_date' => '2000-01-01', 'end_date' => null]);
    $check(null === $show($slug, false), 'show: restricted after publishing gives the public a 404');
    $staffView = $show($slug, true);
    $check(null !== $staffView && str_contains($staffView->withheld[0] ?? '', 'record 16'), 'show: staff see the page and the reason');
    DB::table('rights_embargo')->where('object_id', 16)->delete();
    Filter::addRestrictionSource(function () {
        throw new \RuntimeException('filter down');
    });
    $check(null === $show($slug, false), 'show: filter failure gives the public a 404');
    $staffView = $show($slug, true);
    $check(null !== $staffView && [] !== $staffView->withheld, 'show: filter failure is shown to staff');
    $prop->setValue(null, []);

    // Hours guard, in South African time.
    foreach (['05:59:00' => false, '06:00:00' => true, '17:59:00' => true, '18:00:00' => false] as $time => $open) {
        Gateway::$prompts = [];
        $s = new StorytellingService(new \DateTimeImmutable("2026-11-22 {$time}", new \DateTimeZone('Africa/Johannesburg')));
        $g = $s->generate('Riverboat');
        $d = $s->onThisDay();
        $check($open === $g['ok'] && $open === $d['ok'] && ($open ? 2 : 0) === count(Gateway::$prompts)
            && ($open || (str_contains($g['error'], '06:00') && str_contains($d['error'], '06:00'))), "hours {$time}: ".($open ? 'allowed' : 'refused with a message'));
    }
    $utc = new StorytellingService(new \DateTimeImmutable('2026-11-22 04:00:00', new \DateTimeZone('UTC')));
    $check($utc->generate('Riverboat')['ok'], 'hours: 04:00 UTC is 06:00 SAST');

    echo $fails ? "{$fails} FAILED\n" : "All checks passed\n";
    exit($fails ? 1 : 0);
}
