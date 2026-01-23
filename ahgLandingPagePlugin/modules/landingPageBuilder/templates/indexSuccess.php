<?php
/**
 * Landing Page Public Display
 * With Column Span Support
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
  <?php 
  // Group consecutive blocks that have col_span < 12 into rows
  $blockGroups = [];
  $currentGroup = [];
  $currentColTotal = 0;
  
  foreach ($rawBlocks as $block) {
      $rawBlock = $block instanceof sfOutputEscaperObjectDecorator ? $block->getRawValue() : $block;
      $colSpan = (int)($rawBlock->col_span ?? 12) ?: 12;
      
      // Full-width blocks or row containers get their own group
      if ($colSpan >= 12 || in_array($rawBlock->machine_name, ['hero_banner', 'row_2_col', 'row_3_col'])) {
          // Save current group if not empty
          if (!empty($currentGroup)) {
              $blockGroups[] = ['type' => 'row', 'blocks' => $currentGroup];
              $currentGroup = [];
              $currentColTotal = 0;
          }
          $blockGroups[] = ['type' => 'single', 'blocks' => [$rawBlock]];
      } else {
          // Check if adding this block would exceed 12 columns
          if ($currentColTotal + $colSpan > 12) {
              // Start new row
              if (!empty($currentGroup)) {
                  $blockGroups[] = ['type' => 'row', 'blocks' => $currentGroup];
              }
              $currentGroup = [$rawBlock];
              $currentColTotal = $colSpan;
          } else {
              $currentGroup[] = $rawBlock;
              $currentColTotal += $colSpan;
          }
      }
  }
  
  // Don't forget the last group
  if (!empty($currentGroup)) {
      $blockGroups[] = ['type' => 'row', 'blocks' => $currentGroup];
  }
  
  // Render block groups
  foreach ($blockGroups as $group):
      if ($group['type'] === 'row' && count($group['blocks']) > 1):
  ?>
  <div class="container">
    <div class="row g-4">
      <?php foreach ($group['blocks'] as $rawBlock): 
          $colSpan = (int)($rawBlock->col_span ?? 12) ?: 12;
          $config = is_array($rawBlock->config) ? $rawBlock->config : [];
          $computedData = $rawBlock->computed_data ?? null;
          
          $blockClasses = ['landing-block', 'landing-block-' . $rawBlock->machine_name];
          if (!empty($rawBlock->css_classes)) {
              $blockClasses[] = $rawBlock->css_classes;
          }
          if (!$rawBlock->is_visible && $isPreview) {
              $blockClasses[] = 'opacity-50';
          }
          $blockClasses[] = 'py-' . ($rawBlock->padding_top ?? '3');
          
          $blockStyles = [];
          if (!empty($rawBlock->background_color) && $rawBlock->background_color !== '#ffffff') {
              $blockStyles[] = 'background-color: ' . $rawBlock->background_color;
          }
          if (!empty($rawBlock->text_color) && $rawBlock->text_color !== '#212529') {
              $blockStyles[] = 'color: ' . $rawBlock->text_color;
          }
      ?>
      <div class="col-md-<?php echo $colSpan ?>">
        <div class="<?php echo implode(' ', $blockClasses) ?>"
             style="<?php echo implode('; ', $blockStyles) ?>"
             data-block-id="<?php echo $rawBlock->id ?>">
          <?php
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
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php else:
      // Single block (full width or special type)
      $rawBlock = $group['blocks'][0];
      $config = is_array($rawBlock->config) ? $rawBlock->config : [];
      $computedData = $rawBlock->computed_data ?? null;
      
      $sectionClasses = ['landing-block', 'landing-block-' . $rawBlock->machine_name];
      if (!empty($rawBlock->css_classes)) {
          $sectionClasses[] = $rawBlock->css_classes;
      }
      if (!$rawBlock->is_visible && $isPreview) {
          $sectionClasses[] = 'opacity-50';
      }
      $sectionClasses[] = 'py-' . ($rawBlock->padding_top ?? '3');
      
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
  <?php endif ?>
  <?php endforeach ?>
</div>
