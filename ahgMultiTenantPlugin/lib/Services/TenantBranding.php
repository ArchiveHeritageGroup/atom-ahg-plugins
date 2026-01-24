<?php

namespace AhgMultiTenant\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantBranding Service
 *
 * Manages repository-specific branding (colors, logos, custom CSS).
 * Injects tenant-specific styles into the page for any theme.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantBranding
{
    /** @var array Cached branding data per repository */
    private static array $brandingCache = [];

    /** @var string Upload directory for tenant logos */
    public const LOGO_UPLOAD_DIR = '/uploads/tenants';

    /**
     * Get primary color for a repository
     *
     * @param int $repositoryId Repository ID
     * @return string|null Hex color code or null
     */
    public static function getPrimaryColor(int $repositoryId): ?string
    {
        $branding = self::getBranding($repositoryId);
        return $branding['primary_color'] ?? null;
    }

    /**
     * Get secondary color for a repository
     *
     * @param int $repositoryId Repository ID
     * @return string|null Hex color code or null
     */
    public static function getSecondaryColor(int $repositoryId): ?string
    {
        $branding = self::getBranding($repositoryId);
        return $branding['secondary_color'] ?? null;
    }

    /**
     * Get logo URL for a repository
     *
     * @param int $repositoryId Repository ID
     * @return string|null Logo URL or null
     */
    public static function getLogo(int $repositoryId): ?string
    {
        $branding = self::getBranding($repositoryId);
        return $branding['logo'] ?? null;
    }

    /**
     * Get custom CSS for a repository
     *
     * @param int $repositoryId Repository ID
     * @return string|null Custom CSS or null
     */
    public static function getCustomCss(int $repositoryId): ?string
    {
        $branding = self::getBranding($repositoryId);
        return $branding['custom_css'] ?? null;
    }

    /**
     * Get all branding settings for a repository
     *
     * @param int $repositoryId Repository ID
     * @return array Branding settings
     */
    public static function getBranding(int $repositoryId): array
    {
        if (isset(self::$brandingCache[$repositoryId])) {
            return self::$brandingCache[$repositoryId];
        }

        $branding = [];
        $prefix = "tenant_repo_{$repositoryId}_";

        $settings = DB::table('ahg_settings')
            ->where('setting_key', 'like', $prefix . '%')
            ->whereIn('setting_key', [
                $prefix . 'primary_color',
                $prefix . 'secondary_color',
                $prefix . 'logo',
                $prefix . 'custom_css',
                $prefix . 'header_bg_color',
                $prefix . 'header_text_color',
                $prefix . 'link_color',
                $prefix . 'button_color',
            ])
            ->get();

        foreach ($settings as $setting) {
            $key = str_replace($prefix, '', $setting->setting_key);
            $branding[$key] = $setting->setting_value;
        }

        self::$brandingCache[$repositoryId] = $branding;
        return $branding;
    }

    /**
     * Save branding settings for a repository
     *
     * @param int $repositoryId Repository ID
     * @param array $settings Branding settings to save
     * @param int $userId User ID saving the settings
     * @return array ['success' => bool, 'message' => string]
     */
    public static function saveBranding(int $repositoryId, array $settings, int $userId): array
    {
        // Check permission
        if (!TenantAccess::canManageBranding($userId, $repositoryId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to manage branding for this repository.'
            ];
        }

        $prefix = "tenant_repo_{$repositoryId}_";
        $allowedKeys = [
            'primary_color',
            'secondary_color',
            'header_bg_color',
            'header_text_color',
            'link_color',
            'button_color',
            'custom_css',
        ];

        foreach ($settings as $key => $value) {
            if (!in_array($key, $allowedKeys)) {
                continue;
            }

            // Validate colors
            if (strpos($key, '_color') !== false && !empty($value)) {
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    return [
                        'success' => false,
                        'message' => "Invalid color format for {$key}. Use hex format like #336699."
                    ];
                }
            }

            // Sanitize custom CSS
            if ($key === 'custom_css' && !empty($value)) {
                $value = self::sanitizeCss($value);
            }

            if (empty($value)) {
                // Delete if empty
                DB::table('ahg_settings')
                    ->where('setting_key', $prefix . $key)
                    ->delete();
            } else {
                DB::table('ahg_settings')->updateOrInsert(
                    ['setting_key' => $prefix . $key],
                    [
                        'setting_value' => $value,
                        'setting_group' => 'multi_tenant',
                        'updated_by' => $userId,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            }
        }

        // Clear cache
        unset(self::$brandingCache[$repositoryId]);

        return [
            'success' => true,
            'message' => 'Branding settings saved successfully.'
        ];
    }

    /**
     * Save logo for a repository
     *
     * @param int $repositoryId Repository ID
     * @param array $uploadedFile $_FILES array element
     * @param int $userId User ID
     * @return array ['success' => bool, 'message' => string, 'path' => string|null]
     */
    public static function saveLogo(int $repositoryId, array $uploadedFile, int $userId): array
    {
        // Check permission
        if (!TenantAccess::canManageBranding($userId, $repositoryId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to manage branding for this repository.',
                'path' => null
            ];
        }

        // Validate upload
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'File upload error: ' . $uploadedFile['error'],
                'path' => null
            ];
        }

        // Check file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Allowed: PNG, JPEG, GIF, SVG, WebP.',
                'path' => null
            ];
        }

        // Check file size (max 2MB)
        if ($uploadedFile['size'] > 2 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'File too large. Maximum size is 2MB.',
                'path' => null
            ];
        }

        // Create upload directory
        $webDir = \sfConfig::get('sf_web_dir', sfConfig::get('sf_root_dir'));
        $uploadDir = $webDir . self::LOGO_UPLOAD_DIR . '/' . $repositoryId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate filename
        $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $filePath = $uploadDir . '/' . $filename;
        $webPath = self::LOGO_UPLOAD_DIR . '/' . $repositoryId . '/' . $filename;

        // Delete old logo if exists
        $oldLogo = self::getLogo($repositoryId);
        if ($oldLogo) {
            $oldPath = $webDir . $oldLogo;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            return [
                'success' => false,
                'message' => 'Failed to save uploaded file.',
                'path' => null
            ];
        }

        // Save path to settings
        DB::table('ahg_settings')->updateOrInsert(
            ['setting_key' => "tenant_repo_{$repositoryId}_logo"],
            [
                'setting_value' => $webPath,
                'setting_group' => 'multi_tenant',
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        // Clear cache
        unset(self::$brandingCache[$repositoryId]);

        return [
            'success' => true,
            'message' => 'Logo uploaded successfully.',
            'path' => $webPath
        ];
    }

    /**
     * Delete logo for a repository
     *
     * @param int $repositoryId Repository ID
     * @param int $userId User ID
     * @return array ['success' => bool, 'message' => string]
     */
    public static function deleteLogo(int $repositoryId, int $userId): array
    {
        // Check permission
        if (!TenantAccess::canManageBranding($userId, $repositoryId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to manage branding for this repository.'
            ];
        }

        $logo = self::getLogo($repositoryId);
        if ($logo) {
            $webDir = \sfConfig::get('sf_web_dir', sfConfig::get('sf_root_dir'));
            $filePath = $webDir . $logo;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        DB::table('ahg_settings')
            ->where('setting_key', "tenant_repo_{$repositoryId}_logo")
            ->delete();

        // Clear cache
        unset(self::$brandingCache[$repositoryId]);

        return [
            'success' => true,
            'message' => 'Logo deleted successfully.'
        ];
    }

    /**
     * Generate CSS style block for current tenant
     *
     * @return string CSS style block
     */
    public static function injectStyles(): string
    {
        $repositoryId = TenantContext::getCurrentRepositoryId();
        if ($repositoryId === null) {
            return '';
        }

        $branding = self::getBranding($repositoryId);
        if (empty($branding)) {
            return '';
        }

        $css = [];

        // Primary color
        if (!empty($branding['primary_color'])) {
            $css[] = ':root { --tenant-primary-color: ' . $branding['primary_color'] . '; }';
            $css[] = '.btn-primary, .btn-tenant-primary { background-color: ' . $branding['primary_color'] . ' !important; border-color: ' . $branding['primary_color'] . ' !important; }';
            $css[] = 'a.text-primary, .text-tenant-primary { color: ' . $branding['primary_color'] . ' !important; }';
        }

        // Secondary color
        if (!empty($branding['secondary_color'])) {
            $css[] = ':root { --tenant-secondary-color: ' . $branding['secondary_color'] . '; }';
            $css[] = '.btn-secondary, .btn-tenant-secondary { background-color: ' . $branding['secondary_color'] . ' !important; border-color: ' . $branding['secondary_color'] . ' !important; }';
        }

        // Header colors
        if (!empty($branding['header_bg_color'])) {
            $css[] = '.navbar, .site-header, #top-bar, header.navbar { background-color: ' . $branding['header_bg_color'] . ' !important; }';
        }
        if (!empty($branding['header_text_color'])) {
            $css[] = '.navbar a, .site-header a, #top-bar a, header.navbar a, .navbar .nav-link { color: ' . $branding['header_text_color'] . ' !important; }';
        }

        // Link color
        if (!empty($branding['link_color'])) {
            $css[] = 'a:not(.btn) { color: ' . $branding['link_color'] . '; }';
            $css[] = 'a:not(.btn):hover { color: ' . self::darkenColor($branding['link_color'], 20) . '; }';
        }

        // Button color
        if (!empty($branding['button_color'])) {
            $css[] = '.btn-action, .btn-tenant-action { background-color: ' . $branding['button_color'] . ' !important; border-color: ' . $branding['button_color'] . ' !important; }';
        }

        // Custom CSS (already sanitized)
        if (!empty($branding['custom_css'])) {
            $css[] = $branding['custom_css'];
        }

        if (empty($css)) {
            return '';
        }

        $repoName = TenantContext::getRepositoryName($repositoryId);
        $comment = "/* Tenant branding for: {$repoName} (ID: {$repositoryId}) */";

        return "<style type=\"text/css\">\n{$comment}\n" . implode("\n", $css) . "\n</style>";
    }

    /**
     * Sanitize CSS to prevent XSS
     *
     * @param string $css Raw CSS
     * @return string Sanitized CSS
     */
    private static function sanitizeCss(string $css): string
    {
        // Remove potentially dangerous content
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*data:/i', 'url(', $css);
        $css = preg_replace('/@import/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = preg_replace('/-moz-binding/i', '', $css);

        // Limit length
        return substr($css, 0, 10000);
    }

    /**
     * Darken a hex color by a percentage
     *
     * @param string $hex Hex color
     * @param int $percent Percentage to darken
     * @return string Darkened hex color
     */
    private static function darkenColor(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Clear the branding cache
     */
    public static function clearCache(): void
    {
        self::$brandingCache = [];
    }
}
