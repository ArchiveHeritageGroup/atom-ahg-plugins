<?php
/**
 * Block card partial for the editor canvas
 */
$hiddenClass = $block->is_visible ? '' : 'block-hidden';
$config = is_array($block->config) ? $block->config : [];

// Check if this is a column layout block
$isColumnLayout = in_array($block->machine_name, ['row_2_col', 'row_3_col']);
?>

<div class="block-card card mb-3 <?php echo $hiddenClass ?>" data-block-id="<?php echo $block->id ?>">
  <div class="card-header d-flex align-items-center py-2 cursor-grab block-handle bg-white">
    <span class="me-2 text-muted">☰</span>
    <span class="block-label flex-grow-1 fw-medium">
      <?php echo $block->title ?? $block->type_label ?>
    </span>
    <div class="block-actions btn-group btn-group-sm">
      <button type="button" class="btn btn-sm <?php echo $block->is_visible ? 'btn-outline-secondary' : 'btn-warning' ?> btn-visibility" 
              title="<?php echo $block->is_visible ? 'Hide' : 'Show' ?>">
        <?php echo $block->is_visible ? '👁' : '🚫' ?>
      </button>
      <button type="button" class="btn btn-sm btn-outline-primary btn-edit" title="Edit">
        ✏️
      </button>
      <button type="button" class="btn btn-sm btn-outline-secondary btn-duplicate" title="Duplicate">
        📋
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger btn-delete" title="Delete">
        🗑
      </button>
    </div>
  </div>
  
  <div class="card-body block-preview p-3 bg-light">
    <?php if ($isColumnLayout): ?>
      <?php 
      // Render actual column layout with drop zones
      $numCols = $block->machine_name === 'row_3_col' ? 3 : 2;
      $childBlocks = $block->child_blocks ?? [];
      ?>
      <div class="row g-2">
        <?php for ($i = 1; $i <= $numCols; $i++): ?>
          <?php 
          $colSlot = 'col' . $i;
          $colBlocks = array_filter(is_array($childBlocks) ? $childBlocks : iterator_to_array($childBlocks), 
                                    fn($b) => ($b->column_slot ?? '') === $colSlot);
          ?>
          <div class="col">
            <div class="column-drop-zone border border-2 border-dashed rounded p-2 text-center" 
                 data-parent-block="<?php echo $block->id ?>" 
                 data-column="<?php echo $colSlot ?>"
                 style="min-height: 80px; background: #fff;">
              <?php if (empty($colBlocks)): ?>
                <div class="empty-column text-muted py-2">
                  <small>⬇ Col <?php echo $i ?></small>
                </div>
              <?php else: ?>
                <?php foreach ($colBlocks as $childBlock): ?>
                  <div class="nested-block card mb-1" data-block-id="<?php echo $childBlock->id ?>" draggable="true">
                    <div class="card-body py-1 px-2 small d-flex align-items-center">
                      <span class="drag-handle me-1" style="cursor: grab;">☰</span>
                      <span class="flex-grow-1 text-truncate"><?php echo $childBlock->title ?: $childBlock->type_label ?></span>
                      <button type="button" class="btn btn-link btn-sm p-0 px-1 btn-edit-nested text-primary" 
                              data-block-id="<?php echo $childBlock->id ?>" title="Edit">✏️</button>
                      <button type="button" class="btn btn-link btn-sm p-0 btn-delete-nested text-danger" 
                              data-block-id="<?php echo $childBlock->id ?>" title="Delete">🗑</button>
                    </div>
                  </div>
                <?php endforeach ?>
              <?php endif ?>
            </div>
          </div>
        <?php endfor ?>
      </div>
    <?php else: ?>
      <small class="text-muted d-block mb-1"><?php echo $block->type_label ?></small>
      <?php 
      // Show brief config summary for non-column blocks
      switch ($block->machine_name):
        case 'hero_banner':
          echo '<strong>' . esc_entities($config['title'] ?? 'Hero Title') . '</strong>';
          break;
        case 'search_box':
          echo '🔍 "' . esc_entities($config['placeholder'] ?? 'Search...') . '"';
          break;
        case 'browse_panels':
          $panelCount = count($config['panels'] ?? []);
          echo '📂 ' . $panelCount . ' browse panels';
          break;
        case 'recent_items':
          echo '🕒 ' . esc_entities($config['title'] ?? 'Recent Items') . ' (' . ($config['limit'] ?? 6) . ')';
          break;
        case 'statistics':
          $statCount = count($config['stats'] ?? []);
          echo '📊 ' . esc_entities($config['title'] ?? 'Statistics') . ' (' . $statCount . ')';
          break;
        case 'text_content':
          echo '📝 ' . esc_entities(substr(strip_tags($config['content'] ?? 'Text content'), 0, 50)) . '...';
          break;
        case 'holdings_list':
          echo '📚 ' . esc_entities($config['title'] ?? 'Holdings') . ' (' . ($config['limit'] ?? 10) . ')';
          break;
        case 'image_carousel':
          $imgCount = count($config['images'] ?? []);
          echo '🖼 ' . $imgCount . ' images';
          break;
        case 'quick_links':
          $linkCount = count($config['links'] ?? []);
          echo '🔗 ' . $linkCount . ' links';
          break;
        case 'header_section':
          echo '⬆ Header';
          break;
        case 'footer_section':
          echo '⬇ Footer';
          break;
        case 'row_1_col':
          echo '▭ 1 Column Layout';
          break;
        case 'divider':
          echo '― Divider (' . ($config['style'] ?? 'line') . ')';
          break;
        case 'spacer':
          echo '↕ Spacer (' . ($config['height'] ?? '50px') . ')';
          break;
        default:
          echo $block->type_label;
      endswitch;
      ?>
    <?php endif ?>
  </div>
</div>
