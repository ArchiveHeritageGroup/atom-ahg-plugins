<?php

/**
 * ahgMultiTenantPlugin Configuration
 *
 * Repository-based multi-tenancy for AtoM.
 * Provides user hierarchy: Admin > Super User > User
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgMultiTenantPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Repository-based multi-tenancy with user hierarchy';
    public static $version = '1.0.0';

    public function initialize(): void
    {
        // Register autoloader
        $this->registerAutoloader();

        // Enable modules
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'tenantSwitcher';
        $enabledModules[] = 'tenantAdmin';
        $enabledModules[] = 'tenantUsers';
        $enabledModules[] = 'tenantBranding';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Hook into response for branding injection
        $this->dispatcher->connect('response.filter_content', [$this, 'onResponseFilterContent']);

        // Hook into context end for tenant context cleanup
        $this->dispatcher->connect('context.load_factories', [$this, 'onContextLoadFactories']);
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgMultiTenant\\') === 0) {
                $relativePath = str_replace('AhgMultiTenant\\', '', $class);
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
     * Add plugin routes
     */
    public function addRoutes(sfEvent $event): void
    {
        $routing = $event->getSubject();

        // Tenant switcher
        $routing->prependRoute('tenant_switch', new sfRoute(
            '/tenant/switch/:id',
            ['module' => 'tenantSwitcher', 'action' => 'switch'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_switch_all', new sfRoute(
            '/tenant/switch/all',
            ['module' => 'tenantSwitcher', 'action' => 'switchAll']
        ));

        // Tenant admin routes (for admins)
        $routing->prependRoute('tenant_admin', new sfRoute(
            '/admin/tenants',
            ['module' => 'tenantAdmin', 'action' => 'index']
        ));
        $routing->prependRoute('tenant_admin_edit', new sfRoute(
            '/admin/tenants/:id/edit',
            ['module' => 'tenantAdmin', 'action' => 'edit'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_super_users', new sfRoute(
            '/admin/tenants/:id/super-users',
            ['module' => 'tenantAdmin', 'action' => 'superUsers'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_assign_super_user', new sfRoute(
            '/admin/tenants/assign-super-user',
            ['module' => 'tenantAdmin', 'action' => 'assignSuperUser']
        ));
        $routing->prependRoute('tenant_admin_remove_super_user', new sfRoute(
            '/admin/tenants/remove-super-user',
            ['module' => 'tenantAdmin', 'action' => 'removeSuperUser']
        ));

        // Tenant users routes (for super users)
        $routing->prependRoute('tenant_users', new sfRoute(
            '/tenant/:id/users',
            ['module' => 'tenantUsers', 'action' => 'index'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_users_assign', new sfRoute(
            '/tenant/users/assign',
            ['module' => 'tenantUsers', 'action' => 'assign']
        ));
        $routing->prependRoute('tenant_users_remove', new sfRoute(
            '/tenant/users/remove',
            ['module' => 'tenantUsers', 'action' => 'remove']
        ));

        // Tenant branding routes (for super users)
        $routing->prependRoute('tenant_branding', new sfRoute(
            '/tenant/:id/branding',
            ['module' => 'tenantBranding', 'action' => 'index'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_branding_save', new sfRoute(
            '/tenant/branding/save',
            ['module' => 'tenantBranding', 'action' => 'save']
        ));
        $routing->prependRoute('tenant_branding_logo_upload', new sfRoute(
            '/tenant/branding/logo-upload',
            ['module' => 'tenantBranding', 'action' => 'uploadLogo']
        ));
    }

    /**
     * Initialize tenant context on request
     */
    public function onContextLoadFactories(sfEvent $event): void
    {
        try {
            // Load framework bootstrap if needed
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkPath) && !class_exists('Illuminate\\Database\\Capsule\\Manager')) {
                require_once $frameworkPath;
            }

            // Initialize tenant context from session
            \AhgMultiTenant\Services\TenantContext::initializeFromSession();
        } catch (\Exception $e) {
            error_log('Multi-tenant context init error: ' . $e->getMessage());
        }
    }

    /**
     * Inject tenant branding styles into response
     */
    public function onResponseFilterContent(sfEvent $event, $content)
    {
        try {
            if (!sfContext::hasInstance()) {
                return $content;
            }

            $context = sfContext::getInstance();
            $response = $context->getResponse();

            // Only inject for HTML responses
            $contentType = $response->getContentType();
            if (strpos($contentType, 'text/html') === false) {
                return $content;
            }

            // Get branding styles
            $styles = \AhgMultiTenant\Services\TenantBranding::injectStyles();
            if (empty($styles)) {
                return $content;
            }

            // Inject before </head>
            $content = str_replace('</head>', $styles . "\n</head>", $content);
        } catch (\Exception $e) {
            error_log('Multi-tenant branding error: ' . $e->getMessage());
        }

        return $content;
    }

    /**
     * Get plugin root path
     */
    public static function getPluginPath(): string
    {
        return dirname(__DIR__);
    }
}
