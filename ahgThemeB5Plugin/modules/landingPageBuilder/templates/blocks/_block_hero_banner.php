<?php
/**
 * Hero Banner Block Template
 */
$bgImage = $config['background_image'] ?? '';
$overlay = $config['overlay_opacity'] ?? 0.5;
$height = $config['height'] ?? '400px';
$textAlign = $config['text_align'] ?? 'center';
$titleSize = $config['title_size'] ?? '';
$subtitleSize = $config['subtitle_size'] ?? '';
?>
<div class="hero-banner position-relative d-flex align-items-center justify-content-<?php echo $textAlign ?>"
     style="min-height: <?php echo esc_entities($height) ?>;
            background: url('<?php echo esc_entities($bgImage) ?>') center/cover no-repeat;">
  <div class="position-absolute top-0 start-0 w-100 h-100"
       style="background: rgba(0,0,0,<?php echo $overlay ?>);"></div>
  <div class="container position-relative text-white text-<?php echo $textAlign ?>">
    <?php if (!empty($config['title'])): ?>
      <h1 class="fw-bold mb-3" style="<?php echo $titleSize ? 'font-size: ' . esc_entities($titleSize) . ';' : '' ?>">
        <?php echo esc_entities($config['title']) ?>
      </h1>
    <?php endif ?>
    <?php if (!empty($config['subtitle'])): ?>
      <p class="mb-4" style="<?php echo $subtitleSize ? 'font-size: ' . esc_entities($subtitleSize) . ';' : '' ?>">
        <?php echo esc_entities($config['subtitle']) ?>
      </p>
    <?php endif ?>
    <?php if (!empty($config['cta_text']) && !empty($config['cta_url'])): ?>
      <a href="<?php echo esc_entities($config['cta_url']) ?>" class="btn btn-primary btn-lg">
        <?php echo esc_entities($config['cta_text']) ?>
      </a>
    <?php endif ?>
  </div>
</div>
