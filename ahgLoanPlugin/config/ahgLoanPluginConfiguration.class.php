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

        $loan = new \AtomFramework\Routing\RouteLoader('loan');

        // === LOAN MANAGEMENT ===
        $loan->any('loan_index', '/loan', 'index');
        $loan->any('loan_add', '/loan/add', 'add');
        $loan->any('loan_show', '/loan/:id', 'show', ['id' => '\d+']);
        $loan->any('loan_edit', '/loan/:id/edit', 'edit', ['id' => '\d+']);
        $loan->any('loan_add_object', '/loan/:id/add-object', 'addObject', ['id' => '\d+']);
        $loan->any('loan_remove_object', '/loan/:id/remove-object', 'removeObject', ['id' => '\d+']);
        $loan->any('loan_transition', '/loan/:id/transition', 'transition', ['id' => '\d+']);
        $loan->any('loan_extend', '/loan/:id/extend', 'extend', ['id' => '\d+']);
        $loan->any('loan_return', '/loan/:id/return', 'return', ['id' => '\d+']);
        $loan->any('loan_agreement', '/loan/:id/agreement', 'agreement', ['id' => '\d+']);
        $loan->any('loan_upload_document', '/loan/:id/upload-document', 'uploadDocument', ['id' => '\d+']);
        $loan->any('loan_search_objects', '/loan/search-objects', 'searchObjects');

        // === SECTOR-SPECIFIC LOAN ROUTES ===
        $loan->any('loan_sector_index', '/loan/:sector', 'index', ['sector' => 'museum|gallery|archive|library|dam']);
        $loan->any('loan_sector_add', '/loan/:sector/add', 'add', ['sector' => 'museum|gallery|archive|library|dam']);

        $loan->register($routing);
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
