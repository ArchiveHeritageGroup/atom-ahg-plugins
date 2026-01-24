<?php

/**
 * ahgLoanPlugin Configuration.
 *
 * Shared loan management plugin for GLAM institutions.
 * Provides comprehensive loan functionality for:
 * - Museums (object loans)
 * - Galleries (artwork loans)
 * - Archives (restricted material loans)
 * - DAM (digital asset licensing)
 *
 * Based on Spectrum 5.0 and international GLAM standards.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgLoanPluginConfiguration extends sfPluginConfiguration
{
    /** Plugin version */
    public const VERSION = '1.0.0';

    /** Supported sectors */
    public const SECTORS = [
        'museum' => 'Museum',
        'gallery' => 'Gallery',
        'archive' => 'Archive',
        'library' => 'Library',
        'dam' => 'Digital Assets',
    ];

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        // Register routes
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        // Add CSS
        $this->dispatcher->connect('response.filter_content', [$this, 'addAssets']);

        // Enable the loan module
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'loan';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));

        // Register services
        $this->registerServices();
    }

    /**
     * Register loan routes.
     */
    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // === LOAN MANAGEMENT ===
        $routing->prependRoute('loan_index', new sfRoute(
            '/loan',
            ['module' => 'loan', 'action' => 'index']
        ));
        $routing->prependRoute('loan_add', new sfRoute(
            '/loan/add',
            ['module' => 'loan', 'action' => 'add']
        ));
        $routing->prependRoute('loan_show', new sfRoute(
            '/loan/:id',
            ['module' => 'loan', 'action' => 'show'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_edit', new sfRoute(
            '/loan/:id/edit',
            ['module' => 'loan', 'action' => 'edit'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_add_object', new sfRoute(
            '/loan/:id/add-object',
            ['module' => 'loan', 'action' => 'addObject'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_remove_object', new sfRoute(
            '/loan/:id/remove-object',
            ['module' => 'loan', 'action' => 'removeObject'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_transition', new sfRoute(
            '/loan/:id/transition',
            ['module' => 'loan', 'action' => 'transition'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_extend', new sfRoute(
            '/loan/:id/extend',
            ['module' => 'loan', 'action' => 'extend'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_return', new sfRoute(
            '/loan/:id/return',
            ['module' => 'loan', 'action' => 'return'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_agreement', new sfRoute(
            '/loan/:id/agreement',
            ['module' => 'loan', 'action' => 'agreement'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_upload_document', new sfRoute(
            '/loan/:id/upload-document',
            ['module' => 'loan', 'action' => 'uploadDocument'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('loan_search_objects', new sfRoute(
            '/loan/search-objects',
            ['module' => 'loan', 'action' => 'searchObjects']
        ));

        // === SECTOR-SPECIFIC LOAN ROUTES ===
        $routing->prependRoute('loan_sector_index', new sfRoute(
            '/loan/:sector',
            ['module' => 'loan', 'action' => 'index'],
            ['sector' => 'museum|gallery|archive|library|dam']
        ));
        $routing->prependRoute('loan_sector_add', new sfRoute(
            '/loan/:sector/add',
            ['module' => 'loan', 'action' => 'add'],
            ['sector' => 'museum|gallery|archive|library|dam']
        ));
    }

    /**
     * Add plugin assets to response.
     */
    public function addAssets(sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if ($response instanceof sfWebResponse) {
            // Add loan module CSS
            $response->addStylesheet('/plugins/ahgLoanPlugin/web/css/loan.css', 'last');
        }

        return $content;
    }

    /**
     * Register loan services for dependency injection.
     */
    protected function registerServices()
    {
        // Services are instantiated on demand via factory methods
    }

    /**
     * Get plugin version.
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get supported sectors.
     */
    public static function getSectors(): array
    {
        return self::SECTORS;
    }

    /**
     * Check if a sector is supported.
     */
    public static function isSectorSupported(string $sector): bool
    {
        return isset(self::SECTORS[$sector]);
    }
}
