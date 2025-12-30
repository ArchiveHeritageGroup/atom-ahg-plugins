<?php

/**
 * Audit Trail Event Listener with Old/New Value Capturing
 */
class ahgAuditTrailListener
{
    protected static $auditableActions = [
        'create', 'edit', 'update', 'delete', 'copy',
        'import', 'export', 'move', 'rename',
        'publish', 'unpublish',
        'addDigitalObject', 'deleteDigitalObject',
        'updatePublicationStatus',
        'login', 'logout', 'index',
    ];

    protected static $auditableModules = [
        'informationobject', 'actor', 'repository', 'term', 'taxonomy',
        'accession', 'deaccession', 'donor', 'rightsholder', 'function',
        'physicalobject', 'digitalobject', 'user', 'aclGroup', 'staticpage',
        'sfMuseumPlugin', 'ahgLibraryPlugin', 'ahg3DModelPlugin', 'ahgGalleryPlugin',
        'ahgDAMPlugin', 'sfIsadPlugin', 'sfDcPlugin',
    ];

    protected static $db = null;
    protected static $preActionData = [];

    /**
     * Initialize database connection
     */
    protected static function initDatabase()
    {
        if (self::$db !== null) {
            return self::$db;
        }

        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        $autoloadPath = $frameworkPath . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            return null;
        }
        require_once $autoloadPath;

        $configPath = sfConfig::get('sf_root_dir') . '/config/config.php';
        if (!file_exists($configPath)) {
            return null;
        }

        $config = require $configPath;
        if (!isset($config['all']['propel']['param'])) {
            return null;
        }

        $param = $config['all']['propel']['param'];
        $dsn = $param['dsn'] ?? '';
        $username = $param['username'] ?? 'root';
        $password = $param['password'] ?? '';

        $host = 'localhost';
        $database = 'archive';
        $port = 3306;

        if (preg_match('/host=([^;]+)/', $dsn, $matches)) {
            $host = $matches[1];
        }
        if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
            $database = $matches[1];
        }
        if (preg_match('/port=([^;]+)/', $dsn, $matches)) {
            $port = (int)$matches[1];
        }

        try {
            $capsule = new \Illuminate\Database\Capsule\Manager();
            $capsule->addConnection([
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);

            self::$db = $capsule->getConnection();
            return self::$db;
        } catch (\Exception $e) {
            error_log('AUDIT: Database connection failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Capture state BEFORE action executes (for updates/deletes)
     */
    public static function capturePreActionState(sfEvent $event)
    {
        try {
            $context = sfContext::getInstance();
            $request = $context->getRequest();
            $moduleName = $context->getModuleName();
            $actionName = $context->getActionName();

            // Only capture for edit/update/delete actions
            if (!in_array($actionName, ['edit', 'update', 'delete'])) {
                return;
            }

            $db = self::initDatabase();
            if (!$db) {
                return;
            }

            $entityId = self::resolveEntityId($request, $db);
            if (!$entityId) {
                return;
            }

            $entityType = self::resolveEntityType($moduleName);
            $tableName = self::getTableName($entityType);

            if (!$tableName) {
                return;
            }

            // Capture current state before modification
            $record = $db->table($tableName)->where('id', $entityId)->first();
            if ($record) {
                $key = $entityType . '_' . $entityId;
                self::$preActionData[$key] = (array) $record;
                
                // Also get i18n data if available
                $i18nTable = $tableName . '_i18n';
                try {
                    $i18nRecords = $db->table($i18nTable)->where('id', $entityId)->get();
                    if ($i18nRecords->isNotEmpty()) {
                        self::$preActionData[$key]['_i18n'] = $i18nRecords->toArray();
                    }
                } catch (\Exception $e) {
                    // i18n table might not exist
                }
            }
        } catch (\Exception $e) {
            error_log('AUDIT PRE-CAPTURE ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Log action after response is generated
     */
    public static function logAction(sfEvent $event, $content)
    {
        try {
            $context = sfContext::getInstance();
            $request = $context->getRequest();
            $response = $context->getResponse();
            $user = $context->getUser();

            $moduleName = $context->getModuleName();
            $actionName = $context->getActionName();

            $db = self::initDatabase();
            if (!$db) {
                return $content;
            }

            // Check if audit is enabled
            $enabled = $db->table('ahg_audit_settings')
                ->where('setting_key', 'audit_enabled')
                ->value('setting_value');

            if ($enabled !== '1') {
                return $content;
            }

            // Check if this action should be audited
            if (!self::shouldAudit($moduleName, $actionName, $request, $db)) {
                return $content;
            }

            // Determine the entity being affected
            $entityType = self::resolveEntityType($moduleName);
            $entityId = self::resolveEntityId($request, $db);
            $entityTitle = self::resolveEntityTitle($request);

            // Determine action type
            $action = self::resolveAction($actionName, $request);

            // Get old/new values for updates
            $oldValues = null;
            $newValues = null;
            $changedFields = null;

            if (in_array($action, ['update', 'delete']) && $entityId) {
                $key = $entityType . '_' . $entityId;
                
                if (isset(self::$preActionData[$key])) {
                    $oldValues = self::$preActionData[$key];
                    
                    if ($action === 'update') {
                        // Get current state after update
                        $tableName = self::getTableName($entityType);
                        if ($tableName) {
                            $newRecord = $db->table($tableName)->where('id', $entityId)->first();
                            if ($newRecord) {
                                $newValues = (array) $newRecord;
                                
                                // Get i18n data
                                $i18nTable = $tableName . '_i18n';
                                try {
                                    $i18nRecords = $db->table($i18nTable)->where('id', $entityId)->get();
                                    if ($i18nRecords->isNotEmpty()) {
                                        $newValues['_i18n'] = $i18nRecords->toArray();
                                    }
                                } catch (\Exception $e) {
                                    // i18n table might not exist
                                }
                                
                                // Calculate changed fields
                                $changedFields = self::calculateChanges($oldValues, $newValues);
                            }
                        }
                    }
                    
                    // Clean up
                    unset(self::$preActionData[$key]);
                }
            } elseif ($action === 'create' && $entityId) {
                // For creates, capture new values
                $tableName = self::getTableName($entityType);
                if ($tableName) {
                    $newRecord = $db->table($tableName)->where('id', $entityId)->first();
                    if ($newRecord) {
                        $newValues = (array) $newRecord;
                    }
                }
            }

            // Get user info
            $userId = null;
            $username = 'anonymous';
            $userEmail = null;

            if ($user->isAuthenticated()) {
                $userId = $user->getAttribute('user_id');
                $username = $user->getAttribute('username') ?? $user->getUsername();
                if ($userId) {
                    $userRecord = $db->table('user')->where('id', $userId)->first();
                    if ($userRecord) {
                        $userEmail = $userRecord->email ?? null;
                    }
                }
            }

            // Generate UUID
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Check IP anonymization setting
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            if ($ipAddress) {
                try {
                    $anonymize = $db->table('ahg_audit_settings')
                        ->where('setting_key', 'audit_ip_anonymize')
                        ->value('setting_value');
                    if ($anonymize === '1') {
                        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $ipAddress = preg_replace('/\.\d+$/', '.xxx', $ipAddress);
                        } elseif (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                            $ipAddress = preg_replace('/:[^:]+$/', ':xxxx', $ipAddress);
                        }
                    }
                } catch (\Exception $e) {
                    // Keep original IP on error
                }
            }

            // Insert audit log with old/new values
            $db->table('ahg_audit_log')->insert([
                'uuid' => $uuid,
                'user_id' => $userId,
                'username' => $username,
                'user_email' => $userEmail,
                'ip_address' => $ipAddress,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'session_id' => session_id() ?: null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'entity_slug' => $request->getParameter('slug'),
                'entity_title' => $entityTitle,
                'module' => $moduleName,
                'action_name' => $actionName,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'request_uri' => substr($_SERVER['REQUEST_URI'] ?? '', 0, 2000),
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'changed_fields' => $changedFields ? json_encode($changedFields) : null,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            error_log("AUDIT: Logged {$action} on {$entityType} by {$username}" . 
                ($changedFields ? " (changed: " . implode(', ', array_keys($changedFields)) . ")" : ""));

        } catch (\Exception $e) {
            error_log('AUDIT ERROR: ' . $e->getMessage());
        }

        return $content;
    }

    /**
     * Calculate changed fields between old and new values
     */
    protected static function calculateChanges(array $oldValues, array $newValues): ?array
    {
        $changes = [];
        
        // Skip internal fields
        $skipFields = ['updated_at', 'serial_number', '_i18n'];
        
        foreach ($newValues as $field => $newValue) {
            if (in_array($field, $skipFields)) {
                continue;
            }
            
            $oldValue = $oldValues[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        // Check for removed fields
        foreach ($oldValues as $field => $oldValue) {
            if (in_array($field, $skipFields)) {
                continue;
            }
            if (!array_key_exists($field, $newValues)) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => null
                ];
            }
        }
        
        return empty($changes) ? null : $changes;
    }

    /**
     * Get table name for entity type
     */
    protected static function getTableName(string $entityType): ?string
    {
        $mapping = [
            'QubitInformationObject' => 'information_object',
            'QubitActor' => 'actor',
            'QubitRepository' => 'repository',
            'QubitTerm' => 'term',
            'QubitTaxonomy' => 'taxonomy',
            'QubitAccession' => 'accession',
            'QubitDeaccession' => 'deaccession',
            'QubitDonor' => 'donor',
            'QubitRightsHolder' => 'rights_holder',
            'QubitFunction' => 'function',
            'QubitPhysicalObject' => 'physical_object',
            'QubitDigitalObject' => 'digital_object',
            'QubitUser' => 'user',
            'QubitAclGroup' => 'acl_group',
            'QubitStaticPage' => 'static_page',
            'MuseumObject' => 'museum_object',
            'LibraryItem' => 'library_item',
        ];

        return $mapping[$entityType] ?? null;
    }

    public static function listenToMethodNotFound(sfEvent $event)
    {
        return false;
    }

    protected static function shouldAudit($moduleName, $actionName, $request, $db)
    {
        if (in_array($moduleName, ['sfAsset', 'sfWebDebug', 'default', ''])) {
            return false;
        }

        $getSetting = function($key, $default = '0') use ($db) {
            try {
                $value = $db->table('ahg_audit_settings')
                    ->where('setting_key', $key)
                    ->value('setting_value');
                return $value ?? $default;
            } catch (\Exception $e) {
                return $default;
            }
        };

        $action = self::resolveAction($actionName, $request);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($moduleName === 'user' && in_array($actionName, ['login', 'logout'])) {
            return $getSetting('audit_authentication', '1') === '1';
        }

        if ($action === 'create' || ($method === 'POST' && in_array($actionName, ['create', 'add', 'new']))) {
            return $getSetting('audit_creates', '1') === '1';
        }

        if ($action === 'update' || ($method === 'POST' && in_array($actionName, ['edit', 'update']))) {
            return $getSetting('audit_updates', '1') === '1';
        }

        if ($action === 'delete' || $method === 'DELETE' || $actionName === 'delete') {
            return $getSetting('audit_deletes', '1') === '1';
        }

        if (in_array($actionName, ['import', 'csvImport', 'eadImport', 'xmlImport'])) {
            return $getSetting('audit_imports', '1') === '1';
        }

        if (in_array($actionName, ['export', 'csvExport', 'eadExport', 'report'])) {
            return $getSetting('audit_exports', '1') === '1';
        }

        if (in_array($actionName, ['download', 'downloadMaster', 'downloadReference'])) {
            return $getSetting('audit_downloads', '1') === '1';
        }

        if (in_array($moduleName, ['search']) || $actionName === 'search') {
            return $getSetting('audit_searches', '0') === '1';
        }

        if ($moduleName === 'api' || strpos($actionName, 'api') !== false) {
            return $getSetting('audit_api_requests', '0') === '1';
        }

        if ($actionName === 'index' && in_array($moduleName, self::$auditableModules)) {
            return $getSetting('audit_views', '0') === '1';
        }

        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return true;
        }

        return false;
    }

    protected static function resolveEntityType($moduleName)
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
            'sfMuseumPlugin' => 'MuseumObject',
            'ahgLibraryPlugin' => 'LibraryItem',
            'ahg3DModelPlugin' => 'ahg3DModel',
            'ahgGalleryPlugin' => 'GalleryWork',
            'ahgDAMPlugin' => 'DigitalAsset',
        ];

        return $mapping[$moduleName] ?? ucfirst($moduleName);
    }

    protected static function resolveEntityId($request, $db)
    {
        $id = $request->getParameter('id');
        if ($id && is_numeric($id)) {
            return (int)$id;
        }

        $slug = $request->getParameter('slug');
        if ($slug) {
            try {
                $id = $db->table('slug')->where('slug', $slug)->value('object_id');
                return $id ? (int)$id : null;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    protected static function resolveEntityTitle($request)
    {
        $resource = $request->getAttribute('sf_resource');
        if ($resource) {
            if (method_exists($resource, 'getTitle')) {
                return substr($resource->getTitle() ?? '', 0, 255);
            }
            if (property_exists($resource, 'title')) {
                return substr($resource->title ?? '', 0, 255);
            }
        }
        return null;
    }

    protected static function resolveAction($actionName, $request)
    {
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
            'login' => 'login',
            'logout' => 'logout',
            'home' => 'view',
        ];

        $action = $actionMap[$actionName] ?? $actionName;
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && $action === 'view') {
            $action = 'create';
        }

        return $action;
    }
}
