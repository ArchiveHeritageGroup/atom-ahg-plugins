<?php

class ahgUserRegistrationPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Public user self-registration with admin approval';
    public static $version = '1.0.0';

    public function initialize()
    {

        // Registration link, contributed rather than hardcoded in the theme.
        if (class_exists('AhgNav')) {
            AhgNav::register('anonymous', 'user_register', [
                'route' => '@user_register',
                'label' => 'Create an account',
                'icon' => 'fas fa-user-plus',
                'weight' => 10,
            ]);

            // Tell administrators a request is waiting.
            //
            // Nothing did. ahgThemeB5Plugin renders a banner and a menu entry by
            // querying ahg_registration_request itself
            // (templates/_adminNotifications.php), so the only instances where an
            // administrator learns of a pending registration are the ones running
            // that theme. On stock AtoM a request could sit indefinitely with the
            // applicant waiting and nobody aware.
            //
            // Declared here as a badge instead, which is what AhgNav's badge
            // callback is for: the theme renders whatever is registered and never
            // needs to know this plugin's schema. It then appears wherever AhgNav
            // is rendered - the AHG theme, or AtoM's own quick-links menu via
            // ahgCorePlugin on an instance with no theme at all.
            AhgNav::register('manage', 'user_registrations', [
                'route' => '@admin_registrations',
                'label' => 'Registrations',
                'icon' => 'fas fa-user-check',
                'section' => 'Users',
                'weight' => 30,
                'credentials' => ['administrator'],
                'badge' => static function () {
                    try {
                        return \Illuminate\Database\Capsule\Manager::table('ahg_registration_request')
                            ->whereIn('status', ['pending', 'verified'])
                            ->count();
                    } catch (\Throwable $e) {
                        // A badge is decoration. If the table is missing because
                        // the schema has not been installed yet, the menu entry
                        // should still render rather than take the page down.
                        return null;
                    }
                },
            ]);
        }
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'userRegistration';
        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        // Tell an administrator a request is waiting, on whatever page they are
        // on. The AhgNav entry above carries the same count but sits inside a
        // closed dropdown, so it only reaches somebody already looking for it.
        // See PendingRegistrationBanner.
        require_once __DIR__.'/../lib/Listeners/PendingRegistrationBanner.php';
        $this->dispatcher->connect(
            'response.filter_content',
            ['\AhgUserRegistration\Listeners\PendingRegistrationBanner', 'filter']
        );
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
        $router->any('admin_registrations_verify', '/admin/registrations/verify', 'markVerified');
        $router->any('admin_registrations_reject', '/admin/registrations/reject', 'reject');
        $router->any('admin_registrations', '/admin/registrations', 'pending');

        $router->register($event->getSubject());
    }
}
