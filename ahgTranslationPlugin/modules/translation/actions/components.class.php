<?php

class translationComponents extends sfComponents
{
    public function executeTranslateModal()
    {
        $this->objectId = (int)$this->getVar('objectId');
    }
}
