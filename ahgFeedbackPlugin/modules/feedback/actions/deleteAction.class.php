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

        if ($request->isMethod('delete') || $request->getParameter('confirm')) {
            $this->resource->delete();
            $this->getUser()->setFlash('notice', $this->context->i18n->__('Feedback deleted.'));
            $this->redirect(['module' => 'feedback', 'action' => 'browse']);
        }
    }
}
