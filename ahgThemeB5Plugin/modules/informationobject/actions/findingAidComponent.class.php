<?php

use Illuminate\Database\Capsule\Manager as DB;

class findingAidComponent extends sfComponent
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

        // Get collection root ID
        $collectionRootId = $this->getCollectionRootId($this->resource);
        
        if (!$collectionRootId) {
            return sfView::NONE;
        }

        // Check if finding aid exists for collection root
        $this->findingAid = DB::table('property as p')
            ->leftJoin('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id');
            })
            ->where('p.object_id', $collectionRootId)
            ->where('p.name', 'findingAidStatus')
            ->select(['p.id', 'pi.value'])
            ->first();

        // Get finding aid status
        $this->status = null;
        if ($this->findingAid && isset($this->findingAid->value)) {
            $this->status = $this->findingAid->value;
        }

        // Check if there's a generated finding aid
        $this->hasGeneratedFindingAid = DB::table('property')
            ->where('object_id', $collectionRootId)
            ->where('name', 'findingAidPath')
            ->exists();

        // Store collection root for template
        $this->collectionRootId = $collectionRootId;
        $this->collectionRootSlug = $this->getSlug($collectionRootId);

        // Context menu mode
        $this->contextMenu = isset($this->contextMenu) ? $this->contextMenu : false;
    }

    /**
     * Get collection root ID for resource
     */
    protected function getCollectionRootId($resource): ?int
    {
        if (!$resource) {
            return null;
        }

        // If resource has lft/rgt, find collection root
        if (isset($resource->lft) && isset($resource->rgt)) {
            $root = DB::table('information_object')
                ->where('lft', '<=', $resource->lft)
                ->where('rgt', '>=', $resource->rgt)
                ->where('parent_id', 1) // Direct child of ROOT
                ->orderBy('lft')
                ->first();
            
            return $root ? $root->id : ($resource->id ?? null);
        }

        // Fallback to resource ID
        return $resource->id ?? null;
    }

    /**
     * Get slug for object
     */
    protected function getSlug($objectId): ?string
    {
        if (!$objectId) {
            return null;
        }

        return DB::table('slug')
            ->where('object_id', $objectId)
            ->value('slug');
    }
}
