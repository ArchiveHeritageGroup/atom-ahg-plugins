<?php
use Illuminate\Database\Capsule\Manager as DB;
use AtomFramework\Http\Controllers\AhgEditController;

/*
 * This file is part of Qubit Toolkit.
 *
 * Qubit Toolkit is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Qubit Toolkit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Qubit Toolkit.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Physical Object edit component.
 *
 * @author     Johan Pieterse <johan@theahg.co.za>
 *
 * @version    SVN: $Id
 */
class InformationObjectRemoveFavoritesAction extends AhgEditController
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;
        $this->informationObject = QubitInformationObject::getById($this->resource->id);
        // Add to favorites table
        // Nothing links to this action - the working cart/favourites feature lives in
        // ahgCartPlugin and ahgFavoritesPlugin - so it was reachable only through the
        // catch-all route /:slug/:module/:action, which resolves any object's slug
        // with no class check and does not require a login or a POST.
        //
        // That meant an anonymous GET could write: rows landed with a null user_id
        // that nobody could ever retrieve, and a crawler could create unbounded
        // object + cart/favorites rows. Bingbot is demonstrably walking these
        // combinations on this site.
        if (!$this->resource instanceof QubitInformationObject) {
            $this->forward404();
        }

        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = $this->context->user->getAttribute('user_id');

        // Was a concatenated DELETE via DB::statement(). Uses the query builder the
        // file already imports, so the values are bound rather than interpolated.
        DB::table('favorites')
            ->where('user_id', $userId)
            ->where('archival_description_id', $this->resource->id)
            ->delete();

        $this->redirect([$this->resource, 'module' => 'informationobject']);
    }

    protected function earlyExecute()
    {
        // $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
        $this->resource = $this->getRoute()->resource;

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }
    }
}
