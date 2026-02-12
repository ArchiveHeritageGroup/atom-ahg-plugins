<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Base Report Action - Common functionality for all report actions.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
abstract class BaseReportAction extends AhgController
{
    /**
     * Check if user has report access.
     */
    protected function hasReportAccess(): bool
    {
        return AhgCentralHelpers::hasReportAccess();
    }

    /**
     * Get logger instance for reports.
     */
    protected function getReportLogger(): \Psr\Log\LoggerInterface
    {
        static $logger = null;
        if ($logger === null) {
            $logger = AhgCentralHelpers::createLogger('reports', 'atom-reports.log');
        }
        return $logger;
    }

    /**
     * Forward to unauthorized page.
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    /**
     * Set error flash message and log.
     */
    protected function handleError(Exception $e, string $context = 'Report'): void
    {
        $this->getReportLogger()->error("$context failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
        error_log("$context Error: " . $e->getMessage());
    }
}
