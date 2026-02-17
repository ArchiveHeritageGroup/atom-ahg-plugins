<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Request to Publish Submit Action
 *
 * Public-facing form to submit a publication request.
 * Called from information object view page.
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class requestToPublishSubmitAction extends AhgController
{
    public function execute($request)
    {
        // Initialize service
        require_once $this->config('sf_plugins_dir') . '/ahgRequestToPublishPlugin/lib/Services/RequestToPublishService.php';
        $service = new \ahgRequestToPublishPlugin\Services\RequestToPublishService();

        // Get object ID from route
        $objectId = (int) $request->getParameter('id');
        if (!$objectId) {
            // Try to get from slug
            $slug = $request->getParameter('slug');
            if ($slug) {
                $objectId = \Illuminate\Database\Capsule\Manager::table('slug')
                    ->where('slug', $slug)
                    ->value('object_id');
            }
        }

        if (!$objectId) {
            $this->forward404();
        }

        // Get information object details
        $this->informationObject = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', $objectId)
            ->select('io.*', 'ioi.title', 's.slug')
            ->first();

        if (!$this->informationObject) {
            $this->forward404();
        }

        // Handle form submission
        if ($request->isMethod('post')) {
            try {
                $data = [
                    'object_id' => $objectId,
                    'rtp_name' => $request->getParameter('rtp_name'),
                    'rtp_surname' => $request->getParameter('rtp_surname'),
                    'rtp_email' => $request->getParameter('rtp_email'),
                    'rtp_phone' => $request->getParameter('rtp_phone'),
                    'rtp_institution' => $request->getParameter('rtp_institution'),
                    'rtp_planned_use' => $request->getParameter('rtp_planned_use'),
                    'rtp_motivation' => $request->getParameter('rtp_motivation'),
                    'rtp_need_image_by' => $request->getParameter('rtp_need_image_by') ?: null
                ];

                $service->submitRequest($data);
                $this->getUser()->setFlash('notice', 'Your request has been submitted successfully. We will contact you soon.');
                
                // Redirect back to information object
                $this->redirect([
                    'module' => 'informationobject',
                    'slug' => $this->informationObject->slug
                ]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        // Pre-fill form with logged-in user details if available
        $this->userName = '';
        $this->userSurname = '';
        $this->userEmail = '';

        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            if ($userId) {
                $user = \Illuminate\Database\Capsule\Manager::table('user')
                    ->where('id', $userId)
                    ->first();
                if ($user) {
                    $this->userEmail = $user->email ?? '';
                }

                // Try to get actor name
                $actor = \Illuminate\Database\Capsule\Manager::table('actor_i18n')
                    ->where('id', $userId)
                    ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->first();
                if ($actor && isset($actor->authorized_form_of_name)) {
                    $parts = explode(' ', $actor->authorized_form_of_name, 2);
                    $this->userName = $parts[0] ?? '';
                    $this->userSurname = $parts[1] ?? '';
                }
            }
        }
    }
}
