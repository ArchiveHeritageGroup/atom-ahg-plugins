<?php

use AtomFramework\Http\Controllers\AhgController;

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

/**
 * Override of themesAction that supports symlinked themes.
 *
 * The original themesAction.class.php filters out plugins that are not in
 * sf_plugins_dir, which excludes symlinked plugins from atom-ahg-plugins.
 *
 * This override removes that path check so symlinked themes are displayed.
 */
class sfPluginAdminPluginThemesAction extends AhgController
{
    public function execute($request)
    {
        $title = $this->context->i18n->__('List themes');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->form = new sfForm();

        if (!$this->context->user->isAdministrator()) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        $criteria = new Criteria();
        $criteria->add(QubitSetting::NAME, 'plugins');
        if (1 == count($query = QubitSetting::get($criteria))) {
            $setting = $query[0];

            $this->form->setDefault('enabled', unserialize($setting->getValue(['sourceCulture' => true])) ?: []);
        }

        $configuration = ProjectConfiguration::getActive();
        $pluginPaths = $configuration->getAllPluginPaths();

        // Don't exclude theme plugins - they should still appear in the list
        // even if they're already enabled (original code excluded ALL enabled plugins)
        foreach (sfPluginAdminPluginConfiguration::$pluginNames as $name) {
            // Keep plugins that might be themes (check if they have "Theme" in name)
            if (stripos($name, 'Theme') === false) {
                unset($pluginPaths[$name]);
            }
        }

        $this->plugins = [];

        foreach ($pluginPaths as $name => $path) {
            $className = $name.'Configuration';

            // MODIFIED: Removed sf_plugins_dir check to support symlinked plugins
            // Original check: sfConfig::get('sf_plugins_dir') == substr($path, 0, strlen(sfConfig::get('sf_plugins_dir')))
            // Now we just check if the configuration file is readable
            if (is_readable($classPath = $path.'/config/'.$className.'.class.php')) {
                $this->installPluginAssets($name, $path);

                require_once $classPath;

                $class = new $className($configuration);

                // Build a list of themes
                if (isset($class::$summary) && 1 === preg_match('/theme/i', $class::$summary)) {
                    $this->plugins[$name] = $class;
                }
            }
        }

        $this->form->setValidator('enabled', new sfValidatorChoice([
            'choices' => array_keys($this->plugins),
            'empty_value' => [],
            'multiple' => true,
        ]));

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                if (1 != count($query)) {
                    $setting = new QubitSetting();
                    $setting->name = 'plugins';
                }

                $settings = unserialize($setting->getValue(['sourceCulture' => true])) ?: [];

                foreach (array_keys($this->plugins) as $item) {
                    if (in_array($item, (array) $this->form->getValue('enabled'))) {
                        $settings[] = $item;
                    } else {
                        if (false !== $key = array_search($item, $settings)) {
                            unset($settings[$key]);
                        }
                    }
                }

                $setting->setValue(serialize(array_unique($settings)), ['sourceCulture' => true]);
                $setting->save();

                QubitCache::getInstance()->removePattern('settings:i18n:*');

                // Clear cache
                $cacheClear = new sfCacheClearTask(sfContext::getInstance()->getEventDispatcher(), new sfFormatter());
                $cacheClear->run();

                $this->redirect(['module' => 'sfPluginAdminPlugin', 'action' => 'themes']);
            }
        }
    }

    // Copied from sfPluginPublishAssetsTask
    protected function installPluginAssets($name, $path)
    {
        $webDir = $path.'/web';

        if (is_dir($webDir)) {
            $filesystem = new sfFilesystem();
            $filesystem->relativeSymlink($webDir, sfConfig::get('sf_web_dir').'/'.$name, true);
        }
    }
}
