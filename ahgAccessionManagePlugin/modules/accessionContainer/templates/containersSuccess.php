<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Containers'); ?> &mdash; <?php echo htmlspecialchars($accession['identifier'] ?? ''); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<?php
  $accId = $accession['id'] ?? 0;
  $accIdentifier = htmlspecialchars($accession['identifier'] ?? '', ENT_QUOTES, 'UTF-8');
  $containers = isset($containers) ? $sf_data->getRaw('containers') : [];
  $containerItems = isset($containerItems) ? $sf_data->getRaw('containerItems') : [];
  $containerTypes = isset($containerTypes) ? $sf_data->getRaw('containerTypes') : [];
  $conditionStatuses = isset($conditionStatuses) ? $sf_data->getRaw('conditionStatuses') : [];
?>

<div class="container-fluid px-0">

  <!-- Navigation breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?php echo url_for('@accession_browse_override'); ?>"><?php echo __('Accessions'); ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')); ?>"><?php echo $accIdentifier; ?></a></li>
      <li class="breadcrumb-item active"><?php echo __('Containers'); ?></li>
    </ol>
  </nav>

  <!-- Tab navigation for M3 -->
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link active" href="<?php echo url_for('@accession_containers_view?id=' . $accId); ?>"><?php echo __('Containers'); ?></a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="<?php echo url_for('@accession_rights_view?id=' . $accId); ?>"><?php echo __('Rights'); ?></a>
    </li>
  </ul>

  <!-- Barcode lookup -->
  <div class="card mb-4">
    <div class="card-header">
      <i class="fas fa-barcode me-2"></i><?php echo __('Barcode lookup'); ?>
    </div>
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-4">
          <label for="barcodeLookupInput" class="form-label"><?php echo __('Scan or enter barcode'); ?></label>
          <input type="text" id="barcodeLookupInput" class="form-control" placeholder="<?php echo __('Barcode...'); ?>">
        </div>
        <div class="col-auto">
          <button type="button" id="barcodeLookupBtn" class="btn btn-outline-primary">
            <i class="fas fa-search me-1"></i><?php echo __('Lookup'); ?>
          </button>
        </div>
        <div class="col-12">
          <div id="barcodeLookupResult" class="mt-2" style="display:none;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Container toggle -->
  <div class="mb-4">
    <button type="button" id="toggleAddContainerBtn" class="btn btn-success">
      <i class="fas fa-plus me-1"></i><?php echo __('Add container'); ?>
    </button>
  </div>

  <!-- Add Container form (initially hidden) -->
  <div id="addContainerForm" class="card mb-4" style="display:none;">
    <div class="card-header">
      <i class="fas fa-box me-2"></i><?php echo __('New container'); ?>
    </div>
    <div class="card-body">
      <form id="containerForm">
        <input type="hidden" name="accession_id" value="<?php echo $accId; ?>">
        <input type="hidden" name="container_id" value="0">

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="containerType" class="form-label"><?php echo __('Container type'); ?> <span class="text-danger">*</span></label>
            <select id="containerType" name="container_type" class="form-select" required>
              <?php foreach ($containerTypes as $type): ?>
              <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label for="containerLabel" class="form-label"><?php echo __('Label'); ?> <span class="text-danger">*</span></label>
            <input type="text" id="containerLabel" name="label" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label for="containerBarcode" class="form-label"><?php echo __('Barcode'); ?></label>
            <input type="text" id="containerBarcode" name="barcode" class="form-control">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="containerLocation" class="form-label"><?php echo __('Location detail'); ?></label>
            <input type="text" id="containerLocation" name="location_detail" class="form-control">
          </div>
          <div class="col-md-4">
            <label for="containerDimensions" class="form-label"><?php echo __('Dimensions'); ?></label>
            <input type="text" id="containerDimensions" name="dimensions" class="form-control" placeholder="<?php echo __('e.g. 30x20x15 cm'); ?>">
          </div>
          <div class="col-md-4">
            <label for="containerWeight" class="form-label"><?php echo __('Weight (kg)'); ?></label>
            <input type="number" id="containerWeight" name="weight_kg" class="form-control" step="0.01" min="0">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label for="containerCondition" class="form-label"><?php echo __('Condition'); ?></label>
            <select id="containerCondition" name="condition_status" class="form-select">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php foreach ($conditionStatuses as $cs): ?>
              <option value="<?php echo htmlspecialchars($cs); ?>"><?php echo htmlspecialchars(ucfirst($cs)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label for="containerNotes" class="form-label"><?php echo __('Notes'); ?></label>
            <textarea id="containerNotes" name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save container'); ?>
          </button>
          <button type="button" id="cancelAddContainerBtn" class="btn btn-secondary">
            <?php echo __('Cancel'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Containers list -->
  <?php if (count($containers) === 0): ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i><?php echo __('No containers have been added to this accession yet.'); ?>
    </div>
  <?php else: ?>
    <div id="containersList">
      <?php foreach ($containers as $container): ?>
      <?php
        $cId = $container->id;
        $items = isset($containerItems[$cId]) ? $containerItems[$cId] : [];
        $itemCount = $container->actual_item_count ?? count($items);

        $conditionColors = [
          'excellent' => 'success',
          'good' => 'primary',
          'fair' => 'warning',
          'poor' => 'danger',
          'critical' => 'dark',
        ];
        $condBadge = $conditionColors[$container->condition_status ?? ''] ?? 'secondary';

        $typeIcons = [
          'box' => 'fa-box',
          'folder' => 'fa-folder',
          'envelope' => 'fa-envelope',
          'crate' => 'fa-pallet',
          'tube' => 'fa-scroll',
          'flat_file' => 'fa-layer-group',
          'digital_media' => 'fa-compact-disc',
          'other' => 'fa-archive',
        ];
        $typeIcon = $typeIcons[$container->container_type ?? ''] ?? 'fa-box';
      ?>

      <div class="card mb-3 container-card" data-container-id="<?php echo $cId; ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <i class="fas <?php echo $typeIcon; ?> me-2"></i>
            <span class="badge bg-info text-dark me-2"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $container->container_type ?? 'box'))); ?></span>
            <strong><?php echo htmlspecialchars($container->label ?? ''); ?></strong>
            <?php if (!empty($container->barcode)): ?>
              <code class="ms-2"><?php echo htmlspecialchars($container->barcode); ?></code>
            <?php endif; ?>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary"><?php echo $itemCount; ?> <?php echo __('items'); ?></span>
            <?php if (!empty($container->condition_status)): ?>
              <span class="badge bg-<?php echo $condBadge; ?>"><?php echo htmlspecialchars(ucfirst($container->condition_status)); ?></span>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-primary toggle-items-btn" data-container="<?php echo $cId; ?>" title="<?php echo __('Toggle items'); ?>">
              <i class="fas fa-chevron-down"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger delete-container-btn" data-container="<?php echo $cId; ?>" title="<?php echo __('Delete container'); ?>">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>

        <div class="card-body">
          <!-- Container details row -->
          <div class="row mb-2">
            <?php if (!empty($container->location_name)): ?>
              <div class="col-md-3">
                <small class="text-muted"><?php echo __('Location'); ?>:</small><br>
                <?php echo htmlspecialchars($container->location_name); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($container->location_detail)): ?>
              <div class="col-md-3">
                <small class="text-muted"><?php echo __('Detail'); ?>:</small><br>
                <?php echo htmlspecialchars($container->location_detail); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($container->dimensions)): ?>
              <div class="col-md-3">
                <small class="text-muted"><?php echo __('Dimensions'); ?>:</small><br>
                <?php echo htmlspecialchars($container->dimensions); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($container->weight_kg)): ?>
              <div class="col-md-3">
                <small class="text-muted"><?php echo __('Weight'); ?>:</small><br>
                <?php echo number_format($container->weight_kg, 2); ?> kg
              </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($container->notes)): ?>
            <p class="text-muted small mb-2"><em><?php echo htmlspecialchars($container->notes); ?></em></p>
          <?php endif; ?>

          <!-- Expandable items section -->
          <div class="container-items-section" id="containerItems<?php echo $cId; ?>" style="display:none;">
            <hr>
            <h6 class="mb-3"><i class="fas fa-list me-1"></i><?php echo __('Items in this container'); ?></h6>

            <?php if (count($items) > 0): ?>
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-3">
                  <thead class="table-light">
                    <tr>
                      <th><?php echo __('Title'); ?></th>
                      <th><?php echo __('Quantity'); ?></th>
                      <th><?php echo __('Format'); ?></th>
                      <th><?php echo __('Date range'); ?></th>
                      <th><?php echo __('Linked IO'); ?></th>
                      <th style="width:80px;"><?php echo __('Actions'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($item->title ?? ''); ?></td>
                      <td><?php echo $item->quantity ?? 1; ?></td>
                      <td><?php echo htmlspecialchars($item->format ?? '-'); ?></td>
                      <td><?php echo htmlspecialchars($item->date_range ?? '-'); ?></td>
                      <td>
                        <?php if (!empty($item->io_title)): ?>
                          <span class="text-primary"><?php echo htmlspecialchars($item->io_title); ?></span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-item-btn" data-item="<?php echo $item->id; ?>" data-container="<?php echo $cId; ?>" title="<?php echo __('Delete'); ?>">
                          <i class="fas fa-times"></i>
                        </button>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted small mb-3"><?php echo __('No items in this container yet.'); ?></p>
            <?php endif; ?>

            <!-- Add item form -->
            <div class="bg-light p-3 rounded">
              <h6 class="mb-2"><i class="fas fa-plus me-1"></i><?php echo __('Add item'); ?></h6>
              <form class="add-item-form" data-container="<?php echo $cId; ?>">
                <input type="hidden" name="container_id" value="<?php echo $cId; ?>">
                <div class="row g-2">
                  <div class="col-md-3">
                    <input type="text" name="title" class="form-control form-control-sm" placeholder="<?php echo __('Title'); ?>" required>
                  </div>
                  <div class="col-md-2">
                    <input type="number" name="quantity" class="form-control form-control-sm" placeholder="<?php echo __('Qty'); ?>" value="1" min="1">
                  </div>
                  <div class="col-md-2">
                    <input type="text" name="format" class="form-control form-control-sm" placeholder="<?php echo __('Format'); ?>">
                  </div>
                  <div class="col-md-3">
                    <input type="text" name="date_range" class="form-control form-control-sm" placeholder="<?php echo __('Date range'); ?>">
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                      <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')); ?>" class="btn atom-btn-outline-light">
      <?php echo __('Back to accession'); ?>
    </a>
  </section>
<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var accessionId = <?php echo json_encode($accession['id'] ?? 0); ?>;

  // Toggle add container form
  var toggleBtn = document.getElementById('toggleAddContainerBtn');
  var addForm = document.getElementById('addContainerForm');
  var cancelBtn = document.getElementById('cancelAddContainerBtn');

  if (toggleBtn && addForm) {
    toggleBtn.addEventListener('click', function() {
      addForm.style.display = addForm.style.display === 'none' ? 'block' : 'none';
    });
  }
  if (cancelBtn && addForm) {
    cancelBtn.addEventListener('click', function() {
      addForm.style.display = 'none';
    });
  }

  // Save container (AJAX)
  var containerForm = document.getElementById('containerForm');
  if (containerForm) {
    containerForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(containerForm);
      fetch('<?php echo url_for("@accession_api_container_save"); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          window.location.reload();
        } else {
          alert('<?php echo __("Error saving container"); ?>');
        }
      })
      .catch(function() {
        alert('<?php echo __("Error saving container"); ?>');
      });
    });
  }

  // Barcode lookup
  var barcodeBtn = document.getElementById('barcodeLookupBtn');
  var barcodeInput = document.getElementById('barcodeLookupInput');
  var barcodeResult = document.getElementById('barcodeLookupResult');

  if (barcodeBtn && barcodeInput) {
    barcodeBtn.addEventListener('click', function() {
      var barcode = barcodeInput.value.trim();
      if (!barcode) return;

      fetch('<?php echo url_for("@accession_api_barcode_lookup"); ?>?barcode=' + encodeURIComponent(barcode))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (barcodeResult) {
          barcodeResult.style.display = 'block';
          if (data.success && data.container) {
            var c = data.container;
            barcodeResult.innerHTML = '<div class="alert alert-success mb-0">'
              + '<strong>' + <?php echo json_encode(__('Found:')); ?> + '</strong> '
              + (c.label || '') + ' &mdash; '
              + <?php echo json_encode(__('Accession')); ?> + ': ' + (c.accession_identifier || '')
              + (c.accession_slug ? ' <a href="/accession/' + c.accession_slug + '" class="ms-2">' + <?php echo json_encode(__('View')); ?> + '</a>' : '')
              + '</div>';
          } else {
            barcodeResult.innerHTML = '<div class="alert alert-warning mb-0">' + <?php echo json_encode(__('No container found with that barcode.')); ?> + '</div>';
          }
        }
      })
      .catch(function() {
        if (barcodeResult) {
          barcodeResult.style.display = 'block';
          barcodeResult.innerHTML = '<div class="alert alert-danger mb-0">' + <?php echo json_encode(__('Error performing lookup.')); ?> + '</div>';
        }
      });
    });

    barcodeInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        barcodeBtn.click();
      }
    });
  }

  // Toggle container items
  document.querySelectorAll('.toggle-items-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var cId = btn.getAttribute('data-container');
      var section = document.getElementById('containerItems' + cId);
      if (section) {
        var isHidden = section.style.display === 'none';
        section.style.display = isHidden ? 'block' : 'none';
        var icon = btn.querySelector('i');
        if (icon) {
          icon.className = isHidden ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
        }
      }
    });
  });

  // Delete container
  document.querySelectorAll('.delete-container-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm(<?php echo json_encode(__('Are you sure you want to delete this container and all its items?')); ?>)) return;
      var cId = btn.getAttribute('data-container');
      fetch('/api/accession/container/' + cId + '/delete', {
        method: 'POST'
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          var card = btn.closest('.container-card');
          if (card) card.remove();
        } else {
          alert(<?php echo json_encode(__('Error deleting container.')); ?>);
        }
      })
      .catch(function() {
        alert(<?php echo json_encode(__('Error deleting container.')); ?>);
      });
    });
  });

  // Delete container item
  document.querySelectorAll('.delete-item-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm(<?php echo json_encode(__('Delete this item?')); ?>)) return;
      var itemId = btn.getAttribute('data-item');
      fetch('/api/accession/container-item/' + itemId + '/delete', {
        method: 'POST'
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          var row = btn.closest('tr');
          if (row) row.remove();
        } else {
          alert(<?php echo json_encode(__('Error deleting item.')); ?>);
        }
      })
      .catch(function() {
        alert(<?php echo json_encode(__('Error deleting item.')); ?>);
      });
    });
  });

  // Add item to container
  document.querySelectorAll('.add-item-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(form);
      fetch('<?php echo url_for("@accession_api_container_item_save"); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          window.location.reload();
        } else {
          alert(<?php echo json_encode(__('Error adding item.')); ?>);
        }
      })
      .catch(function() {
        alert(<?php echo json_encode(__('Error adding item.')); ?>);
      });
    });
  });
});
</script>
