<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\AclService;
use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsResetAction extends AhgController
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->getUser()->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        $section = $request->getParameter('section', 'general');
        
        // Default values for each section
        $defaults = [
            'general' => [
                'ahg_theme_enabled' => 'true',
                'ahg_logo_path' => '',
                'ahg_primary_color' => '#005837',
                'ahg_secondary_color' => '#37A07F',
                'ahg_footer_text' => 'Powered by The AHG',
                'ahg_show_branding' => 'true',
                'ahg_custom_css' => '',
                'ahg_card_header_bg' => '#005837',
                'ahg_card_header_text' => '#ffffff',
                'ahg_button_bg' => '#005837',
                'ahg_button_text' => '#ffffff',
                'ahg_link_color' => '#005837',
                'ahg_sidebar_bg' => '#f8f9fa',
                'ahg_sidebar_text' => '#333333',
                // Extended theme colors
                'ahg_success_color' => '#28a745',
                'ahg_warning_color' => '#ffc107',
                'ahg_danger_color' => '#dc3545',
                'ahg_info_color' => '#17a2b8',
                'ahg_light_color' => '#f8f9fa',
                'ahg_dark_color' => '#343a40',
                'ahg_muted_color' => '#6c757d',
                'ahg_border_color' => '#dee2e6',
                'ahg_body_bg' => '#ffffff',
                'ahg_body_text' => '#212529',
            ],
            'metadata' => [
                'meta_extract_on_upload' => 'true',
                'meta_extract_images' => 'true',
                'meta_extract_pdf' => 'true',
                'meta_extract_office' => 'true',
                'meta_extract_video' => 'true',
                'meta_map_title' => 'dc.title',
                'meta_map_description' => 'dc.description',
            ],
            'spectrum' => [
                'spectrum_enabled' => 'true',
                'spectrum_default_currency' => 'ZAR',
                'spectrum_valuation_reminder_days' => '365',
                'spectrum_loan_default_period' => '30',
                'spectrum_condition_check_interval' => '365',
                'spectrum_require_valuation' => 'false',
                'spectrum_require_insurance' => 'false',
                'spectrum_auto_numbering' => 'true',
            ],
            'iiif' => [
                'iiif_enabled' => 'true',
                'iiif_viewer' => 'mirador',
                'iiif_server_url' => '',
                'iiif_default_zoom' => '1',
                'iiif_show_navigator' => 'true',
                'iiif_show_rotation' => 'true',
                'iiif_show_fullscreen' => 'true',
                'iiif_tile_size' => '256',
            ],
            'data_protection' => [
                'dp_enabled' => 'true',
                'dp_default_regulation' => 'POPIA',
                'dp_auto_deadline' => '30',
                'dp_notify_overdue' => 'true',
                'dp_notify_email' => '',
                'dp_retention_default' => '7',
                'dp_anonymize_on_delete' => 'true',
                'dp_audit_logging' => 'true',
                'dp_consent_required' => 'true',
                'dp_data_export_format' => 'json',
                'dp_breach_notify_hours' => '72',
            ],
            'faces' => [
                'face_detect_enabled' => 'false',
                'face_detect_backend' => 'local',
                'face_auto_match' => 'false',
                'face_auto_link' => 'false',
                'face_confidence_threshold' => '0.8',
                'face_min_size' => '30',
                'face_max_faces' => '20',
                'face_blur_unmatched' => 'false',
                'face_api_key' => '',
                'face_api_endpoint' => '',
                'face_store_embeddings' => 'true',
            ],
            'media' => [
                'media_player_type' => 'html5',
                'media_autoplay' => 'false',
                'media_show_controls' => 'true',
                'media_loop' => 'false',
                'media_default_volume' => '80',
                'media_show_waveform' => 'true',
                'media_transcription_enabled' => 'false',
            ],
            'photos' => [
                'photo_max_upload_size' => '10',
                'photo_allowed_types' => 'jpg,jpeg,png,gif,tiff',
                'photo_create_thumbnails' => 'true',
                'photo_thumbnail_small' => '150',
                'photo_thumbnail_medium' => '400',
                'photo_thumbnail_large' => '800',
                'photo_watermark_enabled' => 'false',
                'photo_watermark_text' => '',
                'photo_exif_strip' => 'false',
                'photo_auto_orient' => 'true',
            ],
            'jobs' => [
                'jobs_enabled' => 'true',
                'jobs_max_concurrent' => '2',
                'jobs_timeout' => '3600',
                'jobs_retry_attempts' => '3',
                'jobs_cleanup_days' => '30',
                'jobs_notify_failure' => 'true',
                'jobs_notify_email' => '',
            ],
        ];

        if (!isset($defaults[$section])) {
            $this->getUser()->setFlash('error', 'Invalid section.');
            $this->redirect(['module' => 'ahgSettings', 'action' => 'index']);
        }

        // Reset the settings
        foreach ($defaults[$section] as $key => $value) {
            DB::table('ahg_settings')->updateOrInsert(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'setting_group' => $section,
                    'updated_at' => DB::raw('NOW()')
                ]
            );
        }

        $this->getUser()->setFlash('notice', 'Settings reset to defaults.');
        $this->redirect(['module' => 'ahgSettings', 'action' => 'section', 'section' => $section]);
    }
}
