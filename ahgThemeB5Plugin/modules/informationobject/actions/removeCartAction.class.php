<?php
use Illuminate\Database\Capsule\Manager as DB;

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
class removeCartAction extends DefaultEditAction
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        $sql = 'DELETE FROM cart WHERE id = "'.$this->resource->id.';';

        DB::statement($sql);

        $this->redirect([$this->resource, 'module' => 'cart', 'action' => 'browse']);
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
