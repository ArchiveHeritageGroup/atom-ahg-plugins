<?php
use Illuminate\Database\Capsule\Manager as DB;

class ahgRicExplorerActions extends sfActions
{
    protected $fusekiEndpoint = 'http://192.168.0.112:3030/ric/query';
    protected $baseUri = 'https://archives.theahg.co.za/ric/atom-psis';

    public function executeGetData(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $recordId = $request->getParameter('id');
        if (!$recordId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No ID provided']));
        }
        
        $graphData = $this->buildGraphFromDatabase($recordId);
        
        return $this->renderText(json_encode([
            'success' => true,
            'graphData' => $graphData
        ]));
    }

    protected function buildGraphFromDatabase($recordId)
    {
        $nodes = [];
        $edges = [];
        $nodeIndex = [];

        // Get record info
        $record = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', $recordId)
            ->select('io.*', 'ioi.title')
            ->first();

        if (!$record) return ['nodes' => $nodes, 'edges' => $edges];

        $recordUri = 'record-' . $recordId;
        $nodes[] = [
            'id' => $recordUri,
            'label' => $record->title ?: 'Record ' . $recordId,
            'type' => 'RecordSet'
        ];
        $nodeIndex[$recordUri] = true;

        // Get creators via events
        $events = DB::table('event as e')
            ->leftJoin('actor as a', 'e.actor_id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($j) { 
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en'); 
            })
            ->leftJoin('term_i18n as ti', function($j) {
                $j->on('e.type_id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('e.object_id', $recordId)
            ->select('a.id as actor_id', 'ai.authorized_form_of_name', 'e.type_id', 'ti.name as event_type')
            ->get();

        foreach ($events as $event) {
            if ($event->actor_id) {
                $actorUri = 'actor-' . $event->actor_id;
                if (!isset($nodeIndex[$actorUri])) {
                    $nodeIndex[$actorUri] = true;
                    $nodes[] = [
                        'id' => $actorUri,
                        'label' => $event->authorized_form_of_name ?: 'Actor ' . $event->actor_id,
                        'type' => 'Person'
                    ];
                }
                $edges[] = [
                    'source' => $actorUri, 
                    'target' => $recordUri,
                    'label' => $event->event_type ?: 'related'
                ];
            }
        }

        // Get subject access points
        $subjects = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->join('taxonomy as tax', 't.taxonomy_id', '=', 'tax.id')
            ->where('otr.object_id', $recordId)
            ->whereIn('tax.id', [35, 42, 43]) // subjects, places, genres
            ->select('t.id as term_id', 'ti.name as term_name', 'tax.id as taxonomy_id')
            ->get();

        foreach ($subjects as $subject) {
            $termUri = 'term-' . $subject->term_id;
            if (!isset($nodeIndex[$termUri])) {
                $nodeIndex[$termUri] = true;
                $type = 'Thing';
                if ($subject->taxonomy_id == 42) $type = 'Place';
                $nodes[] = [
                    'id' => $termUri,
                    'label' => $subject->term_name ?: 'Term ' . $subject->term_id,
                    'type' => $type
                ];
            }
            $edges[] = ['source' => $recordUri, 'target' => $termUri, 'label' => 'about'];
        }

        // Get parent/child relations
        if ($record->parent_id && $record->parent_id > 1) {
            $parent = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function($j) {
                    $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                })
                ->where('io.id', $record->parent_id)
                ->select('io.id', 'ioi.title')
                ->first();
            if ($parent) {
                $parentUri = 'record-' . $parent->id;
                if (!isset($nodeIndex[$parentUri])) {
                    $nodeIndex[$parentUri] = true;
                    $nodes[] = [
                        'id' => $parentUri,
                        'label' => $parent->title ?: 'Record ' . $parent->id,
                        'type' => 'RecordSet'
                    ];
                }
                $edges[] = ['source' => $recordUri, 'target' => $parentUri, 'label' => 'part of'];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
