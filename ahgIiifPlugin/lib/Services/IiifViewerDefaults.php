<?php

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Viewer defaults, set by an administrator rather than by editing code.
 *
 * The two viewer plugins carry sensible defaults of their own. What they had no
 * way of doing was letting a site change them: every OpenSeadragon and Mirador
 * setting was fixed in JavaScript, so a repository that wanted the navigator off
 * or a taller viewer had to patch a plugin and carry the patch forward.
 *
 * This reads the same iiif_viewer_settings table the IIIF settings screen
 * already writes, and hands each renderer an options array in the shape it
 * expects. It lives in ahgIiifPlugin because both viewers depend on it, so
 * neither has to know the other exists and a site with one viewer installed gets
 * the same behaviour as a site with both.
 *
 * Everything here is best-effort. A missing table, an absent row or a malformed
 * value falls through to the plugin's own default, because a settings lookup is
 * never worth a viewer that will not open.
 */
class IiifViewerDefaults
{
    /**
     * Rows are stored flat as `<viewer>_<option>`, so the screen can write them
     * with the same loop it uses for everything else. The mapping to each
     * viewer's own option name lives here rather than in the template.
     */
    private const SEADRAGON = [
        'seadragon_show_navigator' => ['showNavigator', 'bool'],
        'seadragon_navigator_position' => ['navigatorPosition', 'string'],
        'seadragon_show_rotation' => ['showRotationControl', 'bool'],
        'seadragon_show_flip' => ['showFlipControl', 'bool'],
        'seadragon_cross_origin' => ['crossOriginPolicy', 'string'],
        'seadragon_zoom_per_click' => ['zoomPerClick', 'float'],
        'seadragon_max_zoom_pixel_ratio' => ['maxZoomPixelRatio', 'float'],
        'seadragon_animation_time' => ['animationTime', 'float'],
        'seadragon_tile_retry_max' => ['tileRetryMax', 'int'],
        'seadragon_tile_retry_delay' => ['tileRetryDelay', 'int'],
    ];

    private const MIRADOR = [
        'mirador_allow_close' => ['window.allowClose', 'bool'],
        'mirador_allow_maximize' => ['window.allowMaximize', 'bool'],
        'mirador_allow_fullscreen' => ['window.allowFullscreen', 'bool'],
        'mirador_sidebar_open' => ['window.sideBarOpenByDefault', 'bool'],
        'mirador_thumbnail_position' => ['thumbnailNavigation.defaultPosition', 'string'],
        'mirador_workspace_panel' => ['workspaceControlPanel.enabled', 'bool'],
        'mirador_zoom_controls' => ['workspace.showZoomControls', 'bool'],
    ];

    /**
     * Options for one viewer, ready to merge under whatever the caller passed.
     *
     * @param string $viewer 'seadragon' or 'mirador'
     */
    public static function forViewer(string $viewer): array
    {
        $map = 'mirador' === $viewer ? self::MIRADOR : self::SEADRAGON;
        $stored = self::stored(array_keys($map));
        $out = [];

        foreach ($map as $key => [$option, $type]) {
            // Absent means "not configured", which is different from "set to
            // off". Only rows an administrator has actually saved are applied.
            if (!array_key_exists($key, $stored)) {
                continue;
            }

            $value = self::cast($stored[$key], $type);

            if (null === $value) {
                continue;
            }

            self::set($out, $option, $value);
        }

        // Height is shared: the screen already has one viewer_height field and
        // there is no reason for the two viewers to disagree about it.
        if ($height = self::height()) {
            $out['height'] = $height;
        }

        return $out;
    }

    private static function stored(array $keys): array
    {
        try {
            return DB::table('iiif_viewer_settings')
                ->whereIn('setting_key', $keys)
                ->pluck('setting_value', 'setting_key')
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function height(): ?string
    {
        try {
            $v = (string) DB::table('iiif_viewer_settings')
                ->where('setting_key', 'viewer_height')
                ->value('setting_value');

            return preg_match('/^\d+(px|%|vh)$/', $v) ? $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Dotted keys become nested arrays, because Mirador's settings are nested
     * and a flat table cannot express that on its own.
     */
    private static function set(array &$target, string $path, $value): void
    {
        $parts = explode('.', $path);
        $cursor = &$target;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $cursor[$part] = $value;

                break;
            }

            if (!isset($cursor[$part]) || !is_array($cursor[$part])) {
                $cursor[$part] = [];
            }

            $cursor = &$cursor[$part];
        }
    }

    private static function cast(string $raw, string $type)
    {
        switch ($type) {
            case 'bool':
                return '1' === $raw || 'true' === $raw;

            case 'int':
                return is_numeric($raw) ? (int) $raw : null;

            case 'float':
                return is_numeric($raw) ? (float) $raw : null;

            default:
                return '' === $raw ? null : $raw;
        }
    }
}
