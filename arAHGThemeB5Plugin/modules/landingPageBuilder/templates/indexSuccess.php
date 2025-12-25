<?php
/**
 * Landing Page Public Display
 */
use_helper('Text');

$isPreview = $isPreview ?? false;
?>

<?php if ($isPreview): ?>
<div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
  <i class="bi bi-eye"></i> <strong>Preview Mode</strong> - This is how the page will appear to visitors.
  Hidden blocks are shown with reduced opacity.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<div class="landing-page" data-page-id="<?php echo $page->id ?>">
  <?php foreach ($blocks as $block): ?>
    <?php 
    $config = is_array($block->config) ? $block->config : [];
    $computedData = $block->computed_data ?? null;
    
    // Build section classes
    $sectionClasses = ['landing-block', 'landing-block-' . $block->machine_name];
    if (!empty($block->css_classes)) {
        $sectionClasses[] = $block->css_classes;
    }
    if (!$block->is_visible && $isPreview) {
        $sectionClasses[] = 'opacity-50';
    }
    $sectionClasses[] = 'py-' . ($block->padding_top ?? '3');
    
    // Build section styles
    $sectionStyles = [];
    if (!empty($block->background_color) && $block->background_color !== '#ffffff') {
        $sectionStyles[] = 'background-color: ' . $block->background_color;
    }
    if (!empty($block->text_color) && $block->text_color !== '#212529') {
        $sectionStyles[] = 'color: ' . $block->text_color;
    }
    
    $containerClass = $block->container_type ?? 'container';
    ?>
    
    <section class="<?php echo implode(' ', $sectionClasses) ?>" 
             style="<?php echo implode('; ', $sectionStyles) ?>"
             data-block-id="<?php echo $block->id ?>">
      
      <?php if ($block->machine_name !== 'hero_banner' && $containerClass !== 'fluid'): ?>
      <div class="<?php echo $containerClass ?>">
      <?php endif ?>
      
      <?php 
      // Include the appropriate block template
      $templateFile = '_block_' . $block->machine_name . '.php';
      $templatePath = dirname(__FILE__) . '/blocks/' . $templateFile;
      
      if (file_exists($templatePath)) {
          $data = $computedData;
          include $templatePath;
      } else {
          echo '<div class="alert alert-warning">Block template not found: ' . esc_entities($block->machine_name) . '</div>';
      }
      ?>
      
      <?php if ($block->machine_name !== 'hero_banner' && $containerClass !== 'fluid'): ?>
      </div>
      <?php endif ?>
      
    </section>
  <?php endforeach ?>
</div>
