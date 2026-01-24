<?php
class ricExplorerComponents extends sfComponents
{
    public function executeRicPanel()
    {
        $this->resourceId = isset($this->resource) ? $this->resource->id : null;
        return sfView::SUCCESS;
    }
}
