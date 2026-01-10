<?php

class ahgNerComponents extends sfComponents
{
    public function executeExtractButton(sfWebRequest $request)
    {
        $this->resource = $this->getVar('resource');
    }
}
