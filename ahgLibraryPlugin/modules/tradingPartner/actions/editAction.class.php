<?php

use AtomFramework\Http\Controllers\AhgController;

class tradingPartnerEditAction extends AhgController
{
    /** endpoint_config sub-keys captured from the form as cfg_<key>. */
    private const CFG_KEYS = [
        'host', 'port', 'username', 'password', 'path', 'private_key',
        'as2_url', 'as2_receiver_id', 'url',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_from', 'smtp_to', 'contact_email',
    ];

    private const EDI_TYPES        = ['EANCOM', 'X12', 'UN/EDIFACT', 'CUSTOM'];
    private const MESSAGE_PROFILES = ['EANCOM_S93', 'EANCOM_S94', 'X12_850', 'CUSTOM'];
    private const ENDPOINT_TYPES   = ['SFTP', 'AS2', 'HTTP_HTTPS', 'EMAIL', 'MANUAL'];

    public function execute($request)
    {
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $id = (int) $request->getParameter('id');

        $this->partner = null;
        if ($id) {
            $this->partner = $db->table('library_trading_partner')->where('id', $id)->first();
            if (!$this->partner) {
                $this->forward404();
            }
            // Decode endpoint_config for the form.
            $this->partner->endpoint_config = $this->partner->endpoint_config
                ? (json_decode($this->partner->endpoint_config, true) ?: [])
                : [];
        }

        // No vendor table on this instance — keep the field optional.
        $this->vendors = $db->getSchemaBuilder()->hasTable('library_vendors')
            ? $db->table('library_vendors')->where('is_active', 1)->orderBy('name')->get(['id', 'name', 'code'])->all()
            : [];

        if ($request->isMethod('post')) {
            $code = trim((string) $request->getParameter('edi_partner_code', ''));
            $ediType        = $request->getParameter('edi_type', 'EANCOM');
            $messageProfile = $request->getParameter('message_profile', 'EANCOM_S93');
            $endpointType   = $request->getParameter('endpoint_type', 'SFTP');

            // Validation
            $errors = [];
            if ($code === '') {
                $errors[] = __('EDI partner code is required.');
            }
            if (!in_array($ediType, self::EDI_TYPES, true)) {
                $errors[] = __('Invalid EDI type.');
            }
            if (!in_array($messageProfile, self::MESSAGE_PROFILES, true)) {
                $errors[] = __('Invalid message profile.');
            }
            if (!in_array($endpointType, self::ENDPOINT_TYPES, true)) {
                $errors[] = __('Invalid endpoint type.');
            }
            if ($code !== '') {
                $dupQuery = $db->table('library_trading_partner')->where('edi_partner_code', $code);
                if ($id) {
                    $dupQuery->where('id', '!=', $id);
                }
                if ($dupQuery->exists()) {
                    $errors[] = __('This EDI partner code is already registered.');
                }
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));

                return sfView::SUCCESS;
            }

            // Build endpoint_config from cfg_<key> inputs (drop empties).
            $cfg = [];
            foreach (self::CFG_KEYS as $k) {
                $v = $request->getParameter('cfg_' . $k);
                if ($v !== null && $v !== '') {
                    $cfg[$k] = $v;
                }
            }

            $now  = date('Y-m-d H:i:s');
            $data = [
                'vendor_id'                => $request->getParameter('vendor_id') ?: null,
                'edi_partner_code'         => $code,
                'edi_type'                 => $ediType,
                'message_profile'          => $messageProfile,
                'endpoint_type'            => $endpointType,
                'endpoint_config'          => json_encode($cfg, JSON_UNESCAPED_SLASHES),
                'outbound_directory'       => trim((string) $request->getParameter('outbound_directory', '/outbox/')) ?: '/outbox/',
                'inbound_directory'        => trim((string) $request->getParameter('inbound_directory', '/inbox/')) ?: '/inbox/',
                'acknowledgement_required' => $request->getParameter('acknowledgement_required') ? 1 : 0,
                'test_mode'                => $request->getParameter('test_mode') ? 1 : 0,
                'is_active'                => $request->getParameter('is_active') ? 1 : 0,
                'notes'                    => $request->getParameter('notes') ?: null,
                'updated_at'               => $now,
            ];

            if ($id) {
                $db->table('library_trading_partner')->where('id', $id)->update($data);
                $this->getUser()->setFlash('notice', __('Trading partner updated.'));
            } else {
                $data['created_at'] = $now;
                $db->table('library_trading_partner')->insert($data);
                $this->getUser()->setFlash('notice', __('Trading partner created.'));
            }

            $this->redirect(['module' => 'tradingPartner', 'action' => 'index']);
        }
    }
}
