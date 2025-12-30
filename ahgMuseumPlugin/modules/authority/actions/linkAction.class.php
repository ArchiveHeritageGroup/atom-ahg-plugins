<?php
/**
 * Authority Linkage Action
 *
 * Provides UI for linking actor records to external authorities.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class authorityLinkAction extends sfAction
{
    // Term IDs for entity types
    private const TERM_PERSON_ID = 131;
    private const TERM_CORPORATE_BODY_ID = 132;
    private const TERM_FAMILY_ID = 133;

    public function execute($request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->forwardUnauthorized();
        }

        $this->actor = $this->getActorBySlug($request->getParameter('slug'));

        if (!$this->actor) {
            $this->forward404('Actor not found');
        }

        // Initialize service
        $this->service = new ahgAuthorityLinkageService();

        // Get current linked authorities
        $this->linkedAuthorities = $this->service->getActorAuthorities($this->actor->id);

        // Get authority sources metadata
        $this->sources = ahgAuthorityLinkageService::$sources;

        // Determine actor type for filtering
        $this->actorType = $this->getActorType();

        // Handle form submissions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ($action === 'link') {
                return $this->handleLink($request);
            } elseif ($action === 'unlink') {
                return $this->handleUnlink($request);
            } elseif ($action === 'enrich') {
                return $this->handleEnrich($request);
            }
        }

        // Handle search request
        $this->searchResults = [];
        $this->searchQuery = $request->getParameter('q');
        $this->searchSource = $request->getParameter('source', 'all');

        if ($this->searchQuery) {
            $this->searchResults = $this->performSearch();
        }

        // Get enrichment data for linked authorities
        $this->enrichmentData = [];
        foreach ($this->linkedAuthorities as $source => $auth) {
            try {
                $data = $this->service->enrichActorFromAuthority($this->actor->id, $source);
                if ($data) {
                    $this->enrichmentData[$source] = $data;
                }
            } catch (\Exception $e) {
                // Silently fail for enrichment
            }
        }
    }

    /**
     * Forward to unauthorized page
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    /**
     * Get actor by slug using Laravel
     */
    protected function getActorBySlug(?string $slug): ?object
    {
        if (!$slug) {
            return null;
        }

        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('actor as a')
            ->join('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('a.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->where('slug.slug', $slug)
            ->select([
                'a.*',
                'slug.slug',
                DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as authorized_form_of_name'),
                DB::raw('COALESCE(ai.dates_of_existence, ai_en.dates_of_existence) as dates_of_existence'),
                DB::raw('COALESCE(ai.history, ai_en.history) as history'),
                DB::raw('COALESCE(ai.places, ai_en.places) as places'),
                DB::raw('COALESCE(ai.legal_status, ai_en.legal_status) as legal_status'),
                DB::raw('COALESCE(ai.functions, ai_en.functions) as functions'),
                DB::raw('COALESCE(ai.mandates, ai_en.mandates) as mandates'),
                DB::raw('COALESCE(ai.internal_structures, ai_en.internal_structures) as internal_structures'),
                DB::raw('COALESCE(ai.general_context, ai_en.general_context) as general_context'),
            ])
            ->first();
    }

    /**
     * Get actor type for search filtering
     */
    protected function getActorType(): ?string
    {
        if (!$this->actor || !isset($this->actor->entity_type_id)) {
            return null;
        }

        if ($this->actor->entity_type_id == self::TERM_PERSON_ID) {
            return 'person';
        } elseif ($this->actor->entity_type_id == self::TERM_CORPORATE_BODY_ID) {
            return 'corporate_body';
        } elseif ($this->actor->entity_type_id == self::TERM_FAMILY_ID) {
            return 'family';
        }

        return null;
    }

    /**
     * Perform authority search
     */
    protected function performSearch(): array
    {
        $query = $this->searchQuery;
        $source = $this->searchSource;
        $type = $this->actorType;

        if ($source === 'all') {
            return $this->service->searchAllSources($query, $type, null, 10);
        } else {
            $sources = [$source];

            return $this->service->searchAllSources($query, $type, $sources, 20);
        }
    }

    /**
     * Handle linking authority
     */
    protected function handleLink($request)
    {
        $source = $request->getParameter('link_source');
        $authorityId = $request->getParameter('link_id');

        if (!$source || !$authorityId) {
            $this->getUser()->setFlash('error', 'Missing source or authority ID');

            return $this->redirect(['module' => 'authority', 'action' => 'link', 'slug' => $this->actor->slug]);
        }

        try {
            $this->service->linkAuthorityToActor($this->actor->id, $source, $authorityId);
            $this->getUser()->setFlash('notice', sprintf(
                'Successfully linked %s authority: %s',
                ahgAuthorityLinkageService::$sources[$source]['label'],
                $authorityId
            ));
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Failed to link authority: ' . $e->getMessage());
        }

        return $this->redirect(['module' => 'authority', 'action' => 'link', 'slug' => $this->actor->slug]);
    }

    /**
     * Handle unlinking authority
     */
    protected function handleUnlink($request)
    {
        $source = $request->getParameter('unlink_source');

        if (!$source) {
            $this->getUser()->setFlash('error', 'Missing source');

            return $this->redirect(['module' => 'authority', 'action' => 'link', 'slug' => $this->actor->slug]);
        }

        try {
            $this->service->unlinkAuthorityFromActor($this->actor->id, $source);
            $this->getUser()->setFlash('notice', sprintf(
                'Successfully unlinked %s authority',
                ahgAuthorityLinkageService::$sources[$source]['label']
            ));
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Failed to unlink authority: ' . $e->getMessage());
        }

        return $this->redirect(['module' => 'authority', 'action' => 'link', 'slug' => $this->actor->slug]);
    }

    /**
     * Handle enrichment from authority
     */
    protected function handleEnrich($request)
    {
        $source = $request->getParameter('enrich_source');
        $fields = $request->getParameter('enrich_fields', []);

        if (!$source || empty($fields)) {
            $this->getUser()->setFlash('error', 'Select fields to import');

            return $this->redirect(['module' => 'authority', 'action' => 'link', 'slug' => $this->actor->slug]);
        }

        try {
            $data = $this->service->enrichActorFromAuthority($this->actor->id, $source);

            if (!$data) {
                throw new \Exception('Could not retrieve authority data');
            }

            $updated = [];
            $culture = 'en';

            // Map and update fields
            foreach ($fields as $field) {
                switch ($field) {
                    case 'dates':
                        // Update dates of existence
                        $datesOfExistence = '';
                        if (!empty($data['birthDate'])) {
                            $datesOfExistence = $data['birthDate'];
                        }
                        if (!empty($data['deathDate'])) {
                            $datesOfExistence .= ' - ' . $data['deathDate'];
                        }
                        if ($datesOfExistence) {
                            $this->updateActorI18n($this->actor->id, 'dates_of_existence', $datesOfExistence, $culture);
                            $updated[] = 'Dates of existence';
                        }
                        break;

                    case 'places':
                        // Update places (as history note)
                        $places = [];
                        if (!empty($data['birthPlace'])) {
                            $places[] = 'Born: ' . $data['birthPlace'];
                        }
                        if (!empty($data['deathPlace'])) {
                            $places[] = 'Died: ' . $data['deathPlace'];
                        }
                        if (!empty($places)) {
                            $history = $this->actor->history ?? '';
                            $placesText = implode('; ', $places);
                            if (strpos($history, $placesText) === false) {
                                $newHistory = trim($history . "\n" . $placesText);
                                $this->updateActorI18n($this->actor->id, 'history', $newHistory, $culture);
                                $updated[] = 'Places';
                            }
                        }
                        break;

                    case 'biography':
                        if (!empty($data['biography']) || !empty($data['description'])) {
                            $bio = $data['biography'] ?? $data['description'];
                            $this->updateActorI18n($this->actor->id, 'history', $bio, $culture);
                            $updated[] = 'Biography/History';
                        }
                        break;

                    case 'nationality':
                        if (!empty($data['nationality'])) {
                            // Add as a note since AtoM actors don't have nationality field
                            $history = $this->actor->history ?? '';
                            $nationalityText = 'Nationality: ' . $data['nationality'];
                            if (strpos($history, $nationalityText) === false) {
                                $newHistory = trim($history . "\n" . $nationalityText);
                                $this->updateActorI18n($this->actor->id, 'history', $newHistory, $culture);
                                $updated[] = 'Nationality';
                            }
                        }
                        break;
                }
            }

            if (!empty($updated)) {
                // Update the updated_at timestamp on object table
                DB::table('object')
                    ->where('id', $this->actor->id)
                    ->update(['updated_at' => date('Y-m-d H:i:s')]);

                $this->getUser()->setFlash('notice', 'Updated: ' . implode(', ', $updated));
            } else {
                $this->getUser()->setFlash('notice', 'No new data to import');
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Enrichment failed: ' . $e->getMessage());
        }

        return $this->redirect(['module' => 'authority', 'action' => 'link', 'slug' => $this->actor->slug]);
    }

    /**
     * Update actor i18n field using Laravel
     */
    protected function updateActorI18n(int $actorId, string $field, string $value, string $culture = 'en'): void
    {
        // Check if i18n record exists
        $exists = DB::table('actor_i18n')
            ->where('id', $actorId)
            ->where('culture', $culture)
            ->exists();

        if ($exists) {
            // Update existing record
            DB::table('actor_i18n')
                ->where('id', $actorId)
                ->where('culture', $culture)
                ->update([$field => $value]);
        } else {
            // Insert new i18n record
            // First get the authorized_form_of_name from English if available
            $englishData = DB::table('actor_i18n')
                ->where('id', $actorId)
                ->where('culture', 'en')
                ->first();

            $insertData = [
                'id' => $actorId,
                'culture' => $culture,
                $field => $value,
            ];

            // Copy authorized_form_of_name if available
            if ($englishData && !empty($englishData->authorized_form_of_name)) {
                $insertData['authorized_form_of_name'] = $englishData->authorized_form_of_name;
            }

            DB::table('actor_i18n')->insert($insertData);
        }

        // Also update the local actor object to reflect changes
        $this->actor->$field = $value;
    }
}