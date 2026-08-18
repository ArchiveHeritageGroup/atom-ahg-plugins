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
     *
     * Gated on the `heritage_homepage_redirect` setting, default on, so existing
     * instances keep the behaviour they have.
     *
     * The gate exists because this redirect hands the public front door to a page
     * whose cost scales with the catalogue. Measured 18 August 2026: the landing
     * page renders in 1.2s against 133 descriptions and 112s against 292,278. An
     * instance large enough for the page to be slow is exactly an instance where
     * every anonymous visitor is sent to it, so the failure is invisible in
     * development and total in production.
     *
     * Turning this off restores AtoM's own homepage and leaves the rest of the
     * plugin working; it is a mitigation, not a fix for the page itself.
     */
    public function redirectHomepageToHeritage(sfEvent $event)
    {
        // Fully-qualified, and guarded: this runs on controller.change_action for
        // every request, so an unresolvable class here is a fatal on every page,
        // not a broken menu entry. If the setting cannot be read, do not redirect -
        // failing towards AtoM's own homepage keeps the site usable.
        $settings = 'AtomExtensions\\Services\\AhgSettingsService';

        if (class_exists($settings) && method_exists($settings, 'getBool')) {
            try {
                if (!$settings::getBool('heritage_homepage_redirect', true)) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }
        }

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
                'route' => '@heritage_admin_dashboard',
                'label' => 'Heritage admin',
                'credentials' => ['administrator'],
                'weight' => 340,
            ]);
            AhgNav::register('manage', 'heritage_analyticsDashboard', [
                'route' => '@heritage_analytics_dashboard',
                'label' => 'Heritage analytics',
                'credentials' => ['administrator'],
                'weight' => 341,
            ]);
            AhgNav::register('manage', 'heritage_custodianDashboard', [
                'route' => '@heritage_custodian_dashboard',
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
