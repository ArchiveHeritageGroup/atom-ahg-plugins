<?php

use AtomFramework\Http\Controllers\AhgController;
class dataMigrationExecuteAction extends AhgController
{
    public function execute($request)
    {
        $this->forward('dataMigration', 'executeAhgImport');
    }
}
