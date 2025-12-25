<?php
/**
 * 2 Column Row - Public Display (no empty placeholders)
 */
$col1Width = $config['col1_width'] ?? '50%';
$gap = $config['gap'] ?? '30px';
$stackMobile = $config['stack_mobile'] ?? true;

$col1Pct = (int)str_replace('%', '', $col1Width);
$col1Md = round($col1Pct / 100 * 12);
$col2Md = 12 - $col1Md;

// Get child blocks
$childBlocks = $block->child_blocks ?? [];
$col1Blocks = array_filter(is_array($childBlocks) ? $childBlocks : [], fn($b) => ($b->column_slot ?? '') === 'col1');
$col2Blocks = array_filter(is_array($childBlocks) ? $childBlocks : [], fn($b) => ($b->column_slot ?? '') === 'col2');

// Don't render if completely empty
if (empty($col1Blocks) && empty($col2Blocks)) return;
?>

<div class="row-2-col">
  <div class="row g-4">
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> col-md-<?php echo $col1Md ?>">
      <?php foreach ($col1Blocks as $childBlock): ?>
        <?php include dirname(__FILE__) . '/_block_' . $childBlock->machine_name . '.php'; ?>
      <?php endforeach ?>
    </div>
    <div class="<?php echo $stackMobile ? 'col-12' : '' ?> col-md-<?php echo $col2Md ?>">
      <?php foreach ($col2Blocks as $childBlock): ?>
        <?php include dirname(__FILE__) . '/_block_' . $childBlock->machine_name . '.php'; ?>
      <?php endforeach ?>
    </div>
  </div>
</div>
