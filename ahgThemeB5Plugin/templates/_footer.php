<?php
$footerText = '';
$showBranding = true;
try {
    if (class_exists('Illuminate\Database\Capsule\Manager')) {
        $rows = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
            ->whereIn('setting_key', ['ahg_footer_text', 'ahg_show_branding'])
            ->get();
        foreach ($rows as $row) {
            if ($row->setting_key === 'ahg_footer_text') {
                $footerText = $row->setting_value;
            }
            if ($row->setting_key === 'ahg_show_branding') {
                $showBranding = $row->setting_value !== 'false';
            }
        }
    }
} catch (Exception $e) {}
?>
<?php if ($showBranding && !empty($footerText)): ?>
<footer class="ahg-site-footer text-center py-3" style="background-color: var(--ahg-primary, #005837); color: #fff;">
  <small><?php echo esc_specialchars($footerText); ?></small>
</footer>
<?php endif; ?>

<!-- Display Mode Switching -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/display-mode.js"></script>

<!-- Base JS for nested dropdowns and UI enhancements -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/base.js"></script>
