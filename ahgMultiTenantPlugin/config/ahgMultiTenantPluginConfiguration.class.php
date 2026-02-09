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
    public static $summary = 'Multi-tenancy with domain routing, dedicated tenant tables, and user hierarchy';
    public static $version = '1.2.0';

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

        // Tenant switcher module
        $switcher = new \AtomFramework\Routing\RouteLoader('tenantSwitcher');
        $switcher->any('tenant_switch', '/tenant/switch/:id', 'switch', ['id' => '\d+']);
        $switcher->any('tenant_switch_all', '/tenant/switch/all', 'switchAll');
        $switcher->register($routing);

        // Tenant admin module (for admins)
        $admin = new \AtomFramework\Routing\RouteLoader('tenantAdmin');
        $admin->any('tenant_admin', '/admin/tenants', 'index');
        $admin->any('tenant_admin_create', '/admin/tenants/create', 'create');
        $admin->any('tenant_admin_store', '/admin/tenants/store', 'store');
        $admin->any('tenant_admin_edit_tenant', '/admin/tenants/:id/edit-tenant', 'editTenant', ['id' => '\d+']);
        $admin->any('tenant_admin_update_tenant', '/admin/tenants/:id/update', 'updateTenant', ['id' => '\d+']);
        $admin->any('tenant_admin_activate', '/admin/tenants/:id/activate', 'activate', ['id' => '\d+']);
        $admin->any('tenant_admin_suspend', '/admin/tenants/:id/suspend', 'suspend', ['id' => '\d+']);
        $admin->any('tenant_admin_extend_trial', '/admin/tenants/:id/extend-trial', 'extendTrial', ['id' => '\d+']);
        $admin->any('tenant_admin_delete', '/admin/tenants/:id/delete', 'delete', ['id' => '\d+']);
        $admin->any('tenant_admin_assign_user', '/admin/tenants/assign-user', 'assignTenantUser');
        $admin->any('tenant_admin_remove_user', '/admin/tenants/remove-user', 'removeTenantUser');
        $admin->any('tenant_admin_update_user_role', '/admin/tenants/update-user-role', 'updateTenantUserRole');
        // Legacy routes for repository-based management
        $admin->any('tenant_admin_edit', '/admin/tenants/:id/edit', 'edit', ['id' => '\d+']);
        $admin->any('tenant_admin_super_users', '/admin/tenants/:id/super-users', 'superUsers', ['id' => '\d+']);
        $admin->any('tenant_admin_assign_super_user', '/admin/tenants/assign-super-user', 'assignSuperUser');
        $admin->any('tenant_admin_remove_super_user', '/admin/tenants/remove-super-user', 'removeSuperUser');
        $admin->register($routing);

        // Tenant users module (for super users)
        $users = new \AtomFramework\Routing\RouteLoader('tenantUsers');
        $users->any('tenant_users', '/tenant/:id/users', 'index', ['id' => '\d+']);
        $users->any('tenant_users_assign', '/tenant/users/assign', 'assign');
        $users->any('tenant_users_remove', '/tenant/users/remove', 'remove');
        $users->register($routing);

        // Tenant branding module (for super users)
        $branding = new \AtomFramework\Routing\RouteLoader('tenantBranding');
        $branding->any('tenant_branding', '/tenant/:id/branding', 'index', ['id' => '\d+']);
        $branding->any('tenant_branding_save', '/tenant/branding/save', 'save');
        $branding->any('tenant_branding_logo_upload', '/tenant/branding/logo-upload', 'uploadLogo');
        $branding->register($routing);
    }

    /**
     * Initialize tenant context on request
     *
     * Resolution order:
     * 1. Domain/subdomain resolution (Issue #85)
     * 2. Session-based context (fallback)
     */
    public function onContextLoadFactories(sfEvent $event): void
    {
        try {
            // Load framework bootstrap if needed
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkPath) && !class_exists('Illuminate\\Database\\Capsule\\Manager')) {
                require_once $frameworkPath;
            }

            // Initialize resolver with configuration
            \AhgMultiTenant\Services\TenantResolver::initialize([
                'base_domain' => sfConfig::get('app_multi_tenant_base_domain'),
                'enabled' => sfConfig::get('app_multi_tenant_domain_routing', true),
                'excluded_domains' => sfConfig::get('app_multi_tenant_excluded_domains', []),
            ]);

            // Try domain resolution first (Issue #85)
            if (\AhgMultiTenant\Services\TenantContext::initializeFromDomain()) {
                // Domain resolution successful, skip session
                return;
            }

            // Fall back to session-based context
            \AhgMultiTenant\Services\TenantContext::initializeFromSession();

            // Check for unknown domain (show error page)
            $this->handleUnknownDomain();
        } catch (\Exception $e) {
            error_log('Multi-tenant context init error: ' . $e->getMessage());
        }
    }

    /**
     * Handle unknown domain requests
     *
     * If domain routing is enabled and the request is for an unknown
     * tenant domain (not excluded, not resolved), show an error.
     */
    protected function handleUnknownDomain(): void
    {
        // Only check if domain routing is enabled
        if (!sfConfig::get('app_multi_tenant_domain_routing', true)) {
            return;
        }

        $resolver = \AhgMultiTenant\Services\TenantResolver::class;
        $details = $resolver::getResolutionDetails();

        // If domain was excluded or resolved, nothing to do
        if ($details['resolution_method'] !== 'none') {
            return;
        }

        // Check if this looks like a tenant subdomain attempt
        if (!empty($details['subdomain'])) {
            // Subdomain exists but no tenant found - this is an unknown tenant
            $this->showUnknownTenantError($details['subdomain']);
        }

        // Custom domain that doesn't match any tenant
        // Only show error if it's not the main site
        $baseDomain = $resolver::getBaseDomain();
        $host = $resolver::normalizeHost($details['host'] ?? '');

        if ($baseDomain && $host !== $baseDomain && $host !== 'www.' . $baseDomain) {
            // Check if this looks like a custom domain attempt (not a subdomain of base)
            if (!str_ends_with($host, '.' . $baseDomain)) {
                $this->showUnknownDomainError($host);
            }
        }
    }

    /**
     * Show error for unknown tenant subdomain
     *
     * @param string $subdomain
     */
    protected function showUnknownTenantError(string $subdomain): void
    {
        // Check if we should show error or redirect
        $action = sfConfig::get('app_multi_tenant_unknown_action', 'error');

        if ($action === 'redirect') {
            $redirectUrl = sfConfig::get('app_multi_tenant_unknown_redirect');
            if ($redirectUrl) {
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Show error page
        header('HTTP/1.1 404 Not Found');
        $errorTemplate = $this->getPluginPath() . '/modules/tenantError/templates/unknownTenantSuccess.php';

        if (file_exists($errorTemplate)) {
            $tenantCode = htmlspecialchars($subdomain, ENT_QUOTES, 'UTF-8');
            include $errorTemplate;
            exit;
        }

        // Fallback error message
        echo '<!DOCTYPE html><html><head><title>Tenant Not Found</title></head><body>';
        echo '<h1>Tenant Not Found</h1>';
        echo '<p>The tenant "' . htmlspecialchars($subdomain, ENT_QUOTES, 'UTF-8') . '" does not exist.</p>';
        echo '</body></html>';
        exit;
    }

    /**
     * Show error for unknown custom domain
     *
     * @param string $domain
     */
    protected function showUnknownDomainError(string $domain): void
    {
        // Check if we should show error or redirect
        $action = sfConfig::get('app_multi_tenant_unknown_action', 'error');

        if ($action === 'redirect') {
            $redirectUrl = sfConfig::get('app_multi_tenant_unknown_redirect');
            if ($redirectUrl) {
                header('Location: ' . $redirectUrl);
                exit;
            }
        }

        // Show error page
        header('HTTP/1.1 404 Not Found');
        $errorTemplate = $this->getPluginPath() . '/modules/tenantError/templates/unknownDomainSuccess.php';

        if (file_exists($errorTemplate)) {
            $domainName = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
            include $errorTemplate;
            exit;
        }

        // Fallback error message
        echo '<!DOCTYPE html><html><head><title>Domain Not Found</title></head><body>';
        echo '<h1>Domain Not Configured</h1>';
        echo '<p>The domain "' . htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') . '" is not configured for any tenant.</p>';
        echo '</body></html>';
        exit;
    }

    /**
     * Get plugin root path
     */
    public static function getPluginPath(): string
    {
        return dirname(__DIR__);
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
}
