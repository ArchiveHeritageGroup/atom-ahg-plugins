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
        $routing->prependRoute('tenant_admin_create', new sfRoute(
            '/admin/tenants/create',
            ['module' => 'tenantAdmin', 'action' => 'create']
        ));
        $routing->prependRoute('tenant_admin_store', new sfRoute(
            '/admin/tenants/store',
            ['module' => 'tenantAdmin', 'action' => 'store']
        ));
        $routing->prependRoute('tenant_admin_edit_tenant', new sfRoute(
            '/admin/tenants/:id/edit-tenant',
            ['module' => 'tenantAdmin', 'action' => 'editTenant'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_update_tenant', new sfRoute(
            '/admin/tenants/:id/update',
            ['module' => 'tenantAdmin', 'action' => 'updateTenant'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_activate', new sfRoute(
            '/admin/tenants/:id/activate',
            ['module' => 'tenantAdmin', 'action' => 'activate'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_suspend', new sfRoute(
            '/admin/tenants/:id/suspend',
            ['module' => 'tenantAdmin', 'action' => 'suspend'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_extend_trial', new sfRoute(
            '/admin/tenants/:id/extend-trial',
            ['module' => 'tenantAdmin', 'action' => 'extendTrial'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_delete', new sfRoute(
            '/admin/tenants/:id/delete',
            ['module' => 'tenantAdmin', 'action' => 'delete'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('tenant_admin_assign_user', new sfRoute(
            '/admin/tenants/assign-user',
            ['module' => 'tenantAdmin', 'action' => 'assignTenantUser']
        ));
        $routing->prependRoute('tenant_admin_remove_user', new sfRoute(
            '/admin/tenants/remove-user',
            ['module' => 'tenantAdmin', 'action' => 'removeTenantUser']
        ));
        $routing->prependRoute('tenant_admin_update_user_role', new sfRoute(
            '/admin/tenants/update-user-role',
            ['module' => 'tenantAdmin', 'action' => 'updateTenantUserRole']
        ));
        // Legacy routes for repository-based management
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
