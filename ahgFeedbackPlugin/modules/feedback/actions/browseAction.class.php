<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Feedback Browse/List action using Laravel Query Builder.
 *
 * @author Johan Pieterse <johan@plainsailingisystems.co.za>
 */
class feedbackBrowseAction extends sfAction
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            QubitAcl::forwardUnauthorized();
        }

        // Initialize Laravel
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $title = $this->context->i18n->__('Feedback Management');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Set defaults
        $this->limit = $request->getParameter('limit', sfConfig::get('app_hits_per_page', 25));
        $this->filter = $request->getParameter('filter', 'all');
        $this->sort = $request->getParameter('sort', 'dateDown');
        $this->page = $request->getParameter('page', 1);

        $culture = $this->getUser()->getCulture();

        // Get counts
        $this->totalCount = DB::table('feedback_i18n')->where('culture', $culture)->count();
        $this->pendingCount = DB::table('feedback_i18n')
            ->where('culture', $culture)
            ->where('status_id', QubitTerm::PENDING_ID)
            ->count();
        $this->completedCount = DB::table('feedback_i18n')
            ->where('culture', $culture)
            ->where('status_id', QubitTerm::COMPLETED_ID)
            ->count();

        // Build query
        $query = DB::table('feedback')
            ->join('feedback_i18n', 'feedback.id', '=', 'feedback_i18n.id')
            ->where('feedback_i18n.culture', $culture)
            ->select(
                'feedback.id',
                'feedback.feed_name',
                'feedback.feed_surname',
                'feedback.feed_phone',
                'feedback.feed_email',
                'feedback.feed_relationship',
                'feedback.feed_type_id',
                'feedback_i18n.name',
                'feedback_i18n.remarks',
                'feedback_i18n.object_id',
                'feedback_i18n.status_id',
                'feedback_i18n.created_at',
                'feedback_i18n.completed_at'
            );

        // Apply filter
        if ($this->filter === 'pending') {
            $query->where('feedback_i18n.status_id', QubitTerm::PENDING_ID);
        } elseif ($this->filter === 'completed') {
            $query->where('feedback_i18n.status_id', QubitTerm::COMPLETED_ID);
        }

        // Apply sorting
        switch ($this->sort) {
            case 'nameUp':
                $query->orderBy('feedback_i18n.name', 'asc');
                break;
            case 'nameDown':
                $query->orderBy('feedback_i18n.name', 'desc');
                break;
            case 'dateUp':
                $query->orderBy('feedback_i18n.created_at', 'asc');
                break;
            case 'dateDown':
            default:
                $query->orderBy('feedback_i18n.created_at', 'desc');
                break;
        }

        // Get total for pagination
        $total = $query->count();

        // Apply pagination
        $offset = ($this->page - 1) * $this->limit;
        $this->feedbackItems = $query->skip($offset)->take($this->limit)->get();

        // Simple pagination info
        $this->totalPages = ceil($total / $this->limit);
        $this->currentPage = $this->page;
        $this->hasMorePages = $this->page < $this->totalPages;
        $this->totalResults = $total;
    }
}
