<?php

/**
 * Donor actions - Bootstrap 5 Theme.
 *
 * Extends qtAccessionPlugin donor functionality with Laravel Query Builder.
 */
class donorActions extends sfActions
{
    /**
     * Pre-execute - load repository.
     */
    public function preExecute()
    {
        // Initialize framework if needed
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }
}
