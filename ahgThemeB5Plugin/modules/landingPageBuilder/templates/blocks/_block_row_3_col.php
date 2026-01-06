<?php
/**
 * 3 Column Row Block Template
 */
$gap = $config['gap'] ?? '30px';
$stackMobile = $config['stack_mobile'] ?? true;

// Handle various types of child_blocks (array, Collection, or Symfony escaped)
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
$col3Blocks = array_filter($childBlocks, fn($b) => (is_object($b) ? ($b->column_slot ?? '') : ($b['column_slot'] ?? '')) === 'col3');

$isEditorMode = isset($isPreview) && $isPreview === true;
if (!$isEditorMode && empty($col1Blocks) && empty($col2Blocks) && empty($col3Blocks)) {
    return;
}
?>
<div class="row-3-col">
  <div class="row g-4">
    <?php for ($i = 1; $i <= 3; $i++):
      $colBlocks = ${'col' . $i . 'Blocks'};
    ?>
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> col-md-4">
      <?php if (!empty($colBlocks)): ?>
        <?php foreach ($colBlocks as $childBlock):
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
          <small>Column <?php echo $i ?> (empty)</small>
        </div>
      <?php endif ?>
    </div>
    <?php endfor ?>
  </div>
</div>
