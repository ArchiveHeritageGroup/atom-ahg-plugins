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

    public function initialize()
    {
        // Symfony 1.4 autoloads lib/ under a plugin by convention, so the services
        // and the task need no registration here. Kept explicit and empty rather
        // than omitted, so the next person does not go looking for what is missing.
    }
}
