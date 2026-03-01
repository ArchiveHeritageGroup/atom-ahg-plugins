<?php

class ahgUserRegistrationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Public user self-registration with admin approval';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'userRegistration';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgUserRegistration\\') === 0) {
                $relativePath = str_replace('AhgUserRegistration\\', '', $class);
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

    public function loadRoutes(sfEvent $event)
    {
        $router = new \AtomFramework\Routing\RouteLoader('userRegistration');

        // Public routes
        $router->any('user_register', '/register', 'register');
        $router->any('user_verify_email', '/register/verify/:token', 'verify', ['token' => '[a-f0-9]+']);

        // Admin routes
        $router->any('admin_registrations_approve', '/admin/registrations/approve', 'approve');
        $router->any('admin_registrations_reject', '/admin/registrations/reject', 'reject');
        $router->any('admin_registrations', '/admin/registrations', 'pending');

        $router->register($event->getSubject());
    }
}
