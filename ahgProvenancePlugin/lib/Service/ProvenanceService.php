<?php

namespace AhgProvenancePlugin\Service;

use AhgProvenancePlugin\Repository\ProvenanceRepository;

class ProvenanceService
{
    private ProvenanceRepository $repository;

    public function __construct()
    {
        $this->repository = new ProvenanceRepository();
    }

    /**
     * Get complete provenance data for an information object
     */
    public function getProvenanceForObject(int $objectId, string $culture = 'en'): array
    {
        $record = $this->repository->getByInformationObjectId($objectId, $culture);
        
        if (!$record) {
            return [
                'exists' => false,
                'record' => null,
                'events' => [],
                'documents' => [],
                'timeline' => []
            ];
        }
        
        $events = $this->repository->getEvents($record->id, $culture);
        $documents = $this->repository->getDocuments($record->id);
        
        return [
            'exists' => true,
            'record' => $record,
            'events' => $events,
            'documents' => $documents,
            'timeline' => $this->buildTimeline($events),
            'summary' => $this->generateSummary($record, $events)
        ];
    }

    /**
     * Build a timeline from events
     */
    public function buildTimeline(array $events): array
    {
        $timeline = [];
        
        foreach ($events as $event) {
            $date = $event->event_date ?? $event->event_date_start ?? null;
            $dateDisplay = $event->event_date_text ?? ($date ? date('Y', strtotime($date)) : 'Unknown date');
            
            $timeline[] = [
                'id' => $event->id,
                'date' => $date,
                'date_display' => $dateDisplay,
                'type' => $event->event_type,
                'type_label' => $this->getEventTypeLabel($event->event_type),
                'from' => $event->from_agent_name,
                'to' => $event->to_agent_name,
                'location' => $event->event_location ?? $event->event_city,
                'certainty' => $event->certainty,
                'description' => $event->event_description ?? $event->notes ?? ''
            ];
        }
        
        return $timeline;
    }

    /**
     * Generate human-readable provenance summary
     */
    public function generateSummary(?object $record, array $events): string
    {
        if (!$record) {
            return 'No provenance information recorded.';
        }
        
        // If there's a manual summary, use it
        if (!empty($record->provenance_summary) || !empty($record->summary_i18n)) {
            return $record->summary_i18n ?? $record->provenance_summary;
        }
        
        // Generate from events
        $parts = [];
        
        foreach ($events as $event) {
            $part = '';
            $date = $event->event_date_text ?? ($event->event_date ? date('Y', strtotime($event->event_date)) : '');
            
            switch ($event->event_type) {
                case 'creation':
                    $part = "Created" . ($event->to_agent_name ? " by {$event->to_agent_name}" : '');
                    break;
                case 'sale':
                case 'purchase':
                    $part = "Sold" . ($event->from_agent_name ? " by {$event->from_agent_name}" : '') 
                          . ($event->to_agent_name ? " to {$event->to_agent_name}" : '');
                    break;
                case 'gift':
                case 'donation':
                    $part = "Donated" . ($event->from_agent_name ? " by {$event->from_agent_name}" : '')
                          . ($event->to_agent_name ? " to {$event->to_agent_name}" : '');
                    break;
                case 'bequest':
                case 'inheritance':
                    $part = "Inherited" . ($event->to_agent_name ? " by {$event->to_agent_name}" : '')
                          . ($event->from_agent_name ? " from {$event->from_agent_name}" : '');
                    break;
                case 'auction':
                    $part = "Sold at auction" . ($event->to_agent_name ? " to {$event->to_agent_name}" : '');
                    break;
                case 'loan_out':
                    $part = "Loaned" . ($event->to_agent_name ? " to {$event->to_agent_name}" : '');
                    break;
                case 'loan_return':
                    $part = "Returned from loan";
                    break;
                case 'theft':
                    $part = "Stolen" . ($event->from_agent_name ? " from {$event->from_agent_name}" : '');
                    break;
                case 'recovery':
                    $part = "Recovered";
                    break;
                case 'confiscation':
                    $part = "Confiscated" . ($event->from_agent_name ? " from {$event->from_agent_name}" : '');
                    break;
                case 'restitution':
                case 'repatriation':
                    $part = "Restituted" . ($event->to_agent_name ? " to {$event->to_agent_name}" : '');
                    break;
                case 'accessioning':
                    $part = "Accessioned" . ($event->to_agent_name ? " by {$event->to_agent_name}" : '');
                    break;
                default:
                    if ($event->from_agent_name && $event->to_agent_name) {
                        $part = "Transferred from {$event->from_agent_name} to {$event->to_agent_name}";
                    } elseif ($event->to_agent_name) {
                        $part = "Acquired by {$event->to_agent_name}";
                    }
            }
            
            if ($part) {
                if ($date) {
                    $part .= " ({$date})";
                }
                if ($event->event_location) {
                    $part .= ", {$event->event_location}";
                }
                $parts[] = $part;
            }
        }
        
        if (empty($parts)) {
            return 'Provenance details pending research.';
        }
        
        return implode('; ', $parts) . '.';
    }

    /**
     * Get event type label
     */
    public function getEventTypeLabel(string $type): string
    {
        $labels = [
            'creation' => 'Creation',
            'commission' => 'Commission',
            'sale' => 'Sale',
            'purchase' => 'Purchase',
            'auction' => 'Auction Sale',
            'gift' => 'Gift',
            'donation' => 'Donation',
            'bequest' => 'Bequest',
            'inheritance' => 'Inheritance',
            'descent' => 'By Descent',
            'loan_out' => 'Loan Out',
            'loan_return' => 'Loan Return',
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal',
            'transfer' => 'Transfer',
            'exchange' => 'Exchange',
            'theft' => 'Theft',
            'recovery' => 'Recovery',
            'confiscation' => 'Confiscation',
            'restitution' => 'Restitution',
            'repatriation' => 'Repatriation',
            'discovery' => 'Discovery',
            'excavation' => 'Excavation',
            'import' => 'Import',
            'export' => 'Export',
            'authentication' => 'Authentication',
            'appraisal' => 'Appraisal',
            'conservation' => 'Conservation',
            'restoration' => 'Restoration',
            'accessioning' => 'Accessioning',
            'deaccessioning' => 'Deaccessioning',
            'unknown' => 'Unknown',
            'other' => 'Other'
        ];
        
        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get all event types for dropdowns
     */
    public function getEventTypes(): array
    {
        return [
            'Ownership Changes' => [
                'sale' => 'Sale',
                'purchase' => 'Purchase',
                'auction' => 'Auction Sale',
                'gift' => 'Gift',
                'donation' => 'Donation',
                'bequest' => 'Bequest',
                'inheritance' => 'Inheritance',
                'descent' => 'By Descent',
                'transfer' => 'Transfer',
                'exchange' => 'Exchange'
            ],
            'Loans & Deposits' => [
                'loan_out' => 'Loan Out',
                'loan_return' => 'Loan Return',
                'deposit' => 'Deposit',
                'withdrawal' => 'Withdrawal'
            ],
            'Creation & Discovery' => [
                'creation' => 'Creation',
                'commission' => 'Commission',
                'discovery' => 'Discovery',
                'excavation' => 'Excavation'
            ],
            'Loss & Recovery' => [
                'theft' => 'Theft',
                'recovery' => 'Recovery',
                'confiscation' => 'Confiscation',
                'restitution' => 'Restitution',
                'repatriation' => 'Repatriation'
            ],
            'Movement' => [
                'import' => 'Import',
                'export' => 'Export'
            ],
            'Documentation' => [
                'authentication' => 'Authentication',
                'appraisal' => 'Appraisal',
                'conservation' => 'Conservation',
                'restoration' => 'Restoration'
            ],
            'Institutional' => [
                'accessioning' => 'Accessioning',
                'deaccessioning' => 'Deaccessioning'
            ],
            'Other' => [
                'unknown' => 'Unknown',
                'other' => 'Other'
            ]
        ];
    }

    /**
     * Get acquisition types for dropdowns
     */
    public function getAcquisitionTypes(): array
    {
        return [
            'donation' => 'Donation',
            'purchase' => 'Purchase',
            'bequest' => 'Bequest',
            'transfer' => 'Transfer',
            'loan' => 'Loan',
            'deposit' => 'Deposit',
            'exchange' => 'Exchange',
            'field_collection' => 'Field Collection',
            'unknown' => 'Unknown'
        ];
    }

    /**
     * Get certainty levels
     */
    public function getCertaintyLevels(): array
    {
        return [
            'certain' => 'Certain - Documented evidence',
            'probable' => 'Probable - Strong circumstantial evidence',
            'possible' => 'Possible - Some supporting evidence',
            'uncertain' => 'Uncertain - Limited evidence',
            'unknown' => 'Unknown - No evidence'
        ];
    }

    /**
     * Create provenance record for an object
     */
    public function createRecord(int $objectId, array $data, string $culture = 'en'): int
    {
        $recordData = [
            'id' => $data['id'] ?? null,
            'information_object_id' => $objectId,
            'provenance_agent_id' => $data['provenance_agent_id'] ?? null,
            'donor_id' => $data['donor_id'] ?? null,
            'donor_agreement_id' => $data['donor_agreement_id'] ?? null,
            'current_status' => $data['current_status'] ?? 'owned',
            'custody_type' => $data['custody_type'] ?? 'permanent',
            'acquisition_type' => $data['acquisition_type'] ?? 'unknown',
            'acquisition_date' => $data['acquisition_date'] ?? null,
            'acquisition_date_text' => $data['acquisition_date_text'] ?? null,
            'acquisition_price' => $data['acquisition_price'] ?? null,
            'acquisition_currency' => $data['acquisition_currency'] ?? null,
            'certainty_level' => $data['certainty_level'] ?? 'unknown',
            'has_gaps' => $data['has_gaps'] ?? 0,
            'research_status' => $data['research_status'] ?? 'not_started',
            'nazi_era_provenance_checked' => $data['nazi_era_provenance_checked'] ?? 0,
            'nazi_era_provenance_clear' => $data['nazi_era_provenance_clear'] ?? null,
            'cultural_property_status' => $data['cultural_property_status'] ?? 'none',
            'is_complete' => $data['is_complete'] ?? 0,
            'is_public' => $data['is_public'] ?? 1,
            'created_by' => $data['created_by'] ?? null
        ];
        
        $id = $this->repository->saveRecord($recordData);
        
        // Save i18n
        $i18nData = [
            'provenance_summary' => $data['provenance_summary'] ?? null,
            'acquisition_notes' => $data['acquisition_notes'] ?? null,
            'gap_description' => $data['gap_description'] ?? null,
            'research_notes' => $data['research_notes'] ?? null,
            'nazi_era_notes' => $data['nazi_era_notes'] ?? null,
            'cultural_property_notes' => $data['cultural_property_notes'] ?? null
        ];
        
        $this->repository->saveRecordI18n($id, $culture, $i18nData);
        
        return $id;
    }

    /**
     * Add event to provenance chain
     */
    public function addEvent(int $recordId, array $data, string $culture = 'en'): int
    {
        // Get next sequence number
        $events = $this->repository->getEvents($recordId);
        $maxSeq = 0;
        foreach ($events as $e) {
            if ($e->sequence_number > $maxSeq) {
                $maxSeq = $e->sequence_number;
            }
        }
        
        $eventData = [
            'provenance_record_id' => $recordId,
            'from_agent_id' => $data['from_agent_id'] ?? null,
            'to_agent_id' => $data['to_agent_id'] ?? null,
            'event_type' => $data['event_type'] ?? 'unknown',
            'event_date' => $data['event_date'] ?? null,
            'event_date_start' => $data['event_date_start'] ?? null,
            'event_date_end' => $data['event_date_end'] ?? null,
            'event_date_text' => $data['event_date_text'] ?? null,
            'date_certainty' => $data['date_certainty'] ?? 'unknown',
            'event_location' => $data['event_location'] ?? null,
            'event_city' => $data['event_city'] ?? null,
            'event_country' => $data['event_country'] ?? null,
            'price' => $data['price'] ?? null,
            'currency' => $data['currency'] ?? null,
            'sale_reference' => $data['sale_reference'] ?? null,
            'evidence_type' => $data['evidence_type'] ?? 'none',
            'certainty' => $data['certainty'] ?? 'uncertain',
            'sequence_number' => $data['sequence_number'] ?? ($maxSeq + 1),
            'is_public' => $data['is_public'] ?? 1,
            'created_by' => $data['created_by'] ?? null
        ];
        
        return $this->repository->saveEvent($eventData);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Search agents
     */
    public function searchAgents(string $term): array
    {
        return $this->repository->searchAgents($term);
    }

    /**
     * Create or find agent
     */
    public function findOrCreateAgent(string $name, string $type = 'person', ?int $actorId = null): int
    {
        // Check if agent exists
        $existing = \Illuminate\Database\Capsule\Manager::table('provenance_agent')
            ->where('name', $name)
            ->first();
        
        if ($existing) {
            return $existing->id;
        }
        
        return $this->repository->saveAgent([
            'name' => $name,
            'agent_type' => $type,
            'actor_id' => $actorId
        ]);
    }
}
