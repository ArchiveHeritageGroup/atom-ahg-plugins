<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * circulationOverdueAction — overdue items list and notice dispatch.
 *
 * GET  /circulation/overdue   → show overdue list
 * POST /circulation/overdue   → send batch overdue notices (param: send_notices=1)
 *
 * The overdue list is loaded via OverdueNoticeService so that the same
 * query logic is shared between the UI and the notice-sending pipeline.
 *
 * @package ahgLibraryPlugin
 */
class circulationOverdueAction extends AhgController
{
    /** @var array */
    public $overduePatrons = [];

    /** @var int */
    public $totalItems = 0;

    /** @var int */
    public $totalPatrons = 0;

    /** @var int */
    public $limit = 25;

    /** @var int */
    public $page = 1;

    /** @var int */
    public $totalPages = 1;

    /** @var string|null */
    public $sendResult;

    /** @var bool */
    public $sendSuccess = false;

    public function execute($request)
    {
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Handle POST — send batch notices
        if ($request->isMethod('post') && $request->getParameter('send_notices')) {
            return $this->handleSendNotices($request);
        }

        $this->page = max(1, (int) $request->getParameter('page', 1));
        $offset = ($this->page - 1) * $this->limit;

        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OverdueNoticeService.php';

            $svc = \ahgLibraryPlugin\Service\OverdueNoticeService::getInstance();
            $result = $svc->getOverdueItems($this->limit, $offset, 1);

            $this->overduePatrons = $result['patrons'];
            $this->totalItems = $result['total'];
            $this->totalPatrons = count($this->overduePatrons);
            $this->totalPages = $this->totalItems > 0
                ? (int) ceil($this->totalItems / $this->limit)
                : 1;

        } catch (\Exception $e) {
            $this->overduePatrons = [];
            $this->totalItems = 0;
            $this->totalPatrons = 0;
        }

        return sfView::SUCCESS;
    }

    /**
     * Handle POST — dispatch batch overdue notices.
     */
    protected function handleSendNotices($request): string
    {
        $dryRun = (bool) $request->getParameter('dry_run', false);
        $minDays = (int) $request->getParameter('min_days', 1);

        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OverdueNoticeService.php';

            $svc = \ahgLibraryPlugin\Service\OverdueNoticeService::getInstance();
            $result = $svc->sendBatchNotices([
                'dry_run'  => $dryRun,
                'min_days' => $minDays,
                'max_recipients' => 500,
            ]);

            if ($dryRun) {
                $this->sendResult = json_encode($result, JSON_PRETTY_PRINT);
            } else {
                $this->sendResult = sprintf(
                    'Sent: %d &bull; Skipped: %d &bull; Total patrons: %d',
                    $result['sent'],
                    $result['skipped'],
                    $result['total_patrons']
                );
            }
            $this->sendSuccess = ($result['sent'] > 0) || $dryRun;

        } catch (\Exception $e) {
            $this->sendResult = 'Error: ' . $e->getMessage();
            $this->sendSuccess = false;
        }

        // Reload list after send
        $this->page = 1;
        $this->offset = 0;

        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/OverdueNoticeService.php';

            $svc = \ahgLibraryPlugin\Service\OverdueNoticeService::getInstance();
            $result = $svc->getOverdueItems($this->limit, 0, 1);
            $this->overduePatrons = $result['patrons'];
            $this->totalItems = $result['total'];
            $this->totalPatrons = count($this->overduePatrons);
            $this->totalPages = $this->totalItems > 0
                ? (int) ceil($this->totalItems / $this->limit)
                : 1;

        } catch (\Exception $e) {
            $this->overduePatrons = [];
        }

        return sfView::SUCCESS;
    }
}
