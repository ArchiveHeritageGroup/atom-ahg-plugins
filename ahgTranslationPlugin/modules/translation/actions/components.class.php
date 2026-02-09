<?php

class translationComponents extends AhgComponents
{
    public function executeTranslateModal()
    {
        $this->objectId = (int)$this->getVar('objectId');
    }
}
