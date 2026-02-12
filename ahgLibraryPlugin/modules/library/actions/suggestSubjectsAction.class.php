<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * AJAX endpoint for AI-powered subject suggestions.
 *
 * Takes title, description, object_id, and existing subjects as input.
 * Returns ranked subject suggestions based on text matching, NER entities,
 * and usage patterns from the subject authority system.
 */
class librarySuggestSubjectsAction extends AhgController
{
    public function execute($request)
    {
        // Set JSON response
        $this->getResponse()->setContentType('application/json');

        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson([
                'success' => false,
                'error' => 'Authentication required',
            ]);
        }

        try {
            // Get input parameters
            $title = $request->getParameter('title', '');
            $description = $request->getParameter('description', '');
            $objectId = $request->getParameter('object_id');
            $existingSubjects = $request->getParameter('existing_subjects', []);

            // Ensure existing_subjects is an array
            if (is_string($existingSubjects)) {
                $existingSubjects = json_decode($existingSubjects, true) ?: [];
            }

            // Load the suggestion service
            require_once sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/SubjectSuggestionService.php';
            require_once sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Repository/SubjectAuthorityRepository.php';

            $service = new \ahgLibraryPlugin\Service\SubjectSuggestionService();

            // Get NER entities if we have an object_id
            $nerEntities = [];
            if ($objectId) {
                $nerEntities = $this->fetchNerEntities((int) $objectId);
            }

            // Get suggestions
            $suggestions = $service->suggest([
                'title' => $title,
                'description' => $description,
                'ner_entities' => $nerEntities,
                'existing_subjects' => $existingSubjects,
            ]);

            return $this->renderJson([
                'success' => true,
                'suggestions' => $suggestions,
                'meta' => [
                    'ner_entity_count' => count($nerEntities),
                    'existing_count' => count($existingSubjects),
                ],
            ]);

        } catch (\Exception $e) {
            error_log('suggestSubjects error: ' . $e->getMessage());

            return $this->renderJson([
                'success' => false,
                'error' => 'Failed to generate suggestions: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch NER entities for an information object.
     *
     * @param int $objectId Information object ID
     * @return array NER entities
     */
    protected function fetchNerEntities(int $objectId): array
    {
        try {
            $db = \Illuminate\Database\Capsule\Manager::connection();

            // Check if table exists
            $tableExists = $db->select("SHOW TABLES LIKE 'ahg_ner_entity'");
            if (empty($tableExists)) {
                return [];
            }

            return $db->table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->select('entity_type as type', 'entity_value as value')
                ->get()
                ->map(fn($row) => ['type' => $row->type, 'value' => $row->value])
                ->toArray();

        } catch (\Exception $e) {
            error_log('fetchNerEntities error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Render JSON response.
     *
     * @param array $data Data to encode
     * @return string JSON response
     */
    protected function renderJson(array $data): string
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return sfView::NONE;
    }
}
