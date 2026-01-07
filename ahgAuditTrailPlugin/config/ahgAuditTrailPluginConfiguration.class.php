<?php
class ahgAuditTrailPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Comprehensive audit trail logging for AtoM';
    public static $version = '1.0.0';

    public function initialize(): void
    {
        // Enable the module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgAuditTrail';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Hook into response event for audit logging (fires after action completes)
        $this->dispatcher->connect('response.filter_content', [$this, 'onResponseFilterContent']);
    }

    public function onResponseFilterContent(sfEvent $event, $content)
    {
        // Log authentication events
        $this->logAuthentication();
        // Log the action after response is generated
        $this->logAction();
        return $content;
    }

    protected function logAction(): void
    {
        try {
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (!file_exists($frameworkPath)) {
                return;
            }
            require_once $frameworkPath;

            if (!sfContext::hasInstance()) {
                return;
            }

            $context = sfContext::getInstance();
            $user = $context->getUser();
            $request = $context->getRequest();
            $module = $context->getModuleName();
            $action = $context->getActionName();

            // Only log write operations and specific actions
            $auditableActions = ['create', 'edit', 'update', 'delete', 'copy', 'import', 'export', 'publish', 'unpublish'];
            $isWriteOperation = $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE');
            
            if (!$isWriteOperation && !in_array($action, $auditableActions)) {
                return;
            }

            // Skip non-auditable modules
            $auditableModules = [
                'informationobject', 'actor', 'repository', 'term', 'taxonomy',
                'accession', 'deaccession', 'donor', 'rightsholder', 'function',
                'physicalobject', 'digitalobject', 'user', 'aclGroup', 'staticpage',
                'ahgMuseumPlugin', 'sfLibraryPlugin', 'sfGalleryPlugin',
            ];
            
            if (!in_array($module, $auditableModules)) {
                return;
            }

            // Check if audit is enabled
            $enabled = \Illuminate\Database\Capsule\Manager::table('ahg_audit_settings')
                ->where('setting_key', 'audit_enabled')
                ->value('setting_value');

            if ($enabled !== '1') {
                return;
            }

            $userId = null;
            $username = 'anonymous';
            if ($user->isAuthenticated()) {
                $userId = $user->getAttribute('user_id');
                $username = $user->getAttribute('username') ?? $user->getUsername();
            }

            // Determine action type
            $actionType = $action;
            if ($request->isMethod('POST') && in_array($action, ['index', 'edit'])) {
                $actionType = $request->getParameter('id') ? 'update' : 'create';
            }

            \Illuminate\Database\Capsule\Manager::table('ahg_audit_log')->insert([
                'uuid' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                'user_id' => $userId,
                'username' => $username,
                'action' => $actionType,
                'entity_type' => $this->resolveEntityType($module),
                'module' => $module,
                'action_name' => $action,
                'request_method' => $request->getMethod(),
                'request_uri' => substr($request->getUri(), 0, 2000),
                'ip_address' => $request->getRemoteAddress(),
                'user_agent' => substr($request->getHttpHeader('User-Agent') ?? '', 0, 500),
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silently fail - don't break the app
            error_log('Audit log error: ' . $e->getMessage());
        }
    }

    protected function resolveEntityType($module): string
    {
        $map = [
            'informationobject' => 'QubitInformationObject',
            'actor' => 'QubitActor',
            'repository' => 'QubitRepository',
            'term' => 'QubitTerm',
            'taxonomy' => 'QubitTaxonomy',
            'user' => 'QubitUser',
            'aclGroup' => 'QubitAclGroup',
            'digitalobject' => 'QubitDigitalObject',
            'accession' => 'QubitAccession',
            'deaccession' => 'QubitDeaccession',
            'donor' => 'QubitDonor',
            'rightsholder' => 'QubitRightsHolder',
            'function' => 'QubitFunction',
            'physicalobject' => 'QubitPhysicalObject',
            'staticpage' => 'QubitStaticPage',
            'ahgMuseumPlugin' => 'QubitMuseumObject',
            'sfLibraryPlugin' => 'QubitLibraryObject',
            'sfGalleryPlugin' => 'QubitGalleryObject',
        ];
        return $map[$module] ?? $module;
    }

    protected function logAuthentication(): void
    {
        try {
            if (!sfContext::hasInstance()) {
                return;
            }
            
            $context = sfContext::getInstance();
            $user = $context->getUser();
            $request = $context->getRequest();
            $module = $context->getModuleName();
            $action = $context->getActionName();
            
            // Only check user module login/logout
            if ($module !== 'user' || !in_array($action, ['login', 'logout'])) {
                return;
            }
            
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (!file_exists($frameworkPath)) {
                return;
            }
            require_once $frameworkPath;
            
            // Check if auth audit is enabled
            $auditAuth = \Illuminate\Database\Capsule\Manager::table('ahg_audit_settings')
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
            
            // Login success - user is now authenticated after POST
            if ($action === 'login' && $request->isMethod('POST') && $user->isAuthenticated()) {
                $eventType = 'login';
                $userId = $user->getAttribute('user_id');
                $username = $user->getAttribute('user_name') ?? $user->getUserName();
            }
            // Failed login - POST but not authenticated
            elseif ($action === 'login' && $request->isMethod('POST') && !$user->isAuthenticated()) {
                $eventType = 'failed_login';
                $username = $request->getParameter('email');
                $status = 'failure';
                $failureReason = 'Invalid credentials';
            }
            // Logout - response is sent after signOut()
            elseif ($action === 'logout') {
                $eventType = 'logout';
                // User is already signed out, get from session or request
                $userId = $request->getAttribute('_pre_logout_user_id');
                $username = $request->getAttribute('_pre_logout_username') ?? 'unknown';
            }
            
            if ($eventType) {
                \Illuminate\Database\Capsule\Manager::table('ahg_audit_authentication')->insert([
                    'uuid' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                    'event_type' => $eventType,
                    'user_id' => $userId,
                    'username' => $username,
                    'ip_address' => $request->getRemoteAddress(),
                    'user_agent' => substr($request->getHttpHeader('User-Agent') ?? '', 0, 500),
                    'session_id' => session_id() ?: null,
                    'status' => $status,
                    'failure_reason' => $failureReason,
                    'failed_attempts' => 0,
                    'metadata' => json_encode(['action' => $action, 'module' => $module]),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                error_log("AUTH LOGGED: {$eventType} for {$username}");
            }
        } catch (\Exception $e) {
            error_log('Auth audit error: ' . $e->getMessage());
        }
    }

}
