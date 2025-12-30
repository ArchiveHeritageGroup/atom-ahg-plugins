<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use arMuseumMetadataPlugin\Models\GettyVocabularyLink;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Getty Vocabulary Link Repository.
 *
 * Manages persistence of Getty vocabulary links using Laravel Query Builder.
 * Framework-independent implementation for atom-framework.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GettyLinkRepository
{
    private const TABLE = 'getty_vocabulary_link';

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
     * Find link by ID.
     */
    public function find(int $id): ?GettyVocabularyLink
    {
        $row = $this->query()->where('id', $id)->first();

        if (!$row) {
            return null;
        }

        return GettyVocabularyLink::fromArray((array) $row);
    }

    /**
     * Find link by term ID.
     */
    public function findByTermId(int $termId): ?GettyVocabularyLink
    {
        $row = $this->query()
            ->where('term_id', $termId)
            ->where('status', '!=', GettyVocabularyLink::STATUS_REJECTED)
            ->orderByDesc('confidence')
            ->first();

        if (!$row) {
            return null;
        }

        return GettyVocabularyLink::fromArray((array) $row);
    }

    /**
     * Find all links for a term (including rejected).
     *
     * @return GettyVocabularyLink[]
     */
    public function findAllByTermId(int $termId): array
    {
        $rows = $this->query()
            ->where('term_id', $termId)
            ->orderByDesc('confidence')
            ->get();

        return array_map(
            fn ($row) => GettyVocabularyLink::fromArray((array) $row),
            $rows->all()
        );
    }

    /**
     * Find link by Getty URI.
     */
    public function findByGettyUri(string $uri): ?GettyVocabularyLink
    {
        $row = $this->query()->where('getty_uri', $uri)->first();

        if (!$row) {
            return null;
        }

        return GettyVocabularyLink::fromArray((array) $row);
    }

    /**
     * Find all confirmed links.
     *
     * @return GettyVocabularyLink[]
     */
    public function findConfirmed(int $limit = 100, int $offset = 0): array
    {
        $rows = $this->query()
            ->where('status', GettyVocabularyLink::STATUS_CONFIRMED)
            ->orderBy('term_id')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return array_map(
            fn ($row) => GettyVocabularyLink::fromArray((array) $row),
            $rows->all()
        );
    }

    /**
     * Find all suggested links (pending review).
     *
     * @return GettyVocabularyLink[]
     */
    public function findSuggested(int $limit = 100, int $offset = 0): array
    {
        $rows = $this->query()
            ->where('status', GettyVocabularyLink::STATUS_SUGGESTED)
            ->orderByDesc('confidence')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return array_map(
            fn ($row) => GettyVocabularyLink::fromArray((array) $row),
            $rows->all()
        );
    }

    /**
     * Find links by vocabulary.
     *
     * @return GettyVocabularyLink[]
     */
    public function findByVocabulary(string $vocabulary, int $limit = 100, int $offset = 0): array
    {
        $rows = $this->query()
            ->where('vocabulary', $vocabulary)
            ->where('status', '!=', GettyVocabularyLink::STATUS_REJECTED)
            ->orderBy('term_id')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return array_map(
            fn ($row) => GettyVocabularyLink::fromArray((array) $row),
            $rows->all()
        );
    }

    /**
     * Find links by taxonomy.
     *
     * @return GettyVocabularyLink[]
     */
    public function findByTaxonomy(int $taxonomyId, int $limit = 100, int $offset = 0): array
    {
        $rows = $this->db->table(self::TABLE)
            ->join('term', 'term.id', '=', self::TABLE.'.term_id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where(self::TABLE.'.status', '!=', GettyVocabularyLink::STATUS_REJECTED)
            ->select(self::TABLE.'.*')
            ->orderBy(self::TABLE.'.term_id')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return array_map(
            fn ($row) => GettyVocabularyLink::fromArray((array) $row),
            $rows->all()
        );
    }

    /**
     * Save a link (insert or update).
     */
    public function save(GettyVocabularyLink $link): GettyVocabularyLink
    {
        $data = [
            'term_id' => $link->getTermId(),
            'vocabulary' => $link->getVocabulary(),
            'getty_uri' => $link->getGettyUri(),
            'getty_id' => $link->getGettyId(),
            'getty_pref_label' => $link->getGettyPrefLabel(),
            'getty_scope_note' => $link->getGettyScopeNote(),
            'status' => $link->getStatus(),
            'confidence' => $link->getConfidence(),
            'confirmed_by_user_id' => $link->getConfirmedByUserId(),
            'confirmed_at' => $link->getConfirmedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($link->getId()) {
            // Update
            $this->query()->where('id', $link->getId())->update($data);

            $this->logger->info('Getty link updated', [
                'id' => $link->getId(),
                'term_id' => $link->getTermId(),
                'vocabulary' => $link->getVocabulary(),
            ]);
        } else {
            // Insert
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = $this->query()->insertGetId($data);
            $link->setId($id);

            $this->logger->info('Getty link created', [
                'id' => $id,
                'term_id' => $link->getTermId(),
                'vocabulary' => $link->getVocabulary(),
                'getty_uri' => $link->getGettyUri(),
            ]);
        }

        return $link;
    }

    /**
     * Delete a link.
     */
    public function delete(int $id): bool
    {
        $deleted = $this->query()->where('id', $id)->delete();

        if ($deleted) {
            $this->logger->info('Getty link deleted', ['id' => $id]);
        }

        return $deleted > 0;
    }

    /**
     * Delete all links for a term.
     */
    public function deleteByTermId(int $termId): int
    {
        $deleted = $this->query()->where('term_id', $termId)->delete();

        $this->logger->info('Getty links deleted for term', [
            'term_id' => $termId,
            'count' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Count links by status.
     */
    public function countByStatus(): array
    {
        $rows = $this->query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $counts = [
            GettyVocabularyLink::STATUS_CONFIRMED => 0,
            GettyVocabularyLink::STATUS_SUGGESTED => 0,
            GettyVocabularyLink::STATUS_REJECTED => 0,
            GettyVocabularyLink::STATUS_PENDING => 0,
        ];

        foreach ($rows as $row) {
            $counts[$row->status] = (int) $row->count;
        }

        return $counts;
    }

    /**
     * Count links by vocabulary.
     */
    public function countByVocabulary(): array
    {
        $rows = $this->query()
            ->selectRaw('vocabulary, COUNT(*) as count')
            ->where('status', '!=', GettyVocabularyLink::STATUS_REJECTED)
            ->groupBy('vocabulary')
            ->get();

        $counts = [
            GettyVocabularyLink::VOCABULARY_AAT => 0,
            GettyVocabularyLink::VOCABULARY_TGN => 0,
            GettyVocabularyLink::VOCABULARY_ULAN => 0,
        ];

        foreach ($rows as $row) {
            $counts[$row->vocabulary] = (int) $row->count;
        }

        return $counts;
    }

    /**
     * Get terms without Getty links (for a taxonomy).
     *
     * @return array Array of term records
     */
    public function findUnlinkedTerms(int $taxonomyId, int $limit = 100): array
    {
        return $this->db->table('term')
            ->join('term_i18n', 'term_i18n.id', '=', 'term.id')
            ->leftJoin(self::TABLE, function ($join) {
                $join->on(self::TABLE.'.term_id', '=', 'term.id')
                    ->where(self::TABLE.'.status', '!=', GettyVocabularyLink::STATUS_REJECTED);
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.culture', 'en')
            ->whereNull(self::TABLE.'.id')
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Check if table exists.
     */
    public function tableExists(): bool
    {
        return $this->db->getSchemaBuilder()->hasTable(self::TABLE);
    }

    /**
     * Get query builder for table.
     */
    private function query(): Builder
    {
        return $this->db->table(self::TABLE);
    }
}
