<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ProvenanceGraphService (#149 strand 3) — relational chain-of-custody graph.
 *
 * The RiC explorer graph is Fuseki/SPARQL-backed (needs the triplestore
 * populated). This builds an always-available provenance graph directly from
 * the ahgProvenancePlugin relational tables (provenance_record / _event / _agent),
 * surfacing the custody chain plus authenticity / due-diligence signals
 * (gaps, certainty, Nazi-era & cultural-property checks).
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */
class ProvenanceGraphService
{
    /** Records that have a provenance record (for the picker). */
    public function listRecords(int $limit = 200): array
    {
        return DB::table('provenance_record as pr')
            ->leftJoin('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'pr.information_object_id')->where('i.culture', '=', 'en');
            })
            ->leftJoin('provenance_event as pe', 'pe.provenance_record_id', '=', 'pr.id')
            ->groupBy('pr.id', 'pr.information_object_id', 'i.title', 'pr.certainty_level', 'pr.has_gaps')
            ->orderByDesc('pr.updated_at')
            ->limit($limit)
            ->get([
                'pr.information_object_id as io_id',
                DB::raw("COALESCE(i.title, CONCAT('Record #', pr.information_object_id)) as title"),
                'pr.certainty_level',
                'pr.has_gaps',
                DB::raw('COUNT(pe.id) as events'),
            ])->all();
    }

    /** Build a cytoscape-friendly graph + summary for one information object. */
    public function build(int $ioId): array
    {
        $title = DB::table('information_object_i18n')
            ->where('id', $ioId)->where('culture', 'en')->value('title') ?: ('Record #'.$ioId);

        $nodes = [['id' => 'io-'.$ioId, 'label' => $title, 'type' => 'record']];
        $edges = [];

        $record = DB::table('provenance_record')->where('information_object_id', $ioId)->first();
        if (!$record) {
            return ['nodes' => $nodes, 'edges' => $edges, 'summary' => ['has_record' => false]];
        }

        $events = DB::table('provenance_event')
            ->where('provenance_record_id', $record->id)
            ->orderBy('sequence_number')->orderBy('sort_order')->orderBy('id')
            ->get();

        // Collect every agent referenced by the chain + the current holder.
        $agentIds = [];
        foreach ($events as $e) {
            if ($e->from_agent_id) {
                $agentIds[] = (int) $e->from_agent_id;
            }
            if ($e->to_agent_id) {
                $agentIds[] = (int) $e->to_agent_id;
            }
        }
        if ($record->provenance_agent_id) {
            $agentIds[] = (int) $record->provenance_agent_id;
        }
        $agentIds = array_values(array_unique($agentIds));
        $agents = $agentIds
            ? DB::table('provenance_agent')->whereIn('id', $agentIds)->get()->keyBy('id')
            : collect();
        foreach ($agents as $a) {
            $nodes[] = [
                'id' => 'agent-'.$a->id, 'label' => $a->name, 'type' => 'agent',
                'verified' => (int) $a->verified, 'agent_type' => $a->agent_type,
            ];
        }

        // Donor → record.
        if ($record->donor_id) {
            $dn = DB::table('actor_i18n')->where('id', $record->donor_id)
                ->where('culture', 'en')->value('authorized_form_of_name');
            if ($dn) {
                $nodes[] = ['id' => 'donor-'.$record->donor_id, 'label' => $dn, 'type' => 'donor'];
                $edges[] = ['source' => 'donor-'.$record->donor_id, 'target' => 'io-'.$ioId, 'label' => 'donated'];
            }
        }

        // Custody transfers as directed edges.
        foreach ($events as $e) {
            $src = $e->from_agent_id ? 'agent-'.$e->from_agent_id : 'io-'.$ioId;
            $tgt = $e->to_agent_id ? 'agent-'.$e->to_agent_id : 'io-'.$ioId;
            $date = $e->event_date_text ?: ($e->event_date ?: ($e->event_date_start ?: ''));
            $label = trim(($e->event_type ?: 'transfer').($date ? ' ('.$date.')' : ''));
            $edges[] = ['source' => $src, 'target' => $tgt, 'label' => $label, 'kind' => 'event'];
        }

        // Current holder (dashed link to the record).
        if ($record->provenance_agent_id && isset($agents[$record->provenance_agent_id])) {
            $edges[] = [
                'source' => 'agent-'.$record->provenance_agent_id, 'target' => 'io-'.$ioId,
                'label' => 'current custody', 'kind' => 'current',
            ];
        }

        $summary = [
            'has_record' => true,
            'events' => count($events),
            'custody_type' => $record->custody_type,
            'current_status' => $record->current_status,
            'acquisition_type' => $record->acquisition_type,
            'acquisition_date' => $record->acquisition_date_text ?: $record->acquisition_date,
            'certainty_level' => $record->certainty_level,
            'has_gaps' => (int) $record->has_gaps,
            'gap_description' => $record->gap_description,
            'nazi_era_checked' => (int) $record->nazi_era_provenance_checked,
            'nazi_era_clear' => (int) $record->nazi_era_provenance_clear,
            'cultural_property_status' => $record->cultural_property_status,
            'is_complete' => (int) $record->is_complete,
            'provenance_summary' => $record->provenance_summary,
        ];

        return ['nodes' => $nodes, 'edges' => $edges, 'summary' => $summary];
    }
}
