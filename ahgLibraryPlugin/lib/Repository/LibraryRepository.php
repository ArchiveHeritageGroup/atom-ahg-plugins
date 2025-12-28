<?php

declare(strict_types=1);

/**
 * LibraryRepository
 *
 * Data access layer for library items using Laravel Query Builder
 * No Propel/Qubit dependencies
 *
 * @package    ahgLibraryPlugin
 * @subpackage Repository
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;

class LibraryRepository
{
    protected static ?LibraryRepository $instance = null;
    protected string $culture;

    protected const TABLE_ITEM = 'library_item';
    protected const TABLE_CREATOR = 'library_creator';
    protected const TABLE_SUBJECT = 'library_subject';
    protected const TABLE_COPY = 'library_copy';
    protected const TABLE_SERIAL = 'library_serial_holding';
    protected const TABLE_CIRCULATION = 'library_circulation';
    protected const TABLE_IO = 'information_object';
    protected const TABLE_IO_I18N = 'information_object_i18n';

    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(?string $culture = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($culture);
        }
        return self::$instance;
    }

    /**
     * Find library item by information object ID
     */
    public function findByObjectId(int $objectId): ?LibraryItem
    {
        $row = DB::table(self::TABLE_ITEM)
            ->where('information_object_id', $objectId)
            ->first();

        if (!$row) {
            return null;
        }

        $item = LibraryItem::fromRow($row);

        // Load related data
        $item->creators = $this->getCreators($item->id);
        $item->subjects = $this->getSubjects($item->id);
        $item->copies = $this->getCopies($item->id);

        if ($item->isSerial()) {
            $item->serial_holdings = $this->getSerialHoldings($item->id);
        }

        return $item;
    }

    /**
     * Find library item by ID
     */
    public function find(int $id): ?LibraryItem
    {
        $row = DB::table(self::TABLE_ITEM)
            ->where('id', $id)
            ->first();

        if (!$row) {
            return null;
        }

        $item = LibraryItem::fromRow($row);
        $item->creators = $this->getCreators($item->id);
        $item->subjects = $this->getSubjects($item->id);
        $item->copies = $this->getCopies($item->id);

        if ($item->isSerial()) {
            $item->serial_holdings = $this->getSerialHoldings($item->id);
        }

        return $item;
    }

    /**
     * Find by ISBN
     */
    public function findByIsbn(string $isbn): ?LibraryItem
    {
        $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        $row = DB::table(self::TABLE_ITEM)
            ->where('isbn', $cleanIsbn)
            ->first();

        if (!$row) {
            return null;
        }

        return $this->find($row->id);
    }

    /**
     * Find by barcode
     */
    public function findByBarcode(string $barcode): ?LibraryItem
    {
        $row = DB::table(self::TABLE_ITEM)
            ->where('barcode', $barcode)
            ->first();

        if (!$row) {
            return null;
        }

        return $this->find($row->id);
    }

    /**
     * Find by call number
     */
    public function findByCallNumber(string $callNumber): array
    {
        $rows = DB::table(self::TABLE_ITEM)
            ->where('call_number', 'LIKE', $callNumber . '%')
            ->orderBy('call_number')
            ->get();

        return $rows->map(fn($row) => $this->find($row->id))->toArray();
    }

    /**
     * Save library item
     */
    public function save(LibraryItem $item): LibraryItem
    {
        $data = $item->toArray();
        $now = date('Y-m-d H:i:s');

        if ($item->id) {
            // Update existing
            $data['updated_at'] = $now;

            DB::table(self::TABLE_ITEM)
                ->where('id', $item->id)
                ->update($data);
        } else {
            // Insert new
            $data['created_at'] = $now;
            $data['updated_at'] = $now;

            $item->id = DB::table(self::TABLE_ITEM)->insertGetId($data);
            $item->created_at = $now;
        }

        $item->updated_at = $now;

        // Save related data
        if (!empty($item->creators)) {
            $this->saveCreators($item->id, $item->creators);
        }

        if (!empty($item->subjects)) {
            $this->saveSubjects($item->id, $item->subjects);
        }

        if (!empty($item->copies)) {
            $this->saveCopies($item->id, $item->copies);
        }

        if ($item->isSerial() && !empty($item->serial_holdings)) {
            $this->saveSerialHoldings($item->id, $item->serial_holdings);
        }

        return $item;
    }

    /**
     * Delete library item
     */
    public function delete(int $id): bool
    {
        return DB::table(self::TABLE_ITEM)
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Delete by information object ID
     */
    public function deleteByObjectId(int $objectId): bool
    {
        return DB::table(self::TABLE_ITEM)
            ->where('information_object_id', $objectId)
            ->delete() > 0;
    }

    /**
     * Get creators for a library item
     */
    public function getCreators(int $itemId): array
    {
        return DB::table(self::TABLE_CREATOR)
            ->where('library_item_id', $itemId)
            ->orderBy('sequence')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    /**
     * Save creators
     */
    public function saveCreators(int $itemId, array $creators): void
    {
        // Delete existing
        DB::table(self::TABLE_CREATOR)
            ->where('library_item_id', $itemId)
            ->delete();

        // Insert new
        $now = date('Y-m-d H:i:s');

        foreach ($creators as $index => $creator) {
            if (empty($creator['name'])) {
                continue;
            }

            DB::table(self::TABLE_CREATOR)->insert([
                'library_item_id' => $itemId,
                'actor_id' => $creator['actor_id'] ?? null,
                'name' => $creator['name'],
                'role' => $creator['role'] ?? 'author',
                'relator_code' => $creator['relator_code'] ?? null,
                'sequence' => $creator['sequence'] ?? $index,
                'is_primary' => $creator['is_primary'] ?? ($index === 0),
                'dates' => $creator['dates'] ?? null,
                'fuller_form' => $creator['fuller_form'] ?? null,
                'affiliation' => $creator['affiliation'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Get subjects for a library item
     */
    public function getSubjects(int $itemId): array
    {
        return DB::table(self::TABLE_SUBJECT)
            ->where('library_item_id', $itemId)
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                if ($data['subdivisions']) {
                    $data['subdivisions'] = json_decode($data['subdivisions'], true);
                }
                return $data;
            })
            ->toArray();
    }

    /**
     * Save subjects
     */
    public function saveSubjects(int $itemId, array $subjects): void
    {
        // Delete existing
        DB::table(self::TABLE_SUBJECT)
            ->where('library_item_id', $itemId)
            ->delete();

        // Insert new
        $now = date('Y-m-d H:i:s');

        foreach ($subjects as $subject) {
            if (empty($subject['heading'])) {
                continue;
            }

            DB::table(self::TABLE_SUBJECT)->insert([
                'library_item_id' => $itemId,
                'term_id' => $subject['term_id'] ?? null,
                'heading' => $subject['heading'],
                'heading_type' => $subject['heading_type'] ?? 'topical',
                'source' => $subject['source'] ?? null,
                'source_code' => $subject['source_code'] ?? null,
                'subdivisions' => isset($subject['subdivisions'])
                    ? json_encode($subject['subdivisions'])
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Get copies for a library item
     */
    public function getCopies(int $itemId): array
    {
        return DB::table(self::TABLE_COPY)
            ->where('library_item_id', $itemId)
            ->orderBy('copy_number')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    /**
     * Save copies
     */
    public function saveCopies(int $itemId, array $copies): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($copies as $copy) {
            $data = [
                'library_item_id' => $itemId,
                'barcode' => $copy['barcode'] ?? null,
                'copy_number' => $copy['copy_number'] ?? null,
                'location_code' => $copy['location_code'] ?? null,
                'shelf_location' => $copy['shelf_location'] ?? null,
                'status' => $copy['status'] ?? 'available',
                'condition' => $copy['condition'] ?? null,
                'condition_note' => $copy['condition_note'] ?? null,
                'acquisition_date' => $copy['acquisition_date'] ?? null,
                'acquisition_source' => $copy['acquisition_source'] ?? null,
                'acquisition_method' => $copy['acquisition_method'] ?? null,
                'acquisition_cost' => $copy['acquisition_cost'] ?? null,
                'fund_code' => $copy['fund_code'] ?? null,
                'updated_at' => $now,
            ];

            if (!empty($copy['id'])) {
                // Update existing copy
                DB::table(self::TABLE_COPY)
                    ->where('id', $copy['id'])
                    ->update($data);
            } else {
                // Insert new copy
                $data['created_at'] = $now;
                DB::table(self::TABLE_COPY)->insert($data);
            }
        }
    }

    /**
     * Get serial holdings
     */
    public function getSerialHoldings(int $itemId): array
    {
        return DB::table(self::TABLE_SERIAL)
            ->where('library_item_id', $itemId)
            ->orderBy('coverage_start')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    /**
     * Save serial holdings
     */
    public function saveSerialHoldings(int $itemId, array $holdings): void
    {
        // Delete existing
        DB::table(self::TABLE_SERIAL)
            ->where('library_item_id', $itemId)
            ->delete();

        // Insert new
        $now = date('Y-m-d H:i:s');

        foreach ($holdings as $holding) {
            DB::table(self::TABLE_SERIAL)->insert([
                'library_item_id' => $itemId,
                'enumeration' => $holding['enumeration'] ?? null,
                'chronology' => $holding['chronology'] ?? null,
                'volume' => $holding['volume'] ?? null,
                'issue' => $holding['issue'] ?? null,
                'part' => $holding['part'] ?? null,
                'supplement' => $holding['supplement'] ?? null,
                'coverage_start' => $holding['coverage_start'] ?? null,
                'coverage_end' => $holding['coverage_end'] ?? null,
                'is_complete' => $holding['is_complete'] ?? true,
                'gaps_note' => $holding['gaps_note'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Search library items
     */
    public function search(array $params = []): array
    {
        $query = DB::table(self::TABLE_ITEM . ' as li')
            ->join(self::TABLE_IO . ' as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin(self::TABLE_IO_I18N . ' as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            });

        // Apply filters
        $this->applySearchFilters($query, $params);

        // Get total count
        $total = $query->count();

        // Apply sorting
        $sortField = $this->mapSortField($params['sort'] ?? 'title');
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDir);

        // Apply pagination
        $limit = min((int) ($params['limit'] ?? 20), 100);
        $offset = (int) ($params['offset'] ?? 0);

        $rows = $query
            ->skip($offset)
            ->take($limit)
            ->select([
                'li.*',
                'io.slug',
                'io.parent_id',
                'ioi.title',
            ])
            ->get();

        // Map to items with basic creator info
        $results = $rows->map(function ($row) {
            $item = LibraryItem::fromRow($row);
            // Load only primary creator for list view
            $creators = $this->getCreators($item->id);
            $item->creators = $creators;
            return $item;
        })->toArray();

        return [
            'results' => $results,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Apply search filters to query
     */
    protected function applySearchFilters(Builder $query, array $params): void
    {
        // Material type filter
        if (!empty($params['material_type'])) {
            $query->where('li.material_type', $params['material_type']);
        }

        // Call number prefix
        if (!empty($params['call_number'])) {
            $query->where('li.call_number', 'LIKE', $params['call_number'] . '%');
        }

        // ISBN exact match
        if (!empty($params['isbn'])) {
            $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($params['isbn']));
            $query->where('li.isbn', $cleanIsbn);
        }

        // ISSN exact match
        if (!empty($params['issn'])) {
            $cleanIssn = preg_replace('/[^0-9X]/', '', strtoupper($params['issn']));
            $query->where('li.issn', $cleanIssn);
        }

        // Publisher filter
        if (!empty($params['publisher'])) {
            $query->where('li.publisher', 'LIKE', '%' . $params['publisher'] . '%');
        }

        // Circulation status
        if (!empty($params['status'])) {
            $query->where('li.circulation_status', $params['status']);
        }

        // Full text search
        if (!empty($params['query'])) {
            $term = '%' . $params['query'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('ioi.title', 'LIKE', $term)
                    ->orWhere('li.isbn', 'LIKE', $term)
                    ->orWhere('li.issn', 'LIKE', $term)
                    ->orWhere('li.call_number', 'LIKE', $term)
                    ->orWhere('li.publisher', 'LIKE', $term)
                    ->orWhere('li.barcode', 'LIKE', $term);
            });
        }

        // Date range
        if (!empty($params['date_from'])) {
            $query->where('li.publication_date', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->where('li.publication_date', '<=', $params['date_to']);
        }
    }

    /**
     * Map sort field to database column
     */
    protected function mapSortField(string $sort): string
    {
        $map = [
            'title' => 'ioi.title',
            'call_number' => 'li.call_number',
            'author' => 'ioi.title', // Would need join
            'date' => 'li.publication_date',
            'publisher' => 'li.publisher',
            'added' => 'li.created_at',
            'updated' => 'li.updated_at',
        ];

        return $map[$sort] ?? 'ioi.title';
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_items' => DB::table(self::TABLE_ITEM)->count(),
            'total_copies' => DB::table(self::TABLE_COPY)->count(),
            'available_copies' => DB::table(self::TABLE_COPY)->where('status', 'available')->count(),
            'on_loan' => DB::table(self::TABLE_COPY)->where('status', 'on_loan')->count(),
            'by_type' => DB::table(self::TABLE_ITEM)
                ->select('material_type', DB::raw('COUNT(*) as count'))
                ->groupBy('material_type')
                ->pluck('count', 'material_type')
                ->toArray(),
            'by_status' => DB::table(self::TABLE_ITEM)
                ->select('circulation_status', DB::raw('COUNT(*) as count'))
                ->groupBy('circulation_status')
                ->pluck('count', 'circulation_status')
                ->toArray(),
        ];
    }
}
