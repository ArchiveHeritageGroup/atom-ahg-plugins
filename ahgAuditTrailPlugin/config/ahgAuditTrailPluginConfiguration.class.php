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
            // Handle AhgCore namespace (load interface before service)
            if (strpos($class, 'AhgCore\\') === 0) {
                $relativePath = str_replace('AhgCore\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

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

            // Auditable actions and modules
            $auditableActions = ['create', 'edit', 'update', 'delete', 'copy', 'import', 'export', 'publish', 'unpublish'];
            $isWriteOperation = $request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE');

            // Broader module list — includes AHG manage plugin module names
            $auditableModules = [
                'informationobject', 'actor', 'repository', 'term', 'taxonomy',
                'accession', 'deaccession', 'donor', 'rightsholder', 'function',
                'physicalobject', 'digitalobject', 'user', 'aclGroup', 'staticpage',
                'museum', 'library', 'gallery',
                'model3d', 'dam',
                // AHG manage plugin modules
                'ahgDisplay', 'ahgActorManage', 'ahgAccessionManage', 'ahgDonorManage',
                'ahgRepositoryManage', 'ahgRightsHolderManage', 'ahgStorageManage',
                'ahgTermTaxonomy', 'ahgUserManage', 'ahgJobsManage',
                'ahgInformationObjectManage', 'ahgDacsManage', 'ahgDcManage',
                'ahgModsManage', 'ahgRadManage',
            ];

            // Skip asset/debug/utility modules entirely
            $skipModules = ['sfAsset', 'sfWebDebug', 'default', 'sfThumbnail', 'ahgVoice'];
            if (in_array($module, $skipModules)) {
                return;
            }

            // Skip AJAX autocomplete/polling/API-data requests (noise)
            $skipActions = ['autocomplete', 'actorAutocomplete', 'repositoryAutocomplete',
                'termAutocomplete', 'objectAutocomplete', 'autocompleteGlam',
                'ajaxStatus', 'apiStatus', 'apiProgress', 'jobStatus',
                'getAnnotation', 'apiRealtime', 'apiCheck', 'health'];
            if ($request->isXmlHttpRequest() && in_array($action, $skipActions)) {
                return;
            }

            // Determine if this is a view (any non-write GET request)
            $isViewAction = !$isWriteOperation && !in_array($action, $auditableActions);

            if ($isViewAction) {
                // Log ALL views/page loads when audit_views is enabled
                $db = class_exists('AhgCore\\Core\\AhgDb')
                    ? \AhgCore\Core\AhgDb::class
                    : \Illuminate\Database\Capsule\Manager::class;
                $auditViews = $db::table('ahg_audit_settings')
                    ->where('setting_key', 'audit_views')
                    ->value('setting_value');
                if ($auditViews !== '1') {
                    return;
                }
            }

            // Use AhgAuditService if available
            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $userId = null;
                $username = 'anonymous';
                if ($user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                    $username = $user->getAttribute('username') ?? $user->getUsername();
                }

                // Determine action type — normalize to standard labels
                $actionType = $action;
                if ($request->isMethod('POST') && in_array($action, ['index', 'edit'])) {
                    $actionType = $request->getParameter('id') ? 'update' : 'create';
                } elseif ($isViewAction) {
                    $actionType = 'view';
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
            // AHG manage plugin modules
            'display' => 'QubitInformationObject',
            'ahgDisplay' => 'QubitInformationObject',
            'ahgActorManage' => 'QubitActor',
            'actorManage' => 'QubitActor',
            'ahgAccessionManage' => 'QubitAccession',
            'ahgDonorManage' => 'QubitDonor',
            'ahgRepositoryManage' => 'QubitRepository',
            'ahgRightsHolderManage' => 'QubitRightsHolder',
            'ahgStorageManage' => 'QubitPhysicalObject',
            'ahgTermTaxonomy' => 'QubitTerm',
            'ahgUserManage' => 'QubitUser',
            'ahgJobsManage' => 'QubitJob',
            'ahgInformationObjectManage' => 'QubitInformationObject',
            'repositoryManage' => 'QubitRepository',
            'sfIsadPlugin' => 'QubitInformationObject',
            'sfDcPlugin' => 'QubitInformationObject',
            'sfRadPlugin' => 'QubitInformationObject',
            'sfModsPlugin' => 'QubitInformationObject',
            'sfIsdiahPlugin' => 'QubitRepository',
            'sfIsaarPlugin' => 'QubitActor',
            'ahgMuseumPlugin' => 'MuseumObject',
            'ahgLibraryPlugin' => 'LibraryItem',
            'ahgGalleryPlugin' => 'GalleryWork',
            'ahgDAMPlugin' => 'DigitalAsset',
            'auditTrail' => 'AuditLog',
            'cco' => 'MuseumObject',
            'condition' => 'ConditionAssessment',
            'provenance' => 'ProvenanceRecord',
            'preservation' => 'PreservationEvent',
            'ai' => 'AIProcess',
            'ner' => 'NERResult',
            'heritage' => 'HeritageAsset',
            'research' => 'ResearchRequest',
            'loan' => 'Loan',
            'exhibition' => 'Exhibition',
            'ingest' => 'IngestSession',
            'registry' => 'Registry',
        ];

        // For registry module, resolve entity type from action name
        if ($module === 'registry') {
            $action = '';
            if (sfContext::hasInstance()) {
                $action = sfContext::getInstance()->getActionName();
            }
            $registryMap = [
                'institutionBrowse' => 'Institution', 'institutionView' => 'Institution',
                'institutionRegister' => 'Institution', 'institutionEdit' => 'Institution',
                'myInstitutionDashboard' => 'Institution', 'myInstitutionInstances' => 'Instance',
                'myInstitutionInstanceAdd' => 'Instance', 'myInstitutionInstanceEdit' => 'Instance',
                'myInstitutionContacts' => 'Contact', 'myInstitutionContactAdd' => 'Contact',
                'myInstitutionContactEdit' => 'Contact', 'myInstitutionSoftware' => 'Software',
                'myInstitutionVendors' => 'Vendor', 'myInstitutionReview' => 'Review',
                'vendorBrowse' => 'Vendor', 'vendorView' => 'Vendor',
                'vendorRegister' => 'Vendor', 'vendorEdit' => 'Vendor',
                'myVendorDashboard' => 'Vendor', 'myVendorClients' => 'Vendor',
                'myVendorSoftware' => 'Software', 'myVendorSoftwareAdd' => 'Software',
                'myVendorSoftwareEdit' => 'Software', 'myVendorContacts' => 'Contact',
                'softwareBrowse' => 'Software', 'softwareView' => 'Software',
                'softwareReleases' => 'SoftwareRelease',
                'groupBrowse' => 'UserGroup', 'groupView' => 'UserGroup',
                'groupCreate' => 'UserGroup', 'groupEdit' => 'UserGroup',
                'groupMembers' => 'UserGroup', 'groupJoin' => 'UserGroup',
                'groupLeave' => 'UserGroup', 'myGroups' => 'UserGroup',
                'discussionList' => 'Discussion', 'discussionView' => 'Discussion',
                'discussionNew' => 'Discussion',
                'blogList' => 'BlogPost', 'blogView' => 'BlogPost',
                'blogNew' => 'BlogPost', 'blogEdit' => 'BlogPost',
                'community' => 'Community', 'search' => 'Search', 'map' => 'Map',
                'adminDashboard' => 'Admin', 'adminInstitutions' => 'Institution',
                'adminVendors' => 'Vendor', 'adminSoftware' => 'Software',
                'adminGroups' => 'UserGroup', 'adminDiscussions' => 'Discussion',
                'adminBlog' => 'BlogPost', 'adminReviews' => 'Review',
                'adminSync' => 'Sync', 'adminSettings' => 'Settings',
            ];
            return $registryMap[$action] ?? 'Registry';
        }

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
