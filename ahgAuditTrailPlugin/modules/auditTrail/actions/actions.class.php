<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

// plugins/ahgAuditTrailPlugin/modules/ahgAuditTrailPlugin/actions/actions.class.php

class auditTrailActions extends AhgController
{
    protected function initFramework(): void
    {
        $frameworkPath = $this->config('sf_root_dir') . '/atom-framework';
        $bootstrapFile = $frameworkPath . '/bootstrap.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }

        $pluginPath = $this->config('sf_plugins_dir') . '/ahgAuditTrailPlugin/lib';
        
        require_once $pluginPath . '/Models/AuditLog.php';
        require_once $pluginPath . '/Models/AuditAuthentication.php';
        require_once $pluginPath . '/Models/AuditAccess.php';
        require_once $pluginPath . '/Models/AuditSetting.php';
        require_once $pluginPath . '/Repositories/AuditLogRepository.php';
        require_once $pluginPath . '/Repositories/AuditAuthenticationRepository.php';
        require_once $pluginPath . '/Repositories/AuditAccessRepository.php';
        require_once $pluginPath . '/Repositories/AuditSettingsRepository.php';
        require_once $pluginPath . '/Services/AuditService.php';
    }

    protected function checkAdmin(): void
    {
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeBrowse($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();

        // Build filters manually - don't use request->getParameter('action') as it conflicts with Symfony routing
        $filters = [];
        
        if ($request->getParameter('filter_action')) {
            $filters['action'] = $request->getParameter('filter_action');
        }
        if ($request->getParameter('user_id')) {
            $filters['user_id'] = $request->getParameter('user_id');
        }
        if ($request->getParameter('username')) {
            $filters['username'] = $request->getParameter('username');
        }
        if ($request->getParameter('entity_type')) {
            $filters['entity_type'] = $request->getParameter('entity_type');
        }
        if ($request->getParameter('status')) {
            $filters['status'] = $request->getParameter('status');
        }
        if ($request->getParameter('ip_address')) {
            $filters['ip_address'] = $request->getParameter('ip_address');
        }
        if ($request->getParameter('security_classification')) {
            $filters['security_classification'] = $request->getParameter('security_classification');
        }
        if ($request->getParameter('from_date')) {
            $filters['from_date'] = $request->getParameter('from_date');
        }
        if ($request->getParameter('to_date')) {
            $filters['to_date'] = $request->getParameter('to_date');
        }

        $page = (int) $request->getParameter('page', 1);
        $this->pager = $auditRepo->getFiltered($filters, 50, $page);
        $this->currentFilters = $filters;

        // Every filter dropdown is built from what the log actually contains.
        //
        // These were hardcoded lists that did not describe this table. The action
        // filter offered create/update/delete/download/export/import/publish -
        // none of which are values this log ever stores. It records "edit", not
        // "update"; "version_created", not "create". Only "view" overlapped, so
        // choosing any other action returned 0 results on 3.2M rows of history
        // and read as "there is no such activity" rather than "this filter cannot
        // match anything".
        //
        // The entity filter had the mirror problem in both directions: it offered
        // QubitRepository, QubitAccession and QubitDigitalObject, which do not
        // appear in the data at all, while omitting HeritageAsset - 930,437 rows,
        // the second most common type in the table - along with QubitStaticPage,
        // Registry, Institution, requestToPublish and feedback. Whole categories
        // of audit history were unreachable through the UI.
        //
        // Reading the values from the table is what the username filter below has
        // always done. The maps here only supply nicer labels; anything not named
        // falls back to a humanised form of the stored value, so a new action or
        // entity type appears in the filter as soon as it is logged, without
        // anyone having to remember to add it here.
        $this->actionTypes = $this->distinctWithLabels('action', [
            'view' => 'Viewed',
            'edit' => 'Edited',
            'login' => 'Logged in',
            'logout' => 'Logged out',
            'register' => 'Registered',
            'version_created' => 'Version created',
            'error404' => 'Not found (404)',
            'errorLog' => 'Error logged',
            'uploadChunk' => 'Upload (chunk)',
        ]);

        $this->entityTypes = $this->distinctWithLabels('entity_type', [
            'QubitInformationObject' => 'Archival Description',
            'QubitActor' => 'Authority Record',
            'QubitRepository' => 'Repository',
            'QubitTerm' => 'Term',
            'QubitUser' => 'User',
            'QubitAccession' => 'Accession',
            'QubitDigitalObject' => 'Digital Object',
            'QubitStaticPage' => 'Static Page',
            'HeritageAsset' => 'Heritage Asset',
            'requestToPublish' => 'Request to Publish',
        ]);

        // Keyed on the codes in the security_classification table - PUBLIC,
        // CONFIDENTIAL - which is what AuditService now records. The lowercase
        // keys that were here matched nothing, and nothing was ever written to
        // this column anyway.
        $this->securityLevels = $this->distinctWithLabels('security_classification', [
            'PUBLIC' => 'Public',
            'INTERNAL' => 'Internal',
            'RESTRICTED' => 'Restricted',
            'CONFIDENTIAL' => 'Confidential',
            'SECRET' => 'Secret',
            'TOP_SECRET' => 'Top Secret',
        ]);

        // Get distinct usernames from audit log for dropdown
        $this->usernames = DB::table('ahg_audit_log')
            ->whereNotNull('username')
            ->distinct()
            ->orderBy('username')
            ->pluck('username')
            ->toArray();
    }

    /**
     * Words that should not be title-cased like ordinary words.
     *
     * Without these, aiAssess reads "Ai Assess", adminErdEdit reads "Admin Erd
     * Edit" and webauthnRegisterBegin reads "Webauthn Register Begin" - each one
     * technically a word and none of them how anyone writes it.
     */
    protected const ACRONYMS = [
        'ai' => 'AI', 'api' => 'API', 'acl' => 'ACL', 'csv' => 'CSV', 'doi' => 'DOI',
        'ead' => 'EAD', 'erd' => 'ERD', 'html' => 'HTML', 'id' => 'ID', 'iiif' => 'IIIF',
        'ip' => 'IP', 'isbn' => 'ISBN', 'json' => 'JSON', 'marc' => 'MARC', 'mfa' => 'MFA',
        'oai' => 'OAI', 'ocr' => 'OCR', 'pdf' => 'PDF', 'pii' => 'PII', 'qr' => 'QR',
        'ric' => 'RiC', 'sip' => 'SIP', 'aip' => 'AIP', 'dip' => 'DIP', 'sql' => 'SQL',
        'url' => 'URL', 'xml' => 'XML', 'ner' => 'NER', 'llm' => 'LLM', 'pwa' => 'PWA',
        'webauthn' => 'WebAuthn', 'graphql' => 'GraphQL', 'sharepoint' => 'SharePoint',
    ];

    /**
     * Turn a stored code into something a person would write.
     *
     * Heratio renders the same list with ucfirst(), which leaves the underscore
     * and the camel hump in place - "Version_created", "UploadChunk". This does
     * the three things that actually make the difference: split on underscores
     * and camelCase, separate a trailing number so error404 is not one word, and
     * keep acronyms in the case people write them in.
     */
    protected static function humanise(string $value): string
    {
        $words = str_replace(['_', '-'], ' ', $value);

        // Split camelCase, but only where there is a case boundary to split on.
        // Applied to an all-caps code such as TOP_SECRET this would put a space
        // before every letter and render "T O P  S E C R E T".
        if ($words !== strtoupper($words)) {
            $words = preg_replace('/(?<!^)[A-Z]/', ' $0', $words);
        }

        // error404 -> "error 404", not "Error404".
        $words = preg_replace('/([a-zA-Z])(\d)/', '$1 $2', $words);

        $parts = preg_split('/\s+/', strtolower(trim($words)), -1, PREG_SPLIT_NO_EMPTY);

        if (!$parts) {
            return $value;
        }

        foreach ($parts as $i => $word) {
            $parts[$i] = self::ACRONYMS[$word] ?? ucfirst($word);
        }

        return implode(' ', $parts);
    }

    /**
     * The values a column actually holds, as value => label.
     *
     * Each of these columns is indexed, so DISTINCT is an index scan rather than
     * a pass over the 3.2M rows. Values without an entry in $labels are titled
     * from the stored value, which keeps a newly logged action or entity type
     * visible in the filter without a code change.
     */
    protected function distinctWithLabels(string $column, array $labels): array
    {
        $values = DB::table('ahg_audit_log')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->toArray();

        $out = [];

        foreach ($values as $value) {
            if (isset($labels[$value])) {
                $out[$value] = $labels[$value];

                continue;
            }

            $out[$value] = self::humanise((string) $value);
        }

        return $out;
    }

    public function executeView($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();
        $uuid = $request->getParameter('uuid');
        $this->auditLog = $uuid ? $auditRepo->findByUuid($uuid) : $auditRepo->find((int) $request->getParameter('id'));

        if (!$this->auditLog) {
            $this->forward404('Audit log entry not found');
        }

        $this->relatedLogs = [];
        if ($this->auditLog->entity_id) {
            $this->relatedLogs = $auditRepo->getEntityHistory($this->auditLog->entity_type, $this->auditLog->entity_id, 10);
        }
    }

    public function executeStatistics($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $days = (int) $request->getParameter('days', 30);
        $fromDate = \Carbon\Carbon::now()->subDays($days);
        $toDate = \Carbon\Carbon::now();

        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();
        $accessRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAccessRepository();

        $this->activitySummary = $auditRepo->getActivitySummary($fromDate, $toDate);
        $this->entityTypeStats = $auditRepo->getEntityTypeStats($fromDate, $toDate);
        $this->userStats = $auditRepo->getUserStats($fromDate, $toDate);
        $this->downloadStats = $accessRepo->getDownloadStats($fromDate, $toDate);
        $this->failedActions = $auditRepo->getFailedActions(20);
        $this->dateRange = ['from' => $fromDate, 'to' => $toDate, 'days' => $days];
    }

    public function executeAuthentication($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $authRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAuthenticationRepository();
        $this->recentLogins = $authRepo->getRecentLogins(50);
        $this->suspiciousActivity = $authRepo->getSuspiciousActivity(50);
    }

    public function executeSecurityAccess($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $classification = $request->getParameter('classification');
        $accessRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAccessRepository();
        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();

        $this->classifiedAccess = $accessRepo->getClassifiedAccess($classification);
        $this->deniedAccess = $accessRepo->getDeniedAccess();
        $this->securityAudit = $auditRepo->getSecurityAudit($classification);
        $this->classifications = ['public' => 'Public', 'restricted' => 'Restricted', 'confidential' => 'Confidential', 'secret' => 'Secret', 'top_secret' => 'Top Secret'];
    }

    public function executeUserActivity($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $userId = (int) $request->getParameter('user_id');
        if (!$userId) {
            $this->forward404('User ID is required');
        }

        $culture = $this->culture();
        $this->targetUser = DB::table("user as u")
            ->join("actor as a", "u.id", "=", "a.id")
            ->leftJoin("actor_i18n as ai", function($j) use ($culture) { $j->on("a.id", "=", "ai.id")->where("ai.culture", "=", $culture); })
            ->leftJoin("slug as s", "u.id", "=", "s.object_id")
            ->where("u.id", $userId)
            ->select("u.*", "ai.authorized_form_of_name as name", "s.slug")
            ->first();
        if (!$this->targetUser) {
            $this->forward404('User not found');
        }

        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();
        $authRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAuthenticationRepository();
        $accessRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAccessRepository();

        $this->activityLogs = $auditRepo->getUserActivity($userId);
        $this->authLogs = $authRepo->getByUser($userId);
        $this->accessLogs = $accessRepo->getByUser($userId);
    }

    public function executeEntityHistory($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $this->entityType = $request->getParameter('entity_type');
        $this->entityId = (int) $request->getParameter('entity_id');

        if (!$this->entityType || !$this->entityId) {
            $this->forward404('Entity type and ID are required');
        }

        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();
        $this->auditLogs = $auditRepo->getEntityHistory($this->entityType, $this->entityId);

        $this->entity = null;
        if (class_exists($this->entityType) && method_exists($this->entityType, 'getById')) {
            $this->entity = $this->entityType::getById($this->entityId);
        }
    }

    public function executeSettings($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $settingsRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditSettingsRepository();

        if ($request->isMethod('post')) {
            $settings = [
                'audit_enabled' => $request->getParameter('audit_enabled', '0'),
                'audit_views' => $request->getParameter('audit_views', '0'),
                'audit_creates' => $request->getParameter('audit_creates', '0'),
                'audit_updates' => $request->getParameter('audit_updates', '0'),
                'audit_deletes' => $request->getParameter('audit_deletes', '0'),
                'audit_authentication' => $request->getParameter('audit_authentication', '0'),
                'audit_failed_logins' => $request->getParameter('audit_failed_logins', '0'),
                'audit_imports' => $request->getParameter('audit_imports', '0'),
                'audit_exports' => $request->getParameter('audit_exports', '0'),
                'audit_downloads' => $request->getParameter('audit_downloads', '0'),
                'audit_sensitive_access' => $request->getParameter('audit_sensitive_access', '0'),
                'audit_permission_changes' => $request->getParameter('audit_permission_changes', '0'),
                'audit_api_requests' => $request->getParameter('audit_api_requests', '0'),
                'audit_searches' => $request->getParameter('audit_searches', '0'),
                'audit_ip_anonymize' => $request->getParameter('audit_ip_anonymize', '0'),
            ];
            foreach ($settings as $key => $value) {
                $settingsRepo->set($key, (bool) $value, 'boolean');
            }
            $this->getUser()->setFlash('notice', 'Audit settings updated successfully');
            $this->redirect(['module' => 'auditTrail', 'action' => 'settings']);
        }

        $this->settings = $settingsRepo->all();
    }

    public function executeExport($request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $format = $request->getParameter('format', 'csv');
        
        $filters = [];
        if ($request->getParameter('from_date')) {
            $filters['from_date'] = $request->getParameter('from_date');
        }
        if ($request->getParameter('to_date')) {
            $filters['to_date'] = $request->getParameter('to_date');
        }
        if ($request->getParameter('filter_action')) {
            $filters['action'] = $request->getParameter('filter_action');
        }
        if ($request->getParameter('entity_type')) {
            $filters['entity_type'] = $request->getParameter('entity_type');
        }

        $auditRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditLogRepository();
        $this->logs = $auditRepo->getFiltered($filters, 10000, 1);
        $this->format = $format;

        $filename = 'audit_log_export_' . date('Y-m-d_His');
        if ($format === 'json') {
            $this->response->setContentType('application/json');
            $this->response->setHttpHeader('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
        } else {
            $this->response->setContentType('text/csv');
            $this->response->setHttpHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
        }
        $this->setLayout(false);
    }
    /**
     * Return audit record data as JSON for compare modal
     */
    public function executeCompareData($request)
    {
        $this->initFramework();
        $this->setLayout(false);
        $this->getResponse()->setContentType('application/json');

        $id = $request->getParameter('id');

        if (!$id) {
            return $this->renderText(json_encode(['error' => 'No audit ID provided']));
        }

        try {
            $record = DB::table('ahg_audit_log')
                ->where('id', $id)
                ->first();

            if (!$record) {
                return $this->renderText(json_encode(['error' => 'Audit record not found']));
            }

            return $this->renderText(json_encode([
                'id' => $record->id,
                'entity_type' => $record->entity_type,
                'entity_id' => $record->entity_id,
                'entity_slug' => $record->entity_slug,
                'entity_title' => $record->entity_title,
                'action' => $record->action,
                'username' => $record->username,
                'old_values' => $record->old_values,
                'new_values' => $record->new_values,
                'changed_fields' => $record->changed_fields,
                'created_at' => $record->created_at,
            ]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    /**
     * Verify the tamper-evident hash chain of ahg_audit_log (#126).
     */
    public function executeIntegrity($request)
    {
        $this->initFramework();
        $this->checkAdmin();
        require_once $this->config('sf_plugins_dir') . '/ahgAuditTrailPlugin/lib/Services/ChainedAuditWriter.php';

        $this->result = \AtoM\Framework\Plugins\AuditTrail\Services\ChainedAuditWriter::verifyChain();
    }
}
