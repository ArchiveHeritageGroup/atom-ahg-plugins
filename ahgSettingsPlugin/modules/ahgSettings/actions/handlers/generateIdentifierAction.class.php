<?php

use AtomExtensions\Services\NumberingService;
use AtomFramework\Http\Controllers\AhgController;

/**
 * Generate Identifier API Action
 *
 * AJAX endpoint for generating/previewing identifiers.
 *
 * GET /ahgSettings/generateIdentifier?sector=museum&preview=1
 * POST /ahgSettings/generateIdentifier (sector=museum) - generates and reserves
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AhgSettingsGenerateIdentifierAction extends AhgController
{
    public function execute($request)
    {
        // Must be authenticated
        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['error' => 'Unauthorized'], 401);
        }

        $sector = $request->getParameter('sector');
        $repositoryId = $request->getParameter('repository_id');
        $preview = $request->getParameter('preview', false);

        if (!$sector) {
            return $this->renderJson(['error' => 'Sector is required'], 400);
        }

        // Build context from request
        $context = [];
        if ($request->getParameter('repo')) {
            $context['repo'] = $request->getParameter('repo');
        }
        if ($request->getParameter('fonds')) {
            $context['fonds'] = $request->getParameter('fonds');
        }
        if ($request->getParameter('series')) {
            $context['series'] = $request->getParameter('series');
        }
        if ($request->getParameter('dept')) {
            $context['dept'] = $request->getParameter('dept');
        }
        if ($request->getParameter('type')) {
            $context['type'] = $request->getParameter('type');
        }
        if ($request->getParameter('project')) {
            $context['project'] = $request->getParameter('project');
        }

        try {
            $service = NumberingService::getInstance();

            if ($preview || $request->isMethod('get')) {
                // Preview only - don't consume sequence
                $info = $service->getNumberingInfo($sector, $context, $repositoryId ? (int) $repositoryId : null);

                return $this->renderJson([
                    'success' => true,
                    'preview' => true,
                    'identifier' => $info['next_reference'],
                    'scheme' => $info['scheme_name'],
                    'pattern' => $info['pattern'],
                    'auto_generate' => $info['auto_generate'],
                    'allow_override' => $info['allow_override'],
                ]);
            }

            // Generate and reserve
            $identifier = $service->getNextReference($sector, $context, $repositoryId ? (int) $repositoryId : null);

            return $this->renderJson([
                'success' => true,
                'preview' => false,
                'identifier' => $identifier,
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
