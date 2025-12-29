<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?><h1><?php echo __('Search History'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>
<?php if (empty($history)): ?>
<div class="alert alert-info"><?php echo __('No search history yet.'); ?></div>
<?php else: ?>
<div class="list-group">
<?php foreach ($history as $item): ?>
<?php $params = json_decode($item->search_params, true) ?: []; ?>
<a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params); ?>" class="list-group-item list-group-item-action">
  <strong><?php echo esc_entities($item->search_query ?: '(Advanced)'); ?></strong>
  <span class="badge bg-secondary"><?php echo $item->result_count; ?> results</span>
  <br><small class="text-muted"><?php echo $item->created_at; ?></small>
</a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php end_slot(); ?>
