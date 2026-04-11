<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Repositories/EcommerceRepository.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';

use AtomAhgPlugins\ahgCartPlugin\Repositories\EcommerceRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Admin Order Detail — view + status update.
 */
class cartAdminOrderDetailAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->forward404();
            return;
        }

        $orderId = (int) $request->getParameter('id');
        if (!$orderId) {
            $this->forward404();
            return;
        }

        $ecommerceRepo = new EcommerceRepository();

        // Handle status update POST
        if ($request->isMethod('post')) {
            $newStatus = $request->getParameter('new_status');
            $validStatuses = ['pending', 'paid', 'processing', 'completed', 'cancelled', 'refunded'];
            if ($newStatus && in_array($newStatus, $validStatuses)) {
                $updateData = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];

                if ($newStatus === 'completed') {
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                } elseif ($newStatus === 'cancelled') {
                    $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                } elseif ($newStatus === 'paid') {
                    $updateData['paid_at'] = date('Y-m-d H:i:s');
                }

                DB::table('ahg_order')->where('id', $orderId)->update($updateData);

                $adminNotes = trim($request->getParameter('admin_notes', ''));
                if (!empty($adminNotes)) {
                    DB::table('ahg_order')->where('id', $orderId)->update([
                        'notes' => DB::raw("CONCAT(IFNULL(notes,''), '\n[" . date('Y-m-d H:i') . " admin] " . addslashes($adminNotes) . "')"),
                    ]);
                }

                $this->getUser()->setFlash('notice', 'Order status updated to ' . ucfirst($newStatus));
                $this->redirect(['module' => 'cart', 'action' => 'adminOrderDetail', 'id' => $orderId]);
                return;
            }
        }

        // Load order
        $this->order = DB::table('ahg_order')->where('id', $orderId)->first();
        if (!$this->order) {
            $this->forward404();
            return;
        }

        // Load order items
        $this->items = DB::table('ahg_order_item')
            ->where('order_id', $orderId)
            ->get()
            ->all();

        // Load payments
        $this->payments = DB::table('ahg_payment')
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        // Load download tokens
        $this->downloads = DB::table('ahg_download_token')
            ->where('order_id', $orderId)
            ->get()
            ->all();

        // Status options
        $this->validStatuses = ['pending', 'paid', 'processing', 'completed', 'cancelled', 'refunded'];
    }
}
