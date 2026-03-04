<?php

/**
 * Update check stub — Heratio does not use AtoM's update check.
 * Returns NONE to render nothing.
 */
class DefaultUpdateCheckComponent extends sfComponent
{
    public function execute($request)
    {
        return sfView::NONE;
    }
}
