<?php
class ricExplorerComponents extends AhgComponents
{
    public function executeRicPanel()
    {
        $this->resourceId = isset($this->resource) ? $this->resource->id : null;
        return sfView::SUCCESS;
    }
}
