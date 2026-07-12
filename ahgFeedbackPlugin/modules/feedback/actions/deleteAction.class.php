<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Delete Feedback action.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class feedbackDeleteAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        $this->resource = QubitFeedback::getById($request->getParameter('id'));

        if (!isset($this->resource)) {
            $this->forward404();
        }

        // SECURITY: require a POST/DELETE — the previous `|| getParameter('confirm')`
        // allowed a GET (e.g. a CSRF <img> tag) to delete feedback.
        if ($request->isMethod('delete') || ($request->isMethod('post') && $request->getParameter('confirm'))) {
            // Dual-mode delete (WP18)
            if (class_exists('\\AtomFramework\\Services\\Delete\\EntityDeleteService')) {
                \AtomFramework\Services\Delete\EntityDeleteService::delete($this->resource->id);
            } else {
                $this->resource->delete();
            }
            $this->getUser()->setFlash('notice', $this->context->i18n->__('Feedback deleted.'));
            $this->redirect(['module' => 'feedback', 'action' => 'browse']);
        }
    }
}
