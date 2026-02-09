<?php
class aiComponents extends AhgComponents
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

    /**
     * AI Description Suggestion button
     * Usage: include_component('ai', 'suggestButton', ['resource' => $resource])
     */
    public function executeSuggestButton(sfWebRequest $request)
    {
        $this->resource = $this->getVar('resource');
    }
}
