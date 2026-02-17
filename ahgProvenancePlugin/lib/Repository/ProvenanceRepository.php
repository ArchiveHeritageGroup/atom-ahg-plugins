<?php

namespace AhgProvenancePlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class ProvenanceRepository
{
    /**
     * Get provenance record for an information object
     */
    public function getByInformationObjectId(int $objectId, string $culture = 'en'): ?object
    {
        return DB::table('provenance_record as pr')
            ->leftJoin('provenance_record_i18n as pri', function($join) use ($culture) {
                $join->on('pr.id', '=', 'pri.id')
                     ->where('pri.culture', '=', $culture);
            })
            ->leftJoin('provenance_agent as pa', 'pr.provenance_agent_id', '=', 'pa.id')
            ->where('pr.information_object_id', $objectId)
            ->select([
                'pr.*',
                'pri.provenance_summary as summary_i18n',
                'pri.acquisition_notes',
                'pri.research_notes as research_notes_i18n',
                'pa.name as current_agent_name',
                'pa.agent_type as current_agent_type'
            ])
            ->first();
    }

    /**
     * Get all provenance events for a record (chain of custody)
     */
    public function getEvents(int $recordId, string $culture = 'en'): array
    {
        return DB::table('provenance_event as pe')
            ->leftJoin('provenance_event_i18n as pei', function($join) use ($culture) {
                $join->on('pe.id', '=', 'pei.id')
                     ->where('pei.culture', '=', $culture);
            })
            ->leftJoin('provenance_agent as from_agent', 'pe.from_agent_id', '=', 'from_agent.id')
            ->leftJoin('provenance_agent as to_agent', 'pe.to_agent_id', '=', 'to_agent.id')
            ->where('pe.provenance_record_id', $recordId)
            ->orderBy('pe.sequence_number')
            ->orderBy('pe.event_date')
            ->select([
                'pe.*',
                'pei.event_description',
                'pei.notes as notes_i18n',
                'from_agent.name as from_agent_name',
                'from_agent.agent_type as from_agent_type',
                'to_agent.name as to_agent_name',
                'to_agent.agent_type as to_agent_type'
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get documents for a provenance record or event
     */
    public function getDocuments(int $recordId = null, int $eventId = null): array
    {
        $query = DB::table('provenance_document');
        
        if ($recordId) {
            $query->where('provenance_record_id', $recordId);
        }
        if ($eventId) {
            $query->where('provenance_event_id', $eventId);
        }
        
        return $query->orderBy('document_date')->get()->toArray();
    }

    /**
     * Get all agents (for dropdowns)
     */
    public function getAllAgents(string $culture = 'en'): array
    {
        return DB::table('provenance_agent as pa')
            ->leftJoin('provenance_agent_i18n as pai', function($join) use ($culture) {
                $join->on('pa.id', '=', 'pai.id')
                     ->where('pai.culture', '=', $culture);
            })
            ->orderBy('pa.name')
            ->select(['pa.*', 'pai.biographical_note'])
            ->get()
            ->toArray();
    }

    /**
     * Search agents
     */
    public function searchAgents(string $term, int $limit = 20): array
    {
        return DB::table('provenance_agent')
            ->where('name', 'LIKE', "%{$term}%")
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Create or update provenance record
     */
    public function saveRecord(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        if (!empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']); // Remove id from update data
            DB::table('provenance_record')
                ->where('id', $id)
                ->update(array_merge($data, ['updated_at' => $now]));
            return $id;
        }
        unset($data['id']); // Remove null id from insert data
        return DB::table('provenance_record')->insertGetId(
            array_merge($data, ['created_at' => $now, 'updated_at' => $now])
        );
    }

    /**
     * Save provenance record i18n
     */
    public function saveRecordI18n(int $id, string $culture, array $data): void
    {
        DB::table('provenance_record_i18n')->updateOrInsert(
            ['id' => $id, 'culture' => $culture],
            $data
        );
    }

    /**
     * Create or update event
     */
    public function saveEvent(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        
        if (!empty($data['id'])) {
            DB::table('provenance_event')
                ->where('id', $data['id'])
                ->update(array_merge($data, ['updated_at' => $now]));
            return $data['id'];
        }
        
        return DB::table('provenance_event')->insertGetId(
            array_merge($data, ['created_at' => $now, 'updated_at' => $now])
        );
    }

    /**
     * Create or update agent
     */
    public function saveAgent(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        
        if (!empty($data['id'])) {
            DB::table('provenance_agent')
                ->where('id', $data['id'])
                ->update(array_merge($data, ['updated_at' => $now]));
            return $data['id'];
        }
        
        return DB::table('provenance_agent')->insertGetId(
            array_merge($data, ['created_at' => $now, 'updated_at' => $now])
        );
    }

    /**
     * Delete event
     */
    public function deleteEvent(int $eventId): bool
    {
        return DB::table('provenance_event')->where('id', $eventId)->delete() > 0;
    }

    /**
     * Get records with incomplete provenance
     */
    public function getIncompleteRecords(int $limit = 50): array
    {
        return DB::table('provenance_record as pr')
            ->join('information_object as io', 'pr.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('pr.is_complete', 0)
            ->orWhere('pr.has_gaps', 1)
            ->orWhere('pr.certainty_level', 'IN', ['uncertain', 'unknown'])
            ->orderBy('pr.updated_at', 'desc')
            ->limit($limit)
            ->select([
                'pr.*',
                'ioi.title as object_title',
                'io.slug as object_slug'
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get records needing Nazi-era provenance check
     */
    public function getNaziEraUnchecked(int $limit = 50): array
    {
        return DB::table('provenance_record as pr')
            ->join('information_object as io', 'pr.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('pr.nazi_era_provenance_checked', 0)
            ->orderBy('pr.created_at')
            ->limit($limit)
            ->select([
                'pr.*',
                'ioi.title as object_title',
                'io.slug as object_slug'
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get provenance statistics
     */
    public function getStatistics(): array
    {
        $total = DB::table('provenance_record')->count();
        $complete = DB::table('provenance_record')->where('is_complete', 1)->count();
        $hasGaps = DB::table('provenance_record')->where('has_gaps', 1)->count();
        $naziEraChecked = DB::table('provenance_record')->where('nazi_era_provenance_checked', 1)->count();
        $disputed = DB::table('provenance_record')->where('cultural_property_status', 'disputed')->count();
        
        $byAcquisitionType = DB::table('provenance_record')
            ->select('acquisition_type', DB::raw('COUNT(*) as count'))
            ->groupBy('acquisition_type')
            ->pluck('count', 'acquisition_type')
            ->toArray();
        
        $byCertainty = DB::table('provenance_record')
            ->select('certainty_level', DB::raw('COUNT(*) as count'))
            ->groupBy('certainty_level')
            ->pluck('count', 'certainty_level')
            ->toArray();
        
        return [
            'total' => $total,
            'complete' => $complete,
            'incomplete' => $total - $complete,
            'has_gaps' => $hasGaps,
            'nazi_era_checked' => $naziEraChecked,
            'nazi_era_unchecked' => $total - $naziEraChecked,
            'disputed' => $disputed,
            'by_acquisition_type' => $byAcquisitionType,
            'by_certainty' => $byCertainty
        ];
    }
}
