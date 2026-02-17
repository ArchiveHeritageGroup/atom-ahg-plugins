<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Provenance Service.
 *
 * Manages provenance records and ownership history for museum objects.
 * Supports CCO provenance recording and generates data for D3.js
 * timeline visualization.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ProvenanceService
{
    /** Transfer types */
    public const TRANSFER_TYPES = [
        'sale' => ['label' => 'Sale', 'icon' => 'shopping-cart'],
        'auction' => ['label' => 'Auction Sale', 'icon' => 'gavel'],
        'gift' => ['label' => 'Gift/Donation', 'icon' => 'gift'],
        'bequest' => ['label' => 'Bequest', 'icon' => 'file-text'],
        'inheritance' => ['label' => 'Inheritance', 'icon' => 'users'],
        'commission' => ['label' => 'Commission', 'icon' => 'edit'],
        'exchange' => ['label' => 'Exchange', 'icon' => 'exchange'],
        'seizure' => ['label' => 'Seizure/Confiscation', 'icon' => 'exclamation-triangle'],
        'restitution' => ['label' => 'Restitution', 'icon' => 'undo'],
        'transfer' => ['label' => 'Transfer', 'icon' => 'arrow-right'],
        'loan' => ['label' => 'Long-term Loan', 'icon' => 'clock'],
        'found' => ['label' => 'Found/Discovered', 'icon' => 'search'],
        'created' => ['label' => 'Created by Artist', 'icon' => 'paint-brush'],
        'unknown' => ['label' => 'Unknown', 'icon' => 'question'],
    ];

    /** Owner types */
    public const OWNER_TYPES = [
        'person' => 'Individual',
        'family' => 'Family/Dynasty',
        'dealer' => 'Art Dealer/Gallery',
        'auction_house' => 'Auction House',
        'museum' => 'Museum/Institution',
        'corporate' => 'Corporate Collection',
        'government' => 'Government/State',
        'religious' => 'Religious Institution',
        'artist' => 'Artist',
        'unknown' => 'Unknown',
    ];

    /** Certainty levels */
    public const CERTAINTY_LEVELS = [
        'certain' => ['label' => 'Certain', 'value' => 100, 'color' => '#4caf50'],
        'probable' => ['label' => 'Probable', 'value' => 75, 'color' => '#8bc34a'],
        'possible' => ['label' => 'Possible', 'value' => 50, 'color' => '#ffc107'],
        'uncertain' => ['label' => 'Uncertain', 'value' => 25, 'color' => '#ff9800'],
        'unknown' => ['label' => 'Unknown', 'value' => 0, 'color' => '#9e9e9e'],
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null
    ) {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Add provenance entry.
     *
     * @param int   $objectId  Information object ID
     * @param array $entry     Provenance entry data
     *
     * @return int Entry ID
     */
    public function addEntry(int $objectId, array $entry): int
    {
        $data = [
            'information_object_id' => $objectId,
            'sequence' => $entry['sequence'] ?? $this->getNextSequence($objectId),
            'owner_name' => $entry['owner_name'],
            'owner_type' => $entry['owner_type'] ?? 'unknown',
            'owner_actor_id' => $entry['owner_actor_id'] ?? null,
            'owner_location' => $entry['owner_location'] ?? null,
            'owner_location_tgn' => $entry['owner_location_tgn'] ?? null,
            'start_date' => $entry['start_date'] ?? null,
            'start_date_qualifier' => $entry['start_date_qualifier'] ?? null, // circa, before, after
            'end_date' => $entry['end_date'] ?? null,
            'end_date_qualifier' => $entry['end_date_qualifier'] ?? null,
            'transfer_type' => $entry['transfer_type'] ?? 'unknown',
            'transfer_details' => $entry['transfer_details'] ?? null,
            'sale_price' => $entry['sale_price'] ?? null,
            'sale_currency' => $entry['sale_currency'] ?? null,
            'auction_house' => $entry['auction_house'] ?? null,
            'auction_lot' => $entry['auction_lot'] ?? null,
            'certainty' => $entry['certainty'] ?? 'unknown',
            'sources' => $entry['sources'] ?? null,
            'notes' => $entry['notes'] ?? null,
            'is_gap' => $entry['is_gap'] ?? false,
            'gap_explanation' => $entry['gap_explanation'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = $this->db->table('provenance_entry')->insertGetId($data);

        $this->logger->info('Provenance entry added', [
            'id' => $id,
            'object_id' => $objectId,
            'owner' => $entry['owner_name'],
        ]);

        // Resequence if needed
        $this->resequence($objectId);

        return $id;
    }

    /**
     * Update provenance entry.
     */
    public function updateEntry(int $entryId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $updated = $this->db->table('provenance_entry')
            ->where('id', $entryId)
            ->update($data);

        if ($updated) {
            $entry = $this->db->table('provenance_entry')
                ->where('id', $entryId)
                ->first();

            if ($entry) {
                $this->resequence($entry->information_object_id);
            }
        }

        return $updated > 0;
    }

    /**
     * Delete provenance entry.
     */
    public function deleteEntry(int $entryId): bool
    {
        $entry = $this->db->table('provenance_entry')
            ->where('id', $entryId)
            ->first();

        if (!$entry) {
            return false;
        }

        $deleted = $this->db->table('provenance_entry')
            ->where('id', $entryId)
            ->delete();

        if ($deleted) {
            $this->resequence($entry->information_object_id);
        }

        return $deleted > 0;
    }

    /**
     * Get provenance chain for an object.
     *
     * @param int $objectId Information object ID
     *
     * @return array Ordered provenance entries
     */
    public function getChain(int $objectId): array
    {
        $entries = $this->db->table('provenance_entry')
            ->where('information_object_id', $objectId)
            ->orderBy('sequence')
            ->get()
            ->map(fn ($e) => (array) $e)
            ->all();

        // Enhance entries with metadata
        foreach ($entries as &$entry) {
            $entry['transfer_info'] = self::TRANSFER_TYPES[$entry['transfer_type']] ?? null;
            $entry['owner_type_label'] = self::OWNER_TYPES[$entry['owner_type']] ?? $entry['owner_type'];
            $entry['certainty_info'] = self::CERTAINTY_LEVELS[$entry['certainty']] ?? null;
            $entry['date_display'] = $this->formatDateRange($entry);
        }

        return $entries;
    }

    /**
     * Get provenance as formatted text (CCO format).
     *
     * @param int $objectId Information object ID
     *
     * @return string Formatted provenance text
     */
    public function getFormattedText(int $objectId): string
    {
        $entries = $this->getChain($objectId);

        if (empty($entries)) {
            return '';
        }

        $parts = [];

        foreach ($entries as $entry) {
            $part = '';

            // Owner
            $part .= $entry['owner_name'];

            // Location
            if ($entry['owner_location']) {
                $part .= ', '.$entry['owner_location'];
            }

            // Dates
            $dateStr = $this->formatDateRange($entry);
            if ($dateStr) {
                $part .= ', '.$dateStr;
            }

            // Transfer method
            if ($entry['transfer_type'] && 'unknown' !== $entry['transfer_type']) {
                $transferLabel = self::TRANSFER_TYPES[$entry['transfer_type']]['label'] ?? '';
                if ($transferLabel) {
                    $part .= ' ['.$transferLabel.']';
                }
            }

            // Certainty marker
            if ($entry['certainty'] && !in_array($entry['certainty'], ['certain', 'unknown'])) {
                $part .= ' ('.$entry['certainty'].')';
            }

            $parts[] = $part;
        }

        return implode('; ', $parts).'.';
    }

    /**
     * Get timeline data for D3.js visualization.
     *
     * @param int $objectId Information object ID
     *
     * @return array D3.js compatible timeline data
     */
    public function getTimelineData(int $objectId): array
    {
        $entries = $this->getChain($objectId);

        if (empty($entries)) {
            return ['nodes' => [], 'links' => [], 'events' => []];
        }

        $nodes = [];
        $links = [];
        $events = [];
        $minYear = null;
        $maxYear = null;

        foreach ($entries as $i => $entry) {
            // Parse years
            $startYear = $this->extractYear($entry['start_date']);
            $endYear = $this->extractYear($entry['end_date']);

            // Track date range
            if ($startYear) {
                $minYear = $minYear ? min($minYear, $startYear) : $startYear;
                $maxYear = $maxYear ? max($maxYear, $startYear) : $startYear;
            }
            if ($endYear) {
                $maxYear = $maxYear ? max($maxYear, $endYear) : $endYear;
            }

            // Create node for owner
            $nodes[] = [
                'id' => 'owner_'.$entry['id'],
                'type' => 'owner',
                'label' => $entry['owner_name'],
                'ownerType' => $entry['owner_type'],
                'location' => $entry['owner_location'],
                'startYear' => $startYear,
                'endYear' => $endYear,
                'certainty' => $entry['certainty'],
                'certaintyValue' => self::CERTAINTY_LEVELS[$entry['certainty']]['value'] ?? 0,
                'color' => self::CERTAINTY_LEVELS[$entry['certainty']]['color'] ?? '#9e9e9e',
                'isGap' => (bool) $entry['is_gap'],
            ];

            // Create transfer event
            if ($i > 0) {
                $prevEntry = $entries[$i - 1];
                $transferYear = $startYear ?? $this->extractYear($prevEntry['end_date']);

                $events[] = [
                    'id' => 'transfer_'.$entry['id'],
                    'type' => 'transfer',
                    'transferType' => $entry['transfer_type'],
                    'label' => self::TRANSFER_TYPES[$entry['transfer_type']]['label'] ?? 'Transfer',
                    'icon' => self::TRANSFER_TYPES[$entry['transfer_type']]['icon'] ?? 'arrow-right',
                    'year' => $transferYear,
                    'from' => 'owner_'.$prevEntry['id'],
                    'to' => 'owner_'.$entry['id'],
                    'details' => $entry['transfer_details'],
                    'salePrice' => $entry['sale_price'],
                    'saleCurrency' => $entry['sale_currency'],
                    'auctionHouse' => $entry['auction_house'],
                    'auctionLot' => $entry['auction_lot'],
                ];

                // Create link
                $links[] = [
                    'source' => 'owner_'.$prevEntry['id'],
                    'target' => 'owner_'.$entry['id'],
                    'type' => $entry['transfer_type'],
                    'year' => $transferYear,
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
            'events' => $events,
            'dateRange' => [
                'min' => $minYear ?? date('Y') - 100,
                'max' => $maxYear ?? (int) date('Y'),
            ],
            'metadata' => [
                'totalOwners' => count($nodes),
                'totalTransfers' => count($events),
                'hasGaps' => count(array_filter($nodes, fn ($n) => $n['isGap'])) > 0,
            ],
        ];
    }

    /**
     * Get provenance data for D3 force-directed graph.
     *
     * @param int[] $objectIds Multiple object IDs for network view
     *
     * @return array Network graph data
     */
    public function getNetworkData(array $objectIds): array
    {
        $allNodes = [];
        $allLinks = [];
        $ownerIndex = []; // Track owners across objects

        foreach ($objectIds as $objectId) {
            $entries = $this->getChain($objectId);

            foreach ($entries as $i => $entry) {
                // Use owner name as unique key
                $ownerKey = strtolower(trim($entry['owner_name']));

                if (!isset($ownerIndex[$ownerKey])) {
                    $ownerIndex[$ownerKey] = [
                        'id' => 'owner_'.count($ownerIndex),
                        'label' => $entry['owner_name'],
                        'type' => $entry['owner_type'],
                        'location' => $entry['owner_location'],
                        'objects' => [],
                    ];
                }

                $ownerIndex[$ownerKey]['objects'][] = $objectId;

                // Add link to previous owner
                if ($i > 0) {
                    $prevOwnerKey = strtolower(trim($entries[$i - 1]['owner_name']));
                    $allLinks[] = [
                        'source' => $ownerIndex[$prevOwnerKey]['id'],
                        'target' => $ownerIndex[$ownerKey]['id'],
                        'objectId' => $objectId,
                        'transferType' => $entry['transfer_type'],
                    ];
                }
            }
        }

        // Convert to array and add object count
        foreach ($ownerIndex as &$owner) {
            $owner['objectCount'] = count(array_unique($owner['objects']));
            $allNodes[] = $owner;
        }

        return [
            'nodes' => $allNodes,
            'links' => $allLinks,
        ];
    }

    /**
     * Search provenance records.
     *
     * @param string $query   Search query
     * @param array  $filters Optional filters
     *
     * @return array Matching entries with object info
     */
    public function search(string $query, array $filters = []): array
    {
        $builder = $this->db->table('provenance_entry as pe')
            ->join('information_object as io', 'io.id', '=', 'pe.information_object_id')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            });

        // Text search
        if ($query) {
            $builder->where(function ($q) use ($query) {
                $q->where('pe.owner_name', 'LIKE', "%{$query}%")
                    ->orWhere('pe.owner_location', 'LIKE', "%{$query}%")
                    ->orWhere('pe.auction_house', 'LIKE', "%{$query}%")
                    ->orWhere('pe.notes', 'LIKE', "%{$query}%");
            });
        }

        // Apply filters
        if (!empty($filters['owner_type'])) {
            $builder->where('pe.owner_type', $filters['owner_type']);
        }

        if (!empty($filters['transfer_type'])) {
            $builder->where('pe.transfer_type', $filters['transfer_type']);
        }

        if (!empty($filters['certainty'])) {
            $builder->where('pe.certainty', $filters['certainty']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('pe.start_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('pe.end_date', '<=', $filters['date_to']);
        }

        return $builder
            ->select(
                'pe.*',
                'io.identifier',
                'ioi.title as object_title'
            )
            ->orderBy('pe.owner_name')
            ->limit(100)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Find objects by former owner.
     *
     * @param string $ownerName Owner name to search
     *
     * @return array Objects that belonged to this owner
     */
    public function findByOwner(string $ownerName): array
    {
        return $this->db->table('provenance_entry as pe')
            ->join('information_object as io', 'io.id', '=', 'pe.information_object_id')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('pe.owner_name', 'LIKE', "%{$ownerName}%")
            ->select(
                'io.id',
                'io.identifier',
                'ioi.title',
                'pe.start_date',
                'pe.end_date',
                'pe.transfer_type'
            )
            ->distinct()
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Identify provenance gaps.
     *
     * @param int $objectId Information object ID
     *
     * @return array Gaps in provenance chain
     */
    public function identifyGaps(int $objectId): array
    {
        $entries = $this->getChain($objectId);
        $gaps = [];

        for ($i = 0; $i < count($entries) - 1; ++$i) {
            $current = $entries[$i];
            $next = $entries[$i + 1];

            $currentEnd = $this->extractYear($current['end_date']);
            $nextStart = $this->extractYear($next['start_date']);

            if ($currentEnd && $nextStart && $nextStart - $currentEnd > 1) {
                $gaps[] = [
                    'from_owner' => $current['owner_name'],
                    'to_owner' => $next['owner_name'],
                    'gap_start' => $currentEnd,
                    'gap_end' => $nextStart,
                    'gap_years' => $nextStart - $currentEnd,
                    'after_entry_id' => $current['id'],
                    'before_entry_id' => $next['id'],
                ];
            }
        }

        return $gaps;
    }

    /**
     * Import provenance from text.
     *
     * Parses standard provenance text format and creates entries.
     *
     * @param int    $objectId Information object ID
     * @param string $text     Provenance text
     *
     * @return array Created entry IDs
     */
    public function importFromText(int $objectId, string $text): array
    {
        // Split by semicolon (standard delimiter)
        $parts = array_map('trim', explode(';', $text));
        $entryIds = [];
        $sequence = 1;

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $entry = $this->parseProvenanceText($part);
            $entry['sequence'] = $sequence++;

            $entryIds[] = $this->addEntry($objectId, $entry);
        }

        return $entryIds;
    }

    /**
     * Export provenance to CSV.
     *
     * @param int $objectId Information object ID
     *
     * @return string CSV content
     */
    public function exportToCsv(int $objectId): string
    {
        $entries = $this->getChain($objectId);

        $headers = [
            'sequence', 'owner_name', 'owner_type', 'owner_location',
            'start_date', 'end_date', 'transfer_type', 'certainty',
            'sale_price', 'sale_currency', 'auction_house', 'sources', 'notes',
        ];

        $csv = implode(',', $headers)."\n";

        foreach ($entries as $entry) {
            $row = [];
            foreach ($headers as $field) {
                $value = $entry[$field] ?? '';
                // Escape for CSV
                $value = str_replace('"', '""', $value);
                $row[] = "\"{$value}\"";
            }
            $csv .= implode(',', $row)."\n";
        }

        return $csv;
    }

    /**
     * Get statistics.
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_entries' => $this->db->table('provenance_entry')->count(),
            'objects_with_provenance' => $this->db->table('provenance_entry')
                ->distinct('information_object_id')
                ->count('information_object_id'),
            'by_transfer_type' => [],
            'by_owner_type' => [],
            'by_certainty' => [],
            'average_chain_length' => 0,
        ];

        // By transfer type
        $byTransfer = $this->db->table('provenance_entry')
            ->selectRaw('transfer_type, COUNT(*) as count')
            ->groupBy('transfer_type')
            ->get();
        foreach ($byTransfer as $row) {
            $stats['by_transfer_type'][$row->transfer_type] = $row->count;
        }

        // By owner type
        $byOwner = $this->db->table('provenance_entry')
            ->selectRaw('owner_type, COUNT(*) as count')
            ->groupBy('owner_type')
            ->get();
        foreach ($byOwner as $row) {
            $stats['by_owner_type'][$row->owner_type] = $row->count;
        }

        // By certainty
        $byCertainty = $this->db->table('provenance_entry')
            ->selectRaw('certainty, COUNT(*) as count')
            ->groupBy('certainty')
            ->get();
        foreach ($byCertainty as $row) {
            $stats['by_certainty'][$row->certainty] = $row->count;
        }

        // Average chain length
        if ($stats['objects_with_provenance'] > 0) {
            $stats['average_chain_length'] = round(
                $stats['total_entries'] / $stats['objects_with_provenance'],
                1
            );
        }

        return $stats;
    }

    /**
     * Get transfer types for dropdown.
     */
    public function getTransferTypes(): array
    {
        return self::TRANSFER_TYPES;
    }

    /**
     * Get owner types for dropdown.
     */
    public function getOwnerTypes(): array
    {
        return self::OWNER_TYPES;
    }

    /**
     * Get certainty levels for dropdown.
     */
    public function getCertaintyLevels(): array
    {
        return self::CERTAINTY_LEVELS;
    }

    /**
     * Get next sequence number for object.
     */
    private function getNextSequence(int $objectId): int
    {
        $max = $this->db->table('provenance_entry')
            ->where('information_object_id', $objectId)
            ->max('sequence');

        return ($max ?? 0) + 1;
    }

    /**
     * Resequence entries for object.
     */
    private function resequence(int $objectId): void
    {
        $entries = $this->db->table('provenance_entry')
            ->where('information_object_id', $objectId)
            ->orderBy('start_date')
            ->orderBy('sequence')
            ->get();

        $seq = 1;
        foreach ($entries as $entry) {
            $this->db->table('provenance_entry')
                ->where('id', $entry->id)
                ->update(['sequence' => $seq++]);
        }
    }

    /**
     * Format date range for display.
     */
    private function formatDateRange(array $entry): string
    {
        $start = $entry['start_date'] ?? null;
        $end = $entry['end_date'] ?? null;
        $startQual = $entry['start_date_qualifier'] ?? null;
        $endQual = $entry['end_date_qualifier'] ?? null;

        if (!$start && !$end) {
            return '';
        }

        $parts = [];

        if ($start) {
            $startYear = $this->extractYear($start);
            $startStr = $startQual ? "{$startQual} {$startYear}" : (string) $startYear;
            $parts[] = $startStr;
        }

        if ($end && $end !== $start) {
            $endYear = $this->extractYear($end);
            $endStr = $endQual ? "{$endQual} {$endYear}" : (string) $endYear;
            $parts[] = $endStr;
        }

        return implode('-', $parts);
    }

    /**
     * Extract year from date string.
     */
    private function extractYear(?string $date): ?int
    {
        if (!$date) {
            return null;
        }

        if (preg_match('/(\d{4})/', $date, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Parse provenance text into entry data.
     */
    private function parseProvenanceText(string $text): array
    {
        $entry = [
            'owner_name' => '',
            'owner_location' => null,
            'start_date' => null,
            'end_date' => null,
            'transfer_type' => 'unknown',
            'certainty' => 'unknown',
            'notes' => $text,
        ];

        // Try to extract components
        // Pattern: Name, Location, Date-Date [transfer type] (certainty)

        // Extract certainty in parentheses
        if (preg_match('/\((probable|possible|uncertain)\)/i', $text, $matches)) {
            $entry['certainty'] = strtolower($matches[1]);
            $text = str_replace($matches[0], '', $text);
        }

        // Extract transfer type in brackets
        if (preg_match('/\[([^\]]+)\]/i', $text, $matches)) {
            $transferText = strtolower($matches[1]);
            foreach (self::TRANSFER_TYPES as $key => $type) {
                if (str_contains(strtolower($type['label']), $transferText)) {
                    $entry['transfer_type'] = $key;
                    break;
                }
            }
            $text = str_replace($matches[0], '', $text);
        }

        // Extract dates (various formats)
        if (preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $text, $matches)) {
            $entry['start_date'] = $matches[1];
            $entry['end_date'] = $matches[2];
            $text = str_replace($matches[0], '', $text);
        } elseif (preg_match('/(\d{4})/', $text, $matches)) {
            $entry['start_date'] = $matches[1];
            $text = str_replace($matches[0], '', $text);
        }

        // Remaining text is name and possibly location
        $text = trim($text, ', ');
        $parts = array_map('trim', explode(',', $text));

        if (count($parts) >= 1) {
            $entry['owner_name'] = $parts[0];
        }
        if (count($parts) >= 2) {
            $entry['owner_location'] = $parts[1];
        }

        return $entry;
    }
}
