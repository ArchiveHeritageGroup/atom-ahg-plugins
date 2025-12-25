<?php
/**
 * Search Box Block Template
 */
$placeholder = $config['placeholder'] ?? 'Search the archive...';
$showAdvanced = $config['show_advanced'] ?? true;
$style = $config['style'] ?? 'default';

$inputClass = 'form-control';
if ($style === 'large') {
    $inputClass .= ' form-control-lg';
}
?>

<div class="search-box-block <?php echo $style === 'large' ? 'py-4' : '' ?>">
  <form action="<?php echo url_for(['module' => 'search', 'action' => 'index']) ?>" method="get">
    <div class="<?php echo $style === 'large' ? 'input-group input-group-lg' : 'input-group' ?>">
      <input type="text" name="query" class="<?php echo $inputClass ?>" 
             placeholder="<?php echo esc_entities($placeholder) ?>"
             aria-label="Search">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-search"></i>
        <?php if ($style !== 'minimal'): ?>
          <span class="d-none d-md-inline ms-1">Search</span>
        <?php endif ?>
      </button>
    </div>
    
    <?php if ($showAdvanced): ?>
      <div class="text-<?php echo $style === 'large' ? 'center' : 'end' ?> mt-2">
        <a href="<?php echo url_for(['module' => 'search', 'action' => 'advanced']) ?>" class="small">
          <i class="bi bi-sliders"></i> Advanced search
        </a>
      </div>
    <?php endif ?>
  </form>
</div>
