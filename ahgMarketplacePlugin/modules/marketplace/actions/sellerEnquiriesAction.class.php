<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceSellerEnquiriesAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $sellerService = new SellerService();
        $this->seller = $sellerService->getSellerByUserId($userId);

        if (!$this->seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        $settingsRepo = new SettingsRepository();

        $this->statusFilter = $request->getParameter('status', '');
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($this->page - 1) * $limit;

        $statusParam = !empty($this->statusFilter) ? $this->statusFilter : null;
        $result = $settingsRepo->getSellerEnquiries($this->seller->id, $statusParam, $limit, $offset);

        $this->enquiries = $result['items'];
        $this->total = $result['total'];

        // Handle POST: reply to enquiry
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');

            if ($formAction === 'reply') {
                $enquiryId = (int) $request->getParameter('enquiry_id');
                $reply = trim($request->getParameter('reply_message', ''));

                if (!$enquiryId || empty($reply)) {
                    $this->getUser()->setFlash('error', 'Reply message is required.');
                } else {
                    $enquiry = $settingsRepo->getEnquiry($enquiryId);

                    if (!$enquiry) {
                        $this->getUser()->setFlash('error', 'Enquiry not found.');
                    } else {
                        $settingsRepo->updateEnquiry($enquiryId, [
                            'seller_reply' => $reply,
                            'status' => 'replied',
                            'replied_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                        $this->getUser()->setFlash('notice', 'Reply sent successfully.');

                        // Reload enquiries
                        $result = $settingsRepo->getSellerEnquiries($this->seller->id, $statusParam, $limit, $offset);
                        $this->enquiries = $result['items'];
                        $this->total = $result['total'];
                    }
                }
            }
        }
    }
}
