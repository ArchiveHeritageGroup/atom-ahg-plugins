<?php decorate_with('layout_1col'); ?>

<?php
  $rawActor         = $sf_data->getRaw('actor');
  $rawFunctionLinks = $sf_data->getRaw('functionLinks');
  $rawRelTypes      = $sf_data->getRaw('relationTypes');

  $actor     = is_object($rawActor) ? $rawActor : (object) $rawActor;
  $links     = is_array($rawFunctionLinks) ? $rawFunctionLinks : [];
  $relTypes  = is_array($rawRelTypes) ? $rawRelTypes : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-sitemap me-2"></i><?php echo __('Function Links'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="/<?php echo $actor->slug ?? ''; ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Functions'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
      <span><i class="fas fa-sitemap me-1"></i><?php echo __('ISDF Function Links'); ?></span>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addFunctionModal">
        <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
      </button>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Function'); ?></th>
            <th><?php echo __('Relation'); ?></th>
            <th><?php echo __('Period'); ?></th>
            <th><?php echo __('Notes'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($links)): ?>
            <tr><td colspan="5" class="text-center text-muted py-3"><?php echo __('No function links.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($links as $link): ?>
              <tr>
                <td>
                  <?php if ($link->function_slug): ?>
                    <a href="/<?php echo htmlspecialchars($link->function_slug); ?>">
                      <?php echo htmlspecialchars($link->function_title ?? 'Function #' . $link->function_id); ?>
                    </a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($link->function_title ?? 'Function #' . $link->function_id); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-info">
                    <?php echo htmlspecialchars($relTypes[$link->relation_type] ?? $link->relation_type); ?>
                  </span>
                </td>
                <td>
                  <?php echo htmlspecialchars($link->date_from ?? ''); ?>
                  <?php if ($link->date_from || $link->date_to): ?> &ndash; <?php endif; ?>
                  <?php echo htmlspecialchars($link->date_to ?? ''); ?>
                </td>
                <td><small><?php echo htmlspecialchars($link->notes ?? ''); ?></small></td>
                <td>
                  <button class="btn btn-sm btn-outline-danger btn-delete-func" data-id="<?php echo $link->id; ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Function Link Modal -->
  <div class="modal fade" id="addFunctionModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Link Function'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Function ID'); ?></label>
            <input type="number" id="func-id" class="form-control" placeholder="<?php echo __('Enter function object ID'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Relation type'); ?></label>
            <select id="func-rel-type" class="form-select">
              <?php foreach ($relTypes as $key => $label): ?>
                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Date from'); ?></label>
              <input type="text" id="func-from" class="form-control" placeholder="YYYY">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Date to'); ?></label>
              <input type="text" id="func-to" class="form-control" placeholder="YYYY">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Notes'); ?></label>
            <textarea id="func-notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="button" class="btn btn-primary" id="btn-save-func">
            <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var actorId = <?php echo (int) $actor->id; ?>;

document.getElementById('btn-save-func').addEventListener('click', function() {
  var data = new FormData();
  data.append('actor_id', actorId);
  data.append('function_id', document.getElementById('func-id').value);
  data.append('relation_type', document.getElementById('func-rel-type').value);
  data.append('date_from', document.getElementById('func-from').value);
  data.append('date_to', document.getElementById('func-to').value);
  data.append('notes', document.getElementById('func-notes').value);

  fetch('<?php echo url_for('@ahg_authority_function_save'); ?>', { method: 'POST', body: data })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.success) location.reload(); });
});

document.querySelectorAll('.btn-delete-func').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('<?php echo __('Delete this function link?'); ?>')) return;
    fetch('/api/authority/function/' + this.dataset.id + '/delete', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
  });
});
</script>
