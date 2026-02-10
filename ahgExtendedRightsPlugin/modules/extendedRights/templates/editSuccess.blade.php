<!-- Tom Select CSS -->
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">

<h1>Edit Rights: {{ $resource->title ?? 'Untitled' }}</h1>
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'extendedRights', 'action' => 'index']) }}">Extended Rights</a></li>
    <li class="breadcrumb-item active">Edit</li>
  </ol>
</nav>

@if ($sf_user->hasFlash('notice'))
  <div class="alert alert-success">{!! $sf_user->getFlash('notice') !!}</div>
@endif
@if ($sf_user->hasFlash('error'))
  <div class="alert alert-danger">{!! $sf_user->getFlash('error') !!}</div>
@endif

<form method="post">
  <div class="row">
    <div class="col-md-6">
      <!-- Rights Statement -->
      <div class="card mb-4">
        <div class="card-header"><strong>Rights Statement</strong></div>
        <div class="card-body">
          <select name="rights_statement_id" class="form-select">
            <option value="">-- None --</option>
            @foreach ($rightsStatements as $rs)
              <option value="{{ $rs->id }}"
                {{ (isset($currentRights->rights_statement) && $currentRights->rights_statement->rights_statement_id == $rs->id) ? 'selected' : '' }}>
                {{ $rs->name ?? $rs->code }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <!-- Creative Commons -->
      <div class="card mb-4">
        <div class="card-header"><strong>Creative Commons License</strong></div>
        <div class="card-body">
          <select name="cc_license_id" class="form-select">
            <option value="">-- None --</option>
            @foreach ($ccLicenses as $cc)
              <option value="{{ $cc->id }}"
                {{ (isset($currentRights->cc_license) && $currentRights->cc_license->creative_commons_license_id == $cc->id) ? 'selected' : '' }}>
                {{ $cc->name ?? $cc->code }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <!-- Rights Holder (Donor) -->
      <div class="card mb-4">
        <div class="card-header"><strong>Rights Holder (Donor)</strong></div>
        <div class="card-body">
          <select name="rights_holder_id" id="rights_holder_id" class="form-select" placeholder="Type to search...">
            <option value="">-- None --</option>
            @if (isset($donors) && count($donors) > 0)
              @php
              $currentHolderId = isset($currentRights->rights_holder->donor_id) ? $currentRights->rights_holder->donor_id : null;
              @endphp
              @foreach ($donors as $donor)
                <option value="{{ $donor->id }}"
                  {{ ($currentHolderId == $donor->id) ? 'selected' : '' }}>
                  {{ $donor->name ?? 'Unknown' }}
                </option>
              @endforeach
            @endif
          </select>
          <small class="text-muted">Select the donor who holds the rights to this material.</small>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <!-- TK Labels -->
      <div class="card mb-4">
        <div class="card-header"><strong>TK Labels</strong></div>
        <div class="card-body">
          @php
          $selectedTkLabels = $currentRights->tk_labels ?? [];
          if ($selectedTkLabels instanceof sfOutputEscaperArrayDecorator) {
              $selectedTkLabels = $selectedTkLabels->getRawValue();
          }
          if (!is_array($selectedTkLabels)) {
              $selectedTkLabels = [];
          }
          @endphp
          @foreach ($tkLabels as $tk)
            <div class="form-check">
              <input type="checkbox" name="tk_label_ids[]" value="{{ $tk->id }}"
                     class="form-check-input" id="tk_{{ $tk->id }}"
                     {{ in_array($tk->id, $selectedTkLabels) ? 'checked' : '' }}>
              <label class="form-check-label" for="tk_{{ $tk->id }}">
                {{ $tk->name ?? $tk->code }}
              </label>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <div class="mb-4">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> Save Rights
    </button>
    <a href="{{ url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]) }}" class="btn btn-secondary">
      Cancel
    </a>
  </div>
</form>

<!-- Tom Select JS -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js"></script>
<script {!! $csp_nonce !!}>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#rights_holder_id', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: 'Type to search for donors...'
    });
});
</script>
