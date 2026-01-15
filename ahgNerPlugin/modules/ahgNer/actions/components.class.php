<?php
class ahgNerComponents extends sfComponents
{
    public function executeExtractButton(sfWebRequest $request)
    {
        $this->resource = $this->getVar('resource');
    }

    public function executeSummarizeButton(sfWebRequest $request)
    {
        $this->resource = $this->getVar('resource');
    }

    /**
     * Combined AI tools button (NER + Summarize)
     */
    public function executeAiTools(sfWebRequest $request)
    {
        $this->resource = $this->getVar('resource');
    }
}
