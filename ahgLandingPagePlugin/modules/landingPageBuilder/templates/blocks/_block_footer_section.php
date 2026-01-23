<?php
/**
 * Footer Section Block Template - Compact 3 Column
 */
$showLogo = $config['show_logo'] ?? false;
$columns = (int)($config['columns'] ?? 3);
$bgColor = $config['background_color'] ?? '#212529';
$textColor = $config['text_color'] ?? '#ffffff';
$sticky = !empty($config['sticky']);

$col1Title = $config['col1_title'] ?? 'About';
$col1Content = $config['col1_content'] ?? '';
$col2Title = $config['col2_title'] ?? 'Quick Links';
$col3Title = $config['col3_title'] ?? 'Contact';
$col3Content = $config['col3_content'] ?? '';
?>

<footer class="landing-footer py-3<?php echo $sticky ? ' sticky-bottom' : '' ?>" style="background-color: <?php echo esc_entities($bgColor) ?>; color: <?php echo esc_entities($textColor) ?>;<?php echo $sticky ? ' position: sticky; bottom: 0; z-index: 999;' : '' ?>">
  <div class="container">
    <div class="row g-3">
      <?php if ($columns >= 1): ?>
        <div class="col-md-<?php echo 12 / $columns ?>">
          <?php if ($showLogo): ?>
            <div class="mb-2">
              <?php if (file_exists(sfConfig::get('sf_upload_dir').'/logos/logo.png')): ?>
                <img src="/uploads/logos/logo.png" alt="Logo" style="max-height: 30px; filter: brightness(0) invert(1);">
              <?php endif ?>
            </div>
          <?php endif ?>
          <?php if (!empty($col1Title)): ?>
            <h6 class="mb-2" style="color: <?php echo $textColor ?>;"><?php echo esc_entities($col1Title) ?></h6>
          <?php endif ?>
          <?php if (!empty($col1Content)): ?>
            <div class="small opacity-75"><?php echo $col1Content ?></div>
          <?php endif ?>
        </div>
      <?php endif ?>

      <?php if ($columns >= 2): ?>
        <div class="col-md-<?php echo 12 / $columns ?>">
          <?php if (!empty($col2Title)): ?>
            <h6 class="mb-2" style="color: <?php echo $textColor ?>;"><?php echo esc_entities($col2Title) ?></h6>
          <?php endif ?>
          <ul class="list-unstyled small mb-0">
            <li class="mb-1"><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>" class="text-decoration-none opacity-75" style="color: <?php echo $textColor ?>;">Browse Collections</a></li>
            <li class="mb-1"><a href="<?php echo url_for(['module' => 'repository', 'action' => 'browse']) ?>" class="text-decoration-none opacity-75" style="color: <?php echo $textColor ?>;">Repositories</a></li>
            <li class="mb-1"><a href="<?php echo url_for(['module' => 'search', 'action' => 'advanced']) ?>" class="text-decoration-none opacity-75" style="color: <?php echo $textColor ?>;">Advanced Search</a></li>
            <li><a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'about']) ?>" class="text-decoration-none opacity-75" style="color: <?php echo $textColor ?>;">About Us</a></li>
          </ul>
        </div>
      <?php endif ?>

      <?php if ($columns >= 3): ?>
        <div class="col-md-<?php echo 12 / $columns ?>">
          <?php if (!empty($col3Title)): ?>
            <h6 class="mb-2" style="color: <?php echo $textColor ?>;"><?php echo esc_entities($col3Title) ?></h6>
          <?php endif ?>
          <?php if (!empty($col3Content)): ?>
            <div class="small opacity-75"><?php echo $col3Content ?></div>
          <?php else: ?>
            <div class="small opacity-75">
              <p class="mb-1">Email: info@example.com</p>
              <p class="mb-0">Phone: +27 12 345 6789</p>
            </div>
          <?php endif ?>
        </div>
      <?php endif ?>
    </div>
  </div>
</footer>
