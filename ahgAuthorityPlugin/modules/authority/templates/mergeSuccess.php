<?php decorate_with('layout_1col'); ?>

<?php
  $rawActor = $sf_data->getRaw('actor');
  $rawHistory = $sf_data->getRaw('mergeHistory');

  $actor = is_object($rawActor) ? $rawActor : (object) $rawActor;
  $history = is_array($rawHistory) ? $rawHistory : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-code-branch me-2"></i><?php echo __('Merge Authority Records'); ?></h1>
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
      <li class="breadcrumb-item active"><?php echo __('Merge'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-compress-arrows-alt me-1"></i><?php echo __('Merge into: %1%', ['%1%' => htmlspecialchars($actor->name ?? '')]); ?>
    </div>
    <div class="card-body">
      <p class="text-muted"><?php echo __('Select a secondary actor to merge into this record. All relations, resources, contacts, and identifiers will be transferred.'); ?></p>

      <div class="row g-2 mb-3">
        <div class="col-md-6">
          <label class="form-label"><?php echo __('Search for actor to merge'); ?></label>
          <input type="text" id="merge-search" class="form-control" placeholder="<?php echo __('Type actor name...'); ?>">
        </div>
        <div class="col-auto align-self-end">
          <button class="btn btn-primary" id="btn-merge-search">
            <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
          </button>
        </div>
      </div>

      <div id="merge-results" class="d-none mb-3">
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th><?php echo __('Name'); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="merge-results-body"></tbody>
        </table>
      </div>

      <!-- Comparison preview (populated via AJAX) -->
      <div id="merge-comparison" class="d-none"></div>
    </div>
  </div>

  <!-- Merge History -->
  <?php if (!empty($history)): ?>
    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-history me-1"></i><?php echo __('Merge History'); ?>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Records Transferred'); ?></th>
              <th><?php echo __('Date'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h): ?>
              <tr>
                <td><span class="badge bg-secondary"><?php echo ucfirst($h->merge_type); ?></span></td>
                <td>
                  <?php
                    $statusColors = ['pending' => 'warning', 'approved' => 'info', 'completed' => 'success', 'rejected' => 'danger', 'reversed' => 'dark'];
                    $color = $statusColors[$h->status] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($h->status); ?></span>
                </td>
                <td>
                  <?php echo __('Relations: %1%, Resources: %2%, Contacts: %3%, IDs: %4%', [
                    '%1%' => $h->relations_transferred,
                    '%2%' => $h->resources_transferred,
                    '%3%' => $h->contacts_transferred,
                    '%4%' => $h->identifiers_transferred,
                  ]); ?>
                </td>
                <td><?php echo $h->performed_at ?? $h->created_at; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var primaryId = <?php echo (int) $actor->id; ?>;

document.getElementById('btn-merge-search').addEventListener('click', function() {
  var q = document.getElementById('merge-search').value;
  if (!q) return;

  // Simple actor search via autocomplete
  fetch('/api/authority/merge/preview?primary_id=' + primaryId + '&secondary_id=0&q=' + encodeURIComponent(q))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      // For now, show a message that preview requires a specific secondary ID
    });
});
</script>
