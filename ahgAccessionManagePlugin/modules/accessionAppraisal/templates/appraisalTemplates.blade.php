@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1><?php echo __('Appraisal Templates'); ?></h1>
@endslot

@slot('content')
@php
  $flash = $sf_user->getFlash('notice', '');
  $sectorLabels = [
      'archive' => __('Archive'),
      'library' => __('Library'),
      'museum' => __('Museum'),
      'gallery' => __('Gallery'),
      'dam' => __('DAM'),
  ];
@endphp

@if ($flash)
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  {{ $flash }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'accessionManage', 'action' => 'dashboard']) }}">{{ __('Accessions') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Appraisal Templates') }}</li>
  </ol>
</nav>

<div class="row">
  <div class="col-lg-7">
    {{-- Existing Templates --}}
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-file-alt me-2"></i>{{ __('Templates') }}
      </div>
      <div class="card-body p-0">
        @if (is_array($templates) && count($templates) > 0)
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Sector') }}</th>
                <th class="text-center">{{ __('Default') }}</th>
                <th class="text-center">{{ __('Criteria') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($templates as $t)
              @php
                $criteriaData = json_decode($t->criteria ?? '[]', true);
                $criteriaCount = is_array($criteriaData) ? count($criteriaData) : 0;
              @endphp
              <tr>
                <td>
                  <strong>{{ $t->name }}</strong>
                  @if (!empty($t->description))
                  <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($t->description, 60) }}</small>
                  @endif
                </td>
                <td>
                  @if (!empty($t->sector))
                  <span class="badge bg-secondary">{{ $sectorLabels[$t->sector] ?? ucfirst($t->sector) }}</span>
                  @else
                  <span class="text-muted">{{ __('All') }}</span>
                  @endif
                </td>
                <td class="text-center">
                  @if ($t->is_default)
                  <span class="badge bg-success"><i class="fas fa-check"></i></span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge bg-info">{{ $criteriaCount }}</span>
                </td>
                <td>
                  <form method="post" action="{{ url_for('@accession_appraisal_templates') }}" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this template?'); ?>');">
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="template_id" value="{{ $t->id }}">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              {{-- Expandable criteria detail --}}
              @if ($criteriaCount > 0)
              <tr class="criteria-row" id="criteria_{{ $t->id }}" style="display:none;">
                <td colspan="5" class="bg-light">
                  <div class="px-3 py-2">
                    <strong class="small text-uppercase text-muted">{{ __('Criteria:') }}</strong>
                    <table class="table table-sm table-borderless mb-0 mt-1">
                      <thead>
                        <tr class="text-muted small">
                          <th>{{ __('Criterion') }}</th>
                          <th style="width:80px;">{{ __('Weight') }}</th>
                          <th>{{ __('Description') }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($criteriaData as $c)
                        <tr>
                          <td>{{ $c['criterion_name'] ?? '' }}</td>
                          <td>{{ number_format($c['weight'] ?? 1.00, 2) }}</td>
                          <td class="text-muted">{{ $c['description'] ?? '' }}</td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
              @endif
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">
          <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
          {{ __('No templates defined yet. Create one using the form.') }}
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    {{-- Create New Template --}}
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <i class="fas fa-plus-circle me-2"></i>{{ __('Create New Template') }}
      </div>
      <div class="card-body">
        <form method="post" action="{{ url_for('@accession_appraisal_templates') }}" id="templateForm">
          <input type="hidden" name="form_action" value="create">

          <div class="mb-3">
            <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required placeholder="{{ __('e.g. Archival Significance Assessment') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Description') }}</label>
            <textarea name="description" class="form-control" rows="2" placeholder="{{ __('Brief description of when to use this template...') }}"></textarea>
          </div>

          <div class="row mb-3">
            <div class="col-md-7">
              <label class="form-label">{{ __('Sector') }}</label>
              <select name="sector" class="form-select">
                <option value="">{{ __('-- All Sectors --') }}</option>
                @foreach ($sectorLabels as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">&nbsp;</label>
              <div class="form-check mt-2">
                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault">
                <label class="form-check-label" for="isDefault">{{ __('Default template') }}</label>
              </div>
            </div>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <label class="form-label fw-bold mb-0">{{ __('Criteria') }}</label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addCriterionBtn">
              <i class="fas fa-plus me-1"></i>{{ __('Add Criterion') }}
            </button>
          </div>

          <div id="criteriaContainer">
            {{-- Initial criterion row --}}
            <div class="criterion-row card card-body bg-light mb-2 p-3">
              <div class="row g-2">
                <div class="col-md-5">
                  <input type="text" name="criterion_names[]" class="form-control form-control-sm" placeholder="{{ __('Criterion name') }}" required>
                </div>
                <div class="col-md-2">
                  <input type="number" name="criterion_weights[]" class="form-control form-control-sm" step="0.01" min="0" value="1.00" placeholder="{{ __('Weight') }}">
                </div>
                <div class="col-md-4">
                  <input type="text" name="criterion_descriptions[]" class="form-control form-control-sm" placeholder="{{ __('Description') }}">
                </div>
                <div class="col-md-1 text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger remove-criterion-btn" title="{{ __('Remove') }}">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <hr class="mt-4">

          <button type="submit" class="btn btn-success w-100">
            <i class="fas fa-save me-1"></i>{{ __('Create Template') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // ---- Add criterion row ----
  var container = document.getElementById('criteriaContainer');
  var addBtn = document.getElementById('addCriterionBtn');

  addBtn.addEventListener('click', function() {
    var row = document.createElement('div');
    row.className = 'criterion-row card card-body bg-light mb-2 p-3';
    row.innerHTML =
      '<div class="row g-2">' +
        '<div class="col-md-5">' +
          '<input type="text" name="criterion_names[]" class="form-control form-control-sm" placeholder="<?php echo __('Criterion name'); ?>" required>' +
        '</div>' +
        '<div class="col-md-2">' +
          '<input type="number" name="criterion_weights[]" class="form-control form-control-sm" step="0.01" min="0" value="1.00" placeholder="<?php echo __('Weight'); ?>">' +
        '</div>' +
        '<div class="col-md-4">' +
          '<input type="text" name="criterion_descriptions[]" class="form-control form-control-sm" placeholder="<?php echo __('Description'); ?>">' +
        '</div>' +
        '<div class="col-md-1 text-end">' +
          '<button type="button" class="btn btn-sm btn-outline-danger remove-criterion-btn" title="<?php echo __('Remove'); ?>">' +
            '<i class="fas fa-times"></i>' +
          '</button>' +
        '</div>' +
      '</div>';
    container.appendChild(row);
    bindRemoveButtons();
  });

  // ---- Remove criterion row ----
  function bindRemoveButtons() {
    var buttons = container.querySelectorAll('.remove-criterion-btn');
    buttons.forEach(function(btn) {
      btn.onclick = function() {
        var rows = container.querySelectorAll('.criterion-row');
        if (rows.length > 1) {
          btn.closest('.criterion-row').remove();
        }
      };
    });
  }
  bindRemoveButtons();

  // ---- Toggle criteria detail rows in template list ----
  var badges = document.querySelectorAll('.badge.bg-info');
  badges.forEach(function(badge) {
    var row = badge.closest('tr');
    if (row) {
      badge.style.cursor = 'pointer';
      badge.title = '<?php echo __('Click to expand criteria'); ?>';
      badge.addEventListener('click', function() {
        var nextRow = row.nextElementSibling;
        if (nextRow && nextRow.classList.contains('criteria-row')) {
          nextRow.style.display = nextRow.style.display === 'none' ? '' : 'none';
        }
      });
    }
  });
});
</script>
@endslot
