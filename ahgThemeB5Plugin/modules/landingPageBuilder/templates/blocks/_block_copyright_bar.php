<?php
/**
 * Copyright Bar Block Template
 */
$copyright = $config['copyright_text'] ?? '© ' . date('Y') . ' All Rights Reserved';
$poweredBy = $config['powered_by'] ?? 'AtoM';
$poweredByUrl = $config['powered_by_url'] ?? 'https://accesstomemory.org';
$enhancedBy = $config['enhanced_by'] ?? '';
$enhancedByUrl = $config['enhanced_by_url'] ?? '';
$bgColor = $config['background_color'] ?? '#1a1a1a';
$textColor = $config['text_color'] ?? '#6c757d';
$sticky = !empty($config['sticky']);
$showVersion = !isset($config['show_version']) || $config['show_version'];

// Get AtoM version from qubitConfiguration constant
$atomVersion = '';
if ($showVersion && defined('qubitConfiguration::VERSION')) {
    $atomVersion = ' ' . qubitConfiguration::VERSION;
}
?>
<div class="copyright-bar py-2<?php echo $sticky ? ' sticky-bottom' : '' ?>" style="background-color: <?php echo esc_entities($bgColor) ?>; color: <?php echo esc_entities($textColor) ?>;<?php echo $sticky ? ' position: sticky; bottom: 0; z-index: 1000;' : '' ?>">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small">
      <span><?php echo esc_entities($copyright) ?></span>
      <span>
        <?php if (!empty($poweredBy)): ?>
          Powered by <a href="<?php echo esc_entities($poweredByUrl) ?>" class="text-decoration-none" style="color: <?php echo esc_entities($textColor) ?>;" target="_blank"><?php echo esc_entities($poweredBy . $atomVersion) ?></a>
        <?php endif ?>
        <?php if (!empty($poweredBy) && !empty($enhancedBy)): ?>
          <span class="mx-1">|</span>
        <?php endif ?>
        <?php if (!empty($enhancedBy)): ?>
          Enhanced by <a href="<?php echo esc_entities($enhancedByUrl) ?>" class="text-decoration-none" style="color: <?php echo esc_entities($textColor) ?>;" target="_blank"><?php echo esc_entities($enhancedBy) ?></a>
        <?php endif ?>
      </span>
    </div>
  </div>
</div>
