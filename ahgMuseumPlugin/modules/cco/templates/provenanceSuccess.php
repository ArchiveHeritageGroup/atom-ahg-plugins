<?php
/**
 * Provenance Chain Visualization Template
 */
$timelineData = $sf_data->getRaw('timelineData');
$provenanceChain = $sf_data->getRaw('provenanceChain');
$resource = $sf_data->getRaw('resource');
$canEdit = $sf_data->getRaw('canEdit');
?>

<h1><?php echo __('Provenance History') ?></h1>

<?php if (isset($informationObject)): ?>
<div class="object-info mb-3">
  <p>
    <strong><?php echo $informationObject->identifier ?></strong> -
    <?php echo $informationObject->title ?? $resource->title ?? '' ?>
  </p>
</div>
<?php endif ?>

<!-- Back button - always visible at top -->
<div class="provenance-navigation mb-4">
  <a href="/<?php echo $resource->slug ?>" class="btn btn-outline-primary">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Archival Description') ?>
  </a>
</div>

<!-- Timeline Container -->
<div id="provenance-timeline" class="provenance-timeline-container">
  <div class="timeline-loading">Loading timeline...</div>
</div>

<!-- Provenance Table -->
<div class="provenance-table-section">
  <h2><?php echo __('Ownership History') ?></h2>

  <?php if (!empty($provenanceChain)): ?>
    <table class="table table-striped provenance-table">
      <thead>
        <tr>
          <th width="5%">#</th>
          <th width="25%"><?php echo __('Owner') ?></th>
          <th width="15%"><?php echo __('Location') ?></th>
          <th width="15%"><?php echo __('Period') ?></th>
          <th width="15%"><?php echo __('Transfer') ?></th>
          <th width="10%"><?php echo __('Certainty') ?></th>
          <?php if ($canEdit): ?>
          <th width="15%"><?php echo __('Actions') ?></th>
          <?php endif ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($provenanceChain as $entry): ?>
          <tr class="<?php echo $entry->is_gap ? 'table-warning' : '' ?>">
            <td><?php echo $entry->sequence ?></td>
            <td>
              <strong><?php echo esc_entities($entry->owner_name) ?></strong>
              <?php if ($entry->owner_type && $entry->owner_type !== 'unknown'): ?>
                <br><small class="text-muted"><?php echo $entry->owner_type_label ?></small>
              <?php endif ?>
            </td>
            <td>
              <?php if ($entry->owner_location): ?>
                <?php echo esc_entities($entry->owner_location) ?>
                <?php if ($entry->owner_location_tgn): ?>
                  <br><a href="<?php echo $entry->owner_location_tgn ?>" target="_blank" class="small">
                    <i class="fas fa-external-link-alt"></i> TGN
                  </a>
                <?php endif ?>
              <?php endif ?>
            </td>
            <td><?php echo $entry->date_display ?? '-' ?></td>
            <td><?php echo ucfirst(str_replace('_', ' ', $entry->transfer_type)) ?></td>
            <td>
              <span class="badge bg-secondary"><?php echo ucfirst($entry->certainty) ?></span>
            </td>
            <?php if ($canEdit): ?>
            <td>
              <button class="btn btn-sm btn-outline-primary edit-entry" data-id="<?php echo $entry->id ?>">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger delete-entry" data-id="<?php echo $entry->id ?>">
                <i class="fas fa-trash"></i>
              </button>
            </td>
            <?php endif ?>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-info">
      <?php echo __('No provenance information recorded for this object.') ?>
    </div>
  <?php endif ?>

  <?php if ($canEdit): ?>
  <div class="provenance-actions mt-3">
    <button class="btn btn-primary" id="add-entry">
      <i class="fas fa-plus"></i> <?php echo __('Add Provenance Entry') ?>
    </button>
    <button class="btn btn-secondary" id="export-csv">
      <i class="fas fa-download"></i> <?php echo __('Export CSV') ?>
    </button>
  </div>
  <?php endif ?>
</div>

<!-- Add/Edit Entry Modal -->
<div class="modal fade" id="entry-modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Provenance Entry') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="entry-form">
          <input type="hidden" name="id" id="entry-id">
          <input type="hidden" name="object_id" value="<?php echo $resource->id ?>">

          <div class="row mb-3">
            <div class="col-md-8">
              <label for="owner_name" class="form-label"><?php echo __('Owner Name') ?> *</label>
              <input type="text" class="form-control" name="owner_name" id="owner_name" required>
            </div>
            <div class="col-md-4">
              <label for="owner_type" class="form-label"><?php echo __('Owner Type') ?></label>
              <select class="form-select" name="owner_type" id="owner_type">
                <option value="unknown">Unknown</option>
                <option value="person">Person</option>
                <option value="family">Family</option>
                <option value="dealer">Dealer</option>
                <option value="auction_house">Auction House</option>
                <option value="museum">Museum</option>
                <option value="corporate">Corporate</option>
                <option value="government">Government</option>
                <option value="religious">Religious</option>
                <option value="artist">Artist</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-8">
              <label for="owner_location" class="form-label"><?php echo __('Location') ?></label>
              <input type="text" class="form-control" name="owner_location" id="owner_location" placeholder="City, Country">
            </div>
            <div class="col-md-4">
              <label for="certainty" class="form-label"><?php echo __('Certainty') ?></label>
              <select class="form-select" name="certainty" id="certainty">
                <option value="certain">Certain</option>
                <option value="probable">Probable</option>
                <option value="possible">Possible</option>
                <option value="uncertain">Uncertain</option>
                <option value="unknown">Unknown</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-3">
              <label for="start_date" class="form-label"><?php echo __('Start Date') ?></label>
              <input type="text" class="form-control" name="start_date" id="start_date" placeholder="YYYY">
            </div>
            <div class="col-md-3">
              <label for="end_date" class="form-label"><?php echo __('End Date') ?></label>
              <input type="text" class="form-control" name="end_date" id="end_date" placeholder="YYYY">
            </div>
            <div class="col-md-6">
              <label for="transfer_type" class="form-label"><?php echo __('Transfer Method') ?></label>
              <select class="form-select" name="transfer_type" id="transfer_type">
                <option value="unknown">Unknown</option>
                <option value="sale">Sale</option>
                <option value="auction">Auction</option>
                <option value="gift">Gift</option>
                <option value="bequest">Bequest</option>
                <option value="inheritance">Inheritance</option>
                <option value="commission">Commission</option>
                <option value="exchange">Exchange</option>
                <option value="seizure">Seizure</option>
                <option value="restitution">Restitution</option>
                <option value="created">Created</option>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="sale_price" class="form-label"><?php echo __('Sale Price') ?></label>
              <input type="number" class="form-control" name="sale_price" id="sale_price" step="0.01">
            </div>
            <div class="col-md-2">
              <label for="sale_currency" class="form-label"><?php echo __('Currency') ?></label>
              <select class="form-select" name="sale_currency" id="sale_currency">
                <option value="">--</option>
                <option value="ZAR">ZAR</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
                <option value="GBP">GBP</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="auction_house" class="form-label"><?php echo __('Auction House') ?></label>
              <input type="text" class="form-control" name="auction_house" id="auction_house">
            </div>
            <div class="col-md-2">
              <label for="auction_lot" class="form-label"><?php echo __('Lot #') ?></label>
              <input type="text" class="form-control" name="auction_lot" id="auction_lot">
            </div>
          </div>

          <div class="mb-3">
            <label for="sources" class="form-label"><?php echo __('Sources/Documentation') ?></label>
            <textarea class="form-control" name="sources" id="sources" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label"><?php echo __('Notes') ?></label>
            <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
          </div>

          <div class="form-check">
            <input type="checkbox" class="form-check-input" name="is_gap" id="is_gap" value="1">
            <label class="form-check-label" for="is_gap"><?php echo __('Mark as provenance gap') ?></label>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
        <button type="button" class="btn btn-primary" id="save-entry"><?php echo __('Save') ?></button>
      </div>
    </div>
  </div>
</div>

<script src="https://d3js.org/d3.v7.min.js"></script>
<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
<?php include sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/js/provenance-timeline.js'; ?>
</script>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
var timelineData = <?php echo json_encode($timelineData ?? ['nodes' => [], 'links' => [], 'events' => []]) ?>;
var objectId = <?php echo $resource->id ?? 0 ?>;
var objectSlug = '<?php echo $resource->slug ?? '' ?>';

document.addEventListener('DOMContentLoaded', function() {
  var container = document.getElementById('provenance-timeline');

  // Initialize timeline
  if (timelineData.nodes && timelineData.nodes.length > 0) {
    var timeline = new ProvenanceTimeline('#provenance-timeline', {
      data: timelineData,
      width: container.offsetWidth || 800,
      height: 300,
      onNodeClick: function(node) {
        console.log('Clicked:', node);
      }
    });

    window.addEventListener('resize', function() {
      timeline.resize(container.offsetWidth, 300);
    });
  } else {
    container.innerHTML = '<p class="text-muted text-center py-5">Add provenance entries to see timeline</p>';
  }

  // Modal instance
  var entryModalEl = document.getElementById('entry-modal');
  var entryModal = entryModalEl ? new bootstrap.Modal(entryModalEl) : null;

  // Add entry button
  var addBtn = document.getElementById('add-entry');
  if (addBtn && entryModal) {
    addBtn.addEventListener('click', function() {
      document.getElementById('entry-form').reset();
      document.getElementById('entry-id').value = '';
      document.querySelector('#entry-modal .modal-title').textContent = 'Add Provenance Entry';
      entryModal.show();
    });
  }

  // Edit entry buttons
  document.querySelectorAll('.edit-entry').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = this.getAttribute('data-id');

      fetch('/museum/provenance/get?id=' + id)
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            var entry = data.entry;
            document.getElementById('entry-id').value = entry.id;
            document.getElementById('owner_name').value = entry.owner_name || '';
            document.getElementById('owner_type').value = entry.owner_type || 'unknown';
            document.getElementById('owner_location').value = entry.owner_location || '';
            document.getElementById('start_date').value = entry.start_date || '';
            document.getElementById('end_date').value = entry.end_date || '';
            document.getElementById('transfer_type').value = entry.transfer_type || 'unknown';
            document.getElementById('certainty').value = entry.certainty || 'unknown';
            document.getElementById('sale_price').value = entry.sale_price || '';
            document.getElementById('sale_currency').value = entry.sale_currency || '';
            document.getElementById('auction_house').value = entry.auction_house || '';
            document.getElementById('auction_lot').value = entry.auction_lot || '';
            document.getElementById('sources').value = entry.sources || '';
            document.getElementById('notes').value = entry.notes || '';
            document.getElementById('is_gap').checked = entry.is_gap == 1;

            document.querySelector('#entry-modal .modal-title').textContent = 'Edit Provenance Entry';
            entryModal.show();
          }
        });
    });
  });

  // Save entry
  var saveBtn = document.getElementById('save-entry');
  if (saveBtn) {
    saveBtn.addEventListener('click', function() {
      var form = document.getElementById('entry-form');
      var formData = new FormData(form);
      formData.append('information_object_id', objectId);

      fetch('/museum/provenance/save', {
        method: 'POST',
        body: formData
      })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(function(err) {
        alert('Error: ' + err.message);
      });
    });
  }

  // Delete entry buttons
  document.querySelectorAll('.delete-entry').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (confirm('Are you sure you want to delete this entry?')) {
        var id = this.getAttribute('data-id');

        fetch('/museum/provenance/delete?id=' + id, { method: 'POST' })
          .then(function(response) { return response.json(); })
          .then(function(data) {
            if (data.success) {
              location.reload();
            } else {
              alert('Error: ' + (data.error || 'Unknown error'));
            }
          });
      }
    });
  });

  // Export CSV
  var exportBtn = document.getElementById('export-csv');
  if (exportBtn) {
    exportBtn.addEventListener('click', function() {
      window.location = '/museum/provenance/export?object=' + objectId;
    });
  }
});
</script>

<style>
.provenance-timeline-container {
  background: #f9f9f9;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 20px;
  margin-bottom: 30px;
  min-height: 300px;
}
.provenance-table-section { margin-bottom: 30px; }
.provenance-actions .btn { margin-right: 10px; }
</style>
