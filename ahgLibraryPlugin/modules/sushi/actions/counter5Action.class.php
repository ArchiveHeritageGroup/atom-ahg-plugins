<?php

declare(strict_types=1);

/**
 * sushi counter5Action
 *
 * SUSHI 5.0 CORS-preflight and main harvest endpoint.
 * Handle: POST /sushi/counter5
 *
 * @package ahgLibraryPlugin
 * @subpackage sushi
 */

namespace AtomExtensions\Modules\Sushi;

class counter5Action extends \AhgController
{
    protected SushiService $svc;

    public function initialize(): void
    {
        parent::initialize();
        $this->svc = new \AtomExtensions\Services\SushiService();
    }

    public function execute($request)
    {
        $this->response->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHttpHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requestor-Id, X-Customer-Id, X-API-Key');
        $this->response->setHttpHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');

        // Handle preflight
        if ($request->getMethod() === 'OPTIONS') {
            $this->response->setStatusCode(204);
            return \sfView::NONE;
        }

        $format = $request->getParameter('format', 'json');
        if ($format === 'xml') {
            // Accept ?format=xml OR Accept header
            $accept = $request->getHttpHeader('Accept', '');
            if (stripos($accept, 'xml') !== false) {
                $format = 'xml';
            }
        }

        // Build header map (normalise case-insensitive keys)
        $rawHeaders = [
            'X-Requestor-Id'  => $request->getHttpHeader('X-Requestor-Id'),
            'X-Customer-Id'  => $request->getHttpHeader('X-Customer-Id'),
            'X-API-Key'       => $request->getHttpHeader('X-API-Key'),
            'Requestor-Id'    => $request->getHttpHeader('Requestor-Id'),
            'Customer-Id'     => $request->getHttpHeader('Customer-Id'),
            'API-Key'         => $request->getHttpHeader('API-Key'),
        ];
        $headers = array_filter($rawHeaders, fn($v) => $v !== null);
        if (empty($headers)) {
            // Try POST body params for non-SOAP clients
            $headers = [
                'X-Requestor-Id' => $request->getParameter('Requestor_Id'),
                'X-Customer-Id' => $request->getParameter('Customer_Id'),
                'X-API-Key'     => $request->getParameter('API_Key'),
            ];
        }

        // Validate SUSHI headers
        $validation = $this->svc->validateRequest($headers);
        if (!$validation['valid']) {
            $resp = $this->svc->sushiError($validation['code'], $validation['message'], $format);
            $this->response->setStatusCode($resp['status']);
            $this->response->setContentType($resp['content_type']);
            echo $resp['body'];
            return \sfView::NONE;
        }

        // Read report parameters
        $reportType = $request->getParameter('report_type',
                         $request->getParameter('report', 'TR_J1'));
        $begin      = $request->getParameter('begin_date',
                         $request->getParameter('begin', date('Y-01-01')));
        $end        = $request->getParameter('end_date',
                         $request->getParameter('end', date('Y-m-d')));

        if (!$begin || !$end) {
            // Default: current calendar year
            $begin = date('Y-01-01');
            $end   = date('Y-m-d');
        }

        $resp = $this->svc->harvest($reportType, $begin, $end, $format);

        $this->response->setStatusCode($resp['status']);
        $this->response->setContentType($resp['content_type']);
        $this->response->setHttpHeader('X-Counter-Version', '5.0');
        $this->response->setHttpHeader('File-Format', $format === 'xml' ? 'tsv' : 'json');

        echo $resp['body'];
        return \sfView::NONE;
    }
}
