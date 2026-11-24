<?php
/**
 * Offline check of the ICIP policy, against a throwaway in-memory SQLite database.
 * Touches no real database and does not boot Symfony.
 *
 *   php ahgICIPPlugin/testing/policy-check.php
 */

namespace AtomFramework\Http\Controllers {
    class AhgController
    {
        public $response;
        public $user;
        public $redirected;

        public function getResponse() { return $this->response; }
        public function getUser() { return $this->user; }
        public function renderText($t) { $this->response->body = $t; return 'None'; }
        public function redirect($u) { $this->redirected = $u; throw new \sfStopException(); }
        public function forward($m, $a) { throw new \sfStopException("{$m}/{$a}"); }
        public function forward404() { throw new \sfError404Exception(); }
    }
}

namespace AtomFramework\Services {
    class CsrfService
    {
        public const FIELD_NAME = '_ahg_csrf_token';
        public static $sent;
        public static function getTokenFromRequest() { return self::$sent; }
        public static function validateToken(string $t): bool { return 'good' === $t; }
        public static function generateToken(): string { return 'good'; }
    }
}

namespace {
    use Illuminate\Database\Capsule\Manager as DB;

    class sfError404Exception extends \Exception {}
    class sfStopException extends \Exception {}
    class sfEvent
    {
        public function __construct(public $subject, public $params = []) {}
        public function getParameters() { return $this->params; }
        public function getSubject() { return $this->subject; }
    }
    class sfContext
    {
        public static $i;
        public $request, $user, $module = 'sfIsadPlugin', $action = 'index', $forwarded;
        public static function hasInstance() { return (bool) self::$i; }
        public static function getInstance() { return self::$i; }
        public function getRequest() { return $this->request; }
        public function getUser() { return $this->user; }
        public function getController() { return $this; }
        public function forward($m, $a) { $this->forwarded = "{$m}/{$a}"; }
        public function getModuleName() { return $this->module; }
        public function getActionName() { return $this->action; }
        public function getI18N() { return $this; }
        public function __($t) { return $t; }
    }

    require '/usr/share/nginx/archive/atom-framework/vendor/autoload.php';
    require __DIR__.'/../lib/ahgICIPService.class.php';
    require __DIR__.'/../lib/IcipPolicy.class.php';

    $capsule = new DB();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $capsule->setAsGlobal();

    foreach ([
        'CREATE TABLE information_object (id INTEGER PRIMARY KEY, lft INT, rgt INT)',
        'CREATE TABLE acl_user_group (user_id INT, group_id INT)',
        'CREATE TABLE icip_community_steward (id INTEGER PRIMARY KEY, community_id INT, user_id INT, created_by INT, created_at TEXT, UNIQUE(community_id, user_id))',
        'CREATE TABLE icip_config (config_key TEXT, config_value TEXT)',
        'CREATE TABLE icip_access_restriction (id INTEGER PRIMARY KEY, information_object_id INT, restriction_type TEXT, community_id INT, start_date TEXT, end_date TEXT, applies_to_descendants INT DEFAULT 1)',
        'CREATE TABLE icip_cultural_notice_type (id INTEGER PRIMARY KEY, name TEXT, default_text TEXT, severity TEXT, requires_acknowledgement INT DEFAULT 0, blocks_access INT DEFAULT 0, display_public INT DEFAULT 1, display_staff INT DEFAULT 1, display_order INT DEFAULT 1, is_active INT DEFAULT 1)',
        'CREATE TABLE icip_cultural_notice (id INTEGER PRIMARY KEY, information_object_id INT, notice_type_id INT, custom_text TEXT, community_id INT, applies_to_descendants INT DEFAULT 1, start_date TEXT, end_date TEXT)',
        'CREATE TABLE icip_notice_acknowledgement (id INTEGER PRIMARY KEY, notice_id INT, user_id INT, acknowledged_at TEXT, ip_address TEXT, user_agent TEXT, UNIQUE(notice_id, user_id))',
    ] as $sql) {
        DB::statement($sql);
    }

    const ANON = null, RESEARCHER = 10, EDITOR = 11, STEWARD = 12, OTHER_STEWARD = 13, ADMIN = 14;
    const COMMUNITY = 1, OTHER_COMMUNITY = 2;

    DB::table('acl_user_group')->insert([
        ['user_id' => RESEARCHER, 'group_id' => 99], ['user_id' => EDITOR, 'group_id' => 101], ['user_id' => ADMIN, 'group_id' => 100],
        ['user_id' => STEWARD, 'group_id' => 99], ['user_id' => OTHER_STEWARD, 'group_id' => 99],
    ]);
    DB::table('icip_community_steward')->insert([
        ['community_id' => COMMUNITY, 'user_id' => STEWARD], ['community_id' => OTHER_COMMUNITY, 'user_id' => OTHER_STEWARD],
    ]);

    $fail = 0;
    $count = 0;
    function check(string $name, $got, $want): void
    {
        global $fail, $count;
        ++$count;
        if ($got !== $want) {
            ++$fail;
            echo "FAIL {$name}: got ".var_export($got, true).', want '.var_export($want, true)."\n";
        }
    }

    $nextId = 100;
    function record(int $lft = 0, int $rgt = 0): int
    {
        global $nextId;
        $id = ++$nextId;
        DB::table('information_object')->insert(['id' => $id, 'lft' => $lft ?: $id * 10, 'rgt' => $rgt ?: $id * 10 + 1]);

        return $id;
    }

    // Policy table: [anonymous, researcher, editor, right steward, wrong steward, admin]
    $hard = ['deny', 'deny', 'metadata_only', 'allow', 'deny', 'allow'];
    $table = [
        'community_permission_required' => $hard, 'initiated_only' => $hard, 'elder_approval_required' => $hard,
        'gender_restricted_male' => $hard, 'gender_restricted_female' => $hard, 'repatriation_pending' => $hard,
        'seasonal' => $hard, 'mourning_period' => $hard, 'custom' => $hard, 'a_type_this_code_does_not_know' => $hard,
        'under_consultation' => ['metadata_only', 'metadata_only', 'allow', 'allow', 'metadata_only', 'allow'],
    ];
    $users = [ANON, RESEARCHER, EDITOR, STEWARD, OTHER_STEWARD, ADMIN];
    foreach ($table as $type => $wants) {
        $id = record();
        DB::table('icip_access_restriction')->insert(['information_object_id' => $id, 'restriction_type' => $type, 'community_id' => COMMUNITY]);
        foreach ($users as $i => $user) {
            check("{$type} user ".var_export($user, true), IcipPolicy::decide($id, $user), $wants[$i]);
        }
    }

    // No community named: no steward can open it.
    $id = record();
    DB::table('icip_access_restriction')->insert(['information_object_id' => $id, 'restriction_type' => 'seasonal', 'community_id' => null]);
    check('no community, steward', IcipPolicy::decide($id, STEWARD), 'deny');

    // Two communities: a steward of one does not open the other's restriction.
    $id = record();
    DB::table('icip_access_restriction')->insert([
        ['information_object_id' => $id, 'restriction_type' => 'seasonal', 'community_id' => COMMUNITY],
        ['information_object_id' => $id, 'restriction_type' => 'custom', 'community_id' => OTHER_COMMUNITY],
    ]);
    check('two communities, steward of one', IcipPolicy::decide($id, STEWARD), 'deny');

    // Inheritance to a grandchild, and applies_to_descendants = 0.
    $top = record(1000, 1005);
    $mid = record(1001, 1004);
    $leaf = record(1002, 1003);
    DB::table('icip_access_restriction')->insert(['information_object_id' => $top, 'restriction_type' => 'initiated_only', 'community_id' => COMMUNITY]);
    check('grandchild inherits', IcipPolicy::decide($leaf, ANON), 'deny');
    check('grandchild, steward', IcipPolicy::decide($leaf, STEWARD), 'allow');
    $top2 = record(2000, 2003);
    $kid2 = record(2001, 2002);
    DB::table('icip_access_restriction')->insert(['information_object_id' => $top2, 'restriction_type' => 'custom', 'applies_to_descendants' => 0]);
    check('not inherited when off: child', IcipPolicy::decide($kid2, ANON), 'allow');
    check('not inherited when off: parent', IcipPolicy::decide($top2, ANON), 'deny');

    // Expired and future.
    $id = record();
    DB::table('icip_access_restriction')->insert(['information_object_id' => $id, 'restriction_type' => 'seasonal', 'end_date' => date('Y-m-d', strtotime('-1 day'))]);
    check('expired restriction', IcipPolicy::decide($id, ANON), 'allow');
    $id = record();
    DB::table('icip_access_restriction')->insert(['information_object_id' => $id, 'restriction_type' => 'seasonal', 'start_date' => date('Y-m-d', strtotime('+1 day'))]);
    check('future restriction', IcipPolicy::decide($id, ANON), 'allow');
    $id = record();
    DB::table('icip_access_restriction')->insert(['information_object_id' => $id, 'restriction_type' => 'seasonal', 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]);
    check('in force today', IcipPolicy::decide($id, ANON), 'deny');

    // Notices.
    $ackType = DB::table('icip_cultural_notice_type')->insertGetId(['name' => 'Ack', 'requires_acknowledgement' => 1]);
    $blockType = DB::table('icip_cultural_notice_type')->insertGetId(['name' => 'Block', 'requires_acknowledgement' => 1, 'blocks_access' => 1]);
    $hiddenType = DB::table('icip_cultural_notice_type')->insertGetId(['name' => 'Staff only', 'requires_acknowledgement' => 1, 'display_public' => 0]);
    $offType = DB::table('icip_cultural_notice_type')->insertGetId(['name' => 'Off', 'blocks_access' => 1, 'is_active' => 0]);

    $ackRecord = record();
    $ackNotice = DB::table('icip_cultural_notice')->insertGetId(['information_object_id' => $ackRecord, 'notice_type_id' => $ackType, 'community_id' => COMMUNITY]);
    check('ack: anonymous', IcipPolicy::decide($ackRecord, ANON), 'acknowledge');
    check('ack: anonymous with session ack', IcipPolicy::decide($ackRecord, ANON, [$ackNotice]), 'allow');
    check('ack: another session', IcipPolicy::decide($ackRecord, ANON, []), 'acknowledge');
    check('ack: session ack ignored once signed in', IcipPolicy::decide($ackRecord, RESEARCHER, [$ackNotice]), 'acknowledge');
    check('ack: steward of community', IcipPolicy::decide($ackRecord, STEWARD), 'allow');
    check('ack: admin', IcipPolicy::decide($ackRecord, ADMIN), 'allow');

    $blockRecord = record();
    $blockNotice = DB::table('icip_cultural_notice')->insertGetId(['information_object_id' => $blockRecord, 'notice_type_id' => $blockType, 'community_id' => COMMUNITY]);
    DB::table('icip_notice_acknowledgement')->insert(['notice_id' => $blockNotice, 'user_id' => RESEARCHER]);
    check('blocks_access: not lifted by an acknowledgement', IcipPolicy::decide($blockRecord, RESEARCHER), 'deny');
    check('blocks_access: session ack does not lift', IcipPolicy::decide($blockRecord, ANON, [$blockNotice]), 'deny');
    check('blocks_access: editor', IcipPolicy::decide($blockRecord, EDITOR), 'metadata_only');
    check('blocks_access: steward', IcipPolicy::decide($blockRecord, STEWARD), 'allow');
    check('blocks_access: wrong steward', IcipPolicy::decide($blockRecord, OTHER_STEWARD), 'deny');
    check('blocks_access: not acknowledgeable', IcipPolicy::isAcknowledgeable($blockNotice), false);
    $denied = IcipPolicy::explain($blockRecord, ANON);
    check('deny carries no notice detail', [$denied['visible'], $denied['pending']], [[], []]);

    $id = record();
    DB::table('icip_cultural_notice')->insert(['information_object_id' => $id, 'notice_type_id' => $hiddenType]);
    check('staff-only notice not demanded of public', IcipPolicy::decide($id, ANON), 'allow');
    check('staff-only notice demanded of editor', IcipPolicy::decide($id, EDITOR), 'acknowledge');
    $id = record();
    DB::table('icip_cultural_notice')->insert(['information_object_id' => $id, 'notice_type_id' => $offType]);
    check('inactive notice type', IcipPolicy::decide($id, ANON), 'allow');

    // Failure denies: a broken table on a record.
    $id = record();
    DB::table('icip_access_restriction')->insert(['information_object_id' => $id, 'restriction_type' => 'custom']);
    DB::statement('ALTER TABLE acl_user_group RENAME TO acl_user_group_x');
    check('policy exception denies', IcipPolicy::decide(record(), RESEARCHER) === 'allow' ? IcipPolicy::decide($id, RESEARCHER) : 'unexpected', 'deny');
    $explained = IcipPolicy::explain($id, RESEARCHER);
    check('policy exception is marked failed', $explained['failed'], true);
    DB::statement('ALTER TABLE acl_user_group_x RENAME TO acl_user_group');
    DB::statement('ALTER TABLE icip_cultural_notice RENAME TO icip_cultural_notice_x');
    DB::statement('CREATE TABLE icip_cultural_notice (id INTEGER PRIMARY KEY)');
    check('broken notice table denies', IcipPolicy::decide($ackRecord, ADMIN), 'deny');
    DB::statement('DROP TABLE icip_cultural_notice');
    DB::statement('ALTER TABLE icip_cultural_notice_x RENAME TO icip_cultural_notice');

    // The real acknowledge action.
    require __DIR__.'/../lib/IcipGate.class.php';
    require __DIR__.'/../modules/icip/actions/actions.class.php';

    class Res
    {
        public $status = 200;
        public $body;
        public function setContentType($t) {}
        public function setStatusCode($c) { $this->status = $c; }
        public function setHttpHeader($k, $v) {}
    }
    class Usr
    {
        public $a = [];
        public function __construct($id = null) { if ($id) { $this->a['user_id'] = $id; } }
        public function getAttribute($k, $d = null) { return $this->a[$k] ?? $d; }
        public function setAttribute($k, $v) { $this->a[$k] = $v; }
        public $creds = [];
        public function hasCredential($c) { return in_array($c, $this->creds, true); }
    }
    class Req
    {
        public function __construct(public $m, public $p = []) {}
        public function isMethod($x) { return $x === $this->m; }
        public function getParameter($k, $d = null) { return $this->p[$k] ?? $d; }
        public function isXmlHttpRequest() { return false; }
    }
    function ack(string $method, ?string $token, int $noticeId, Usr $user, string $return = '/rec'): icipActions
    {
        \AtomFramework\Services\CsrfService::$sent = $token;
        $a = (new ReflectionClass('icipActions'))->newInstanceWithoutConstructor();
        $a->response = new Res();
        $a->user = $user;
        try {
            $a->executeAcknowledge(new Req($method, ['notice_id' => $noticeId, 'return' => $return]));
        } catch (sfStopException $e) {
        }

        return $a;
    }
    $acks = fn () => DB::table('icip_notice_acknowledgement')->where('notice_id', $ackNotice)->count();

    check('GET refused', ack('get', 'good', $ackNotice, new Usr(RESEARCHER))->response->status, 405);
    check('GET wrote nothing', $acks(), 0);
    check('POST without CSRF refused', ack('post', null, $ackNotice, new Usr(RESEARCHER))->response->status, 403);
    check('POST with bad CSRF refused', ack('post', 'bad', $ackNotice, new Usr(RESEARCHER))->response->status, 403);
    check('refused POSTs wrote nothing', $acks(), 0);
    check('blocking notice cannot be acknowledged', ack('post', 'good', $blockNotice, new Usr(EDITOR))->response->status, 404);
    check('unknown notice', ack('post', 'good', 999999, new Usr(RESEARCHER))->response->status, 404);

    $done = ack('post', 'good', $ackNotice, new Usr(RESEARCHER));
    check('valid POST recorded', $acks(), 1);
    check('valid POST returns to the page', $done->redirected, '/rec');
    check('that user is unlocked', IcipPolicy::decide($ackRecord, RESEARCHER), 'allow');
    check('another user is not', IcipPolicy::decide($ackRecord, EDITOR), 'acknowledge');
    check('anonymous is not', IcipPolicy::decide($ackRecord, ANON), 'acknowledge');

    $visitor = new Usr();
    ack('post', 'good', $ackNotice, $visitor);
    check('anonymous ack stays in the session', $acks(), 1);
    check('that session is unlocked', IcipPolicy::decide($ackRecord, ANON, $visitor->a[IcipGate::SESSION_ACKS]), 'allow');
    check('another session is not', IcipPolicy::decide($ackRecord, ANON, (new Usr())->getAttribute(IcipGate::SESSION_ACKS, [])), 'acknowledge');

    foreach (['//evil.example/x', '/\\evil.example', 'https://evil.example/', "/a\r\nX: y", "/a\n"] as $bad) {
        check('open redirect refused: '.addcslashes($bad, "\r\n"), ack('post', 'good', $ackNotice, new Usr(RESEARCHER), $bad)->redirected, '@homepage');
    }

    // The real gate listeners.
    DB::statement('CREATE TABLE slug (object_id INT, slug TEXT)');
    DB::statement('CREATE TABLE digital_object (id INTEGER PRIMARY KEY, object_id INT, parent_id INT, path TEXT, name TEXT)');
    DB::statement('CREATE TABLE icip_access_log (id INTEGER PRIMARY KEY, information_object_id INT, user_id INT, access_allowed INT, blocked_reason TEXT, ip_address TEXT, created_at TEXT)');
    DB::statement('CREATE TABLE icip_tk_label (id INTEGER PRIMARY KEY, information_object_id INT, label_type_id INT, community_id INT)');
    DB::statement('CREATE TABLE icip_tk_label_type (id INTEGER PRIMARY KEY, code TEXT, category TEXT, name TEXT, description TEXT, icon_path TEXT, local_contexts_url TEXT, display_order INT DEFAULT 1, is_active INT DEFAULT 1)');
    DB::statement('CREATE TABLE icip_community (id INTEGER PRIMARY KEY, name TEXT)');

    class GateReq
    {
        public $attr = [];
        public function __construct(public $slug = '', public $uri = 'http://x/') {}
        public function getParameterHolder() { return new class { public function remove($k) {} }; }
        public function getParameter($k, $d = null) { return 'slug' === $k ? $this->slug : $d; }
        public function getAttribute($k, $d = null) { return $this->attr[$k] ?? $d; }
        public function setAttribute($k, $v) { $this->attr[$k] = $v; }
        public function getUri() { return $this->uri; }
    }
    function gate(array $params, GateReq $req, ?int $user = null, array $acks = []): string
    {
        $c = new sfContext();
        $c->request = $req;
        $c->user = new Usr($user);
        if ($acks) {
            $c->user->a[IcipGate::SESSION_ACKS] = $acks;
        }
        sfContext::$i = $c;
        try {
            IcipGate::onChangeAction(new sfEvent(null, $params));
        } catch (sfError404Exception $e) {
            return '404';
        } catch (sfStopException $e) {
            return 'forward:'.$c->forwarded;
        }

        return 'pass';
    }
    $page = fn ($slug, $user = null, $acks = [], $module = 'informationobject') => gate(['module' => $module, 'action' => 'index'], new GateReq($slug), $user, $acks);
    $file = fn ($uri, $user = null) => gate(['module' => 'digitalobject', 'action' => 'view'], new GateReq('', 'http://x'.$uri), $user);

    $closed = record();
    $open = record();
    $consult = record();
    DB::table('icip_access_restriction')->insert([
        ['information_object_id' => $closed, 'restriction_type' => 'initiated_only', 'community_id' => COMMUNITY],
        ['information_object_id' => $consult, 'restriction_type' => 'under_consultation', 'community_id' => COMMUNITY],
    ]);
    DB::table('slug')->insert([
        ['object_id' => $closed, 'slug' => 'closed'], ['object_id' => $open, 'slug' => 'open'],
        ['object_id' => $consult, 'slug' => 'consult'], ['object_id' => $ackRecord, 'slug' => 'needs-ack'],
    ]);
    DB::table('digital_object')->insert([
        ['id' => 1, 'object_id' => $closed, 'parent_id' => null, 'path' => '/uploads/r/x/closed/', 'name' => 'm.tif'],
        ['id' => 2, 'object_id' => null, 'parent_id' => 1, 'path' => '/uploads/r/x/closed/', 'name' => 'm_141.jpg'],
        ['id' => 3, 'object_id' => $consult, 'parent_id' => null, 'path' => '/uploads/r/x/consult/', 'name' => 'c.tif'],
        ['id' => 4, 'object_id' => $open, 'parent_id' => null, 'path' => '/uploads/r/x/open/', 'name' => 'o.tif'],
        ['id' => 5, 'object_id' => $ackRecord, 'parent_id' => null, 'path' => '/uploads/r/x/ack/', 'name' => 'a.tif'],
    ]);

    foreach (['informationobject', 'sfIsadPlugin', 'museum', 'library', 'gallery', 'dam'] as $module) {
        check("denied on {$module}", $page('closed', null, [], $module), '404');
        check("open on {$module}", $page('open', null, [], $module), 'pass');
    }
    check('denied same as missing', $page('closed'), '404');
    check('steward passes', $page('closed', STEWARD), 'pass');
    check('ack forwards to the notice step', $page('needs-ack'), 'forward:icip/notice');
    check('session ack passes the gate', $page('needs-ack', null, [$ackNotice]), 'pass');
    check('metadata only reaches the page', $page('consult'), 'pass');
    check('unknown slug left to the app', $page('no-such-record'), 'pass');

    check('master denied', $file('/uploads/r/x/closed/m.tif'), '404');
    check('derivative denied via fallback', $file('/uploads/r/x/closed/m_141.jpg'), '404');
    check('derivative with index.php prefix denied', $file('/index.php/uploads/r/x/closed/m_141.jpg'), '404');
    check('doubled slashes denied', $file('/uploads//r/x/closed/m_141.jpg'), '404');
    check('encoded path denied', $file('/uploads/r/x/closed/m%5F141.jpg'), '404');
    check('steward gets the file', $file('/uploads/r/x/closed/m.tif', STEWARD), 'pass');
    check('metadata-only user gets no file', $file('/uploads/r/x/consult/c.tif'), '404');
    check('unacknowledged user gets no master', $file('/uploads/r/x/ack/a.tif'), '404');
    check('open file passes', $file('/uploads/r/x/open/o.tif'), 'pass');
    check('file that is not a record passes', $file('/uploads/r/x/conf/logo.png'), 'pass');

    DB::statement('ALTER TABLE digital_object RENAME TO digital_object_x');
    check('file lookup failure denies', $file('/uploads/r/x/open/o.tif'), '404');
    DB::statement('ALTER TABLE digital_object_x RENAME TO digital_object');
    DB::statement('ALTER TABLE slug RENAME TO slug_x');
    check('slug lookup failure denies', $page('open'), '404');
    DB::statement('ALTER TABLE slug_x RENAME TO slug');

    // Image server and IIIF by numeric id.
    class GateReqP extends GateReq
    {
        public $params = [];
        public function getParameter($k, $d = null) { return $this->params[$k] ?? $d; }
    }
    $tile = function ($identifier, $grants = '') {
        $r = new GateReqP();
        $r->params = ['identifier' => $identifier, 'grants' => $grants];

        return gate(['module' => 'iiifAuth', 'action' => 'cantaloupeCheck'], $r);
    };
    $byId = function ($action, $id, $user = null) {
        $r = new GateReqP();
        $r->params = ['id' => $id];

        return gate(['module' => 'iiif', 'action' => $action], $r, $user);
    };
    check('tile of a closed record refused', $tile('uploads_SL_r_SL_x_SL_closed_SL_m_141.jpg'), 'forward:icip/imageDenied');
    check('tile, percent-encoded identifier', $tile('uploads%2Fr%2Fx%2Fclosed%2Fm_141.jpg'), 'forward:icip/imageDenied');
    check('tile, multi-page suffix', $tile('uploads_SL_r_SL_x_SL_closed_SL_m.tif;2'), 'forward:icip/imageDenied');
    check('tile of a metadata-only record refused', $tile('uploads_SL_r_SL_x_SL_consult_SL_c.tif'), 'forward:icip/imageDenied');
    check('tile of an open record passes', $tile('uploads_SL_r_SL_x_SL_open_SL_o.tif'), 'pass');
    check('tile of an acknowledge-only record passes', $tile('uploads_SL_r_SL_x_SL_ack_SL_a.tif'), 'pass');
    check('unknown file left to the action', $tile('uploads_SL_r_SL_nothing.jpg'), 'pass');
    check('forged grant does not open a tile', $tile('uploads_SL_r_SL_x_SL_closed_SL_m_141.jpg', ADMIN.'.9999999999.'.str_repeat('a', 64)), 'forward:icip/imageDenied');
    foreach (['manifestById', 'viewer', 'ocrExport', 'aiCanvases', 'aiExtractList', 'aiExtractReview'] as $action) {
        check("iiif {$action} denied", $byId($action, $closed), '404');
        check("iiif {$action} open", $byId($action, $open), 'pass');
    }
    check('iiif viewer by digital object id denied', $byId('viewer', 1), '404');
    check('iiif by id, steward', $byId('manifestById', $closed, STEWARD), 'pass');
    check('iiif by id, metadata only', $byId('manifestById', $consult), '404');
    check('iiif manifest by slug, metadata only', gate(['module' => 'iiif', 'action' => 'manifest'], new GateReq('consult')), '404');

    // Page output.
    $html = '<body><div id="main-column" role="main"><img src="/uploads/r/x/consult/c_141.jpg" alt=""><a href="/uploads/r/x/consult/c.tif">x</a>'
        .'<div data-id="uploads_SL_r_SL_x_SL_consult_SL_c.tif"></div><script>var u="\/uploads\/r\/x\/consult\/c.tif"</script><img src="/keep.png"></div>';
    $out = IcipGate::withholdMedia($html, $consult);
    check('media path gone', false === strpos($out, 'consult'), true);
    check('media tag gone', false === strpos($out, 'c_141'), true);
    check('other images stay', false !== strpos($out, '/keep.png'), true);

    $xss = (object) ['name' => '<script>x</script>', 'custom_text' => '"><b>', 'default_text' => '', 'severity' => 'critical'];
    $b = IcipGate::banner(['decision' => 'allow', 'visible' => [$xss]], [(object) ['label_name' => 'TK <A>', 'local_contexts_url' => 'javascript:alert(1)']]);
    check('banner escapes notice text', false === strpos($b, '<script>') && false === strpos($b, '"><b>'), true);
    check('label link must be http(s)', false === strpos($b, 'javascript:'), true);
    check('critical maps to danger', false !== strpos($b, 'alert-danger'), true);
    $b = IcipGate::banner(['decision' => 'allow', 'visible' => []], [(object) ['label_name' => 'TK A', 'local_contexts_url' => 'https://localcontexts.org/label/tk-a/']]);
    check('label links to Local Contexts', false !== strpos($b, 'href="https://localcontexts.org/label/tk-a/"'), true);
    check('nothing to show, no markup', IcipGate::banner(['decision' => 'allow', 'visible' => []], []), '');

    // Filter on a real request shape: denied or non-view pages untouched, metadata-only stripped.
    class FilterRes { public $status = 200; public function getContentType() { return 'text/html; charset=utf-8'; } public function setStatusCode($c) { $this->status = $c; } }
    $filter = function ($result, $module, $action, $body) {
        $c = new sfContext();
        $c->request = new GateReq();
        $c->request->attr['ahg_icip_result'] = $result;
        $c->user = new Usr();
        $c->module = $module;
        $c->action = $action;
        sfContext::$i = $c;

        return IcipGate::filterContent(new sfEvent(new FilterRes()), $body);
    };
    $meta = ['decision' => 'metadata_only', 'visible' => [], 'pending' => [], 'governed' => true, 'admin' => false, 'failed' => false, 'object_id' => $consult];
    foreach (['sfIsadPlugin', 'museum', 'library', 'gallery', 'dam'] as $module) {
        $got = $filter($meta, $module, 'index', $html);
        check("{$module}: media withheld", false === strpos($got, 'consult'), true);
        check("{$module}: banner placed in main column", false !== strpos($got, 'icip-record-notices'), true);
    }
    check('edit page not rewritten', $filter($meta, 'museum', 'edit', $html), $html);
    sfContext::$i = null;

    // The real steward screen.
    DB::statement('CREATE TABLE user (id INTEGER PRIMARY KEY, username TEXT)');
    DB::table('user')->insert(['id' => 50, 'username' => 'newsteward']);
    DB::table('icip_community')->insert([['id' => COMMUNITY, 'name' => 'A'], ['id' => OTHER_COMMUNITY, 'name' => 'B']]);
    $stewardCall = function (string $method, ?string $token, array $params, bool $admin = true) {
        \AtomFramework\Services\CsrfService::$sent = $token;
        $a = (new ReflectionClass('icipActions'))->newInstanceWithoutConstructor();
        $a->response = new Res();
        $a->user = new Usr(ADMIN);
        $a->user->creds = $admin ? ['administrator'] : ['editor'];
        try {
            $a->executeCommunityStewards(new Req($method, $params + ['id' => COMMUNITY]));
        } catch (sfStopException $e) {
            return $e->getMessage() ?: 'redirect';
        }

        return 'rendered:'.$a->response->status;
    };
    $isSteward = fn () => DB::table('icip_community_steward')->where(['community_id' => COMMUNITY, 'user_id' => 50])->exists();

    check('steward screen refused to an editor', $stewardCall('post', 'good', ['username' => 'newsteward'], false), 'admin/secure');
    check('editor added nobody', $isSteward(), false);
    check('add without CSRF refused', $stewardCall('post', null, ['username' => 'newsteward']), 'rendered:403');
    check('refused add wrote nothing', $isSteward(), false);
    $stewardCall('get', null, ['username' => 'newsteward']);
    check('GET adds nobody', $isSteward(), false);
    check('admin adds a steward', $stewardCall('post', 'good', ['username' => 'newsteward']), 'redirect');
    check('steward recorded', $isSteward(), true);
    check('new steward opens the record', IcipPolicy::decide($closed, 50), 'allow');
    $sid = (int) DB::table('icip_community_steward')->where(['community_id' => COMMUNITY, 'user_id' => 50])->value('id');
    $otherSid = (int) DB::table('icip_community_steward')->where('community_id', OTHER_COMMUNITY)->value('id');
    $stewardCall('post', 'good', ['form_action' => 'remove', 'steward_id' => $otherSid]);
    check('remove cannot reach another community', DB::table('icip_community_steward')->where('id', $otherSid)->exists(), true);
    $stewardCall('post', 'good', ['form_action' => 'remove', 'steward_id' => $sid]);
    check('admin removes a steward', $isSteward(), false);
    check('removed steward is denied', IcipPolicy::decide($closed, 50), 'deny');

    echo $fail ? "{$fail} of {$count} failed\n" : "All {$count} checks passed\n";
    exit($fail ? 1 : 0);
}
