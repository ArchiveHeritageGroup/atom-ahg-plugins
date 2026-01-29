<?php

use AtomExtensions\Services\NumberingService;

/**
 * Validate Identifier API Action
 *
 * AJAX endpoint for validating identifiers.
 *
 * GET/POST /ahgSettings/validateIdentifier
 *   - identifier: The identifier to validate
 *   - sector: Sector code (archive, library, museum, gallery, dam)
 *   - exclude_id: Optional object ID to exclude (for edits)
 *   - repository_id: Optional repository ID
 *
 * Returns JSON:
 *   {
 *     "valid": true|false,
 *     "errors": ["error message", ...],
 *     "warnings": ["warning message", ...],
 *     "expected_format": "pattern" (if format mismatch)
 *   }
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AhgSettingsValidateIdentifierAction extends sfAction
{
    public function execute($request)
    {
        // Must be authenticated
        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['error' => 'Unauthorized'], 401);
        }

        $identifier = $request->getParameter('identifier');
        $sector = $request->getParameter('sector', 'archive');
        $excludeId = $request->getParameter('exclude_id');
        $repositoryId = $request->getParameter('repository_id');

        if (empty($identifier)) {
            return $this->renderJson([
                'valid' => true,
                'errors' => [],
                'warnings' => [],
                'message' => 'Empty identifier will be auto-generated',
            ]);
        }

        try {
            $service = NumberingService::getInstance();

            $result = $service->validateReference(
                $identifier,
                $sector,
                $excludeId ? (int) $excludeId : null,
                $repositoryId ? (int) $repositoryId : null
            );

            return $this->renderJson([
                'success' => true,
                'valid' => $result['valid'],
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
                'expected_format' => $result['expected_format'] ?? null,
            ]);
        } catch (\Exception $e) {
            return $this->renderJson([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function renderJson(array $data, int $status = 200)
    {
        $this->response->setStatusCode($status);
        $this->response->setContentType('application/json');
        $this->response->setContent(json_encode($data));

        return sfView::NONE;
    }
}
