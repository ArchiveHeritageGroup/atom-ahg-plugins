<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?><h1><i class="fa fa-bookmark me-2"></i><?php echo __('My Saved Searches'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>

<?php if (empty($savedSearches)): ?>
<div class="alert alert-info"><?php echo __('No saved searches yet. Use the "Save This Search" button on search results.'); ?></div>
<?php else: ?>

<div class="table-responsive">
<table class="table table-striped">
  <thead>
    <tr>
      <th><?php echo __('Name'); ?></th>
      <th><?php echo __('Type'); ?></th>
      <th><?php echo __('Notifications'); ?></th>
      <th><?php echo __('Uses'); ?></th>
      <th><?php echo __('Last Used'); ?></th>
      <th><?php echo __('Actions'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($savedSearches as $search): ?>
    <tr>
      <td><strong><?php echo esc_entities($search->name); ?></strong></td>
      <td><code><?php echo esc_entities($search->entity_type); ?></code></td>
      <td>
        <?php if ($search->notify_on_new): ?>
          <span class="badge bg-info"><?php echo ucfirst($search->notification_frequency); ?></span>
        <?php else: ?>
          <span class="text-muted">-</span>
        <?php endif; ?>
      </td>
      <td><?php echo (int)$search->usage_count; ?></td>
      <td><?php echo $search->last_used_at ?: '-'; ?></td>
      <td>
        <?php 
          $params = json_decode($search->search_params, true) ?: [];
          $runUrl = url_for('@glam_browse') . '?' . http_build_query($params);
        ?>
        <a href="<?php echo $runUrl; ?>" class="btn btn-primary btn-sm">
          <i class="fa fa-play"></i> <?php echo __('Run'); ?>
        </a>
        <button type="button" class="btn btn-danger btn-sm" onclick="deleteSearch(<?php echo $search->id; ?>)">
          <i class="fa fa-trash"></i>
        </button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php endif; ?>

<div class="mt-3">
  <a href="<?php echo url_for('@glam_browse'); ?>" class="btn btn-secondary">
    <i class="fa fa-search me-1"></i><?php echo __('Browse Records'); ?>
  </a>
</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function deleteSearch(id) {
  if (!confirm('<?php echo __('Delete this saved search?'); ?>')) return;
  
  fetch('/index.php/searchEnhancement/deleteSavedSearch?id=' + id, {
    method: 'POST',
    headers: {'X-Requested-With': 'XMLHttpRequest'}
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'Error deleting');
    }
  })
  .catch(err => {
    alert('Error: ' + err.message);
  });
}
</script>
<?php end_slot(); ?>
