<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CCO (Cataloging Cultural Objects) module actions - Laravel version
 */
class ccoActions extends AhgController
{
    /**
     * Display provenance - forwards to ahgMuseumPlugin provenance
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
        
        // Use the provenanceSuccess template from cco/templates
        $this->setTemplate('provenance');
    }

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

    protected function getProvenanceEvents($objectId)
    {
        $events = [];

        $rows = DB::table('event')
            ->where('object_id', $objectId)
            ->get();

        foreach ($rows as $row) {
            $typeName = '';
            if ($row->type_id) {
                $type = DB::table('term_i18n')
                    ->where('id', $row->type_id)
                    ->where('culture', 'en')
                    ->first();
                $typeName = $type ? $type->name : '';
            }

            $actorName = '';
            if ($row->actor_id) {
                $actor = DB::table('actor_i18n')
                    ->where('id', $row->actor_id)
                    ->where('culture', 'en')
                    ->first();
                $actorName = $actor ? $actor->authorized_form_of_name : '';
            }

            $placeName = '';
            if ($row->place_id) {
                $place = DB::table('term_i18n')
                    ->where('id', $row->place_id)
                    ->where('culture', 'en')
                    ->first();
                $placeName = $place ? $place->name : '';
            }

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
            // Table may not exist
        }

        return $history;
    }

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
    
    public function executeIndex($request)
    {
        $this->forward('cco', 'provenance');
    }

    /**
     * Save provenance entry (AJAX)
     */
    public function executeProvenanceSave($request)
    {
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $this->getResponse()->setContentType('application/json');

        $id = $request->getParameter('id');
        $objectId = $request->getParameter('information_object_id');

        $data = [
            'information_object_id' => $objectId,
            'owner_name' => $request->getParameter('owner_name'),
            'owner_type' => $request->getParameter('owner_type', 'unknown'),
            'owner_location' => $request->getParameter('owner_location'),
            'start_date' => $request->getParameter('start_date') ?: null,
            'end_date' => $request->getParameter('end_date') ?: null,
            'transfer_type' => $request->getParameter('transfer_type', 'unknown'),
            'certainty' => $request->getParameter('certainty', 'unknown'),
            'sale_price' => $request->getParameter('sale_price') ?: null,
            'sale_currency' => $request->getParameter('sale_currency') ?: null,
            'auction_house' => $request->getParameter('auction_house') ?: null,
            'auction_lot' => $request->getParameter('auction_lot') ?: null,
            'sources' => $request->getParameter('sources') ?: null,
            'notes' => $request->getParameter('notes') ?: null,
            'is_gap' => $request->getParameter('is_gap') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            if ($id) {
                DB::table('provenance_entry')->where('id', $id)->update($data);
            } else {
                // Get next sequence number
                $maxSeq = DB::table('provenance_entry')
                    ->where('information_object_id', $objectId)
                    ->max('sequence') ?? 0;
                $data['sequence'] = $maxSeq + 1;
                $data['created_at'] = date('Y-m-d H:i:s');
                $id = DB::table('provenance_entry')->insertGetId($data);
            }

            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Get provenance entry (AJAX)
     */
    public function executeProvenanceGet($request)
    {
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $this->getResponse()->setContentType('application/json');

        $id = $request->getParameter('id');

        $entry = DB::table('provenance_entry')->where('id', $id)->first();

        if (!$entry) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entry not found']));
        }

        return $this->renderText(json_encode(['success' => true, 'entry' => $entry]));
    }

    /**
     * Delete provenance entry (AJAX)
     */
    public function executeProvenanceDelete($request)
    {
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $this->getResponse()->setContentType('application/json');

        $id = $request->getParameter('id');

        try {
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                DB::table('provenance_entry')->where('id', $id)->delete();
            } else {
                $conn = \Propel::getConnection();
                $stmt = $conn->prepare('DELETE FROM provenance_entry WHERE id = ?');
                $stmt->execute([$id]);
            }
            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Export provenance (CSV)
     */
    public function executeProvenanceExport($request)
    {
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $objectId = $request->getParameter('object');

        $entries = DB::table('provenance_entry')
            ->where('information_object_id', $objectId)
            ->orderBy('sequence')
            ->get();

        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="provenance_' . $objectId . '.csv"');

        $csv = "Sequence,Owner,Type,Location,Start Date,End Date,Transfer,Certainty,Notes\n";

        foreach ($entries as $entry) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $entry->sequence,
                str_replace('"', '""', $entry->owner_name ?? ''),
                $entry->owner_type ?? '',
                str_replace('"', '""', $entry->owner_location ?? ''),
                $entry->start_date ?? '',
                $entry->end_date ?? '',
                $entry->transfer_type ?? '',
                $entry->certainty ?? '',
                str_replace('"', '""', $entry->notes ?? '')
            );
        }

        return $this->renderText($csv);
    }
}