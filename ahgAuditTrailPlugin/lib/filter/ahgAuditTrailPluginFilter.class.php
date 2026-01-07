<?php

/**
 * Audit Trail Filter - Intercepts all actions and logs them
 *
 * @package    ahgAuditTrailPlugin
 * @subpackage filter
 */
class ahgAuditTrailPluginFilter extends sfFilter
{
    /**
     * Actions that should be audited
     */
    protected static $auditableActions = [
        'create', 'edit', 'update', 'delete', 'copy',
        'import', 'export', 'move', 'rename',
        'publish', 'unpublish',
        'addDigitalObject', 'deleteDigitalObject',
        'updatePublicationStatus',
    ];

    /**
     * Modules to audit
     */
    protected static $auditableModules = [
        'informationobject',
        'actor',
        'repository',
        'term',
        'taxonomy',
        'accession',
        'deaccession',
        'donor',
        'rightsholder',
        'function',
        'physicalobject',
        'digitalobject',
        'user',
        'aclGroup',
        'staticpage',
        'ahgMuseumPlugin',
        'sfLibraryPlugin',
        'sf3DModelPlugin',
        'sfGalleryPlugin',
    ];

    /**
     * Execute the filter
     */
    public function execute($filterChain)
    {
        // Only execute on first call
        if ($this->isFirstCall()) {
            $context = $this->getContext();
            $request = $context->getRequest();
            $user = $context->getUser();
            
            $moduleName = $context->getModuleName();
            $actionName = $context->getActionName();

            // Store pre-action state for comparison
            $request->setAttribute('_audit_pre_state', $this->captureState($request));
            $request->setAttribute('_audit_start_time', microtime(true));
            
            // Track authentication state before action
            $request->setAttribute('_audit_was_authenticated', $user->isAuthenticated());
            $request->setAttribute('_audit_pre_user_id', $user->getAttribute('user_id'));
        }

        // Execute the rest of the filter chain
        $filterChain->execute();

        // After action execution - log if applicable
        if ($this->isFirstCall()) {
            $this->logAuthenticationChange();
            $this->logAction();
        }
    }

    /**
     * Log the action to the audit trail
     */
    protected function logAction()
    {
        try {
            $context = $this->getContext();
            $request = $context->getRequest();
            $response = $context->getResponse();
            $user = $context->getUser();

            $moduleName = $context->getModuleName();
            $actionName = $context->getActionName();

            // Check if this action should be audited
            if (!$this->shouldAudit($moduleName, $actionName, $request)) {
                return;
            }

            // Initialize Laravel framework
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
            if (!file_exists($frameworkPath . '/bootstrap.php')) {
                return;
            }

            require_once $frameworkPath . '/bootstrap.php';

            // Check if audit is enabled
            $enabled = \Illuminate\Support\Facades\DB::table('ahg_audit_settings')
                ->where('setting_key', 'audit_enabled')
                ->value('setting_value');

            if ($enabled !== '1') {
                return;
            }

            // Determine the entity being affected
            $entityType = $this->resolveEntityType($moduleName);
            $entityId = $this->resolveEntityId($request);
            $entityTitle = $this->resolveEntityTitle($request);

            // Determine action type
            $action = $this->resolveAction($actionName, $request);

            // Get user info
            $userId = null;
            $username = 'anonymous';
            $userEmail = null;

            if ($user->isAuthenticated()) {
                $userId = $user->getAttribute('user_id');
                $username = $user->getAttribute('username') ?? $user->getUsername() ?? 'anonymous';
                if ($userId) {
                    $userRecord = \Illuminate\Support\Facades\DB::table('user')
                        ->where('id', $userId)
                        ->first();
                    if ($userRecord) {
                        $userEmail = $userRecord->email ?? null;
                        // Get username from DB if not in session
                        if ($username === 'anonymous' || empty($username)) {
                            $username = $userRecord->username ?? 'anonymous';
                        }
                    }
                }
            }

            // Capture changes for update actions
            $oldValues = null;
            $newValues = null;
            if ($action === 'update') {
                $preState = $request->getAttribute('_audit_pre_state');
                $postState = $this->captureState($request);
                if ($preState && $postState) {
                    $oldValues = json_encode($preState);
                    $newValues = json_encode($postState);
                }
            }

            // Insert audit log
            \Illuminate\Support\Facades\DB::table('ahg_audit_log')->insert([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $userId,
                'username' => $username,
                'user_email' => $userEmail,
                'ip_address' => $request->getRemoteAddress(),
                'user_agent' => substr($request->getHttpHeader('User-Agent') ?? '', 0, 500),
                'session_id' => session_id() ?: null,
                'action' => $action,
                'action_category' => $this->getActionCategory($action),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'entity_title' => $entityTitle,
                'entity_slug' => $request->getParameter('slug'),
                'module_name' => $moduleName,
                'action_name' => $actionName,
                'request_uri' => $request->getUri(),
                'request_method' => $request->getMethod(),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'response_status' => $response->getStatusCode(),
                'execution_time' => microtime(true) - $request->getAttribute('_audit_start_time', microtime(true)),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            // Log error but don't break the application
            error_log('AuditTrail Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if this request should be audited
     */
    protected function shouldAudit($moduleName, $actionName, $request)
    {
        // Skip AJAX polling requests
        if ($request->isXmlHttpRequest() && $actionName === 'index') {
            return false;
        }

        // Skip static assets
        // Skip ahgMuseumPlugin - it handles its own audit logging
        if ($moduleName === 'ahgMuseumPlugin') {
            return false;
        }
        if (in_array($moduleName, ['sfAsset', 'sfWebDebug', 'default'])) {
            return false;
        }

        // Always audit write operations
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            return true;
        }

        // Audit specific actions
        if (in_array($actionName, self::$auditableActions)) {
            return true;
        }

        // Check if views should be audited
        if ($actionName === 'index' && in_array($moduleName, self::$auditableModules)) {
            try {
                $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
                if (file_exists($frameworkPath . '/bootstrap.php')) {
                    require_once $frameworkPath . '/bootstrap.php';
                    $auditViews = \Illuminate\Support\Facades\DB::table('ahg_audit_settings')
                        ->where('setting_key', 'audit_views')
                        ->value('setting_value');
                    return $auditViews === '1';
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Resolve entity type from module name
     */
    protected function resolveEntityType($moduleName)
    {
        $mapping = [
            'informationobject' => 'QubitInformationObject',
            'actor' => 'QubitActor',
            'repository' => 'QubitRepository',
            'term' => 'QubitTerm',
            'taxonomy' => 'QubitTaxonomy',
            'accession' => 'QubitAccession',
            'deaccession' => 'QubitDeaccession',
            'donor' => 'QubitDonor',
            'rightsholder' => 'QubitRightsHolder',
            'function' => 'QubitFunction',
            'physicalobject' => 'QubitPhysicalObject',
            'digitalobject' => 'QubitDigitalObject',
            'user' => 'QubitUser',
            'aclGroup' => 'QubitAclGroup',
            'staticpage' => 'QubitStaticPage',
            'ahgMuseumPlugin' => 'MuseumObject',
            'sfLibraryPlugin' => 'LibraryItem',
            'sf3DModelPlugin' => 'Object3D',
            'sfGalleryPlugin' => 'GalleryWork',
        ];

        return $mapping[$moduleName] ?? ucfirst($moduleName);
    }

    /**
     * Resolve entity ID from request
     */
    protected function resolveEntityId($request)
    {
        // Try various parameter names
        $id = $request->getParameter('id');
        if ($id) {
            return is_numeric($id) ? (int)$id : null;
        }

        // Try to get from slug
        $slug = $request->getParameter('slug');
        if ($slug) {
            try {
                $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
                if (file_exists($frameworkPath . '/bootstrap.php')) {
                    require_once $frameworkPath . '/bootstrap.php';
                    $id = \Illuminate\Support\Facades\DB::table('slug')
                        ->where('slug', $slug)
                        ->value('object_id');
                    return $id ? (int)$id : null;
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Resolve entity title from request
     */
    protected function resolveEntityTitle($request)
    {
        // Try to get from request attributes (set by action)
        $resource = $request->getAttribute('sf_resource');
        if ($resource && method_exists($resource, '__toString')) {
            return substr((string)$resource, 0, 255);
        }

        if ($resource && property_exists($resource, 'title')) {
            return substr($resource->title ?? '', 0, 255);
        }

        return null;
    }

    /**
     * Resolve action type
     */
    protected function resolveAction($actionName, $request)
    {
        // Normalize action names
        $actionMap = [
            'create' => 'create',
            'add' => 'create',
            'new' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'delete' => 'delete',
            'destroy' => 'delete',
            'index' => 'view',
            'show' => 'view',
            'view' => 'view',
            'copy' => 'copy',
            'duplicate' => 'copy',
            'import' => 'import',
            'export' => 'export',
            'download' => 'download',
            'publish' => 'publish',
            'unpublish' => 'unpublish',
            'move' => 'move',
            'addDigitalObject' => 'upload',
            'deleteDigitalObject' => 'delete_file',
        ];

        $action = $actionMap[$actionName] ?? $actionName;

        // Override based on HTTP method for ambiguous actions
        if ($request->isMethod('POST') && $action === 'view') {
            $action = 'create';
        }

        return $action;
    }

    /**
     * Get action category
     */
    protected function getActionCategory($action)
    {
        $categories = [
            'create' => 'data_modification',
            'update' => 'data_modification',
            'delete' => 'data_modification',
            'view' => 'data_access',
            'copy' => 'data_modification',
            'import' => 'data_transfer',
            'export' => 'data_transfer',
            'download' => 'data_access',
            'upload' => 'data_modification',
            'delete_file' => 'data_modification',
            'publish' => 'workflow',
            'unpublish' => 'workflow',
            'move' => 'data_modification',
        ];

        return $categories[$action] ?? 'other';
    }

    /**
     * Capture current state for change tracking
     */
    protected function captureState($request)
    {
        $params = $request->getParameterHolder()->getAll();
        
        // Remove sensitive data
        unset($params['password'], $params['confirm_password'], $params['_csrf_token']);
        
        return $params;
    }

    /**
     * Log authentication state changes (login/logout/failed login)
     */
    protected function logAuthenticationChange()
    {
        try {
            $context = $this->getContext();
            $request = $context->getRequest();
            $user = $context->getUser();
            
            $moduleName = $context->getModuleName();
            $actionName = $context->getActionName();
            
            // Only check for user module login/logout actions
            if ($moduleName !== 'user' || !in_array($actionName, ['login', 'logout'])) {
                return;
            }
            
            $wasAuthenticated = $request->getAttribute('_audit_was_authenticated', false);
            $isAuthenticated = $user->isAuthenticated();
            $preUserId = $request->getAttribute('_audit_pre_user_id');
            
            // Initialize Laravel
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
            if (!file_exists($frameworkPath . '/bootstrap.php')) {
                return;
            }
            require_once $frameworkPath . '/bootstrap.php';
            
            // Check if auth audit is enabled
            $auditAuth = \Illuminate\Support\Facades\DB::table('ahg_audit_settings')
                ->where('setting_key', 'audit_authentication')
                ->value('setting_value');
            if ($auditAuth !== '1') {
                return;
            }
            
            $eventType = null;
            $userId = null;
            $username = null;
            $status = 'success';
            $failureReason = null;
            
            // Detect login success
            if ($actionName === 'login' && !$wasAuthenticated && $isAuthenticated) {
                $eventType = 'login';
                $userId = $user->getAttribute('user_id');
                $username = $user->getAttribute('user_name') ?? $user->getUserName();
            }
            // Detect logout
            elseif ($actionName === 'logout' && $wasAuthenticated && !$isAuthenticated) {
                $eventType = 'logout';
                $userId = $preUserId;
                // Get username from DB since session is cleared
                if ($userId) {
                    $userRecord = \Illuminate\Support\Facades\DB::table('user')
                        ->where('id', $userId)
                        ->first();
                    $username = $userRecord->username ?? 'unknown';
                }
            }
            // Detect failed login (POST to login, still not authenticated)
            elseif ($actionName === 'login' && $request->isMethod('POST') && !$isAuthenticated) {
                $eventType = 'failed_login';
                $username = $request->getParameter('email'); // AtoM uses email field
                $status = 'failure';
                $failureReason = 'Invalid credentials';
            }
            
            if ($eventType) {
                \Illuminate\Support\Facades\DB::table('ahg_audit_authentication')->insert([
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'event_type' => $eventType,
                    'user_id' => $userId,
                    'username' => $username,
                    'ip_address' => $request->getRemoteAddress(),
                    'user_agent' => substr($request->getHttpHeader('User-Agent') ?? '', 0, 500),
                    'session_id' => session_id() ?: null,
                    'status' => $status,
                    'failure_reason' => $failureReason,
                    'failed_attempts' => 0,
                    'metadata' => json_encode(['action' => $actionName, 'module' => $moduleName]),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            error_log('AuditTrail Auth Error: ' . $e->getMessage());
        }
    }

}
