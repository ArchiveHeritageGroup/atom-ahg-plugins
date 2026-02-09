<?php

class ahgUserManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'User browse and management';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'userManage';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgUserManage\\') === 0) {
                $relativePath = str_replace('AhgUserManage\\', '', $class);
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
        $routing = $event->getSubject();

        // userManage module routes
        $userManage = new \AtomFramework\Routing\RouteLoader('userManage');

        // Catch-all slug route (checked last after prepending)
        $userManage->any('user_view_override', '/user/:slug', 'view');
        $userManage->any('user_delete_override', '/user/:slug/delete', 'delete');
        $userManage->any('user_edit_override', '/user/:slug/edit', 'edit');

        // Specific routes
        $userManage->any('user_add_override', '/user/add', 'edit');
        $userManage->any('user_list_override', '/user/list', 'browse');
        $userManage->any('user_index_override', '/user', 'browse');

        $userManage->register($routing);

        // Base AtoM user passthrough routes (module: user)
        $userPassthrough = new \AtomFramework\Routing\RouteLoader('user');
        $userPassthrough->any('user_login_passthrough', '/user/login', 'login');
        $userPassthrough->any('user_logout_passthrough', '/user/logout', 'logout');
        $userPassthrough->any('user_password_edit_passthrough', '/user/passwordEdit', 'passwordEdit');
        $userPassthrough->any('user_clipboard_passthrough', '/user/clipboard', 'clipboard');
        $userPassthrough->any('user_password_reset_passthrough', '/user/passwordReset', 'passwordReset');

        $userPassthrough->register($routing);
    }
}
