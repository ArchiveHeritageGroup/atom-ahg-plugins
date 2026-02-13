<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomFramework\Services\Pagination\PaginationService;
/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

class RightsHolderListAction extends AhgController
{
    public function execute($request)
    {
        if (!isset($request->limit)) {
            $request->limit = $this->config('app_hits_per_page');
        }

        if (!isset($request->subquery)) {
            $this->redirect(['module' => 'rightsholder', 'action' => 'browse']);
        }

        if (class_exists('QubitPager')) {
            // === Propel mode (existing code, unchanged) ===
            $criteria = new Criteria();
            $criteria->addDescendingOrderByColumn(QubitObject::UPDATED_AT);
            $criteria->addJoin(QubitRightsHolder::ID, QubitActorI18n::ID);
            $criteria->add(QubitActorI18n::CULTURE, $this->culture());
            $criteria->add(QubitActorI18n::AUTHORIZED_FORM_OF_NAME, "%{$request->subquery}%", Criteria::LIKE);

            // Page results
            $this->pager = new QubitPager('QubitRightsHolder');
            $this->pager->setCriteria($criteria);
            $this->pager->setMaxPerPage($request->limit);
            $this->pager->setPage($request->page);
        } else {
            // === Standalone mode — PaginationService ===
            $this->pager = PaginationService::paginateActors('rights_holder', $this->culture(), $request->subquery ?? null, 'updated_at_desc', (int)($request->page ?? 1), (int)$request->limit);
        }

        $this->rightsHolders = $this->pager->getResults();
    }
}
