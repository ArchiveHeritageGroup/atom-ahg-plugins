<?php

/*
 * AHG Display Plugin - Digital Object Imageflow Component
 *
 * Migrates base AtoM DigitalObjectImageflowComponent.
 * Displays a carousel of thumbnails for compound objects.
 * Replaces Propel Criteria with Laravel Query Builder.
 */

use Illuminate\Database\Capsule\Manager as DB;

class imageflowComponent extends AhgComponents
{
    public function execute($request)
    {
        if (!sfConfig::get('app_toggleIoSlider')) {
            return sfView::NONE;
        }

        $this->thumbnails = [];
        $this->thumbnailMeta = [];

        // Set limit (null for no limit)
        if (!isset($request->showFullImageflow) || 'true' != $request->showFullImageflow) {
            $this->limit = sfConfig::get('app_hits_per_page', 10);
        }

        try {
            // Bootstrap Laravel DB if needed
            if (!DB::schema()->hasTable('information_object')) {
                require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap/db.php';
            }

            // Find descendant digital objects using MPTT
            $query = DB::table('information_object as io')
                ->join('digital_object as do', 'io.id', '=', 'do.object_id')
                ->where('io.lft', '>', $this->resource->lft)
                ->where('io.rgt', '<', $this->resource->rgt);

            // Hide drafts - exclude publication status draft
            $query->join('status', function ($join) {
                $join->on('io.id', '=', 'status.object_id')
                    ->where('status.type_id', '=', QubitTerm::STATUS_TYPE_PUBLICATION_ID);
            })
            ->where('status.status_id', '!=', QubitTerm::PUBLICATION_STATUS_DRAFT_ID);

            if (isset($this->limit)) {
                $query->limit($this->limit);
            }

            $rows = $query->select('do.id', 'do.object_id')->get();

            // Pre-fetch slugs for all information objects
            $objectIds = $rows->pluck('object_id')->toArray();
            $slugMap = [];
            if (!empty($objectIds)) {
                $slugRows = DB::table('slug')
                    ->whereIn('object_id', $objectIds)
                    ->select('object_id', 'slug')
                    ->get();
                foreach ($slugRows as $sr) {
                    $slugMap[$sr->object_id] = $sr->slug;
                }
            }

            // Pre-fetch titles for all information objects
            $titleMap = [];
            if (!empty($objectIds)) {
                $titleRows = DB::table('information_object_i18n')
                    ->whereIn('id', $objectIds)
                    ->where('culture', '=', 'en')
                    ->select('id', 'title')
                    ->get();
                foreach ($titleRows as $tr) {
                    $titleMap[$tr->id] = $tr->title;
                }
            }

            $this->thumbnailMeta = [];

            foreach ($rows as $row) {
                $item = QubitDigitalObject::getById($row->id);
                if (!$item) {
                    continue;
                }

                if (QubitTerm::OFFLINE_ID == $item->usageId) {
                    $thumbnail = QubitDigitalObject::getGenericRepresentation(
                        $item->mimeType,
                        QubitTerm::THUMBNAIL_ID
                    );
                    $thumbnail->setParent($item);
                } elseif (!QubitAcl::check($item->object, 'readThumbnail')) {
                    $thumbnail = QubitDigitalObject::getGenericRepresentation(
                        $item->mimeType,
                        QubitTerm::THUMBNAIL_ID
                    );
                    $thumbnail->setParent($item);
                } else {
                    $thumbnail = $item->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);

                    if (!$thumbnail) {
                        $thumbnail = QubitDigitalObject::getGenericRepresentation(
                            $item->mimeType,
                            QubitTerm::THUMBNAIL_ID
                        );
                        $thumbnail->setParent($item);
                    }
                }

                $this->thumbnails[] = $thumbnail;
                $this->thumbnailMeta[] = [
                    'slug' => $slugMap[$row->object_id] ?? null,
                    'title' => $titleMap[$row->object_id] ?? '',
                ];
            }
        } catch (\Exception $e) {
            error_log('ahgDisplayPlugin imageflow error: ' . $e->getMessage());

            // Fallback to Propel approach
            $this->loadThumbnailsViaPropel();
        }

        // Get total number of descendant digital objects
        $this->total = $this->getDescendantDigitalObjectCount();

        if (0 === count($this->thumbnails)) {
            return sfView::NONE;
        }
    }

    /**
     * Fallback: load thumbnails using Propel Criteria.
     */
    protected function loadThumbnailsViaPropel()
    {
        $criteria = new Criteria();
        $criteria->addJoin(QubitInformationObject::ID, QubitDigitalObject::OBJECT_ID);

        $criteria->add(
            QubitInformationObject::LFT,
            $this->resource->lft,
            Criteria::GREATER_THAN
        );

        $criteria->add(
            QubitInformationObject::RGT,
            $this->resource->rgt,
            Criteria::LESS_THAN
        );

        if (isset($this->limit)) {
            $criteria->setLimit($this->limit);
        }

        $criteria = QubitAcl::addFilterDraftsCriteria($criteria);

        foreach (QubitDigitalObject::get($criteria) as $item) {
            if (QubitTerm::OFFLINE_ID == $item->usageId) {
                $thumbnail = QubitDigitalObject::getGenericRepresentation(
                    $item->mimeType,
                    QubitTerm::THUMBNAIL_ID
                );
                $thumbnail->setParent($item);
            } elseif (!QubitAcl::check($item->object, 'readThumbnail')) {
                $thumbnail = QubitDigitalObject::getGenericRepresentation(
                    $item->mimeType,
                    QubitTerm::THUMBNAIL_ID
                );
                $thumbnail->setParent($item);
            } else {
                $thumbnail = $item->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);

                if (!$thumbnail) {
                    $thumbnail = QubitDigitalObject::getGenericRepresentation(
                        $item->mimeType,
                        QubitTerm::THUMBNAIL_ID
                    );
                    $thumbnail->setParent($item);
                }
            }

            $this->thumbnails[] = $thumbnail;

            // Build meta from Propel objects
            $infoObj = $item->object;
            $slug = null;
            $title = '';
            if ($infoObj) {
                try {
                    $slug = $infoObj->slug;
                } catch (\Exception $e) {
                    $slug = null;
                }
                $title = (string) $infoObj;
            }
            $this->thumbnailMeta[] = [
                'slug' => $slug,
                'title' => $title,
            ];
        }
    }

    /**
     * Query Elasticsearch to get a count of all digital objects that are
     * descendants of the current resource.
     *
     * @return int count of descendants with digital objects
     */
    protected function getDescendantDigitalObjectCount()
    {
        try {
            $search = new arElasticSearchPluginQuery(0);
            $search->addAdvancedSearchFilters(
                InformationObjectBrowseAction::$NAMES,
                [
                    'ancestor' => $this->resource->id,
                    'topLod' => false,
                    'onlyMedia' => true,
                ],
                'isad'
            );

            $results = QubitSearch::getInstance()
                ->index
                ->getIndex('QubitInformationObject')
                ->search($search->getQuery(false, true));

            return $results->getTotalHits();
        } catch (\Exception $e) {
            error_log('ahgDisplayPlugin imageflow count error: ' . $e->getMessage());

            return 0;
        }
    }
}
