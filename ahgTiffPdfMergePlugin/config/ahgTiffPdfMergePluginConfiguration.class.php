<?php

/**
 * DEPRECATED — All TIFF/PDF merge functionality is now in ahgPreservationPlugin.
 *
 * This plugin is kept as an inert shell to avoid breaking the atom_plugin table.
 * It registers no modules and no routes; ahgPreservationPlugin handles everything.
 */
class ahgTiffPdfMergePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'DEPRECATED — merged into ahgPreservationPlugin';
    public static $version = '1.0.1';

    public function initialize()
    {
        // No-op: all routes and modules are provided by ahgPreservationPlugin
    }
}
