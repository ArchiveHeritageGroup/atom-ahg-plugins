<?php

class ahgDonorActions extends sfActions
{
    public function preExecute()
    {
        sfContext::getInstance()->getConfiguration()->loadHelpers(['Url', 'Tag', 'I18N', 'Date']);
    }
}
