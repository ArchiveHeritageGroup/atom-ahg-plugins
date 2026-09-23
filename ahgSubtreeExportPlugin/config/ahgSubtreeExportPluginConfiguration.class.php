<?php

/**
 * ahgSubtreeExportPlugin - AtoM 2.8.1.
 *
 * CLI only by design. No routes, no module, no menu entry: this exists to be run by
 * an operator on the AtoM host, and a web action that copies tens of gigabytes to
 * external storage would only ever time out.
 */
class ahgSubtreeExportPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Export a run of descriptions and their digital objects to external storage (CLI).';

    // Admin > Plugins reads this. Without it the row renders half a table row and
    // no checkbox, so the plugin cannot be enabled from the UI at all.
    public static $version = '1.0.0';

    public function initialize()
    {
        // Symfony 1.4 autoloads lib/ under a plugin by convention, so the services
        // and the task need no registration here. Kept explicit and empty rather
        // than omitted, so the next person does not go looking for what is missing.
    }
}
