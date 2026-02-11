<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * ahgMuseumPlugin actions - Laravel Query Builder version.
 */
class museumActions extends AhgController
{
    /**
     * Display provenance/custody history with D3.js timeline visualization.
     */
    public function executeProvenance($request)
    {
        $slug = $request->getParameter('slug');
        
        if (!$slug) {
            $this->forward404();
        }
        
        // Get resource using Laravel
        $this->resource = $this->getResourceBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404();
        }

        // Get provenance data
        $this->provenanceEvents = $this->getProvenanceEvents($this->resource->id);
        $this->custodyHistory = $this->getCustodyHistory($this->resource->id);

        // Get i18n fields
        $culture = $this->getContext()->getUser()->getCulture() ?? 'en';
        $i18n = DB::table('information_object_i18n')
            ->where('id', $this->resource->id)
            ->where('culture', $culture)
            ->first();

        if (!$i18n && $culture !== 'en') {
            $i18n = DB::table('information_object_i18n')
                ->where('id', $this->resource->id)
                ->where('culture', 'en')
                ->first();
        }

        $this->archivalHistory = $i18n->archival_history ?? null;
        $this->custodialHistory = $i18n->archival_history ?? null;
        $this->immediateSourceOfAcquisition = $i18n->acquisition ?? null;

        $this->timelineData = $this->prepareTimelineData();
    }

    /**
     * Get resource by slug using Laravel
     */
    protected function getResourceBySlug($slug)
    {
        $slugRecord = DB::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$slugRecord) {
            return null;
        }

        $resource = DB::table('information_object')
            ->where('id', $slugRecord->object_id)
            ->first();

        if ($resource) {
            $resource->slug = $slug;

            // Get i18n data
            $i18n = DB::table('information_object_i18n')
                ->where('id', $resource->id)
                ->where('culture', 'en')
                ->first();

            if ($i18n) {
                $resource->title = $i18n->title;
            }
        }

        return $resource;
    }

    /**
     * Get provenance-related events using Laravel Query Builder.
     */
    protected function getProvenanceEvents($objectId)
    {
        $events = [];

        $rows = DB::table('event')
            ->where('object_id', $objectId)
            ->get();

        foreach ($rows as $row) {
            // Get event type
            $typeName = '';
            if ($row->type_id) {
                $type = DB::table('term_i18n')
                    ->where('id', $row->type_id)
                    ->where('culture', 'en')
                    ->first();
                $typeName = $type ? $type->name : '';
            }

            // Get actor name
            $actorName = '';
            if ($row->actor_id) {
                $actor = DB::table('actor_i18n')
                    ->where('id', $row->actor_id)
                    ->where('culture', 'en')
                    ->first();
                $actorName = $actor ? $actor->authorized_form_of_name : '';
            }

            // Get place name
            $placeName = '';
            if ($row->place_id) {
                $place = DB::table('term_i18n')
                    ->where('id', $row->place_id)
                    ->where('culture', 'en')
                    ->first();
                $placeName = $place ? $place->name : '';
            }

            // Get event i18n data
            $eventI18n = DB::table('event_i18n')
                ->where('id', $row->id)
                ->where('culture', 'en')
                ->first();

            $events[] = [
                'id' => $row->id,
                'type' => $typeName,
                'actor' => $actorName,
                'place' => $placeName,
                'date' => $eventI18n ? $eventI18n->date : null,
                'startDate' => $row->start_date,
                'endDate' => $row->end_date,
                'description' => $eventI18n ? $eventI18n->description : null,
            ];
        }

        return $events;
    }

    /**
     * Get custody history using Laravel Query Builder.
     */
    protected function getCustodyHistory($objectId)
    {
        $history = [];

        try {
            $rows = DB::table('museum_custody')
                ->where('object_id', $objectId)
                ->orderBy('start_date', 'asc')
                ->get();

            foreach ($rows as $row) {
                $custodianName = '';
                if ($row->custodian_id) {
                    $custodian = DB::table('actor_i18n')
                        ->where('id', $row->custodian_id)
                        ->where('culture', 'en')
                        ->first();
                    $custodianName = $custodian ? $custodian->authorized_form_of_name : '';
                }

                $history[] = [
                    'id' => $row->id,
                    'custodian' => $custodianName,
                    'custodianType' => $row->custodian_type ?? '',
                    'startDate' => $row->start_date,
                    'endDate' => $row->end_date,
                    'location' => $row->location ?? '',
                    'acquisitionMethod' => $row->acquisition_method ?? '',
                    'verified' => $row->verified ?? false,
                    'notes' => $row->notes ?? '',
                ];
            }
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        return $history;
    }

    /**
     * Prepare timeline data as JSON for D3.js visualization.
     */
    protected function prepareTimelineData()
    {
        $timelineItems = [];

        foreach ($this->provenanceEvents as $event) {
            if ($event['startDate'] || $event['date']) {
                $timelineItems[] = [
                    'type' => $event['type'] ?: 'Event',
                    'label' => $event['actor'] ?: $event['type'],
                    'startDate' => $event['startDate'] ?: $event['date'],
                    'endDate' => $event['endDate'],
                    'description' => $event['description'],
                    'category' => $this->categorizeEventType($event['type']),
                ];
            }
        }

        foreach ($this->custodyHistory as $custody) {
            if ($custody['startDate']) {
                $timelineItems[] = [
                    'type' => 'Custody',
                    'label' => $custody['custodian'],
                    'startDate' => $custody['startDate'],
                    'endDate' => $custody['endDate'],
                    'description' => $custody['notes'],
                    'category' => $custody['verified'] ? 'verified_custody' : 'unverified_custody',
                ];
            }
        }

        usort($timelineItems, function($a, $b) {
            return strcmp($a['startDate'], $b['startDate']);
        });

        return json_encode($timelineItems);
    }

    /**
     * Categorize event type for timeline visualization.
     */
    protected function categorizeEventType($type)
    {
        $type = strtolower($type ?? '');

        if (strpos($type, 'creat') !== false) return 'creation';
        if (strpos($type, 'accumul') !== false) return 'accumulation';
        if (strpos($type, 'collect') !== false) return 'collection';
        if (strpos($type, 'contrib') !== false) return 'contribution';
        if (strpos($type, 'custod') !== false) return 'custody';

        return 'event';
    }
}
