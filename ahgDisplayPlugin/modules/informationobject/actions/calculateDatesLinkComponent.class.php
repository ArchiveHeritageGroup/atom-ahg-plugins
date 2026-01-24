<?php

use Illuminate\Database\Capsule\Manager as DB;

class calculateDatesLinkComponent extends sfComponent
{
    public function execute($request)
    {
        // Check if resource exists
        if (!isset($this->resource) || !$this->resource) {
            return sfView::NONE;
        }

        $resourceId = is_object($this->resource) ? ($this->resource->id ?? null) : $this->resource;
        
        if (!$resourceId) {
            return sfView::NONE;
        }

        // Check if resource has children (only show for parent descriptions)
        $this->hasChildren = DB::table('information_object')
            ->where('parent_id', $resourceId)
            ->exists();

        // Get slug for URL
        $this->slug = is_object($this->resource) ? ($this->resource->slug ?? null) : null;
        if (!$this->slug) {
            $this->slug = DB::table('slug')
                ->where('object_id', $resourceId)
                ->value('slug');
        }

        // Context menu mode
        $this->contextMenu = isset($this->contextMenu) ? $this->contextMenu : false;
    }
}
