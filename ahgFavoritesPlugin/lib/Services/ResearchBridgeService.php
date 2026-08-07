<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Research Bridge Service - Optional integration with ahgResearchPlugin
 *
 * All methods guard with isResearchEnabled() and lazy-load research services.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ResearchBridgeService
{
    /**
     * Check if ahgResearchPlugin is enabled
     */
    public function isResearchEnabled(): bool
    {
        // Asked of the configuration, not the atom_plugin table.
        //
        // That table is the AHG framework's own registry and does not exist on a
        // stock AtoM, where enablement lives in the `plugins` setting maintained
        // by AtoM's plugin admin. Querying it threw on any install without the
        // framework schema, so a check meant to detect an optional integration
        // took the page down instead of returning false.
        $configuration = \sfProjectConfiguration::getActive();

        if (!$configuration) {
            return false;
        }

        return in_array('ahgResearchPlugin', $configuration->getPlugins(), true);
    }

    /**
     * Resolve researcher ID from user ID
     */
    private function getResearcherId(int $userId): ?int
    {
        $researcher = DB::table('research_researcher')
            ->where('user_id', $userId)
            ->first();

        return $researcher ? (int) $researcher->id : null;
    }

    /**
     * Lazy-load ResearchService
     */
    private function getResearchService(): object
    {
        $this->requireResearchClass('ResearchService');

        return new \ResearchService();
    }

    /**
     * Lazy-load ProjectService
     */
    private function getProjectService(): object
    {
        $this->requireResearchClass('ProjectService');

        return new \ProjectService();
    }

    /**
     * Lazy-load BibliographyService
     */
    private function getBibliographyService(): object
    {
        $this->requireResearchClass('BibliographyService');

        return new \BibliographyService();
    }

    /**
     * Resolve object IDs from favorite IDs
     */
    private function resolveFavoriteObjectIds(int $userId, array $favoriteIds): array
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereIn('id', array_map('intval', $favoriteIds))
            ->pluck('archival_description_id')
            ->toArray();
    }

    /**
     * Send favourites to a research collection
     */
    public function sendToCollection(int $userId, array $favoriteIds, int $collectionId, bool $includeNotes = true): array
    {
        if (!$this->isResearchEnabled()) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('Research plugin not enabled.')];
        }

        $researcherId = $this->getResearcherId($userId);
        if (!$researcherId) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('You are not registered as a researcher.')];
        }

        // SECURITY: the destination collection must belong to this researcher —
        // collectionId comes from the request and was previously unchecked, so a
        // user could inject items into another researcher's collection.
        if ((int) DB::table('research_collection')->where('id', $collectionId)->value('researcher_id') !== (int) $researcherId) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('Not authorized for that collection.')];
        }

        $service = $this->getResearchService();
        $objectIds = $this->resolveFavoriteObjectIds($userId, $favoriteIds);

        $added = 0;
        $skipped = 0;

        foreach ($objectIds as $objectId) {
            $notes = null;
            if ($includeNotes) {
                $notes = DB::table('favorites')
                    ->where('user_id', $userId)
                    ->where('archival_description_id', $objectId)
                    ->value('notes');
            }

            $result = $service->addToCollection($collectionId, (int) $objectId, $notes);
            if ($result) {
                $added++;
            } else {
                $skipped++;
            }
        }

        return [
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'message' => \__('Added %1% items to collection.', ['%1%' => $added]) . ($skipped ? ' ' . \__('%1% already existed.', ['%1%' => $skipped]) : ''),
        ];
    }

    /**
     * Send favourites to a research project
     */
    public function sendToProject(int $userId, array $favoriteIds, int $projectId): array
    {
        if (!$this->isResearchEnabled()) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('Research plugin not enabled.')];
        }

        // SECURITY: require a registered researcher who OWNS the destination
        // project (projectId came from the request unchecked).
        $researcherId = $this->getResearcherId($userId);
        if (!$researcherId
            || (int) DB::table('research_project')->where('id', $projectId)->value('owner_id') !== (int) $researcherId) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('Not authorized for that project.')];
        }

        $service = $this->getProjectService();
        $objectIds = $this->resolveFavoriteObjectIds($userId, $favoriteIds);

        $added = 0;
        $skipped = 0;

        foreach ($objectIds as $objectId) {
            try {
                $service->addResource($projectId, [
                    'resource_type' => 'object',
                    'object_id' => (int) $objectId,
                ], $userId);
                $added++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return [
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'message' => \__('Added %1% items to project.', ['%1%' => $added]) . ($skipped ? ' ' . \__('%1% skipped.', ['%1%' => $skipped]) : ''),
        ];
    }

    /**
     * Send favourites to a bibliography
     */
    public function sendToBibliography(int $userId, array $favoriteIds, int $bibliographyId, string $style = 'chicago'): array
    {
        if (!$this->isResearchEnabled()) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('Research plugin not enabled.')];
        }

        // SECURITY: require a registered researcher who OWNS the destination
        // bibliography (bibliographyId came from the request unchecked).
        $researcherId = $this->getResearcherId($userId);
        if (!$researcherId
            || (int) DB::table('research_bibliography')->where('id', $bibliographyId)->value('researcher_id') !== (int) $researcherId) {
            return ['success' => false, 'added' => 0, 'skipped' => 0, 'message' => \__('Not authorized for that bibliography.')];
        }

        $service = $this->getBibliographyService();
        $objectIds = $this->resolveFavoriteObjectIds($userId, $favoriteIds);

        $added = 0;
        $skipped = 0;

        foreach ($objectIds as $objectId) {
            try {
                $service->addEntryFromObject($bibliographyId, (int) $objectId);
                $added++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        return [
            'success' => true,
            'added' => $added,
            'skipped' => $skipped,
            'message' => \__('Added %1% citations.', ['%1%' => $added]) . ($skipped ? ' ' . \__('%1% skipped.', ['%1%' => $skipped]) : ''),
        ];
    }

    /**
     * Get researcher's collections for picker modal
     */
    public function getResearcherCollections(int $userId): array
    {
        if (!$this->isResearchEnabled()) {
            return [];
        }

        $researcherId = $this->getResearcherId($userId);
        if (!$researcherId) {
            return [];
        }

        $service = $this->getResearchService();

        return $service->getCollections($researcherId);
    }

    /**
     * Get researcher's projects for picker modal
     */
    public function getResearcherProjects(int $userId): array
    {
        if (!$this->isResearchEnabled()) {
            return [];
        }

        $researcherId = $this->getResearcherId($userId);
        if (!$researcherId) {
            return [];
        }

        $service = $this->getProjectService();

        return $service->getProjects($researcherId);
    }

    /**
     * Get researcher's bibliographies for picker modal
     */
    public function getResearcherBibliographies(int $userId): array
    {
        if (!$this->isResearchEnabled()) {
            return [];
        }

        $researcherId = $this->getResearcherId($userId);
        if (!$researcherId) {
            return [];
        }

        $service = $this->getBibliographyService();

        return $service->getBibliographies($researcherId);
    }

    /**
     * Load one of ahgResearchPlugin's service classes, if that plugin is present.
     *
     * The paths here were fixed to <root>/atom-ahg-plugins/, which is the
     * development checkout layout. Installed from a bundle the plugin sits under
     * <root>/plugins/, so the require failed - and a failed require is fatal, so
     * an optional integration took the whole page down and returned an empty body.
     *
     * Both layouts are tried, and a missing file raises a catchable exception
     * rather than killing the request: ahgResearchPlugin is optional here.
     */
    private function requireResearchClass(string $class): void
    {
        $root = \sfConfig::get('sf_root_dir');

        foreach ([
            $root.'/plugins/ahgResearchPlugin/lib/Services/'.$class.'.php',
            $root.'/atom-ahg-plugins/ahgResearchPlugin/lib/Services/'.$class.'.php',
        ] as $path) {
            if (is_file($path)) {
                require_once $path;

                return;
            }
        }

        throw new \RuntimeException(sprintf('ahgResearchPlugin is not installed, so %s is unavailable.', $class));
    }

}
