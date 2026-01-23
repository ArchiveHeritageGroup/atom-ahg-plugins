<?php

class ahgTranslationComponents extends sfComponents
{
    public function executeTranslateModal()
    {
        $this->objectId = (int)$this->getVar('objectId');
    }
}
