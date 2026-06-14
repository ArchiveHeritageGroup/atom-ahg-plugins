<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Export library items as MARC — binary MARC21/ISO-2709 (.mrc) or MARCXML.
 *
 * GET /library/marc-export?format=marc21|marcxml[&ids=1,2,3]
 * Authenticated (catalogue export is a staff function).
 */
class libraryMarcExportAction extends AhgController
{
    public function execute($request)
    {
        $this->requireAuth();

        $servicePath = \sfConfig::get('sf_plugins_dir') . '/ahgLibraryPlugin/lib/Service/MarcService.php';
        if (!is_file($servicePath)) {
            $this->forward404('MARC service unavailable');
        }
        require_once $servicePath;

        $svc = new \MarcService();

        $idsRaw = trim((string) $request->getParameter('ids', ''));
        $ids = $idsRaw !== '' ? array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $idsRaw)))) : [];

        $format = strtolower((string) $request->getParameter('format', 'marc21'));
        $stamp = date('Ymd-His');

        if ('marcxml' === $format || 'xml' === $format) {
            $body = $svc->exportMarcXml($ids);
            $this->getResponse()->setContentType('application/xml; charset=utf-8');
            $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="library-' . $stamp . '.marcxml"');

            return $this->renderText($body);
        }

        // Default: binary MARC21 (ISO 2709)
        $body = $svc->exportMarc21($ids);
        $this->getResponse()->setContentType('application/marc');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="library-' . $stamp . '.mrc"');

        return $this->renderText($body);
    }
}
