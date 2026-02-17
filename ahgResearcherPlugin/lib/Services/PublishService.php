<?php

namespace AhgResearcherPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Publish approved researcher submissions to AtoM records.
 *
 * Creates information_object, digital_object, and access point relations
 * for each approved submission item.
 */
class PublishService
{
    private SubmissionService $submissionService;

    public function __construct()
    {
        $this->submissionService = new SubmissionService();
    }

    /**
     * Publish an approved submission.
     *
     * @return array{created_objects: array, created_actors: array, created_repos: array, errors: array}
     */
    public function publish(int $submissionId, int $userId): array
    {
        $data = $this->submissionService->getSubmission($submissionId);
        if (!$data) {
            return ['created_objects' => [], 'created_actors' => [], 'created_repos' => [], 'errors' => ['Submission not found.']];
        }

        $sub = $data['submission'];
        if (!in_array($sub->status, ['approved'])) {
            return ['created_objects' => [], 'created_actors' => [], 'created_repos' => [], 'errors' => ['Submission is not approved.']];
        }

        $repositoryId = $sub->repository_id;
        $parentObjectId = $sub->parent_object_id ?? \QubitInformationObject::ROOT_ID;

        $results = [
            'created_objects' => [],
            'created_actors'  => [],
            'created_repos'   => [],
            'errors'          => [],
        ];

        // Sort items: repositories first, then creators, then descriptions
        $items = $data['items'];
        $files = $data['files'];

        $repos = array_filter($items, fn ($i) => $i->item_type === 'repository');
        $creators = array_filter($items, fn ($i) => $i->item_type === 'creator');
        $descriptions = array_filter($items, fn ($i) => $i->item_type === 'description');
        $notes = array_filter($items, fn ($i) => $i->item_type === 'note');

        // Map: item_id => created AtoM object ID (for hierarchy resolution)
        $itemToObjectId = [];

        // 1. Create repositories
        foreach ($repos as $item) {
            try {
                $repoId = $this->createRepository($item, $userId);
                $itemToObjectId[$item->id] = $repoId;
                $results['created_repos'][] = [
                    'item_id' => $item->id,
                    'object_id' => $repoId,
                    'title' => $item->title,
                ];
                DB::table('researcher_submission_item')
                    ->where('id', $item->id)
                    ->update(['published_object_id' => $repoId]);
            } catch (\Exception $e) {
                $results['errors'][] = "Repository '{$item->title}': " . $e->getMessage();
            }
        }

        // 2. Create creators
        foreach ($creators as $item) {
            try {
                $actorId = $this->createActor($item, $userId);
                $itemToObjectId[$item->id] = $actorId;
                $results['created_actors'][] = [
                    'item_id' => $item->id,
                    'object_id' => $actorId,
                    'title' => $item->title,
                ];
                DB::table('researcher_submission_item')
                    ->where('id', $item->id)
                    ->update(['published_object_id' => $actorId]);
            } catch (\Exception $e) {
                $results['errors'][] = "Creator '{$item->title}': " . $e->getMessage();
            }
        }

        // 3. Create descriptions (respect hierarchy)
        // First pass: root items (no parent_item_id), then children
        $rootDescriptions = array_filter($descriptions, fn ($i) => empty($i->parent_item_id));
        $childDescriptions = array_filter($descriptions, fn ($i) => !empty($i->parent_item_id));

        foreach ($rootDescriptions as $item) {
            try {
                $objectId = $this->createInformationObject($item, $parentObjectId, $repositoryId, $userId);
                $itemToObjectId[$item->id] = $objectId;

                // Attach files
                $this->attachFiles($objectId, $files[$item->id] ?? [], $userId);

                // Link access points
                $this->linkAccessPoints($objectId, $item);

                $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');
                $results['created_objects'][] = [
                    'item_id'   => $item->id,
                    'object_id' => $objectId,
                    'slug'      => $slug,
                    'title'     => $item->title,
                    'level'     => $item->level_of_description,
                ];

                DB::table('researcher_submission_item')
                    ->where('id', $item->id)
                    ->update(['published_object_id' => $objectId]);
            } catch (\Exception $e) {
                $results['errors'][] = "Item '{$item->title}': " . $e->getMessage();
            }
        }

        // Process children (may need multiple passes for deep hierarchy)
        $remaining = $childDescriptions;
        $maxPasses = 10;
        $pass = 0;

        while (!empty($remaining) && $pass < $maxPasses) {
            $pass++;
            $stillRemaining = [];

            foreach ($remaining as $item) {
                $parentId = $itemToObjectId[$item->parent_item_id] ?? null;

                if ($parentId === null) {
                    // Parent not yet created — defer to next pass
                    $stillRemaining[] = $item;
                    continue;
                }

                try {
                    $objectId = $this->createInformationObject($item, $parentId, $repositoryId, $userId);
                    $itemToObjectId[$item->id] = $objectId;

                    $this->attachFiles($objectId, $files[$item->id] ?? [], $userId);
                    $this->linkAccessPoints($objectId, $item);

                    $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');
                    $results['created_objects'][] = [
                        'item_id'   => $item->id,
                        'object_id' => $objectId,
                        'slug'      => $slug,
                        'title'     => $item->title,
                        'level'     => $item->level_of_description,
                    ];

                    DB::table('researcher_submission_item')
                        ->where('id', $item->id)
                        ->update(['published_object_id' => $objectId]);
                } catch (\Exception $e) {
                    $results['errors'][] = "Item '{$item->title}': " . $e->getMessage();
                }
            }

            $remaining = $stillRemaining;
        }

        // 4. Process notes (attach to existing records)
        foreach ($notes as $item) {
            try {
                if ($item->reference_object_id) {
                    $this->attachNote($item);
                    $results['created_objects'][] = [
                        'item_id'   => $item->id,
                        'object_id' => $item->reference_object_id,
                        'slug'      => $item->reference_slug,
                        'title'     => $item->title,
                        'level'     => 'note',
                    ];
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Note '{$item->title}': " . $e->getMessage();
            }
        }

        // Add review log
        DB::table('researcher_submission_review')->insert([
            'submission_id' => $submissionId,
            'reviewer_id'   => $userId,
            'action'        => 'publish',
            'comment'       => sprintf(
                'Published %d records, %d creators, %d repositories.',
                count($results['created_objects']),
                count($results['created_actors']),
                count($results['created_repos'])
            ),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->submissionService->markPublished($submissionId);

        return $results;
    }

    /**
     * Create an information_object from a submission item.
     */
    protected function createInformationObject(object $item, int $parentId, ?int $repositoryId, int $userId): int
    {
        // Create base object record
        $objectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Determine MPTT bounds from parent
        $parent = DB::table('information_object')->where('id', $parentId)->first();
        $rgt = $parent ? $parent->rgt : 2;

        // Shift existing nodes
        DB::table('information_object')->where('rgt', '>=', $rgt)->increment('rgt', 2);
        DB::table('information_object')->where('lft', '>', $rgt)->increment('lft', 2);

        // Map level_of_description to term_id
        $levelTermId = $this->resolveLevelOfDescription($item->level_of_description);

        // Insert information_object
        DB::table('information_object')->insert([
            'id'                     => $objectId,
            'identifier'             => $item->identifier,
            'level_of_description_id' => $levelTermId,
            'repository_id'          => $repositoryId,
            'parent_id'              => $parentId,
            'lft'                    => $rgt,
            'rgt'                    => $rgt + 1,
            'source_culture'         => 'en',
        ]);

        // Insert i18n
        DB::table('information_object_i18n')->insert([
            'id'                  => $objectId,
            'culture'             => 'en',
            'title'               => $item->title,
            'scope_and_content'   => $item->scope_and_content,
            'extent_and_medium'   => $item->extent_and_medium,
            'access_conditions'   => $item->access_conditions,
            'reproduction_conditions' => $item->reproduction_conditions,
        ]);

        // Generate slug
        $slug = $this->generateSlug($item->title);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug'      => $slug,
        ]);

        // Create date event if date info provided
        if ($item->date_display || $item->date_start) {
            $this->createDateEvent($objectId, $item);
        }

        return $objectId;
    }

    /**
     * Create a repository record from a submission item.
     */
    protected function createRepository(object $item, int $userId): int
    {
        // Create object
        $objectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitRepository',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create actor (repository extends actor)
        DB::table('actor')->insert([
            'id'             => $objectId,
            'parent_id'      => \QubitActor::ROOT_ID,
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
        ]);

        // Create actor_i18n
        DB::table('actor_i18n')->insert([
            'id'                          => $objectId,
            'culture'                     => 'en',
            'authorized_form_of_name'     => $item->repository_name ?? $item->title,
        ]);

        // Create repository
        DB::table('repository')->insert([
            'id'             => $objectId,
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
        ]);

        // Create repository_i18n
        DB::table('repository_i18n')->insert([
            'id'      => $objectId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'desc_detail' => $item->scope_and_content,
        ]);

        // Generate slug
        $slug = $this->generateSlug($item->repository_name ?? $item->title);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug'      => $slug,
        ]);

        return $objectId;
    }

    /**
     * Create an actor (creator) from a submission item.
     */
    protected function createActor(object $item, int $userId): int
    {
        $objectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('actor')->insert([
            'id'             => $objectId,
            'parent_id'      => \QubitActor::ROOT_ID,
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
        ]);

        DB::table('actor_i18n')->insert([
            'id'                          => $objectId,
            'culture'                     => 'en',
            'authorized_form_of_name'     => $item->title,
            'history'                     => $item->scope_and_content,
            'dates_of_existence'          => $item->date_display,
        ]);

        $slug = $this->generateSlug($item->title);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug'      => $slug,
        ]);

        return $objectId;
    }

    /**
     * Attach uploaded files to an information_object as digital objects.
     */
    protected function attachFiles(int $objectId, array $files, int $userId): void
    {
        foreach ($files as $file) {
            if (empty($file->stored_path) || !file_exists($file->stored_path)) {
                continue;
            }

            try {
                $this->createDigitalObject($file, $objectId);
            } catch (\Exception $e) {
                // Log but don't fail the whole publish
            }
        }
    }

    /**
     * Create a digital_object record and move the file to AtoM uploads.
     */
    protected function createDigitalObject(object $file, int $objectId): int
    {
        $uploadsDir = \sfConfig::get('sf_root_dir') . '/uploads/r';
        $subDir = sprintf('%06d', $objectId);
        $targetDir = $uploadsDir . '/' . $subDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $destName = $file->original_name;
        $destPath = $targetDir . '/' . $destName;

        // Copy file to uploads (keep staging copy for safety)
        copy($file->stored_path, $destPath);

        // Create object record for DO
        $doObjectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitDigitalObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $relativePath = '/uploads/r/' . $subDir . '/' . $destName;

        DB::table('digital_object')->insert([
            'id'                 => $doObjectId,
            'object_id'          => $objectId,
            'name'               => $file->original_name,
            'path'               => $relativePath,
            'mime_type'          => $file->mime_type,
            'byte_size'          => $file->file_size,
            'checksum'           => $file->checksum,
            'checksum_type'      => 'sha256',
            'usage_id'           => \QubitTerm::MASTER_ID,
            'sequence'           => $file->sort_order ?? 0,
        ]);

        // Update file record with published DO ID
        DB::table('researcher_submission_file')
            ->where('id', $file->id)
            ->update(['published_do_id' => $doObjectId]);

        return $doObjectId;
    }

    /**
     * Link access points (subjects, places, genres, creators) to an information_object.
     */
    protected function linkAccessPoints(int $objectId, object $item): void
    {
        // Subjects (taxonomy_id = 35)
        if (!empty($item->subjects)) {
            $this->linkTerms($objectId, $item->subjects, \QubitTaxonomy::SUBJECT_ID);
        }

        // Places (taxonomy_id = 42)
        if (!empty($item->places)) {
            $this->linkTerms($objectId, $item->places, \QubitTaxonomy::PLACE_ID);
        }

        // Genres (taxonomy_id = 78)
        if (!empty($item->genres)) {
            $this->linkTerms($objectId, $item->genres, \QubitTaxonomy::GENRE_ID);
        }

        // Creators — link as name access point events
        if (!empty($item->creators)) {
            $this->linkCreators($objectId, $item->creators);
        }
    }

    /**
     * Link comma-separated terms to an object via object_term_relation.
     */
    protected function linkTerms(int $objectId, string $terms, int $taxonomyId): void
    {
        $termNames = array_map('trim', explode(',', $terms));

        foreach ($termNames as $termName) {
            if (empty($termName)) {
                continue;
            }

            // Find or create term
            $termId = $this->findOrCreateTerm($termName, $taxonomyId);

            // Check if relation already exists
            $exists = DB::table('object_term_relation')
                ->where('object_id', $objectId)
                ->where('term_id', $termId)
                ->exists();

            if (!$exists) {
                $relId = (int) DB::table('object')->insertGetId([
                    'class_name' => 'QubitObjectTermRelation',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('object_term_relation')->insert([
                    'id'        => $relId,
                    'object_id' => $objectId,
                    'term_id'   => $termId,
                ]);
            }
        }
    }

    /**
     * Find or create a term in a taxonomy.
     */
    protected function findOrCreateTerm(string $name, int $taxonomyId): int
    {
        $existing = DB::table('term')
            ->join('term_i18n', function ($j) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', 'en');
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', $name)
            ->select('term.id')
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        // Create new term
        $objectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitTerm',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $parent = DB::table('term')
            ->where('taxonomy_id', $taxonomyId)
            ->where('parent_id', \QubitTerm::ROOT_ID)
            ->first();

        $parentId = $parent ? $parent->id : \QubitTerm::ROOT_ID;

        // Get MPTT position
        $rgt = DB::table('term')
            ->where('taxonomy_id', $taxonomyId)
            ->max('rgt') ?? 2;

        DB::table('term')->insert([
            'id'             => $objectId,
            'taxonomy_id'    => $taxonomyId,
            'parent_id'      => $parentId,
            'lft'            => $rgt,
            'rgt'            => $rgt + 1,
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
        ]);

        DB::table('term_i18n')->insert([
            'id'      => $objectId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'name'    => $name,
        ]);

        $slug = $this->generateSlug($name);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug'      => $slug,
        ]);

        return $objectId;
    }

    /**
     * Link creators to an information_object via event records.
     */
    protected function linkCreators(int $objectId, string $creators): void
    {
        $creatorNames = array_map('trim', explode(',', $creators));

        foreach ($creatorNames as $creatorName) {
            if (empty($creatorName)) {
                continue;
            }

            // Find existing actor by name
            $actor = DB::table('actor_i18n')
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->where('authorized_form_of_name', $creatorName)
                ->first();

            $actorId = $actor ? (int) $actor->id : null;

            // Create an event linking creator to IO
            $eventObjectId = (int) DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('event')->insert([
                'id'                    => $eventObjectId,
                'information_object_id' => $objectId,
                'actor_id'              => $actorId,
                'type_id'               => \QubitTerm::CREATION_ID,
                'source_culture'        => 'en',
            ]);

            if ($actorId) {
                DB::table('event_i18n')->insert([
                    'id'      => $eventObjectId,
                    'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                    'name'    => $creatorName,
                ]);
            }
        }
    }

    /**
     * Attach a research note to an existing AtoM record.
     */
    protected function attachNote(object $item): void
    {
        $objectId = $item->reference_object_id;

        // Create a note on the information_object
        $noteObjectId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitNote',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('note')->insert([
            'id'             => $noteObjectId,
            'object_id'      => $objectId,
            'type_id'        => \QubitTerm::GENERAL_NOTE_ID,
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
        ]);

        DB::table('note_i18n')->insert([
            'id'      => $noteObjectId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'content' => $item->scope_and_content ?? $item->notes ?? $item->title,
        ]);
    }

    /**
     * Create a date event for an information_object.
     */
    protected function createDateEvent(int $objectId, object $item): void
    {
        $eventId = (int) DB::table('object')->insertGetId([
            'class_name' => 'QubitEvent',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('event')->insert([
            'id'                    => $eventId,
            'information_object_id' => $objectId,
            'type_id'               => \QubitTerm::CREATION_ID,
            'start_date'            => $item->date_start ?: null,
            'end_date'              => $item->date_end ?: null,
            'source_culture'        => 'en',
        ]);

        DB::table('event_i18n')->insert([
            'id'      => $eventId,
            'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
            'date'    => $item->date_display,
        ]);
    }

    /**
     * Resolve level of description string to term ID.
     */
    protected function resolveLevelOfDescription(string $level): ?int
    {
        $map = [
            'fonds'       => null,
            'subfonds'    => null,
            'collection'  => null,
            'series'      => null,
            'subseries'   => null,
            'file'        => null,
            'item'        => null,
        ];

        $term = DB::table('term')
            ->join('term_i18n', function ($j) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', 'en');
            })
            ->where('term.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->whereRaw('LOWER(term_i18n.name) = ?', [strtolower($level)])
            ->select('term.id')
            ->first();

        return $term ? (int) $term->id : null;
    }

    /**
     * Generate a unique URL slug from a title.
     */
    protected function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 200) ?: 'untitled';

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
