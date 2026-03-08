<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Create a new ILL request.
 *
 * GET: display form.
 * POST: validate title required, create request.
 */
class illEditAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load ILLService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/ILLService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->error = null;

        // POST: save
        if ('POST' === $request->getMethod()) {
            $title = trim($request->getParameter('title', ''));

            if (empty($title)) {
                $this->error = __('Title is required.');
                return;
            }

            $formData = [
                'direction'          => $request->getParameter('direction', 'borrow'),
                'patron_id'          => $request->getParameter('patron_id') ?: null,
                'title'              => $title,
                'author'             => $request->getParameter('author'),
                'isbn'               => $request->getParameter('isbn'),
                'issn'               => $request->getParameter('issn'),
                'volume_issue'       => $request->getParameter('volume_issue'),
                'pages_needed'       => $request->getParameter('pages_needed'),
                'requesting_library' => $request->getParameter('requesting_library'),
                'lending_library'    => $request->getParameter('lending_library'),
                'needed_by_date'     => $request->getParameter('needed_by_date'),
                'notes'              => $request->getParameter('notes'),
            ];

            try {
                if (!class_exists('ILLService')) {
                    throw new \RuntimeException('ILLService not available.');
                }

                $service = ILLService::getInstance();
                $newId = $service->createRequest($formData);

                $this->getUser()->setFlash('notice', __('ILL request created successfully.'));
                $this->redirect(['module' => 'ill', 'action' => 'view', 'id' => $newId]);
            } catch (\Exception $e) {
                $this->error = __('Error creating request: %1%', ['%1%' => $e->getMessage()]);
            }
        }
    }
}
