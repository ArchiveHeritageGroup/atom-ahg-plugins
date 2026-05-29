<?php decorate_with('layout_1col'); ?>

<?php $auth = $sf_data->getRaw('authority'); ?>
<?php $linkedItems = $sf_data->getRaw('linkedItems'); ?>

<?php slot('title'); ?>
  <h1><?php echo esc_entities($auth->heading ?? __('Authority Record')); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3 gap-2">
  <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'edit', 'id' => $auth->id]); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-edit me-2"></i><?php echo __('Edit'); ?>
  </a>
  <a href="<?php echo url_for(['module' => 'authorityControl', 'action' => 'link', 'id' => $auth->id]); ?>" class="btn btn-primary">
    <i class="fas fa-link me-2"></i><?php echo __('Link to Item'); ?>
  </a>
  <form method="post" action="<?php echo url_for(['module' => 'authorityControl', 'action' => 'delete', 'id' => $auth->id]); ?>"
        onsubmit="return confirm('<?php echo __('Delete this authority record? Linked items will be unlinked.'); ?>');">
    <button type="submit" class="btn btn-outline-danger">
      <i class="fas fa-trash me-2"></i><?php echo __('Delete'); ?>
    </button>
  </form>
</div>

<div class="card mb-4">
  <div class="card-header bg-light">
    <h5 class="mb-0"><?php echo __('Details'); ?></h5>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo __('Heading'); ?></dt>
      <dd class="col-sm-9"><?php echo esc_entities($auth->heading ?? ''); ?></dd>

      <dt class="col-sm-3"><?php echo __('Subject Type'); ?></dt>
      <dd class="col-sm-9"><span class="badge bg-secondary"><?php echo esc_entities(ucfirst($auth->subject_type ?? 'topic')); ?></span></dd>

      <dt class="col-sm-3"><?php echo __('Source'); ?></dt>
      <dd class="col-sm-9"><?php echo esc_entities(strtoupper($auth->source ?? '')); ?></dd>

      <?php if (!empty($auth->uri)): ?>
        <dt class="col-sm-3"><?php echo __('URI'); ?></dt>
        <dd class="col-sm-9"><a href="<?php echo esc_entities($auth->uri); ?>" target="_blank" rel="noopener"><?php echo esc_entities($auth->uri); ?></a></dd>
      <?php endif; ?>

      <dt class="col-sm-3"><?php echo __('Linked items'); ?></dt>
      <dd class="col-sm-9"><?php echo (int) ($auth->linked_count ?? 0); ?></dd>
    </dl>
  </div>
</div>

<div class="card">
  <div class="card-header bg-light">
    <h5 class="mb-0"><?php echo __('Linked Library Items'); ?></h5>
  </div>
  <?php if (empty($linkedItems)): ?>
    <div class="card-body">
      <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle me-2"></i><?php echo __('No library items linked to this heading yet.'); ?>
      </div>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Title'); ?></th>
            <th><?php echo __('MARC tag'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($linkedItems as $item): ?>
            <tr>
              <td><?php echo esc_entities($item->title ?? __('(untitled)')); ?></td>
              <td><code><?php echo esc_entities($item->source_tag ?? ''); ?></code></td>
              <td class="text-end">
                <form method="post" action="<?php echo url_for(['module' => 'authorityControl', 'action' => 'unlink', 'linkId' => $item->link_id]); ?>"
                      onsubmit="return confirm('<?php echo __('Remove this link?'); ?>');">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Unlink'); ?>">
                    <i class="fas fa-unlink"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
