<?php

class ahgHeritagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Heritage discovery platform with contributor system, custodian management, and analytics';
    public static $version = '1.1.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $context = $event->getSubject();
        $context->getConfiguration()->loadHelpers(['Asset', 'Url', 'Tag', 'Partial']);
    }

    /**
     * Redirect unauthenticated users from homepage to heritage landing page.
     */
    public function redirectHomepageToHeritage(sfEvent $event)
    {
        $context = sfContext::getInstance();
        $request = $context->getRequest();
        $user = $context->getUser();

        // Get module/action from the event (controller.change_action)
        $module = $event['module'] ?? $request->getParameter('module');
        $action = $event['action'] ?? $request->getParameter('action');

        // Check if this is the homepage
        $isHomepage = ('staticpage' === $module && 'home' === $action)
            || ('staticpage' === $module && 'index' === $action)
            || ('default' === $module && 'index' === $action);

        // If on homepage and NOT authenticated, redirect to heritage landing
        if ($isHomepage && !$user->isAuthenticated()) {
            $context->getController()->redirect('heritage/landing');

            throw new sfStopException();
        }
    }

    public function initialize()
    {

        // Navigation entry, contributed by the plugin rather than named by the
        // theme.
        //
        // These modules were reachable only because ahgThemeB5Plugin hardcodes
        // them in its own admin menu, so on a stock AtoM install - which is what
        // the published bundles target - the plugin installed, worked, and had
        // nowhere to click from. ahgCorePlugin renders these into AtoM's own
        // quick-links menu when the theme is absent, so the entry now follows
        // the plugin and disappears with it. Issue #292.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'heritage_adminDashboard', [
                'route' => ['module' => 'heritage', 'action' => 'adminDashboard'],
                'label' => 'Heritage admin',
                'credentials' => ['administrator'],
                'weight' => 340,
            ]);
            AhgNav::register('manage', 'heritage_analyticsDashboard', [
                'route' => ['module' => 'heritage', 'action' => 'analyticsDashboard'],
                'label' => 'Heritage analytics',
                'credentials' => ['administrator'],
                'weight' => 341,
            ]);
            AhgNav::register('manage', 'heritage_custodianDashboard', [
                'route' => ['module' => 'heritage', 'action' => 'custodianDashboard'],
                'label' => 'Heritage custodian',
                'credentials' => ['editor', 'administrator'],
                'weight' => 342,
            ]);
        }
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('controller.change_action', [$this, 'redirectHomepageToHeritage']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'heritage';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
