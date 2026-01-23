<?php
/**
 * 2 Column Row Block Template
 * With Column Span Support for Nested Blocks
 */
$gap = $config['gap'] ?? '30px';
$stackMobile = $config['stack_mobile'] ?? true;
$col1Width = str_replace('%', '', $config['col1_width'] ?? '50');
$col2Width = str_replace('%', '', $config['col2_width'] ?? '50');

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
$colMap = ['15' => '2', '20' => '2', '25' => '3', '33' => '4', '40' => '5', '50' => '6', '60' => '7', '66' => '8', '75' => '9', '80' => '10', '85' => '10'];
$col1Class = 'col-md-' . ($colMap[$col1Width] ?? '6');
$col2Class = 'col-md-' . ($colMap[$col2Width] ?? '6');
?>
<div class="row-2-col">
  <div class="row g-4">
    <!-- Column 1 -->
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> <?php echo $col1Class ?>">
      <?php if (!empty($col1Blocks)): ?>
        <div class="row g-3">
        <?php foreach ($col1Blocks as $childBlock):
          $childConfig = is_object($childBlock) ? ($childBlock->config ?? []) : ($childBlock['config'] ?? []);
          $childMachineName = is_object($childBlock) ? ($childBlock->machine_name ?? '') : ($childBlock['machine_name'] ?? '');
          $childColSpan = is_object($childBlock) ? ($childBlock->col_span ?? 12) : ($childBlock['col_span'] ?? 12);
          // Ensure col_span is valid (1-12), default to 12 (full width within column)
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
          <small>Column 1 (empty)</small>
        </div>
      <?php endif ?>
    </div>
    
    <!-- Column 2 -->
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> <?php echo $col2Class ?>">
      <?php if (!empty($col2Blocks)): ?>
        <div class="row g-3">
        <?php foreach ($col2Blocks as $childBlock):
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
          <small>Column 2 (empty)</small>
        </div>
      <?php endif ?>
    </div>
  </div>
</div>
