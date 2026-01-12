<?php
/**
 * 1 Column Row Block Template
 * Container for child blocks in a single column
 */

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

// Get blocks in col1
$col1Blocks = array_filter($childBlocks, fn($b) => (is_object($b) ? ($b->column_slot ?? '') : ($b['column_slot'] ?? '')) === 'col1');

$isEditorMode = isset($isPreview) && $isPreview === true;
if (!$isEditorMode && empty($col1Blocks)) {
    return;
}
?>
<div class="row-1-col">
  <?php if (!empty($col1Blocks)): ?>
    <div class="row g-3">
    <?php foreach ($col1Blocks as $childBlock):
      $childConfig = is_object($childBlock) ? ($childBlock->config ?? []) : ($childBlock['config'] ?? []);
      $childMachineName = is_object($childBlock) ? ($childBlock->machine_name ?? '') : ($childBlock['machine_name'] ?? '');
      $childColSpan = is_object($childBlock) ? ($childBlock->col_span ?? 12) : ($childBlock['col_span'] ?? 12);
      $childColSpan = max(1, min(12, (int)$childColSpan ?: 12));
      
      if (!is_array($childConfig)) $childConfig = json_decode($childConfig, true) ?? [];
      $templateFile = dirname(__FILE__) . '/_block_' . $childMachineName . '.php';
      if (file_exists($templateFile)):
    ?>
      <div class="col-md-<?php echo $childColSpan ?>">
        <?php
          $config = $childConfig;
          $data = is_object($childBlock) ? ($childBlock->computed_data ?? null) : ($childBlock['computed_data'] ?? null);
          $block = $childBlock;
          include $templateFile;
        ?>
      </div>
    <?php endif; endforeach ?>
    </div>
  <?php elseif ($isEditorMode): ?>
    <div class="empty-column-preview text-center text-muted py-4 bg-light rounded border border-dashed">
      <small>Add content to this row</small>
    </div>
  <?php endif ?>
</div>
