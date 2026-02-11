<?php

use AtomFramework\Http\Controllers\AhgController;
class dataMigrationAhgImportResultsAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->stats = $this->getUser()->getAttribute('ahg_import_stats', [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'provenance_created' => 0,
            'rights_created' => 0,
            'security_set' => 0,
        ]);

        // Clear session data
        $this->getUser()->setAttribute('ahg_import_stats', null);
    }
}
