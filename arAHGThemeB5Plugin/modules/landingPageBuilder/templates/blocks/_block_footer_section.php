<?php
/**
 * Footer Section Block Template
 */
$showLogo = $config['show_logo'] ?? false;
$copyright = $config['copyright_text'] ?? '© ' . date('Y') . ' All Rights Reserved';
$columns = (int)($config['columns'] ?? 3);
$bgColor = $config['background_color'] ?? '#212529';
$textColor = $config['text_color'] ?? '#ffffff';

$col1Title = $config['col1_title'] ?? 'About';
$col1Content = $config['col1_content'] ?? '';
$col2Title = $config['col2_title'] ?? 'Quick Links';
$col3Title = $config['col3_title'] ?? 'Contact';
$col3Content = $config['col3_content'] ?? '';
?>

<footer class="landing-footer py-5" style="background-color: <?php echo esc_entities($bgColor) ?>; color: <?php echo esc_entities($textColor) ?>;">
  <div class="container">
    <div class="row g-4">
      <?php if ($columns >= 1): ?>
        <div class="col-md-<?php echo 12 / $columns ?>">
          <?php if ($showLogo): ?>
            <div class="mb-3">
              <?php if (file_exists(sfConfig::get('sf_upload_dir').'/logos/logo.png')): ?>
                <img src="/uploads/logos/logo.png" alt="Logo" style="max-height: 40px; filter: brightness(0) invert(1);">
              <?php endif ?>
            </div>
          <?php endif ?>
          <?php if (!empty($col1Title)): ?>
            <h5 class="mb-3"><?php echo esc_entities($col1Title) ?></h5>
          <?php endif ?>
          <?php if (!empty($col1Content)): ?>
            <div class="small opacity-75"><?php echo $col1Content ?></div>
          <?php endif ?>
        </div>
      <?php endif ?>
      
      <?php if ($columns >= 2): ?>
        <div class="col-md-<?php echo 12 / $columns ?>">
          <?php if (!empty($col2Title)): ?>
            <h5 class="mb-3"><?php echo esc_entities($col2Title) ?></h5>
          <?php endif ?>
          <ul class="list-unstyled small">
            <li class="mb-2"><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>" class="text-decoration-none" style="color: <?php echo $textColor ?>; opacity: 0.75;">Browse Collections</a></li>
            <li class="mb-2"><a href="<?php echo url_for(['module' => 'repository', 'action' => 'browse']) ?>" class="text-decoration-none" style="color: <?php echo $textColor ?>; opacity: 0.75;">Repositories</a></li>
            <li class="mb-2"><a href="<?php echo url_for(['module' => 'search', 'action' => 'advanced']) ?>" class="text-decoration-none" style="color: <?php echo $textColor ?>; opacity: 0.75;">Advanced Search</a></li>
            <li class="mb-2"><a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'about']) ?>" class="text-decoration-none" style="color: <?php echo $textColor ?>; opacity: 0.75;">About Us</a></li>
          </ul>
        </div>
      <?php endif ?>
      
      <?php if ($columns >= 3): ?>
        <div class="col-md-<?php echo 12 / $columns ?>">
          <?php if (!empty($col3Title)): ?>
            <h5 class="mb-3"><?php echo esc_entities($col3Title) ?></h5>
          <?php endif ?>
          <?php if (!empty($col3Content)): ?>
            <div class="small opacity-75"><?php echo $col3Content ?></div>
          <?php else: ?>
            <address class="small opacity-75">
              <p class="mb-1">Email: info@example.com</p>
              <p class="mb-1">Phone: +27 12 345 6789</p>
            </address>
          <?php endif ?>
        </div>
      <?php endif ?>
    </div>
    
    <hr class="my-4 opacity-25">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small opacity-75">
      <p class="mb-2 mb-md-0"><?php echo esc_entities($copyright) ?></p>
      <p class="mb-0">Powered by <a href="https://accesstomemory.org" class="text-decoration-none" style="color: <?php echo $textColor ?>;">AtoM</a></p>
    </div>
  </div>
</footer>
