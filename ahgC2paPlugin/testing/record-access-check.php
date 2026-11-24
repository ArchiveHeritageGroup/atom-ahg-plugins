<?php

/**
 * ahgC2paPlugin - offline access matrix for every public record route (#152).
 *
 *   php ahgC2paPlugin/testing/record-access-check.php [plugin-dir]
 *
 * Runs the real action classes, RecordAccess, badge listener and the framework
 * SearchAccessFilterService against an in-memory SQLite database. It does not
 * boot Symfony, read a site database, or touch a key or a master file.
 * Set ATOM_FRAMEWORK if atom-framework is not in a parent directory.
 * Exit code 0 = every check passes.
 */

declare(strict_types=1);

namespace AtomFramework\Http\Controllers {
    #[\AllowDynamicProperties]
    class AhgController
    {
        public \StubResponse $response;

        public $context = null;

        public function __construct()
        {
            $this->response = new \StubResponse();
        }

        public function getResponse(): \StubResponse
        {
            return $this->response;
        }

        public function getUser(): \StubUser
        {
            return \sfContext::getInstance()->getUser();
        }

        public function forward404($message = null): void
        {
            throw new \StubNotFound();
        }

        public function renderText($text)
        {
            $this->response->body = (string) $text;

            return \sfView::NONE;
        }

        // Same as the framework under Symfony: the status argument is not applied.
        protected function renderJson(array $data, int $status = 200)
        {
            return $this->renderText((string) json_encode($data));
        }

        protected function renderJsonError(string $message, int $code = 400)
        {
            return $this->renderJson(['error' => $message], $code);
        }
    }
}

namespace AhgInferenceReceipts {
    if (!class_exists(JcsEncoder::class, false)) {
        class JcsEncoder
        {
            public static function encode($value): string
            {
                return (string) json_encode($value);
            }
        }
    }
}

namespace {
    use AhgC2pa\Listeners\ContentCredentialsBadge;
    use AtomExtensions\Services\Search\SearchAccessFilterService;
    use Illuminate\Database\Capsule\Manager as DB;

    final class StubNotFound extends \RuntimeException
    {
    }

    final class StubResponse
    {
        public int $status = 200;

        public string $body = '';

        public array $headers = [];

        public function setStatusCode($code): void
        {
            $this->status = (int) $code;
        }

        public function getStatusCode(): int
        {
            return $this->status;
        }

        public function setContentType($type): void
        {
            $this->headers['Content-Type'] = $type;
        }

        public function setHttpHeader($name, $value): void
        {
            $this->headers[$name] = $value;
        }

        public function clearHttpHeaders(): void
        {
            $this->headers = [];
        }

        public function sendHttpHeaders(): void
        {
        }
    }

    final class StubUser
    {
        public function __construct(public int $id = 0)
        {
        }

        public function isAuthenticated(): bool
        {
            return $this->id > 0;
        }

        public function getAttribute($name, $default = null)
        {
            return 'user_id' === $name ? $this->id : $default;
        }

        public function hasCredential($credentials, $useAnd = true): bool
        {
            return false;
        }

        public function getCulture(): string
        {
            return 'en';
        }
    }

    final class sfContext
    {
        public static StubUser $user;

        private static ?self $instance = null;

        public static function hasInstance(): bool
        {
            return true;
        }

        public static function getInstance(): self
        {
            return self::$instance ??= new self();
        }

        public function getUser(): StubUser
        {
            return self::$user;
        }
    }

    final class sfView
    {
        public const SUCCESS = 'Success';
        public const NONE = 'None';
    }

    final class QubitTerm
    {
        public const PUBLICATION_STATUS_PUBLISHED_ID = 160;
        public const TEXT_ID = 139;
    }

    class QubitInformationObject
    {
        public int $id = 0;

        public static function getById($id): ?self
        {
            if (!DB::table('information_object')->where('id', $id)->exists()) {
                return null;
            }
            $io = new self();
            $io->id = (int) $id;

            return $io;
        }
    }

    final class QubitAcl
    {
        // Deliberately permissive for read: the plugin gate must not lean on it.
        public static function check($resource, $action): bool
        {
            return 'viewDraft' === $action ? sfContext::$user->id === ADMIN_ID : true;
        }
    }

    final class QubitDigitalObject
    {
        public ?int $parentId = null;

        public $object = null;

        public int $mediaTypeId = 136;

        public static function getById($id): ?self
        {
            $row = DB::table('digital_object')->where('id', $id)->first();
            if (null === $row) {
                return null;
            }
            $do = new self();
            $do->parentId = $row->parent_id;
            $do->object = QubitInformationObject::getById($row->object_id);

            return $do;
        }

        public function masterAccessibleViaUrl(): bool
        {
            return true;
        }

        public function hasConditionalCopyright(): bool
        {
            return false;
        }
    }

    final class StubProvenance
    {
        public function listForObject(int $id): array
        {
            return [];
        }

        public function listForDigitalObject(int $id): array
        {
            return [];
        }

        public function verifyRecord(int $id): array
        {
            return ['status' => 'verified'];
        }
    }

    final class StubMasters
    {
        public function resolveMasterForDownload(int $id): ?array
        {
            return ['path' => MASTER_FIXTURE, 'name' => 'fixture.txt', 'mime' => 'text/plain', 'object_id' => $id];
        }
    }

    final class StubSigning
    {
        public function canSign(): bool
        {
            return true;
        }

        public function canEmbed(): bool
        {
            return false;
        }

        public function manifestForDigitalObject(...$args): array
        {
            return ['claim' => ['fixture' => 1]];
        }

        public function signManifest(array $manifest): array
        {
            return $manifest;
        }
    }

    final class C2paBootstrap
    {
        public static function load(): bool
        {
            return true;
        }

        public static function provenanceService(): StubProvenance
        {
            return new StubProvenance();
        }

        public static function digitalObjectService(): StubMasters
        {
            return new StubMasters();
        }

        public static function c2paService(): StubSigning
        {
            return new StubSigning();
        }
    }

    function url_for($route, $absolute = false): string
    {
        return '/stub';
    }
    function __($text, $args = []): string
    {
        return (string) $text;
    }

    $plugin = rtrim($argv[1] ?? dirname(__DIR__), '/');
    $framework = getenv('ATOM_FRAMEWORK') ?: null;
    for ($dir = $plugin; null === $framework && '/' !== $dir; $dir = dirname($dir)) {
        if (is_dir($dir . '/atom-framework/src')) {
            $framework = $dir . '/atom-framework';
        }
    }
    if (null === $framework) {
        fwrite(STDERR, "atom-framework not found; set ATOM_FRAMEWORK\n");
        exit(2);
    }
    require $framework . '/vendor/autoload.php';
    require_once $framework . '/src/Services/Search/SearchAccessFilterService.php';
    foreach (['Manifest/CborEncoder', 'Manifest/Assertion', 'Manifest/Claim', 'Manifest/StandardMetadataLoader', 'Manifest/C2paSigner', 'Manifest/ManifestBuilder',
        'Services/C2paService', 'Services/ProvenanceRecordService', 'Services/ManifestPresenter', 'Services/RecordAccess', 'Listeners/ContentCredentialsBadge'] as $file) {
        require_once $plugin . '/lib/' . $file . '.php';
    }
    require_once $plugin . '/modules/c2pa/actions/actions.class.php';
    require_once $plugin . '/modules/c2paVerify/actions/actions.class.php';

    const ADMIN_ID = 5;
    define('MASTER_FIXTURE', (string) tempnam(sys_get_temp_dir(), 'c2pa-check-'));
    file_put_contents(MASTER_FIXTURE, 'fixture');
    register_shutdown_function(static fn () => @unlink(MASTER_FIXTURE));

    $capsule = new DB();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    foreach ([
        'information_object (id INTEGER PRIMARY KEY, identifier TEXT, lft INT, rgt INT)',
        'information_object_i18n (id INT, culture TEXT, title TEXT)',
        'slug (object_id INT, slug TEXT)',
        'status (object_id INT, type_id INT, status_id INT)',
        'digital_object (id INTEGER PRIMARY KEY, object_id INT, name TEXT, mime_type TEXT, parent_id INT)',
        'ahg_c2pa_manifest (id INTEGER PRIMARY KEY, information_object_id INT, digital_object_id INT, manifest_label TEXT, asset_hash TEXT, kid TEXT, signature_hex TEXT, manifest_json TEXT, created_at TEXT)',
        'ahg_c2pa_provenance (id INTEGER PRIMARY KEY, information_object_id INT, digital_object_id INT, manifest_id INT)',
        'security_classification (id INTEGER PRIMARY KEY, level INT)',
        'object_security_classification (object_id INT, classification_id INT, active INT)',
        'rights_embargo (id INTEGER PRIMARY KEY, object_id INT, status TEXT, embargo_type TEXT, start_date TEXT, end_date TEXT)',
        'icip_access_restriction (id INTEGER PRIMARY KEY, information_object_id INT, start_date TEXT, end_date TEXT, applies_to_descendants INT)',
        'acl_user_group (user_id INT, group_id INT)',
    ] as $table) {
        DB::statement('CREATE TABLE ' . $table);
    }

    $records = ['public' => 10, 'draft' => 11, 'embargoed' => 12, 'classified' => 13, 'icip' => 14, 'no-status' => 15];
    foreach ($records as $name => $id) {
        DB::table('information_object')->insert(['id' => $id, 'identifier' => 'REF-' . $id, 'lft' => $id * 2, 'rgt' => $id * 2 + 1]);
        DB::table('information_object_i18n')->insert(['id' => $id, 'culture' => 'en', 'title' => 'SECRET-TITLE-' . $id]);
        DB::table('slug')->insert(['object_id' => $id, 'slug' => $name]);
        if ('no-status' !== $name) {
            DB::table('status')->insert(['object_id' => $id, 'type_id' => 158, 'status_id' => 'draft' === $name ? 159 : 160]);
        }
        DB::table('digital_object')->insert(['id' => $id + 100, 'object_id' => $id, 'name' => 'f.txt', 'mime_type' => 'text/plain', 'parent_id' => null]);
        DB::table('ahg_c2pa_manifest')->insert(['id' => $id + 200, 'information_object_id' => $id, 'digital_object_id' => $id + 100,
            'manifest_label' => 'L', 'asset_hash' => 'H', 'kid' => 'k', 'signature_hex' => 'ab', 'manifest_json' => '{"claim":{"title":"SECRET"}}', 'created_at' => '2026-01-01']);
        DB::table('ahg_c2pa_provenance')->insert(['information_object_id' => $id, 'digital_object_id' => $id + 100, 'manifest_id' => $id + 200]);
    }
    // A manifest on a public record whose file belongs to a draft, and one with no record.
    DB::table('ahg_c2pa_manifest')->insert(['id' => 400, 'information_object_id' => 10, 'digital_object_id' => 111,
        'manifest_label' => 'L', 'asset_hash' => 'H', 'kid' => 'k', 'signature_hex' => 'ab', 'manifest_json' => '{"claim":{"title":"SECRET"}}', 'created_at' => '2026-01-01']);
    DB::table('ahg_c2pa_manifest')->insert(['id' => 401, 'information_object_id' => null, 'digital_object_id' => null,
        'manifest_label' => 'L', 'asset_hash' => 'H', 'kid' => 'k', 'signature_hex' => 'ab', 'manifest_json' => '{"claim":{"title":"SECRET"}}', 'created_at' => '2026-01-01']);
    DB::table('security_classification')->insert(['id' => 1, 'level' => 3]);
    DB::table('object_security_classification')->insert(['object_id' => 13, 'classification_id' => 1, 'active' => 1]);
    DB::table('rights_embargo')->insert(['object_id' => 12, 'status' => 'active', 'embargo_type' => 'full', 'start_date' => '2020-01-01', 'end_date' => null]);
    DB::table('icip_access_restriction')->insert(['information_object_id' => 14, 'start_date' => null, 'end_date' => null, 'applies_to_descendants' => 0]);
    DB::table('acl_user_group')->insert(['user_id' => ADMIN_ID, 'group_id' => 100]);

    $filterFails = false;
    SearchAccessFilterService::addRestrictionSource(static function () use (&$filterFails): array {
        if ($filterFails) {
            throw new \RuntimeException('fixture filter failure');
        }

        return [];
    });

    final class StubRequest
    {
        public function __construct(private int $id)
        {
        }

        public function getParameter($name, $default = null)
        {
            return 'id' === $name ? $this->id : $default;
        }
    }

    /** Run one action; the result is what a client could tell apart. */
    function call(string $class, string $method, int $id): array
    {
        $action = new $class();
        ob_start();
        try {
            $action->{$method}(new StubRequest($id));
            $body = $action->response->body . (string) ob_get_contents();
            if (isset($action->object) && null !== $action->object) {
                $body .= json_encode($action->object);
            }
            $status = $action->response->status;
        } catch (StubNotFound) {
            [$status, $body] = [404, ''];
        } finally {
            ob_end_clean();
        }

        return ['status' => $status, 'body' => 404 === $status ? '' : $body];
    }

    $routes = [
        'manifest' => ['c2paActions', 'executeManifest', 200],
        'manifests' => ['c2paActions', 'executeManifests', 0],
        'record' => ['c2paVerifyActions', 'executeRecord', 0],
        'object' => ['c2paVerifyActions', 'executeObject', 100],
        'download' => ['c2paVerifyActions', 'executeDownload', 100],
        'credentials' => ['c2paVerifyActions', 'executeCredentials', 100],
        'badge' => ['c2paVerifyActions', 'executeBadge', 100],
        'badge-json' => ['c2paVerifyActions', 'executeBadgeJson', 100],
    ];

    $fails = 0;
    $check = static function (bool $ok, string $what) use (&$fails): void {
        echo($ok ? 'PASS ' : 'FAIL ') . $what . "\n";
        $fails += $ok ? 0 : 1;
    };
    $badge = new ReflectionMethod(ContentCredentialsBadge::class, 'badgeForSlug');

    sfContext::$user = new StubUser();
    foreach ($routes as $route => [$class, $method, $offset]) {
        $missing = call($class, $method, 99 + $offset);
        $check(404 === $missing['status'], "{$route}: missing record answers 404");
        $check(200 === call($class, $method, 10 + $offset)['status'], "{$route}: public record is served");
        foreach (['draft', 'embargoed', 'classified', 'icip', 'no-status'] as $name) {
            $check(call($class, $method, $records[$name] + $offset) === $missing, "{$route}: {$name} matches the missing-record 404");
        }
        $filterFails = true;
        $check(call($class, $method, 10 + $offset) === $missing, "{$route}: filter exception fails closed");
        $filterFails = false;
    }

    $missing = call('c2paActions', 'executeManifest', 299);
    $check(call('c2paActions', 'executeManifest', 400) === $missing, 'manifest: public record, file on a draft: 404');
    $check(call('c2paActions', 'executeManifest', 401) === $missing, 'manifest: no linked record: 404');
    $list = json_decode(call('c2paActions', 'executeManifests', 10)['body'], true);
    $check([210] === array_column($list['manifests'] ?? [], 'id'), 'manifests: list omits a manifest whose file is on a draft');

    $check('' !== $badge->invoke(null, 'public'), 'page badge: shown on a public record');
    foreach (['draft', 'embargoed', 'classified', 'icip', 'no-status', 'absent'] as $name) {
        $check('' === $badge->invoke(null, $name), "page badge: none for {$name}");
    }
    $filterFails = true;
    $check('' === $badge->invoke(null, 'public'), 'page badge: filter exception shows none');
    $filterFails = false;

    sfContext::$user = new StubUser(ADMIN_ID);
    foreach (['draft', 'embargoed', 'classified', 'icip'] as $name) {
        $check(200 === call('c2paActions', 'executeManifest', $records[$name] + 200)['status'], "administrator can still read {$name}");
    }

    echo $fails ? "{$fails} check(s) FAILED\n" : "All checks passed\n";
    exit($fails ? 1 : 0);
}
