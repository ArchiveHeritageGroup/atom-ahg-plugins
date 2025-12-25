<?php
/**
 * 2 Column Row Block Template
 * Shows drop zones in editor, clean output in preview/public
 */
$col1Width = $config['col1_width'] ?? '50%';
$gap = $config['gap'] ?? '30px';
$stackMobile = $config['stack_mobile'] ?? true;

$col1Pct = (int)str_replace('%', '', $col1Width);
$col1Md = round($col1Pct / 100 * 12);
$col2Md = 12 - $col1Md;

// Get child blocks
$childBlocks = $block->child_blocks ?? [];
if (!is_array($childBlocks) && $childBlocks instanceof \Illuminate\Support\Collection) {
    $childBlocks = $childBlocks->toArray();
}
$col1Blocks = array_filter($childBlocks, fn($b) => (is_object($b) ? ($b->column_slot ?? '') : ($b['column_slot'] ?? '')) === 'col1');
$col2Blocks = array_filter($childBlocks, fn($b) => (is_object($b) ? ($b->column_slot ?? '') : ($b['column_slot'] ?? '')) === 'col2');

// Check if in editor/preview mode (presence of isPreview variable)
$isEditorMode = isset($isPreview) && $isPreview === true;

// For public view: don't show empty column blocks
if (!$isEditorMode && empty($col1Blocks) && empty($col2Blocks)) {
    return; // Don't render anything
}
?>

<div class="row-2-col">
  <div class="row g-4">
    <!-- Column 1 -->
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> col-md-<?php echo $col1Md ?>">
      <?php if (!empty($col1Blocks)): ?>
        <?php foreach ($col1Blocks as $childBlock): 
          $childConfig = is_object($childBlock) ? ($childBlock->config ?? []) : ($childBlock['config'] ?? []);
          $childMachineName = is_object($childBlock) ? ($childBlock->machine_name ?? '') : ($childBlock['machine_name'] ?? '');
          if (!is_array($childConfig)) $childConfig = json_decode($childConfig, true) ?? [];
          
          // Render child block
          $templateFile = dirname(__FILE__) . '/_block_' . $childMachineName . '.php';
          if (file_exists($templateFile)) {
              $config = $childConfig;
              $block = $childBlock;
              include $templateFile;
          }
        endforeach ?>
      <?php elseif ($isEditorMode): ?>
        <div class="empty-column-preview text-center text-muted py-4 bg-light rounded border border-dashed">
          <small>Column 1 (empty)</small>
        </div>
      <?php endif ?>
    </div>
    
    <!-- Column 2 -->
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> col-md-<?php echo $col2Md ?>">
      <?php if (!empty($col2Blocks)): ?>
        <?php foreach ($col2Blocks as $childBlock): 
          $childConfig = is_object($childBlock) ? ($childBlock->config ?? []) : ($childBlock['config'] ?? []);
          $childMachineName = is_object($childBlock) ? ($childBlock->machine_name ?? '') : ($childBlock['machine_name'] ?? '');
          if (!is_array($childConfig)) $childConfig = json_decode($childConfig, true) ?? [];
          
          $templateFile = dirname(__FILE__) . '/_block_' . $childMachineName . '.php';
          if (file_exists($templateFile)) {
              $config = $childConfig;
              $block = $childBlock;
              include $templateFile;
          }
        endforeach ?>
      <?php elseif ($isEditorMode): ?>
        <div class="empty-column-preview text-center text-muted py-4 bg-light rounded border border-dashed">
          <small>Column 2 (empty)</small>
        </div>
      <?php endif ?>
    </div>
  </div>
</div>
