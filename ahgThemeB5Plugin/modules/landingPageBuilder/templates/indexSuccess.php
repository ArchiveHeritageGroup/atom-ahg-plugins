<?php
/**
 * Landing Page Public Display
 */
use_helper('Text');
$isPreview = $isPreview ?? false;

// Get raw blocks if escaped
$rawBlocks = $blocks instanceof sfOutputEscaperIteratorDecorator ? $blocks->getRawValue() : $blocks;
?>
<?php if ($isPreview): ?>
<div class="alert alert-warning alert-dismissible fade show m-3" role="alert">
  <i class="bi bi-eye"></i> <strong>Preview Mode</strong> - This is how the page will appear to visitors.
  Hidden blocks are shown with reduced opacity.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>
<div class="landing-page" data-page-id="<?php echo $page->id ?>">
  <?php foreach ($rawBlocks as $block): ?>
    <?php
    // Get raw block if escaped
    $rawBlock = $block instanceof sfOutputEscaperObjectDecorator ? $block->getRawValue() : $block;
    $config = is_array($rawBlock->config) ? $rawBlock->config : [];
    error_log("INDEX DEBUG FIXED: block=" . $rawBlock->machine_name . " config=" . json_encode($config));
    $computedData = $rawBlock->computed_data ?? null;
    // Build section classes
    $sectionClasses = ['landing-block', 'landing-block-' . $rawBlock->machine_name];
    if (!empty($rawBlock->css_classes)) {
        $sectionClasses[] = $rawBlock->css_classes;
    }
    if (!$rawBlock->is_visible && $isPreview) {
        $sectionClasses[] = 'opacity-50';
    }
    $sectionClasses[] = 'py-' . ($rawBlock->padding_top ?? '3');
    // Build section styles
    $sectionStyles = [];
    if (!empty($rawBlock->background_color) && $rawBlock->background_color !== '#ffffff') {
        $sectionStyles[] = 'background-color: ' . $rawBlock->background_color;
    }
    if (!empty($rawBlock->text_color) && $rawBlock->text_color !== '#212529') {
        $sectionStyles[] = 'color: ' . $rawBlock->text_color;
    }
    $containerClass = $rawBlock->container_type ?? 'container';
    ?>
    <section class="<?php echo implode(' ', $sectionClasses) ?>"
             style="<?php echo implode('; ', $sectionStyles) ?>"
             data-block-id="<?php echo $rawBlock->id ?>">
      <?php if ($rawBlock->machine_name !== 'hero_banner' && $containerClass !== 'fluid'): ?>
      <div class="<?php echo $containerClass ?>">
      <?php endif ?>
      <?php
      // Include the appropriate block template
      $templateFile = '_block_' . $rawBlock->machine_name . '.php';
      $templatePath = dirname(__FILE__) . '/blocks/' . $templateFile;
      if (file_exists($templatePath)) {
          $data = $computedData;
          $block = $rawBlock;
          include $templatePath;
      } else {
          echo '<div class="alert alert-warning">Block template not found: ' . esc_entities($rawBlock->machine_name) . '</div>';
      }
      ?>
      <?php if ($rawBlock->machine_name !== 'hero_banner' && $containerClass !== 'fluid'): ?>
      </div>
      <?php endif ?>
    </section>
  <?php endforeach ?>
</div>
