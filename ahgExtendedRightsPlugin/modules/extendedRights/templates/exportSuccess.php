<!-- Tom Select CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-download me-2"></i><?php echo __('Export Rights Data'); ?></h1>
  
  <div class="row">
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Export Single Object'); ?></h5>
        </div>
        <div class="card-body">
          <form method="get" action="<?php echo url_for(['module' => 'extendedRights', 'action' => 'export']); ?>">
            <div class="mb-3">
              <label for="single_id" class="form-label"><?php echo __('Search and select object'); ?></label>
              <select name="id" id="single_id" class="form-select" placeholder="<?php echo __('Type to search...'); ?>">
                <option value=""><?php echo __('-- Select an object --'); ?></option>
                <?php if (isset($topLevelRecords) && count($topLevelRecords) > 0): ?>
                  <?php foreach ($topLevelRecords as $record): ?>
                    <option value="<?php echo $record->id; ?>">
                      <?php echo htmlspecialchars($record->title ?? 'Untitled'); ?>
                      <?php if (!empty($record->identifier)): ?> [<?php echo $record->identifier; ?>]<?php endif; ?>
                      <?php if (!empty($record->level)): ?> - <?php echo $record->level; ?><?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label"><?php echo __('Format'); ?></label>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="format" id="format_csv" value="csv" checked>
                <label class="form-check-label" for="format_csv">CSV</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="format" id="format_jsonld" value="json-ld">
                <label class="form-check-label" for="format_jsonld">JSON-LD</label>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-download me-1"></i><?php echo __('Export'); ?>
            </button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Bulk Export'); ?></h5>
        </div>
        <div class="card-body">
          <form method="get" action="<?php echo url_for(['module' => 'extendedRights', 'action' => 'export']); ?>">
            <input type="hidden" name="format" value="csv">
            <div class="mb-3">
              <label for="bulk_select" class="form-label"><?php echo __('Search and select multiple objects'); ?></label>
              <select name="ids[]" id="bulk_select" multiple class="form-select" placeholder="<?php echo __('Type to search...'); ?>">
                <?php if (isset($topLevelRecords) && count($topLevelRecords) > 0): ?>
                  <?php foreach ($topLevelRecords as $record): ?>
                    <option value="<?php echo $record->id; ?>">
                      <?php echo htmlspecialchars($record->title ?? 'Untitled'); ?>
                      <?php if (!empty($record->identifier)): ?> [<?php echo $record->identifier; ?>]<?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
              <small class="text-muted"><?php echo __('Leave empty to export all objects with rights.'); ?></small>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-download me-1"></i><?php echo __('Export as CSV'); ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Export Statistics'); ?></h5>
    </div>
    <div class="card-body">
      <?php 
      $totalWithRights = is_array($stats) ? ($stats['total_with_rights'] ?? 0) : ($stats->total_with_rights ?? 0);
      $inheritedRights = is_array($stats) ? ($stats['inherited_rights'] ?? 0) : ($stats->inherited_rights ?? 0);
      ?>
      <p><?php echo __('Total objects with extended rights'); ?>: <strong><?php echo number_format($totalWithRights); ?></strong></p>
      <p><?php echo __('Objects with inherited rights'); ?>: <strong><?php echo number_format($inheritedRights); ?></strong></p>
    </div>
  </div>

  <div class="mt-3">
    <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'dashboard']); ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard'); ?>
    </a>
  </div>
</main>

<!-- Tom Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Single object select
    new TomSelect('#single_id', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: '<?php echo __('Type to search for an object...'); ?>'
    });

    // Bulk select with multiple
    new TomSelect('#bulk_select', {
        plugins: ['remove_button', 'clear_button'],
        maxItems: null,
        create: false,
        placeholder: '<?php echo __('Type to search for objects...'); ?>',
        render: {
            option: function(data, escape) {
                return '<div class="py-1">' + escape(data.text) + '</div>';
            },
            item: function(data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            }
        }
    });
});
</script>
