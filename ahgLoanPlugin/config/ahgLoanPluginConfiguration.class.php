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
        // Add CSS
        $this->dispatcher->connect('response.filter_content', [$this, 'addAssets']);

        // Register services
        $this->registerServices();
    }

    /**
     * Add plugin assets to response.
     */
    public function addAssets(sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if ($response instanceof sfWebResponse) {
            // Add loan module CSS
            $response->addStylesheet('/plugins/ahgLoanPlugin/css/loan.css', 'last');
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
