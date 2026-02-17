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
        return DB::table('atom_plugin')
            ->where('name', 'ahgResearchPlugin')
            ->where('is_enabled', 1)
            ->exists();
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
        $path = \sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgResearchPlugin/lib/Services/ResearchService.php';
        require_once $path;

        return new \ResearchService();
    }

    /**
     * Lazy-load ProjectService
     */
    private function getProjectService(): object
    {
        $path = \sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgResearchPlugin/lib/Services/ProjectService.php';
        require_once $path;

        return new \ProjectService();
    }

    /**
     * Lazy-load BibliographyService
     */
    private function getBibliographyService(): object
    {
        $path = \sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgResearchPlugin/lib/Services/BibliographyService.php';
        require_once $path;

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
}
