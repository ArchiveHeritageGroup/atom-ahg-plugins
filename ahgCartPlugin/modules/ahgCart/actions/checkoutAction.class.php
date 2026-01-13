<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use Illuminate\Database\Capsule\Manager as DB;

class ahgCartCheckoutAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $userId = $this->context->user->getAttribute('user_id');
        $service = new CartService();

        $this->items = $service->getUserCart($userId);
        $this->count = count($this->items);

        if ($this->count === 0) {
            $this->context->user->setFlash('error', 'Your cart is empty.');
            $this->redirect(['module' => 'ahgCart', 'action' => 'browse']);
            return;
        }

        $this->user = DB::table('user')->where('id', $userId)->first();

        if ($request->isMethod('post')) {
            $rtp_name = $request->getParameter('rtp_name');
            $rtp_surname = $request->getParameter('rtp_surname');
            $rtp_phone = $request->getParameter('rtp_phone');
            $rtp_email = $request->getParameter('rtp_email');
            $rtp_institution = $request->getParameter('rtp_institution');
            $rtp_planned_use = $request->getParameter('rtp_planned_use');
            $rtp_motivation = $request->getParameter('rtp_motivation');
            $rtp_need_image_by = $request->getParameter('rtp_need_image_by');

            if (empty($rtp_name) || empty($rtp_surname) || empty($rtp_email) || empty($rtp_phone) || empty($rtp_institution) || empty($rtp_planned_use)) {
                $this->context->user->setFlash('error', 'Please fill in all required fields.');
                return sfView::SUCCESS;
            }

            $createdIds = [];

            foreach ($this->items as $item) {
                $objectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitRequestToPublish',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('request_to_publish')->insert([
                    'id' => $objectId,
                    'parent_id' => null,
                    'rtp_type_id' => null,
                    'lft' => 0,
                    'rgt' => 1,
                    'source_culture' => 'en',
                ]);

                DB::table('request_to_publish_i18n')->insert([
                    'id' => $objectId,
                    'unique_identifier' => (string) $userId,
                    'object_id' => (string) $item->archival_description_id,
                    'rtp_name' => $rtp_name,
                    'rtp_surname' => $rtp_surname,
                    'rtp_phone' => $rtp_phone,
                    'rtp_email' => $rtp_email,
                    'rtp_institution' => $rtp_institution,
                    'rtp_motivation' => $rtp_motivation,
                    'rtp_planned_use' => $rtp_planned_use,
                    'rtp_need_image_by' => $rtp_need_image_by ? $rtp_need_image_by . ' 00:00:00' : null,
                    'status_id' => 220,
                    'created_at' => date('Y-m-d H:i:s'),
                    'culture' => 'en',
                ]);

                $createdIds[] = $objectId;
            }

            $service->clearAll($userId);

            $this->context->user->setFlash('notice', 'Your request to publish has been submitted for ' . count($createdIds) . ' item(s).');
            $this->redirect(['module' => 'requestToPublish', 'action' => 'browse']);
            return;
        }
    }
}
