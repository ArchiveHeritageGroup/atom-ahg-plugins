<?php
/**
 * Quick Links Block Template
 */
$links = $config['links'] ?? [];
$title = $config['title'] ?? '';
$layout = $config['layout'] ?? 'inline';
$style = $config['style'] ?? 'buttons';
?>

<?php if (!empty($title)): ?>
  <h2 class="h5 mb-3"><?php echo esc_entities($title) ?></h2>
<?php endif ?>

<?php if ($layout === 'grid'): ?>
  <div class="row g-3">
    <?php foreach ($links as $link): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?php echo esc_entities($link['url'] ?? '#') ?>" 
           class="<?php echo $style === 'cards' ? 'card text-decoration-none h-100' : 'btn btn-outline-primary w-100' ?>"
           <?php echo !empty($link['new_window']) ? 'target="_blank"' : '' ?>>
          <?php if ($style === 'cards'): ?>
            <div class="card-body text-center">
              <?php if (!empty($link['icon'])): ?>
                <i class="bi <?php echo $link['icon'] ?> display-6 mb-2"></i>
              <?php endif ?>
              <div><?php echo esc_entities($link['label'] ?? '') ?></div>
            </div>
          <?php else: ?>
            <?php if (!empty($link['icon'])): ?>
              <i class="bi <?php echo $link['icon'] ?> me-1"></i>
            <?php endif ?>
            <?php echo esc_entities($link['label'] ?? '') ?>
          <?php endif ?>
        </a>
      </div>
    <?php endforeach ?>
  </div>
<?php elseif ($layout === 'list'): ?>
  <ul class="list-group">
    <?php foreach ($links as $link): ?>
      <li class="list-group-item">
        <a href="<?php echo esc_entities($link['url'] ?? '#') ?>" 
           class="text-decoration-none"
           <?php echo !empty($link['new_window']) ? 'target="_blank"' : '' ?>>
          <?php if (!empty($link['icon'])): ?>
            <i class="bi <?php echo $link['icon'] ?> me-2"></i>
          <?php endif ?>
          <?php echo esc_entities($link['label'] ?? '') ?>
        </a>
      </li>
    <?php endforeach ?>
  </ul>
<?php else: ?>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($links as $link): ?>
      <?php if ($style === 'buttons'): ?>
        <a href="<?php echo esc_entities($link['url'] ?? '#') ?>" 
           class="btn btn-outline-primary"
           <?php echo !empty($link['new_window']) ? 'target="_blank"' : '' ?>>
          <?php if (!empty($link['icon'])): ?>
            <i class="bi <?php echo $link['icon'] ?> me-1"></i>
          <?php endif ?>
          <?php echo esc_entities($link['label'] ?? '') ?>
        </a>
      <?php else: ?>
        <a href="<?php echo esc_entities($link['url'] ?? '#') ?>" 
           class="text-decoration-none me-3"
           <?php echo !empty($link['new_window']) ? 'target="_blank"' : '' ?>>
          <?php if (!empty($link['icon'])): ?>
            <i class="bi <?php echo $link['icon'] ?> me-1"></i>
          <?php endif ?>
          <?php echo esc_entities($link['label'] ?? '') ?>
        </a>
      <?php endif ?>
    <?php endforeach ?>
  </div>
<?php endif ?>
