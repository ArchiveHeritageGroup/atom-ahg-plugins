<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\SettingService;

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

class SettingsDeleteAction extends AhgController
{
    public function execute($request)
    {
        $setting = SettingService::getById($this->getRequest()->id);

        $this->forward404Unless($setting);

        // check that the setting is deleteable
        if ($setting->isDeleteable()) {
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                \Illuminate\Database\Capsule\Manager::table('setting_i18n')->where('id', $setting->id)->delete();
                \Illuminate\Database\Capsule\Manager::table('setting')->where('id', $setting->id)->delete();
            } else {
                $setting->delete();
            }
        }
        // TODO: else populate an error?

        if (null !== $this->context->getViewCacheManager()) {
            $this->context->getViewCacheManager()->remove('@sf_cache_partial?module=menu&action=_browseMenu&sf_cache_key=*');
            $this->context->getViewCacheManager()->remove('@sf_cache_partial?module=menu&action=_mainMenu&sf_cache_key=*');
        }

        $this->redirect('settings/language');
    }
}
