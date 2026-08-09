<?php

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

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Image viewer settings, under Admin > Settings.
 *
 * Every OpenSeadragon and Mirador option was fixed in each viewer plugin's
 * JavaScript, so a repository that wanted the navigator off, rotation on or a
 * taller viewer had to patch a plugin and carry the patch across upgrades.
 *
 * The values live in iiif_viewer_settings, which ahgIiifPlugin owns and already
 * uses for its own screen, and are read back by IiifViewerDefaults. This action
 * is only the interface: it deliberately holds no viewer knowledge beyond the
 * key names, so adding an option later is a form field and a row rather than a
 * change here and in two renderers.
 *
 * Lives in ahgCorePlugin rather than ahgIiifPlugin because this is the settings
 * module, and one plugin owning that module override avoids the partial-module
 * shadowing that has bitten us before. It degrades cleanly: with no IIIF plugin
 * installed the menu entry hides and this page says so.
 */
class SettingsViewersAction extends sfAction
{
    /**
     * key => [type, default]
     *
     * The default is what the viewer plugin itself does when nothing is stored,
     * repeated here so a checkbox that has never been configured renders in the
     * state the reader actually gets. Without that, a switch shows as off,
     * saving the form writes 0, and the first save silently disables something
     * nobody chose to disable.
     */
    public static $FIELDS = [
        'seadragon_show_navigator' => ['bool', '1'],
        'seadragon_navigator_position' => ['string', 'BOTTOM_RIGHT'],
        'seadragon_show_rotation' => ['bool', '1'],
        'seadragon_show_flip' => ['bool', '1'],
        'seadragon_cross_origin' => ['string', 'Anonymous'],
        'seadragon_zoom_per_click' => ['string', '1.5'],
        'seadragon_max_zoom_pixel_ratio' => ['string', '4'],
        'seadragon_animation_time' => ['string', '0.5'],
        'seadragon_tile_retry_max' => ['string', '3'],
        'seadragon_tile_retry_delay' => ['string', '2000'],

        'mirador_allow_close' => ['bool', '0'],
        'mirador_allow_maximize' => ['bool', '1'],
        'mirador_allow_fullscreen' => ['bool', '1'],
        'mirador_sidebar_open' => ['bool', '0'],
        'mirador_thumbnail_position' => ['string', 'far-bottom'],
        'mirador_workspace_panel' => ['bool', '0'],
        'mirador_zoom_controls' => ['bool', '1'],

        'viewer_height' => ['string', '600px'],
    ];

    public function execute($request)
    {
        if (class_exists('\AhgCore\Core\AhgDb')) {
            \AhgCore\Core\AhgDb::init();
        }

        $this->tableMissing = false;
        $this->seadragon = $this->pluginInstalled('ahgSeadragonPlugin');
        $this->mirador = $this->pluginInstalled('ahgMiradorPlugin');

        if ($request->isMethod('post')) {
            foreach (self::$FIELDS as $key => [$type, $default]) {
                $value = $request->getParameter($key);

                // A checkbox posts nothing when it is off, which is the whole
                // reason the form renders from defaults rather than from blank.
                if ('bool' === $type) {
                    $value = $value ? '1' : '0';
                }

                $this->save($key, (string) $value);
            }

            $this->getUser()->setFlash('notice', $this->context->i18n->__('Viewer settings saved.'));

            $this->redirect(['module' => 'settings', 'action' => 'viewers']);

            return;
        }

        $this->settings = $this->load();
    }

    /**
     * Current values, falling back to each viewer's own default.
     */
    protected function load(): array
    {
        $out = [];

        foreach (self::$FIELDS as $key => [$type, $default]) {
            $out[$key] = $default;
        }

        try {
            $rows = DB::table('iiif_viewer_settings')
                ->whereIn('setting_key', array_keys(self::$FIELDS))
                ->pluck('setting_value', 'setting_key')
                ->all();

            foreach ($rows as $key => $value) {
                $out[$key] = (string) $value;
            }
        } catch (\Throwable $e) {
            // The table belongs to ahgIiifPlugin. Its absence means that plugin
            // is not installed, which is a legitimate configuration rather than
            // an error - the page says so instead of failing.
            $this->tableMissing = true;
        }

        return $out;
    }

    protected function save(string $key, string $value): void
    {
        try {
            if (DB::table('iiif_viewer_settings')->where('setting_key', $key)->exists()) {
                DB::table('iiif_viewer_settings')
                    ->where('setting_key', $key)
                    ->update(['setting_value' => $value]);

                return;
            }

            DB::table('iiif_viewer_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('Could not save viewer settings: %1%', ['%1%' => $e->getMessage()]));
        }
    }

    protected function pluginInstalled(string $plugin): bool
    {
        try {
            $configuration = sfProjectConfiguration::getActive();

            return $configuration && in_array($plugin, $configuration->getPlugins(), true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
