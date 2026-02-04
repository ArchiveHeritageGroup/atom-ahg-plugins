<?php
/**
 * User Dashboard Display
 */
use_helper('Text');

// Get raw blocks if escaped
$rawBlocks = $blocks instanceof sfOutputEscaperIteratorDecorator ? $blocks->getRawValue() : $blocks;
?>

<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h4 mb-1"><?php echo esc_entities($page->name) ?></h1>
      <small class="text-muted">Your personal dashboard</small>
    </div>
    <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardEdit']) ?>"
       class="btn btn-outline-primary btn-sm">
      <i class="bi bi-pencil"></i> Customize Dashboard
    </a>
  </div>
</div>

<div class="landing-page user-dashboard" data-page-id="<?php echo $page->id ?>">
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
          if (!empty($currentGroup)) {
              $blockGroups[] = ['type' => 'row', 'blocks' => $currentGroup];
              $currentGroup = [];
              $currentColTotal = 0;
          }
          $blockGroups[] = ['type' => 'single', 'blocks' => [$rawBlock]];
      } else {
          if ($currentColTotal + $colSpan > 12) {
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
          }
          ?>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php else:
      $rawBlock = $group['blocks'][0];
      $config = is_array($rawBlock->config) ? $rawBlock->config : [];
      $computedData = $rawBlock->computed_data ?? null;

      $sectionClasses = ['landing-block', 'landing-block-' . $rawBlock->machine_name];
      if (!empty($rawBlock->css_classes)) {
          $sectionClasses[] = $rawBlock->css_classes;
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
      }
      ?>
      <?php if ($rawBlock->machine_name !== 'hero_banner' && $containerClass !== 'fluid'): ?>
      </div>
      <?php endif ?>
    </section>
  <?php endif ?>
  <?php endforeach ?>
</div>

<?php if (empty($rawBlocks) || count($rawBlocks) === 0): ?>
<div class="container py-5 text-center">
  <i class="bi bi-grid-3x3-gap display-1 text-muted"></i>
  <h3 class="mt-3 text-muted">Your Dashboard is Empty</h3>
  <p class="text-muted">Add blocks to customize your personal dashboard</p>
  <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'myDashboardEdit']) ?>"
     class="btn btn-primary btn-lg mt-2">
    <i class="bi bi-pencil"></i> Customize Dashboard
  </a>
</div>
<?php endif ?>
