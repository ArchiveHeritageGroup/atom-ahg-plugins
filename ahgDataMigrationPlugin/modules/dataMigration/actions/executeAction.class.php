<?php

class dataMigrationExecuteAction extends sfAction
{
    public function execute($request)
    {
        $this->forward('dataMigration', 'executeAhgImport');
    }
}
