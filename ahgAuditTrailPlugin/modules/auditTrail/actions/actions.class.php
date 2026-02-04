<?php
use Illuminate\Database\Capsule\Manager as DB;

// plugins/ahgAuditTrailPlugin/modules/ahgAuditTrailPlugin/actions/actions.class.php

class auditTrailActions extends sfActions
{
    protected function initFramework(): void
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        $bootstrapFile = $frameworkPath . '/bootstrap.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }

        $pluginPath = sfConfig::get('sf_plugins_dir') . '/ahgAuditTrailPlugin/lib';
        
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
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeBrowse(sfWebRequest $request)
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

        $this->actionTypes = [
            'create' => 'Created', 'update' => 'Updated', 'delete' => 'Deleted',
            'view' => 'Viewed', 'download' => 'Downloaded', 'export' => 'Exported',
            'import' => 'Imported', 'publish' => 'Published',
        ];

        $this->entityTypes = [
            'QubitInformationObject' => 'Archival Description',
            'QubitActor' => 'Authority Record',
            'QubitRepository' => 'Repository',
            'QubitTerm' => 'Term',
            'QubitUser' => 'User',
            'QubitAccession' => 'Accession',
            'QubitDigitalObject' => 'Digital Object',
        ];

        $this->securityLevels = [
            'public' => 'Public', 'restricted' => 'Restricted',
            'confidential' => 'Confidential', 'secret' => 'Secret', 'top_secret' => 'Top Secret',
        ];

        // Get distinct usernames from audit log for dropdown
        $this->usernames = DB::table('ahg_audit_log')
            ->whereNotNull('username')
            ->distinct()
            ->orderBy('username')
            ->pluck('username')
            ->toArray();
    }

    public function executeView(sfWebRequest $request)
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

    public function executeStatistics(sfWebRequest $request)
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

    public function executeAuthentication(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $authRepo = new \AtoM\Framework\Plugins\AuditTrail\Repositories\AuditAuthenticationRepository();
        $this->recentLogins = $authRepo->getRecentLogins(50);
        $this->suspiciousActivity = $authRepo->getSuspiciousActivity(50);
    }

    public function executeSecurityAccess(sfWebRequest $request)
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

    public function executeUserActivity(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->initFramework();

        $userId = (int) $request->getParameter('user_id');
        if (!$userId) {
            $this->forward404('User ID is required');
        }

        $culture = $this->context->user->getCulture();
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

    public function executeEntityHistory(sfWebRequest $request)
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

    public function executeSettings(sfWebRequest $request)
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

    public function executeExport(sfWebRequest $request)
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
    public function executeCompareData(sfWebRequest $request)
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
}
