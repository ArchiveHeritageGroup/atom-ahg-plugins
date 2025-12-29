<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * GRAP Heritage Asset Financial Data View Action.
 *
 * Displays GRAP 103 financial accounting data for heritage assets
 * in read-only view mode.
 *
 * Uses Laravel Query Builder for PHP 8.3 compatibility.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapIndexAction extends sfAction
{
    /** @var QubitInformationObject */
    public $resource;

    /** @var array GRAP data */
    public $grapData = [];

    /** @var bool Has GRAP data */
    public $hasData = false;

    /** @var bool User can edit */
    public $canEdit = false;

    /**
     * Execute action.
     */
    public function execute($request)
    {
        // Get the information object using Laravel Query Builder
        $this->resource = $this->loadResource($request->getParameter('slug'));

        if (!$this->resource instanceof QubitInformationObject) {
            $this->forward404();
        }

        // Check user has read permission
        if (!($this->getUser()->isAuthenticated() || (isset($this->resource->publication_status_id) && $this->resource->publication_status_id == 160))) {
            $this->forward('admin', 'secure');
        }

        // Load GRAP data using Laravel Query Builder
        $this->loadGrapData();

        // Check if user can edit
        $this->canEdit = ($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'));
    }

    /**
     * Load information object by slug using Laravel Query Builder.
     *
     * @param string $slug The slug to look up
     *
     * @return null|QubitInformationObject
     */
    protected function loadResource(?string $slug): ?QubitInformationObject
    {
        if (empty($slug)) {
            return null;
        }

        // Use Laravel Query Builder to find the object ID
        $result = DB::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$result) {
            return null;
        }

        // Load the QubitInformationObject using its native method
        $object = QubitInformationObject::getById($result->object_id);

        return $object instanceof QubitInformationObject ? $object : null;
    }

    /**
     * Load GRAP data from database using Laravel Query Builder.
     */
    protected function loadGrapData(): void
    {
        $row = DB::table('grap_heritage_asset')
            ->where('object_id', $this->resource->id)
            ->first();

        if ($row) {
            $this->grapData = (array) $row;
            $this->hasData = true;
        }
    }
}
