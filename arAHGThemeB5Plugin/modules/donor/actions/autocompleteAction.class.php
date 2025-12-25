<?php

/**
 * Donor autocomplete action.
 *
 * Pure Laravel Query Builder via DonorRepository.
 */
class DonorAutocompleteAction extends sfAction
{
    public function execute($request)
    {
        // Initialize framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Set JSON response
        $this->getResponse()->setContentType('application/json');

        // Get search query
        $query = $request->getParameter('query', '');
        $limit = (int) $request->getParameter('limit', 10);

        if (strlen($query) < 2) {
            return $this->renderText(json_encode([]));
        }

        // Get current culture
        $culture = $this->context->user->getCulture();

        // Search via repository
        $repository = new \AtomExtensions\Repositories\DonorRepository($culture);
        $results = $repository->autocomplete($query, $limit);

        // Format results
        $data = [];
        foreach ($results as $donor) {
            $data[] = [
                'id' => $donor->id,
                'name' => $donor->authorizedFormOfName,
                'slug' => $donor->slug,
                'url' => $this->context->routing->generate(null, [
                    'module' => 'donor',
                    'action' => 'index',
                    'slug' => $donor->slug,
                ]),
            ];
        }

        return $this->renderText(json_encode($data));
    }
}
