<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Repositories/EcommerceRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Repositories\EcommerceRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Admin Orders Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartAdminOrdersAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        if (!$this->context->user->hasCredential("administrator")) {
            $this->forward404();
            return;
        }

        $ecommerceRepo = new EcommerceRepository();

        // Get filter parameters
        $status = $request->getParameter('status', '');
        $page = max(1, intval($request->getParameter('page', 1)));
        $perPage = 20;

        // Build query
        $query = DB::table('ahg_order')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        // Get total count
        $this->totalOrders = (clone $query)->count();

        // Paginate
        $this->orders = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->all();

        $this->currentPage = $page;
        $this->totalPages = ceil($this->totalOrders / $perPage);
        $this->status = $status;

        // Get stats
        $this->stats = $ecommerceRepo->getOrderStats();
    }
}
