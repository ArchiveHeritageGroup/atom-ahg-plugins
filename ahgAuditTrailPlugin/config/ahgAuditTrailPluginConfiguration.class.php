<?php

/**
 * ahgAuditTrailPlugin Configuration
 *
 * Comprehensive audit trail logging for AtoM.
 * Provides AhgAuditService for centralized audit logging.
 */
class ahgAuditTrailPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Comprehensive audit trail logging for AtoM';
    public static $version = '1.1.0';

    public function initialize(): void
    {
        // Register autoloader for plugin namespaces
        $this->registerAutoloader();

        // Enable the module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'auditTrail';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register AhgAuditService with AhgCore
        $this->registerAuditService();

        // Hook into response event for audit logging (fires after action completes)
        $this->dispatcher->connect('response.filter_content', [$this, 'onResponseFilterContent']);
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            // Handle AhgAuditTrail namespace
            if (strpos($class, 'AhgAuditTrail\\') === 0) {
                $relativePath = str_replace('AhgAuditTrail\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Register the audit service with AhgCore
     */
    protected function registerAuditService(): void
    {
        try {
            // Load AhgCore if available
            if (class_exists('AhgCore\\AhgCore', true)) {
                \AhgAuditTrail\Services\AhgAuditService::register();
            }
        } catch (\Exception $e) {
            // AhgCore not available, continue without registration
        }
    }

    /**
     * Response filter content hook
     */
    public function onResponseFilterContent(sfEvent $event, $content)
    {
        // Log authentication events
        $this->logAuthentication();
        // Log the action after response is generated
        $this->logAction();
        return $content;
    }

    /**
     * Log action using AhgAuditService
     */
    protected function logAction(): void
    {
        try {
            if (!sfContext::hasInstance()) {
                return;
            }

            // Use AhgCore's database if available
            if (!class_exists('AhgCore\\Core\\AhgDb')) {
                // Fallback to direct bootstrap
                $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
                if (!file_exists($frameworkPath)) {
                    return;
                }
                require_once $frameworkPath;
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
                'museum', 'library', 'gallery',
                'model3d', 'dam',
            ];

            if (!in_array($module, $auditableModules)) {
                return;
            }

            // Use AhgAuditService if available
            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
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

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $actionType,
                    $this->resolveEntityType($module),
                    $this->resolveEntityId($request),
                    [
                        'user_id' => $userId,
                        'username' => $username,
                        'module' => $module,
                        'action_name' => $action,
                        'slug' => $request->getParameter('slug'),
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log('Audit log error: ' . $e->getMessage());
        }
    }

    /**
     * Resolve entity ID from request
     */
    protected function resolveEntityId($request): ?int
    {
        $id = $request->getParameter('id');
        if ($id && is_numeric($id)) {
            return (int)$id;
        }

        $slug = $request->getParameter('slug');
        if ($slug) {
            try {
                $db = class_exists('AhgCore\\Core\\AhgDb')
                    ? \AhgCore\Core\AhgDb::class
                    : \Illuminate\Database\Capsule\Manager::class;
                $objectId = $db::table('slug')->where('slug', $slug)->value('object_id');
                return $objectId ? (int)$objectId : null;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Resolve entity type from module name
     */
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
            'museum' => 'MuseumObject',
            'library' => 'LibraryItem',
            'gallery' => 'GalleryWork',
            'model3d' => 'Model3D',
            'dam' => 'DigitalAsset',
        ];
        return $map[$module] ?? $module;
    }

    /**
     * Log authentication events
     */
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

            // Use AhgAuditService if available
            if (!class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                return;
            }

            $service = \AhgAuditTrail\Services\AhgAuditService::getInstance();

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
            // Logout
            elseif ($action === 'logout') {
                $eventType = 'logout';
                $userId = $request->getAttribute('_pre_logout_user_id');
                $username = $request->getAttribute('_pre_logout_username') ?? 'unknown';
            }

            if ($eventType) {
                $service->logAuth($eventType, $userId, $username, [
                    'status' => $status,
                    'failure_reason' => $failureReason,
                    'metadata' => ['action' => $action, 'module' => $module],
                ]);
            }
        } catch (\Exception $e) {
            error_log('Auth audit error: ' . $e->getMessage());
        }
    }
}
