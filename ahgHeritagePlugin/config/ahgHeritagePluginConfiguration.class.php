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
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('controller.change_action', [$this, 'redirectHomepageToHeritage']);

        // Enable module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'heritage';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }
}
