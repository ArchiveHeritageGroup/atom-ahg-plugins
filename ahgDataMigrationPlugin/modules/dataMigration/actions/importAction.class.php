<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Data Migration Import — alias for the index page (where the upload form lives).
 *
 * The data migration "import" workflow starts at the index page (file upload),
 * then progresses through preview → map → execute. This action exists so the
 * /admin/data-migration/import URL works (referenced from menus and docs).
 */
class dataMigrationImportAction extends AhgController
{
    public function execute($request)
    {
        $this->forward('dataMigration', 'index');
    }
}
