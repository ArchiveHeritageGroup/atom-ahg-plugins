<?php

/*
 * AHG Display Plugin - Digital Object Show Compound Component
 *
 * Migrates base AtoM DigitalObjectShowCompoundComponent.
 * Displays a page turner for compound digital objects.
 * Replaces Propel Criteria with Laravel Query Builder.
 */

use Illuminate\Database\Capsule\Manager as DB;

class showCompoundComponent extends sfComponent
{
    public function execute($request)
    {
        if (!isset($this->resource->object)) {
            return sfView::NONE;
        }

        $parentIoId = $this->resource->object->id;
        $page = max(1, (int) ($request->page ?? 1));
        $perPage = 2;
        $offset = ($page - 1) * $perPage;

        try {
            // Bootstrap Laravel DB if needed
            if (!DB::schema()->hasTable('information_object')) {
                require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap/db.php';
            }

            // Count total child IOs with digital objects
            $totalCount = DB::table('information_object as io')
                ->join('digital_object as do', 'io.id', '=', 'do.object_id')
                ->where('io.parent_id', $parentIoId)
                ->count();

            // Fetch child digital objects with pagination
            $rows = DB::table('information_object as io')
                ->join('digital_object as do', 'io.id', '=', 'do.object_id')
                ->where('io.parent_id', $parentIoId)
                ->orderBy('io.lft')
                ->offset($offset)
                ->limit($perPage)
                ->select('do.id')
                ->get();

            $this->leftObject = null;
            $this->rightObject = null;

            if ($rows->count() > 0) {
                $this->leftObject = QubitDigitalObject::getById($rows[0]->id);
            }
            if ($rows->count() > 1) {
                $this->rightObject = QubitDigitalObject::getById($rows[1]->id);
            }

            // Build simple pager info for the template
            $this->page = $page;
            $this->totalPages = (int) ceil($totalCount / $perPage);
            $this->totalCount = $totalCount;
        } catch (\Exception $e) {
            error_log('ahgDisplayPlugin showCompound error: ' . $e->getMessage());

            // Fallback to base AtoM Propel approach
            $criteria = new Criteria();
            $criteria->add(QubitInformationObject::PARENT_ID, $parentIoId);
            $criteria->addJoin(QubitInformationObject::ID, QubitDigitalObject::OBJECT_ID);

            $this->pager = new QubitPager('QubitDigitalObject');
            $this->pager->setCriteria($criteria);
            $this->pager->setMaxPerPage($perPage);
            $this->pager->setPage($page);

            $results = $this->pager->getResults();

            $this->leftObject = $results[0] ?? null;
            $this->rightObject = (count($results) > 1) ? $results[1] : null;
            $this->page = $page;
            $this->totalPages = $this->pager->getNbPages();
            $this->totalCount = $this->pager->getNbResults();
        }
    }
}
