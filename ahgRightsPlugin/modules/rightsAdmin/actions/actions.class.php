<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

use Illuminate\Database\Capsule\Manager as DB;
use Plugins\ahgRightsPlugin\Services\RightsService;

/**
 * rightsAdmin actions
 *
 * Admin pages for managing rights, embargoes, orphan works, TK labels
 *
 * @package ahgRightsPlugin
 */
class rightsAdminActions extends AhgController
{
    protected RightsService $rightsService;

    public function boot(): void
    {
if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->rightsService = RightsService::getInstance();
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function executeIndex($request): void
    {
        $this->stats = $this->rightsService->getStatistics();
        $this->expiringEmbargoes = $this->rightsService->getExpiringEmbargoes(30);
        $this->reviewDue = $this->rightsService->getEmbargoesForReview();
        $this->formOptions = $this->rightsService->getFormOptions();
    }

    // =========================================================================
    // EMBARGOES
    // =========================================================================

    public function executeEmbargoes($request): void
    {
        $status = $request->getParameter('status', 'active');

        $query = DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'e.object_id')
            ->select([
                'e.*',
                'ei.reason_note',
                'ioi.title as object_title',
                's.slug',
            ]);

        if ('all' !== $status) {
            $query->where('e.status', $status);
        }

        $this->embargoes = $query->orderBy('e.end_date')->limit(100)->get();
        $this->status = $status;
        $this->formOptions = $this->rightsService->getFormOptions();
    }

    public function executeEmbargoEdit($request): void
    {
        $id = (int) $request->getParameter('id');

        if ($id) {
            $this->embargo = DB::table('rights_embargo as e')
                ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                    $join->on('e.id', '=', 'ei.id')
                        ->where('ei.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('e.id', $id)
                ->select(['e.*', 'ei.reason_note', 'ei.internal_note'])
                ->first();

            $this->embargoLog = DB::table('rights_embargo_log')
                ->where('embargo_id', $id)
                ->orderBy('performed_at', 'desc')
                ->get();
        }

        $this->formOptions = $this->rightsService->getFormOptions();

        if ($request->isMethod('post')) {
            $this->processEmbargoSave($request, $id);
        }
    }

    protected function processEmbargoSave(sfWebRequest $request, int $id = 0): void
    {
        $data = [
            'object_id' => (int) $request->getParameter('object_id'),
            'embargo_type' => $request->getParameter('embargo_type'),
            'reason' => $request->getParameter('reason'),
            'start_date' => $request->getParameter('start_date'),
            'end_date' => $request->getParameter('end_date') ?: null,
            'auto_release' => $request->getParameter('auto_release') ? 1 : 0,
            'review_date' => $request->getParameter('review_date') ?: null,
            'review_interval_months' => (int) $request->getParameter('review_interval_months') ?: 12,
            'notify_before_days' => (int) $request->getParameter('notify_before_days') ?: 30,
            'notify_emails' => array_filter(explode(',', $request->getParameter('notify_emails', ''))),
            'reason_note' => $request->getParameter('reason_note'),
            'internal_note' => $request->getParameter('internal_note'),
            'created_by' => $this->getUser()->getAttribute('user_id'),
        ];

        try {
            if ($id) {
                // Update existing
                DB::table('rights_embargo')->where('id', $id)->update([
                    'embargo_type' => $data['embargo_type'],
                    'reason' => $data['reason'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'auto_release' => $data['auto_release'],
                    'review_date' => $data['review_date'],
                    'review_interval_months' => $data['review_interval_months'],
                    'notify_before_days' => $data['notify_before_days'],
                    'notify_emails' => json_encode($data['notify_emails']),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('rights_embargo_i18n')->updateOrInsert(
                    ['id' => $id, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                    ['reason_note' => $data['reason_note'], 'internal_note' => $data['internal_note']]
                );

                $this->getUser()->setFlash('notice', 'Embargo updated successfully.');
            } else {
                $id = $this->rightsService->createEmbargo($data);
                $this->getUser()->setFlash('notice', 'Embargo created successfully.');
            }

            $this->redirect(['module' => 'rightsAdmin', 'action' => 'embargoes']);
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error saving embargo: '.$e->getMessage());
        }
    }

    public function executeEmbargoLift($request): void
    {
        $id = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $reason = $request->getParameter('lift_reason');
            $userId = $this->getUser()->getAttribute('user_id');

            if ($this->rightsService->liftEmbargo($id, $reason, $userId)) {
                $this->getUser()->setFlash('notice', 'Embargo lifted successfully.');
            } else {
                $this->getUser()->setFlash('error', 'Failed to lift embargo.');
            }
        }

        $this->redirect(['module' => 'rightsAdmin', 'action' => 'embargoes']);
    }

    public function executeEmbargoExtend($request): void
    {
        $id = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $newEndDate = $request->getParameter('new_end_date');
            $reason = $request->getParameter('extend_reason');
            $userId = $this->getUser()->getAttribute('user_id');

            if ($this->rightsService->extendEmbargo($id, $newEndDate, $reason, $userId)) {
                $this->getUser()->setFlash('notice', 'Embargo extended successfully.');
            } else {
                $this->getUser()->setFlash('error', 'Failed to extend embargo.');
            }
        }

        $this->redirect(['module' => 'rightsAdmin', 'action' => 'embargoes']);
    }

    public function executeProcessExpired($request): void
    {
        $count = $this->rightsService->processExpiredEmbargoes();
        $this->getUser()->setFlash('notice', "Processed {$count} expired embargoes.");
        $this->redirect(['module' => 'rightsAdmin', 'action' => 'embargoes']);
    }

    // =========================================================================
    // ORPHAN WORKS
    // =========================================================================

    public function executeOrphanWorks($request): void
    {
        $status = $request->getParameter('status', 'all');

        $query = DB::table('rights_orphan_work as o')
            ->leftJoin('rights_orphan_work_i18n as oi', function ($join) {
                $join->on('o.id', '=', 'oi.id')
                    ->where('oi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('o.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'o.object_id')
            ->select([
                'o.*',
                'oi.notes',
                'oi.search_summary',
                'ioi.title as object_title',
                's.slug',
            ]);

        if ('all' !== $status) {
            $query->where('o.status', $status);
        }

        $this->orphanWorks = $query->orderBy('o.created_at', 'desc')->limit(100)->get();
        $this->status = $status;
        $this->formOptions = $this->rightsService->getFormOptions();
    }

    public function executeOrphanWorkEdit($request): void
    {
        $id = (int) $request->getParameter('id');

        if ($id) {
            $this->orphanWork = DB::table('rights_orphan_work as o')
                ->leftJoin('rights_orphan_work_i18n as oi', function ($join) {
                    $join->on('o.id', '=', 'oi.id')
                        ->where('oi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('o.id', $id)
                ->select(['o.*', 'oi.notes', 'oi.search_summary'])
                ->first();

            $this->searchSteps = $this->rightsService->getOrphanWorkSearchSteps($id);
        }

        $this->formOptions = $this->rightsService->getFormOptions();

        if ($request->isMethod('post')) {
            $this->processOrphanWorkSave($request, $id);
        }
    }

    protected function processOrphanWorkSave(sfWebRequest $request, int $id = 0): void
    {
        $data = [
            'object_id' => (int) $request->getParameter('object_id'),
            'work_type' => $request->getParameter('work_type'),
            'search_jurisdiction' => $request->getParameter('search_jurisdiction') ?: 'ZA',
            'intended_use' => $request->getParameter('intended_use'),
            'proposed_fee' => $request->getParameter('proposed_fee') ?: null,
            'notes' => $request->getParameter('notes'),
            'created_by' => $this->getUser()->getAttribute('user_id'),
        ];

        try {
            if ($id) {
                DB::table('rights_orphan_work')->where('id', $id)->update([
                    'work_type' => $data['work_type'],
                    'search_jurisdiction' => $data['search_jurisdiction'],
                    'intended_use' => $data['intended_use'],
                    'proposed_fee' => $data['proposed_fee'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('rights_orphan_work_i18n')->updateOrInsert(
                    ['id' => $id, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                    ['notes' => $data['notes']]
                );

                $this->getUser()->setFlash('notice', 'Orphan work record updated.');
            } else {
                $id = $this->rightsService->createOrphanWork($data);
                $this->getUser()->setFlash('notice', 'Orphan work record created.');
            }

            $this->redirect(['module' => 'rightsAdmin', 'action' => 'orphanWorkEdit', 'id' => $id]);
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }
    }

    public function executeAddSearchStep($request): void
    {
        $orphanWorkId = (int) $request->getParameter('orphan_work_id');

        if ($request->isMethod('post')) {
            $data = [
                'source_type' => $request->getParameter('source_type'),
                'source_name' => $request->getParameter('source_name'),
                'source_url' => $request->getParameter('source_url'),
                'search_date' => $request->getParameter('search_date') ?: date('Y-m-d'),
                'search_terms' => $request->getParameter('search_terms'),
                'results_found' => $request->getParameter('results_found') ? 1 : 0,
                'results_description' => $request->getParameter('results_description'),
                'performed_by' => $this->getUser()->getAttribute('user_id'),
            ];

            try {
                $this->rightsService->addOrphanWorkSearchStep($orphanWorkId, $data);
                $this->getUser()->setFlash('notice', 'Search step added.');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'rightsAdmin', 'action' => 'orphanWorkEdit', 'id' => $orphanWorkId]);
    }

    public function executeCompleteOrphanSearch($request): void
    {
        $id = (int) $request->getParameter('id');
        $found = $request->getParameter('rights_holder_found') ? true : false;

        if ($this->rightsService->completeOrphanWorkSearch($id, $found)) {
            $this->getUser()->setFlash('notice', 'Search marked as complete.');
        }

        $this->redirect(['module' => 'rightsAdmin', 'action' => 'orphanWorks']);
    }

    // =========================================================================
    // TK LABELS
    // =========================================================================

    public function executeTkLabels($request): void
    {
        $this->tkLabels = $this->rightsService->getTkLabels();

        $this->assignments = DB::table('rights_object_tk_label as otl')
            ->join('rights_tk_label as tl', 'otl.tk_label_id', '=', 'tl.id')
            ->leftJoin('rights_tk_label_i18n as tli', function ($join) {
                $join->on('tl.id', '=', 'tli.id')
                    ->where('tli.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('otl.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'otl.object_id')
            ->select([
                'otl.*',
                'tl.code',
                'tl.color',
                'tli.name as label_name',
                'ioi.title as object_title',
                's.slug',
            ])
            ->orderBy('otl.created_at', 'desc')
            ->limit(100)
            ->get();
    }

    public function executeAssignTkLabel($request): void
    {
        if ($request->isMethod('post')) {
            $objectId = (int) $request->getParameter('object_id');
            $labelId = (int) $request->getParameter('tk_label_id');

            $data = [
                'community_name' => $request->getParameter('community_name'),
                'community_contact' => $request->getParameter('community_contact'),
                'custom_text' => $request->getParameter('custom_text'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ];

            try {
                $this->rightsService->assignTkLabel($objectId, $labelId, $data);
                $this->getUser()->setFlash('notice', 'TK Label assigned successfully.');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'rightsAdmin', 'action' => 'tkLabels']);
    }

    public function executeRemoveTkLabel($request): void
    {
        $objectId = (int) $request->getParameter('object_id');
        $labelId = (int) $request->getParameter('label_id');

        if ($this->rightsService->removeTkLabel($objectId, $labelId)) {
            $this->getUser()->setFlash('notice', 'TK Label removed.');
        }

        $this->redirect(['module' => 'rightsAdmin', 'action' => 'tkLabels']);
    }

    // =========================================================================
    // RIGHTS STATEMENTS & CC LICENSES
    // =========================================================================

    public function executeStatements($request): void
    {
        $this->rightsStatements = $this->rightsService->getRightsStatements();
        $this->ccLicenses = $this->rightsService->getCcLicenses();
    }

    // =========================================================================
    // REPORTS
    // =========================================================================

    public function executeReport($request): void
    {
        $type = $request->getParameter('type', 'summary');

        $this->reportType = $type;

        switch ($type) {
            case 'embargoes':
                $this->data = $this->rightsService->getActiveEmbargoes();
                break;

            case 'orphan_works':
                $this->data = DB::table('rights_orphan_work as o')
                    ->leftJoin('information_object_i18n as ioi', function ($join) {
                        $join->on('o.object_id', '=', 'ioi.id')
                            ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->select(['o.*', 'ioi.title as object_title'])
                    ->orderBy('o.status')
                    ->get();
                break;

            case 'tk_labels':
                $this->data = DB::table('rights_object_tk_label as otl')
                    ->join('rights_tk_label as tl', 'otl.tk_label_id', '=', 'tl.id')
                    ->leftJoin('rights_tk_label_i18n as tli', function ($join) {
                        $join->on('tl.id', '=', 'tli.id')
                            ->where('tli.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->leftJoin('information_object_i18n as ioi', function ($join) {
                        $join->on('otl.object_id', '=', 'ioi.id')
                            ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->select(['otl.*', 'tl.code', 'tli.name as label_name', 'ioi.title as object_title'])
                    ->get();
                break;

            default:
                $this->data = $this->rightsService->getStatistics();
        }

        // Export if requested
        if ($request->getParameter('export')) {
            $this->exportReport($type, $this->data);
        }
    }

    protected function exportReport(string $type, $data): void
    {
        $filename = "rights_{$type}_".date('Y-m-d').'.csv';

        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $output = fopen('php://output', 'w');

        // Headers based on type
        switch ($type) {
            case 'embargoes':
                fputcsv($output, ['Object', 'Type', 'Reason', 'Start Date', 'End Date', 'Status']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row->object_title ?? $row->object_id,
                        $row->embargo_type,
                        $row->reason,
                        $row->start_date,
                        $row->end_date,
                        $row->status,
                    ]);
                }
                break;

            case 'tk_labels':
                fputcsv($output, ['Object', 'Label Code', 'Label Name', 'Community', 'Verified']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row->object_title ?? $row->object_id,
                        $row->code,
                        $row->label_name,
                        $row->community_name,
                        $row->verified ? 'Yes' : 'No',
                    ]);
                }
                break;
        }

        fclose($output);
        exit;
    }
}
