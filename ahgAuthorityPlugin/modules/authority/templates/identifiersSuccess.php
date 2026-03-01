<?php decorate_with('layout_1col'); ?>

<?php
  $rawActor       = $sf_data->getRaw('actor');
  $rawIdentifiers = $sf_data->getRaw('identifiers');
  $rawPatterns    = $sf_data->getRaw('uriPatterns');

  $actor       = is_object($rawActor) ? $rawActor : (object) $rawActor;
  $identifiers = is_array($rawIdentifiers) ? $rawIdentifiers : [];
  $patterns    = is_array($rawPatterns) ? $rawPatterns : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-link me-2"></i><?php echo __('External Identifiers'); ?></h1>
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
      <li class="breadcrumb-item active"><?php echo __('External Identifiers'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- Existing Identifiers -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
      <span><i class="fas fa-external-link-alt me-1"></i><?php echo __('Linked Identifiers'); ?></span>
      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addIdentifierModal">
        <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
      </button>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Source'); ?></th>
            <th><?php echo __('Identifier'); ?></th>
            <th><?php echo __('Label'); ?></th>
            <th><?php echo __('Verified'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody id="identifiers-list">
          <?php if (empty($identifiers)): ?>
            <tr id="no-identifiers"><td colspan="5" class="text-center text-muted py-3"><?php echo __('No external identifiers yet.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($identifiers as $ident): ?>
              <tr data-id="<?php echo $ident->id; ?>">
                <td>
                  <span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($ident->identifier_type)); ?></span>
                </td>
                <td>
                  <?php if ($ident->uri): ?>
                    <a href="<?php echo htmlspecialchars($ident->uri); ?>" target="_blank" rel="noopener">
                      <?php echo htmlspecialchars($ident->identifier_value); ?>
                      <i class="fas fa-external-link-alt ms-1 small"></i>
                    </a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($ident->identifier_value); ?>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($ident->label ?? ''); ?></td>
                <td>
                  <?php if ($ident->is_verified): ?>
                    <span class="badge bg-success"><i class="fas fa-check"></i> <?php echo __('Verified'); ?></span>
                  <?php else: ?>
                    <button class="btn btn-sm btn-outline-success btn-verify" data-id="<?php echo $ident->id; ?>">
                      <i class="fas fa-check"></i> <?php echo __('Verify'); ?>
                    </button>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-danger btn-delete-id" data-id="<?php echo $ident->id; ?>">
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

  <!-- Lookup Tools -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-search me-1"></i><?php echo __('Search External Authorities'); ?>
    </div>
    <div class="card-body">
      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <input type="text" id="lookup-query" class="form-control" placeholder="<?php echo __('Search name...'); ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-primary" onclick="searchAuthority('wikidata')">
            <i class="fas fa-globe me-1"></i>Wikidata
          </button>
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-primary" onclick="searchAuthority('viaf')">VIAF</button>
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-primary" onclick="searchAuthority('ulan')">ULAN</button>
        </div>
        <div class="col-auto">
          <button class="btn btn-outline-primary" onclick="searchAuthority('lcnaf')">LCNAF</button>
        </div>
      </div>
      <div id="lookup-results" class="d-none">
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th><?php echo __('ID'); ?></th>
              <th><?php echo __('Label'); ?></th>
              <th><?php echo __('Description'); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="lookup-results-body"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add Identifier Modal -->
  <div class="modal fade" id="addIdentifierModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Add External Identifier'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Source'); ?></label>
            <select id="add-id-type" class="form-select">
              <?php foreach (array_keys($patterns) as $type): ?>
                <option value="<?php echo $type; ?>"><?php echo strtoupper($type); ?></option>
              <?php endforeach; ?>
              <option value="uri"><?php echo __('Other URI'); ?></option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Identifier Value'); ?></label>
            <input type="text" id="add-id-value" class="form-control" placeholder="Q12345">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Label (optional)'); ?></label>
            <input type="text" id="add-id-label" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('URI (auto-constructed if blank)'); ?></label>
            <input type="text" id="add-id-uri" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="button" class="btn btn-primary" id="btn-save-identifier">
            <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var actorId = <?php echo (int) $actor->id; ?>;

document.getElementById('btn-save-identifier').addEventListener('click', function() {
  var data = new FormData();
  data.append('actor_id', actorId);
  data.append('identifier_type', document.getElementById('add-id-type').value);
  data.append('identifier_value', document.getElementById('add-id-value').value);
  data.append('label', document.getElementById('add-id-label').value);
  data.append('uri', document.getElementById('add-id-uri').value);

  fetch('<?php echo url_for('@ahg_authority_identifier_save'); ?>', { method: 'POST', body: data })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.success) location.reload(); });
});

document.querySelectorAll('.btn-delete-id').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('<?php echo __('Delete this identifier?'); ?>')) return;
    fetch('/api/authority/identifier/' + this.dataset.id + '/delete', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
  });
});

document.querySelectorAll('.btn-verify').forEach(function(btn) {
  btn.addEventListener('click', function() {
    fetch('/api/authority/identifier/' + this.dataset.id + '/verify', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function(d) { if (d.success) location.reload(); });
  });
});

function searchAuthority(source) {
  var q = document.getElementById('lookup-query').value;
  if (!q) return;

  fetch('/api/authority/' + source + '/search?q=' + encodeURIComponent(q))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      var results = d.results || [];
      var tbody = document.getElementById('lookup-results-body');
      tbody.innerHTML = '';

      results.forEach(function(r) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + (r.id || '') + '</td>' +
          '<td>' + (r.label || '') + '</td>' +
          '<td><small>' + (r.description || '') + '</small></td>' +
          '<td><button class="btn btn-sm btn-success" onclick="linkResult(\'' + source + '\',\'' +
          (r.id || '').replace(/'/g, "\\'") + '\',\'' +
          (r.label || '').replace(/'/g, "\\'") + '\',\'' +
          (r.uri || '').replace(/'/g, "\\'") + '\')"><i class="fas fa-link"></i></button></td>';
        tbody.appendChild(tr);
      });

      document.getElementById('lookup-results').classList.remove('d-none');
    });
}

function linkResult(source, id, label, uri) {
  var data = new FormData();
  data.append('actor_id', actorId);
  data.append('identifier_type', source);
  data.append('identifier_value', id);
  data.append('label', label);
  data.append('uri', uri);
  data.append('source', 'reconciliation');

  fetch('<?php echo url_for('@ahg_authority_identifier_save'); ?>', { method: 'POST', body: data })
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.success) location.reload(); });
}
</script>
