<?php

namespace AhgAuthority\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service #5: NER-to-Authority Pipeline Service (#204)
 *
 * Extends the NER review workflow: findMatchingActors -> create stub -> link entity.
 * Delegates to NerRepository from ahgAIPlugin when available.
 */
class AuthorityNerPipelineService
{
    /**
     * Get NER entities that can become authority stubs.
     */
    public function getPendingEntities(array $filters = []): array
    {
        $query = DB::table('ner_entity as ne')
            ->leftJoin('ahg_ner_authority_stub as stub', 'ne.id', '=', 'stub.ner_entity_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ne.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->whereNull('stub.id')
            ->whereIn('ne.entity_type', ['PERSON', 'ORG', 'GPE'])
            ->select(
                'ne.*',
                'ioi.title as source_title'
            );

        if (!empty($filters['entity_type'])) {
            $query->where('ne.entity_type', $filters['entity_type']);
        }

        if (!empty($filters['min_confidence'])) {
            $query->where('ne.confidence', '>=', $filters['min_confidence']);
        }

        if (!empty($filters['search'])) {
            $query->where('ne.entity_value', 'like', '%' . $filters['search'] . '%');
        }

        $sort = $filters['sort'] ?? 'ne.confidence';
        $dir = $filters['sortDir'] ?? 'desc';
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;

        return $query->orderBy($sort, $dir)
            ->paginate($limit, ['*'], 'page', $page)
            ->toArray();
    }

    /**
     * Get existing authority stubs.
     */
    public function getStubs(array $filters = []): array
    {
        $query = DB::table('ahg_ner_authority_stub as s')
            ->leftJoin('actor_i18n as ai', function ($j) {
                $j->on('s.actor_id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('s.source_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 's.actor_id', '=', 'slug.object_id')
            ->select(
                's.*',
                'ai.authorized_form_of_name as actor_name',
                'ioi.title as source_title',
                'slug.slug'
            );

        if (!empty($filters['status'])) {
            $query->where('s.status', $filters['status']);
        }

        if (!empty($filters['entity_type'])) {
            $query->where('s.entity_type', $filters['entity_type']);
        }

        $sort = $filters['sort'] ?? 's.created_at';
        $dir = $filters['sortDir'] ?? 'desc';
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;

        return $query->orderBy($sort, $dir)
            ->paginate($limit, ['*'], 'page', $page)
            ->toArray();
    }

    /**
     * Find matching actors for a NER entity value.
     * Delegates to NerRepository if ahgAIPlugin is available.
     */
    public function findMatchingActors(string $entityValue, string $entityType = 'PERSON', int $limit = 5): array
    {
        // Try to use ahgAIPlugin's NerRepository
        $nerRepoFile = \sfConfig::get('sf_root_dir') .
            '/atom-ahg-plugins/ahgAIPlugin/lib/repository/NerRepository.php';

        if (file_exists($nerRepoFile)) {
            require_once $nerRepoFile;

            if (class_exists('\\AhgAI\\Repository\\NerRepository')) {
                $repo = new \AhgAI\Repository\NerRepository();
                if (method_exists($repo, 'findMatchingActors')) {
                    return $repo->findMatchingActors($entityValue, $limit);
                }
            }
        }

        // Fallback: simple LIKE search on actor_i18n
        return DB::table('actor_i18n as ai')
            ->leftJoin('slug', 'ai.id', '=', 'slug.object_id')
            ->where('ai.culture', 'en')
            ->where('ai.authorized_form_of_name', 'like', '%' . $entityValue . '%')
            ->select('ai.id', 'ai.authorized_form_of_name as name', 'slug.slug')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Create a stub authority record from a NER entity.
     */
    public function createStub(int $nerEntityId, int $userId): ?int
    {
        // Get the NER entity
        $entity = DB::table('ner_entity')
            ->where('id', $nerEntityId)
            ->first();

        if (!$entity) {
            return null;
        }

        // Check if stub already exists
        $existing = DB::table('ahg_ner_authority_stub')
            ->where('ner_entity_id', $nerEntityId)
            ->first();

        if ($existing) {
            return (int) $existing->actor_id;
        }

        // Create the actor via AtoM's object/actor tables
        // Insert into object table first
        $objectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Insert actor record
        DB::table('actor')->insert([
            'id'             => $objectId,
            'entity_type_id' => $this->getEntityTypeId($entity->entity_type),
        ]);

        // Insert actor_i18n
        DB::table('actor_i18n')->insert([
            'id'                          => $objectId,
            'culture'                     => 'en',
            'authorized_form_of_name'     => $entity->entity_value,
            'description_identifier'      => 'NER-STUB-' . $nerEntityId,
            'sources'                     => 'Created from NER extraction',
        ]);

        // Generate slug
        $slug = $this->generateSlug($entity->entity_value);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug'      => $slug,
        ]);

        // Record the stub
        DB::table('ahg_ner_authority_stub')->insert([
            'ner_entity_id'   => $nerEntityId,
            'actor_id'        => $objectId,
            'source_object_id' => $entity->object_id,
            'entity_type'     => $entity->entity_type,
            'entity_value'    => $entity->entity_value,
            'confidence'      => $entity->confidence ?? 1.0,
            'status'          => 'stub',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return $objectId;
    }

    /**
     * Promote a stub to a full authority record.
     */
    public function promoteStub(int $stubId, int $userId): bool
    {
        return DB::table('ahg_ner_authority_stub')
            ->where('id', $stubId)
            ->update([
                'status'      => 'promoted',
                'promoted_by' => $userId,
                'promoted_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Reject a stub (mark as not a valid authority).
     */
    public function rejectStub(int $stubId, int $userId): bool
    {
        return DB::table('ahg_ner_authority_stub')
            ->where('id', $stubId)
            ->update([
                'status'      => 'rejected',
                'promoted_by' => $userId,
                'promoted_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get pipeline statistics.
     */
    public function getStats(): array
    {
        $byStatus = DB::table('ahg_ner_authority_stub')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->all();

        $pendingEntities = 0;
        try {
            $pendingEntities = DB::table('ner_entity as ne')
                ->leftJoin('ahg_ner_authority_stub as stub', 'ne.id', '=', 'stub.ner_entity_id')
                ->whereNull('stub.id')
                ->whereIn('ne.entity_type', ['PERSON', 'ORG', 'GPE'])
                ->count();
        } catch (\Exception $e) {
            // ner_entity table may not exist
        }

        return [
            'pending_entities' => $pendingEntities,
            'by_status'        => $byStatus,
            'total_stubs'      => DB::table('ahg_ner_authority_stub')->count(),
        ];
    }

    /**
     * Map NER entity type to AtoM entity type term ID.
     */
    protected function getEntityTypeId(string $entityType): ?int
    {
        $map = [
            'PERSON' => 'Corporate body', // Will be overridden below
            'ORG'    => 'Corporate body',
            'GPE'    => 'Corporate body',
        ];

        $termName = 'Person';
        if ($entityType === 'ORG' || $entityType === 'GPE') {
            $termName = 'Corporate body';
        }

        $term = DB::table('term_i18n')
            ->join('term', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', 32) // ACTOR_ENTITY_TYPE taxonomy
            ->where('term_i18n.culture', 'en')
            ->where('term_i18n.name', $termName)
            ->select('term.id')
            ->first();

        return $term ? (int) $term->id : null;
    }

    /**
     * Generate a URL-safe slug from a name.
     */
    protected function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'ner-stub';
        }

        // Ensure uniqueness
        $base = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
