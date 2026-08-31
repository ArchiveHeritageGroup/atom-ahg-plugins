<?php

use AtomFramework\Http\Controllers\AhgController;
class identifierApiActions extends AhgController
{
    public function executeLookup($request)
    {
        $type = $request->getParameter('type', 'isbn');
        $value = $request->getParameter('value');

        if (empty($value)) {
            return $this->jsonError('Value is required', 400);
        }

        try {
            require_once $this->config('sf_root_dir')
                . '/atom-framework/src/Services/IsbnLookupService.php';
            require_once $this->config('sf_root_dir')
                . '/atom-framework/src/Services/GlamIdentifierService.php';

            $lookupService = new \AtomFramework\Services\IsbnLookupService();

            $result = match ($type) {
                'isbn' => $lookupService->lookupByIsbn($value),
                'issn' => $lookupService->lookupByIssn($value),
                default => throw new \InvalidArgumentException('Unsupported type: ' . $type),
            };

            if (!$result) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No results found',
                ]);
            }

            return $this->jsonResponse([
                'success' => true,
                'raw' => $result,
                'mapped' => $lookupService->mapToLibraryFields($result),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 400);
        }
    }

    public function executeValidate($request)
    {
        $type = $request->getParameter('type');
        $value = $request->getParameter('value');

        if (empty($type) || empty($value)) {
            return $this->jsonError('Type and value required', 400);
        }

        try {
            require_once $this->config('sf_root_dir')
                . '/atom-framework/src/Services/GlamIdentifierService.php';

            $service = new \AtomFramework\Services\GlamIdentifierService();

            return $this->jsonResponse([
                'success' => true,
                'validation' => $service->validateIdentifier($value, $type),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }

    public function executeDetect($request)
    {
        $value = $request->getParameter('value');

        if (empty($value)) {
            return $this->jsonError('Value required', 400);
        }

        try {
            require_once $this->config('sf_root_dir')
                . '/atom-framework/src/Services/GlamIdentifierService.php';

            $service = new \AtomFramework\Services\GlamIdentifierService();
            $type = $service->detectIdentifierType($value);
            $validation = $type ? $service->validateIdentifier($value, $type) : null;

            return $this->jsonResponse([
                'success' => true,
                'detected_type' => $type,
                'validation' => $validation,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }

    public function executeBarcode($request)
    {
        $objectId = (int) $request->getParameter('objectId');
        $type = $request->getParameter('type');

        if ($objectId < 1) {
            return $this->jsonError('Invalid object ID', 400);
        }

        $object = QubitInformationObject::getById($objectId);
        if (!$object || !\AtomExtensions\Services\AclService::check($object, 'read')) {
            return $this->jsonError('Object not found or access denied', 404);
        }

        try {
            require_once $this->config('sf_root_dir')
                . '/atom-framework/src/Services/GlamIdentifierService.php';

            $service = new \AtomFramework\Services\GlamIdentifierService();
            $identifier = $service->getBestBarcodeIdentifier($objectId);

            if (!$identifier) {
                return $this->jsonError('No valid identifier found', 400);
            }

            $slug = QubitSlug::getByObjectId($objectId);
            $qrUrl = 'https://psis.theahg.co.za/' . ($slug ? $slug->slug : '');

            return $this->jsonResponse([
                'success' => true,
                'barcode' => [
                    'object_id' => $objectId,
                    'sector' => $identifier['sector'],
                    'identifier_type' => $identifier['type'],
                    'identifier_value' => $identifier['value'],
                    'barcodes' => [
                        'linear' => [
                            'svg' => $this->generateSimpleSvg($identifier['value']),
                        ],
                        'qr' => [
                            // Real SVG, generated locally. This returned an <img>
                            // pointing at chart.googleapis.com, which now 404s -
                            // so an API consumer received markup for an image
                            // that could never load, under the key 'svg'.
                            'svg' => $this->qrSvg($qrUrl),
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }

    public function executeTypes($request)
    {
        $objectId = (int) $request->getParameter('objectId');

        if ($objectId < 1) {
            return $this->jsonError('Invalid object ID', 400);
        }

        try {
            require_once $this->config('sf_root_dir')
                . '/atom-framework/src/Services/GlamIdentifierService.php';

            $service = new \AtomFramework\Services\GlamIdentifierService();
            $sector = $service->detectObjectSector($objectId);
            $types = $service->getIdentifierTypesForSector($sector);

            return $this->jsonResponse([
                'success' => true,
                'object_id' => $objectId,
                'sector' => $sector,
                'types' => $types,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * QR as inline SVG, generated on this server.
     *
     * Returns an empty string rather than throwing: an identifier response is
     * still useful without its QR, and a barcode endpoint should not 500
     * because a URL was too long to encode.
     */
    protected function qrSvg(string $url): string
    {
        try {
            require_once \sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/QrCodeService.php';
            $qr = new \AtomExtensions\Services\QrCodeService();

            return $qr->svg($url, 3, 4);
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('identifierApi: QR generation failed - ' . $e->getMessage());
            }

            return '';
        }
    }

    private function generateSimpleSvg(string $data): string
    {
        $width = 200;
        $height = 50;
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d">',
            $width,
            $height + 20
        );
        $svg .= '<rect width="100%" height="100%" fill="white"/>';

        $x = 10;
        foreach (str_split($data) as $char) {
            $barWidth = (ord($char) % 3) + 1;
            $svg .= sprintf(
                '<rect x="%d" y="0" width="%d" height="%d" fill="black"/>',
                $x,
                $barWidth,
                $height
            );
            $x += $barWidth + 2;
        }

        $svg .= sprintf(
            '<text x="%d" y="%d" text-anchor="middle" font-family="monospace" font-size="10">%s</text>',
            $width / 2,
            $height + 15,
            htmlspecialchars($data)
        );
        $svg .= '</svg>';

        return $svg;
    }

    private function jsonResponse(array $data): string
    {
        $this->response->setHttpHeader('Content-Type', 'application/json');

        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function jsonError(string $message, int $code = 400): string
    {
        $this->response->setStatusCode($code);

        return $this->jsonResponse([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ]);
    }
}
