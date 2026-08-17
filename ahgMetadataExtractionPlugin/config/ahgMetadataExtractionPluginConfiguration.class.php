<?php

/**
 * ahgMetadataExtractionPlugin Configuration.
 *
 * Provides universal metadata extraction capabilities:
 * - ExifTool integration for comprehensive format support
 * - Automatic field mapping to AtoM descriptive fields
 * - Batch extraction support
 * - Extraction logging and audit trail
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgMetadataExtractionPluginConfiguration extends sfPluginConfiguration
{
    /** Shown by AtoM's stock plugin admin; omitted plugins are hidden there. */
    public static $summary = 'Embedded metadata extraction from digital objects (EXIF, IPTC, XMP)';

    // Required: sfPluginAdminPlugin renders $plugin::$version, and reading an
    // undeclared static property is a fatal Error in PHP 8 - it kills the
    // plugins page mid-render, taking the save button with it.
    public static $version = "1.0.0";

    /** Plugin version */
    public const VERSION = '1.0.0';

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        // Register event listeners for automatic extraction
        $this->dispatcher->connect('digital_object.post_create', [$this, 'onDigitalObjectCreate']);
    }

    /**
     * Handle digital object creation - trigger metadata extraction.
     */
    public function onDigitalObjectCreate(sfEvent $event)
    {
        // Auto-extraction logic will be implemented here
    }

    /**
     * Get plugin version.
     */
    public static function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Check if ExifTool is available on the system.
     */
    public static function isExifToolAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('which exiftool 2>/dev/null', $output, $returnCode);

        return $returnCode === 0 && !empty($output);
    }
}
