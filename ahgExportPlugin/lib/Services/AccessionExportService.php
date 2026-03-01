<?php

namespace AhgExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Export accession records to CSV format matching the ingest import format.
 *
 * Columns mirror base AtoM's csvAccessionImportTask + ahgIngestPlugin accession target fields
 * so exported CSV can be directly re-imported via the ingest wizard.
 */
class AccessionExportService
{
    /**
     * Export accession records to CSV string.
     *
     * @param array $filters  Optional filters: repository_id, date_from, date_to
     * @param string $culture Culture code (default: en)
     * @return string CSV content
     */
    public function exportCsv(array $filters = [], string $culture = 'en'): string
    {
        $query = DB::table('accession as a')
            ->join('accession_i18n as ai', function ($join) use ($culture) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->where('a.id', '!=', 0);

        // Repository filter (accessions linked via relation to repository)
        if (!empty($filters['repository_id'])) {
            $repoId = (int) $filters['repository_id'];
            $accessionIds = DB::table('relation')
                ->where('object_id', $repoId)
                ->pluck('subject_id')
                ->toArray();
            if (!empty($accessionIds)) {
                $query->whereIn('a.id', $accessionIds);
            } else {
                // No accessions for this repository
                return $this->buildCsv([]);
            }
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->where('a.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('a.date', '<=', $filters['date_to']);
        }

        $records = $query->select([
            'a.id',
            'a.identifier',
            'a.date',
            'a.acquisition_type_id',
            'a.resource_type_id',
            'a.processing_status_id',
            'a.processing_priority_id',
            'a.source_culture',
            'ai.title',
            'ai.scope_and_content',
            'ai.appraisal',
            'ai.archival_history',
            'ai.location_information',
            'ai.processing_notes',
            'ai.received_extent_units',
            'ai.source_of_acquisition',
            'ai.physical_characteristics',
        ])->orderBy('a.id')->get();

        $rows = [];
        foreach ($records as $r) {
            $row = [
                'accessionNumber' => $r->identifier,
                'title' => $r->title,
                'acquisitionDate' => $r->date,
                'sourceOfAcquisition' => $r->source_of_acquisition,
                'locationInformation' => $r->location_information,
                'receivedExtentUnits' => $r->received_extent_units,
                'scopeAndContent' => $r->scope_and_content,
                'appraisal' => $r->appraisal,
                'archivalHistory' => $r->archival_history,
                'processingNotes' => $r->processing_notes,
                'acquisitionType' => $this->resolveTermName($r->acquisition_type_id, $culture),
                'resourceType' => $this->resolveTermName($r->resource_type_id, $culture),
                'processingStatus' => $this->resolveTermName($r->processing_status_id, $culture),
                'processingPriority' => $this->resolveTermName($r->processing_priority_id, $culture),
                'culture' => $r->source_culture,
            ];

            // Donor info
            $donor = $this->getDonorInfo($r->id, $culture);
            $row = array_merge($row, $donor);

            // Accession events
            $events = $this->getAccessionEvents($r->id, $culture);
            $row = array_merge($row, $events);

            // Alternative identifiers
            $altIds = $this->getAlternativeIdentifiers($r->id, $culture);
            $row = array_merge($row, $altIds);

            // Extended fields (accession_v2)
            $extended = $this->getExtendedFields($r->id);
            $row = array_merge($row, $extended);

            $rows[] = $row;
        }

        return $this->buildCsv($rows);
    }

    /**
     * Resolve a term ID to its name.
     */
    protected function resolveTermName(?int $termId, string $culture = 'en'): string
    {
        if (!$termId) {
            return '';
        }

        return DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', $culture)
            ->value('name') ?? '';
    }

    /**
     * Get donor information for an accession.
     */
    protected function getDonorInfo(int $accessionId, string $culture): array
    {
        $defaults = [
            'donorName' => '',
            'donorStreetAddress' => '',
            'donorCity' => '',
            'donorRegion' => '',
            'donorCountry' => '',
            'donorPostalCode' => '',
            'donorTelephone' => '',
            'donorFax' => '',
            'donorEmail' => '',
            'donorContactPerson' => '',
            'donorNote' => '',
        ];

        // Find donor via relation
        $donorRelation = DB::table('relation')
            ->where('object_id', $accessionId)
            ->where('type_id', \QubitTerm::DONOR_ID ?? 334)
            ->first();

        if (!$donorRelation) {
            return $defaults;
        }

        $donorId = $donorRelation->subject_id;

        // Get actor name
        $actor = DB::table('actor_i18n')
            ->where('id', $donorId)
            ->where('culture', $culture)
            ->first();

        $result = $defaults;
        if ($actor) {
            $result['donorName'] = $actor->authorized_form_of_name ?? '';
        }

        // Get contact information
        $contact = DB::table('contact_information')
            ->where('actor_id', $donorId)
            ->first();

        if ($contact) {
            $result['donorStreetAddress'] = $contact->street_address ?? '';
            $result['donorPostalCode'] = $contact->postal_code ?? '';
            $result['donorCountry'] = $contact->country_code ?? '';
            $result['donorTelephone'] = $contact->telephone ?? '';
            $result['donorFax'] = $contact->fax ?? '';
            $result['donorEmail'] = $contact->email ?? '';
            $result['donorContactPerson'] = $contact->contact_person ?? '';

            // City/region from i18n
            $contactI18n = DB::table('contact_information_i18n')
                ->where('id', $contact->id)
                ->where('culture', $culture)
                ->first();

            if ($contactI18n) {
                $result['donorCity'] = $contactI18n->city ?? '';
                $result['donorRegion'] = $contactI18n->region ?? '';
                $result['donorNote'] = $contactI18n->note ?? '';
            }
        }

        return $result;
    }

    /**
     * Get accession events as pipe-delimited strings.
     */
    protected function getAccessionEvents(int $accessionId, string $culture): array
    {
        $events = DB::table('accession_event as ae')
            ->leftJoin('accession_event_i18n as aei', function ($join) use ($culture) {
                $join->on('ae.id', '=', 'aei.id')
                    ->where('aei.culture', '=', $culture);
            })
            ->where('ae.accession_id', $accessionId)
            ->select('ae.type_id', 'ae.date', 'aei.agent')
            ->get();

        if ($events->isEmpty()) {
            return [
                'accessionEventTypes' => '',
                'accessionEventDates' => '',
                'accessionEventAgents' => '',
            ];
        }

        $types = [];
        $dates = [];
        $agents = [];

        foreach ($events as $e) {
            $types[] = $this->resolveTermName($e->type_id, $culture);
            $dates[] = $e->date ?? '';
            $agents[] = $e->agent ?? '';
        }

        return [
            'accessionEventTypes' => implode('|', $types),
            'accessionEventDates' => implode('|', $dates),
            'accessionEventAgents' => implode('|', $agents),
        ];
    }

    /**
     * Get alternative identifiers as pipe-delimited strings.
     */
    protected function getAlternativeIdentifiers(int $accessionId, string $culture): array
    {
        $altIds = DB::table('other_name')
            ->leftJoin('other_name_i18n', 'other_name.id', '=', 'other_name_i18n.id')
            ->where('other_name.object_id', $accessionId)
            ->where('other_name_i18n.culture', $culture)
            ->select('other_name_i18n.name', 'other_name_i18n.note')
            ->get();

        if ($altIds->isEmpty()) {
            return [
                'alternativeIdentifiers' => '',
                'alternativeIdentifierNotes' => '',
            ];
        }

        return [
            'alternativeIdentifiers' => $altIds->pluck('name')->filter()->implode('|'),
            'alternativeIdentifierNotes' => $altIds->pluck('note')->filter()->implode('|'),
        ];
    }

    /**
     * Get extended fields from accession_v2.
     */
    protected function getExtendedFields(int $accessionId): array
    {
        $defaults = [
            'intakeNotes' => '',
            'intakePriority' => '',
        ];

        try {
            $ext = DB::table('accession_v2')
                ->where('accession_id', $accessionId)
                ->first();

            if ($ext) {
                $defaults['intakeNotes'] = $ext->intake_notes ?? '';
                $defaults['intakePriority'] = $ext->priority ?? '';
            }
        } catch (\Throwable $e) {
            // Table may not exist
        }

        return $defaults;
    }

    /**
     * Build CSV string from rows array.
     */
    protected function buildCsv(array $rows): string
    {
        $columns = [
            'accessionNumber', 'title', 'acquisitionDate', 'sourceOfAcquisition',
            'locationInformation', 'receivedExtentUnits', 'scopeAndContent',
            'appraisal', 'archivalHistory', 'processingNotes',
            'acquisitionType', 'resourceType', 'processingStatus', 'processingPriority',
            'donorName', 'donorStreetAddress', 'donorCity', 'donorRegion',
            'donorCountry', 'donorPostalCode', 'donorTelephone', 'donorFax',
            'donorEmail', 'donorContactPerson', 'donorNote',
            'accessionEventTypes', 'accessionEventDates', 'accessionEventAgents',
            'alternativeIdentifiers', 'alternativeIdentifierNotes',
            'intakeNotes', 'intakePriority',
            'culture',
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $columns);

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($output, $line);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
