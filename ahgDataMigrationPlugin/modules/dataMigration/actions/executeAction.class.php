<?php

class dataMigrationExecuteAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        $this->filepath = $this->getUser()->getAttribute('migration_file');
        $this->filename = $this->getUser()->getAttribute('migration_filename');
        $this->detection = $this->getUser()->getAttribute('migration_detection');
        $this->mapping = $this->getUser()->getAttribute('migration_mapping');
        
        if (!$this->filepath || !file_exists($this->filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        // For now, show a confirmation page
        // Actual import will be implemented next
        $this->rowCount = count($this->detection['rows'] ?? []);
    }
}
