<?php
/**
 * 2 Column Row Block Template
 */
$gap = $config['gap'] ?? '30px';
$stackMobile = $config['stack_mobile'] ?? true;
$col1Width = $config['col1_width'] ?? '50';
$col2Width = $config['col2_width'] ?? '50';

// Handle various types of child_blocks
$childBlocks = $block->child_blocks ?? [];
if ($childBlocks instanceof sfOutputEscaperIteratorDecorator) {
    $childBlocks = $childBlocks->getRawValue();
}
if ($childBlocks instanceof \Illuminate\Support\Collection) {
    $childBlocks = $childBlocks->toArray();
}
if (!is_array($childBlocks)) {
    $childBlocks = [];
}

$col1Blocks = array_filter($childBlocks, fn($b) => (is_object($b) ? ($b->column_slot ?? '') : ($b['column_slot'] ?? '')) === 'col1');
$col2Blocks = array_filter($childBlocks, fn($b) => (is_object($b) ? ($b->column_slot ?? '') : ($b['column_slot'] ?? '')) === 'col2');

$isEditorMode = isset($isPreview) && $isPreview === true;
if (!$isEditorMode && empty($col1Blocks) && empty($col2Blocks)) {
    return;
}

// Map percentage to Bootstrap columns
$colMap = ['25' => '3', '33' => '4', '50' => '6', '66' => '8', '75' => '9'];
$col1Class = 'col-md-' . ($colMap[$col1Width] ?? '6');
$col2Class = 'col-md-' . ($colMap[$col2Width] ?? '6');
?>
<div class="row-2-col">
  <div class="row g-4">
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> <?php echo $col1Class ?>">
      <?php if (!empty($col1Blocks)): ?>
        <?php foreach ($col1Blocks as $childBlock):
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
          <small>Column 1 (empty)</small>
        </div>
      <?php endif ?>
    </div>
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> <?php echo $col2Class ?>">
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
