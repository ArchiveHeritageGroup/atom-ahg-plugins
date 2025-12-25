<?php
/**
 * Nested block display within columns (editor view)
 */
$config = is_array($block->config) ? $block->config : [];
?>
<div class="nested-block card mb-2" data-block-id="<?php echo $block->id ?>">
  <div class="card-header py-1 px-2 d-flex align-items-center bg-white">
    <span class="drag-handle me-2" style="cursor: grab;">☰</span>
    <span class="small flex-grow-1"><?php echo $block->type_label ?></span>
    <div class="btn-group btn-group-sm">
      <button type="button" class="btn btn-link btn-sm p-0 px-1 btn-edit text-primary" title="Edit">✏️</button>
      <button type="button" class="btn btn-link btn-sm p-0 px-1 btn-delete text-danger" title="Delete">🗑</button>
    </div>
  </div>
  <div class="card-body py-2 px-2 bg-light small">
    <?php 
    switch ($block->machine_name):
      case 'text_content':
        echo esc_entities(substr(strip_tags($config['content'] ?? ''), 0, 40)) . '...';
        break;
      case 'search_box':
        echo '🔍 Search Box';
        break;
      case 'statistics':
        echo '📊 ' . count($config['stats'] ?? []) . ' stats';
        break;
      case 'recent_items':
        echo '🕒 Recent Items';
        break;
      case 'quick_links':
        echo '🔗 ' . count($config['links'] ?? []) . ' links';
        break;
      default:
        echo $block->type_label;
    endswitch;
    ?>
  </div>
</div>
