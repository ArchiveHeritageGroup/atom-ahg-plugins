<?php
/**
 * GRAP Install Action
 * 
 * Plugin installation and database setup.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class grapInstallAction extends sfAction
{
    public function execute($request)
    {
        // Require administrator
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->status = ahgGrapInstallService::getStatus();
        $this->isInstalled = ahgGrapInstallService::isInstalled();

        // Handle actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            try {
                switch ($action) {
                    case 'install':
                        ahgGrapInstallService::install();
                        $this->getUser()->setFlash('notice', 'GRAP plugin installed successfully');
                        break;

                    case 'uninstall':
                        ahgGrapInstallService::uninstall(true);
                        $this->getUser()->setFlash('notice', 'GRAP plugin uninstalled');
                        break;

                    case 'migrate':
                        $count = ahgGrapInstallService::migrateExistingData();
                        $this->getUser()->setFlash('notice', "Migrated {$count} existing records");
                        break;

                    case 'snapshot':
                        $repositoryId = $request->getParameter('repository_id') ?: null;
                        $fyEnd = $request->getParameter('financial_year_end') ?: date('Y') . '-03-31';
                        $count = ahgGrapInstallService::createFinancialYearSnapshot($repositoryId, $fyEnd);
                        $this->getUser()->setFlash('notice', "Created {$count} snapshots for FY ending {$fyEnd}");
                        break;
                }
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            }

            $this->redirect(['module' => 'grap', 'action' => 'install']);
        }
    }
}
