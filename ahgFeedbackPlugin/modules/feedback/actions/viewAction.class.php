<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * View Feedback action using Laravel Query Builder.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class feedbackViewAction extends AhgController
{
    public function execute($request)
    {
        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $id = $request->getParameter('id');
        $culture = $this->culture();

        // Load feedback from database
        $this->resource = DB::table('feedback')
            ->join('feedback_i18n', 'feedback.id', '=', 'feedback_i18n.id')
            ->where('feedback.id', $id)
            ->where('feedback_i18n.culture', $culture)
            ->select(
                'feedback.*',
                'feedback_i18n.name',
                'feedback_i18n.remarks',
                'feedback_i18n.object_id',
                'feedback_i18n.status',
                'feedback_i18n.created_at',
                'feedback_i18n.completed_at'
            )
            ->first();

        if (!$this->resource) {
            $this->forward404();
        }

        // Get linked information object if exists
        $this->informationObject = null;
        if ($this->resource->object_id) {
            $this->informationObject = QubitInformationObject::getById($this->resource->object_id);
        }

        // Feedback type labels
        $this->feedbackTypes = [
            0 => $this->context->i18n->__('General'),
            1 => $this->context->i18n->__('Error Report'),
            2 => $this->context->i18n->__('Suggestion'),
            3 => $this->context->i18n->__('Correction'),
            4 => $this->context->i18n->__('Need Assistance'),
        ];
    }
}
