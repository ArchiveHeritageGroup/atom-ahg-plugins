<?php

/**
 * imageAr actions (#147) — 2D image AR viewer.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use AtomFramework\Http\Controllers\AhgController;

class imageArActions extends AhgController
{
    public function executeView($request)
    {
        require_once $this->config('sf_root_dir').'/plugins/ahgImageArPlugin/lib/Services/ImageArService.php';
        $svc = new ImageArService();

        $slug = trim((string) $request->getParameter('slug', ''));
        $id = (int) $request->getParameter('id', 0);

        $data = null;
        if ($slug !== '') {
            $data = $svc->resolveBySlug($slug);
        } elseif ($id > 0) {
            $data = $svc->resolveById($id);
        }
        if ($data === null) {
            $this->forward404('No displayable image found for this object');
        }

        $this->item = $data;
    }
}
