<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

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

        // Read through the query builder like the rest of this plugin. The previous
        // QubitFeedback::getById() referenced a Propel model that is defined nowhere
        // in AtoM or in this plugin, so every request to this action died with
        // 'Class "QubitFeedback" not found' and rendered a blank page. Delete has
        // never worked.
        $id = (int) $request->getParameter('id');
        $culture = $this->getUser()->getCulture();

        $this->resource = DB::table('feedback as f')
            ->leftJoin('feedback_i18n as fi', function ($join) use ($culture) {
                $join->on('fi.id', '=', 'f.id')->where('fi.culture', '=', $culture);
            })
            ->where('f.id', $id)
            ->select('f.*', 'fi.name', 'fi.remarks', 'fi.status')
            ->first();

        if (!$this->resource) {
            $this->forward404();

            return;
        }

        // SECURITY: require a POST/DELETE - the previous `|| getParameter('confirm')`
        // allowed a GET (e.g. a CSRF <img> tag) to delete feedback.
        if (!$request->isMethod('delete') && !($request->isMethod('post') && $request->getParameter('confirm'))) {
            return;   // GET: render the confirmation template
        }

        $right = (int) $this->resource->rgt;
        $width = $right - (int) $this->resource->lft + 1;

        // feedback.id and feedback_i18n.id both cascade from object, so removing the
        // base row takes the whole record with it.
        DB::table('object')->where('id', $id)->delete();

        // feedback is a nested set. Close the gap the row leaves behind, or the next
        // insert derives its position from a max(rgt) that no longer describes the
        // tree.
        if ($width > 0) {
            DB::table('feedback')->where('lft', '>', $right)->update(['lft' => DB::raw("lft - {$width}")]);
            DB::table('feedback')->where('rgt', '>', $right)->update(['rgt' => DB::raw("rgt - {$width}")]);
        }

        $this->getUser()->setFlash('notice', $this->context->i18n->__('Feedback deleted.'));
        $this->redirect(['module' => 'feedback', 'action' => 'browse']);

        // AhgController::redirect() does not throw, so execution continues past it.
        return;
    }
}
