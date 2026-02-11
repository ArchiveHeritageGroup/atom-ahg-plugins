<!-- Tom Select CSS -->
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet" {!! $csp_nonce !!}>

<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-layer-group me-2"></i>{{ __('Batch Rights Assignment') }}</h1>

  @if ($sf_user->hasFlash('notice'))
    <div class="alert alert-success">{!! $sf_user->getFlash('notice') !!}</div>
  @endif

  @if ($sf_user->hasFlash('error'))
    <div class="alert alert-danger">{!! $sf_user->getFlash('error') !!}</div>
  @endif

  <form method="post" id="batch-rights-form">

    <!-- Action Selection -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('1. Select Action') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="batch_action" id="action_assign" value="assign" checked>
              <label class="form-check-label" for="action_assign">
                <strong>{{ __('Assign Rights') }}</strong><br>
                <small class="text-muted">{{ __('Apply rights to selected objects') }}</small>
              </label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="batch_action" id="action_embargo" value="embargo">
              <label class="form-check-label" for="action_embargo">
                <strong>{{ __('Apply Embargo') }}</strong><br>
                <small class="text-muted">{{ __('Restrict access to selected objects') }}</small>
              </label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="batch_action" id="action_clear" value="clear">
              <label class="form-check-label" for="action_clear">
                <strong>{{ __('Clear Rights') }}</strong><br>
                <small class="text-muted">{{ __('Remove rights from selected objects') }}</small>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Object Selection -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('2. Select Objects') }}</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">{{ __('Search and select objects') }}</label>
          <select name="object_ids[]" id="object_select" multiple placeholder="{{ __('Type to search...') }}" class="form-select">
            @if (isset($topLevelRecords) && count($topLevelRecords) > 0)
              @foreach ($topLevelRecords as $record)
                <option value="{{ $record->id }}">
                  {{ $record->title ?? 'Untitled' }}
                  @if (!empty($record->identifier)) [{{ $record->identifier }}]@endif
                  @if (!empty($record->level)) - {{ $record->level }}@endif
                </option>
              @endforeach
            @endif
          </select>
          <small class="text-muted">{{ __('Start typing to search for objects. Select multiple items.') }}</small>
        </div>
        <div class="form-check">
          <input type="checkbox" name="overwrite" id="overwrite" value="1" class="form-check-input">
          <label class="form-check-label" for="overwrite">{{ __('Overwrite existing rights') }}</label>
        </div>
      </div>
    </div>

    <!-- Rights Options -->
    <div id="assign_options" class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('3. Rights Details') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="rights_statement_id" class="form-label">{{ __('Rights Statement') }}</label>
            <select name="rights_statement_id" id="rights_statement_id" class="form-select">
              <option value="">{{ __('-- Select --') }}</option>
              @foreach ($rightsStatements as $rs)
              <option value="{{ $rs->id }}">[{{ $rs->code }}] {{ $rs->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="creative_commons_id" class="form-label">{{ __('Creative Commons License') }}</label>
            <select name="creative_commons_id" id="creative_commons_id" class="form-select">
              <option value="">{{ __('-- Select --') }}</option>
              @foreach ($ccLicenses as $cc)
              <option value="{{ $cc->id }}">[{{ $cc->code }}] {{ $cc->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="rights_holder_id" class="form-label">{{ __('Rights Holder (Donor)') }}</label>
            <select name="rights_holder_id" id="rights_holder_id" class="form-select" placeholder="{{ __('Type to search...') }}">
              <option value="">{{ __('-- Select --') }}</option>
              @if (isset($donors) && count($donors) > 0)
                @foreach ($donors as $donor)
                  <option value="{{ $donor->id }}">{{ $donor->name ?? 'Unknown' }}</option>
                @endforeach
              @endif
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="copyright_notice" class="form-label">{{ __('Copyright Notice') }}</label>
            <input type="text" name="copyright_notice" id="copyright_notice" class="form-control" placeholder="&copy; 2024 ...">
          </div>
        </div>

        <!-- TK Labels -->
        <div class="mb-3">
          <label class="form-label">{{ __('Traditional Knowledge Labels') }}</label>
          <div class="row">
            @foreach ($tkLabels as $tk)
            <div class="col-md-4 mb-2">
              <div class="form-check">
                <input type="checkbox" name="tk_label_ids[]" value="{{ $tk->id }}" class="form-check-input" id="tk_{{ $tk->id }}">
                <label class="form-check-label" for="tk_{{ $tk->id }}">
                  @if (!empty($tk->icon_url))
                  <img src="{{ $tk->icon_url }}" alt="" style="width:20px;height:20px;" class="me-1">
                  @endif
                  {{ $tk->name ?? $tk->code }}
                </label>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- Embargo Options (hidden by default) -->
    <div id="embargo_options" class="card mb-4" style="display:none;">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Embargo Details') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="embargo_type" class="form-label">{{ __('Embargo Type') }}</label>
            <select name="embargo_type" id="embargo_type" class="form-select">
              <option value="full">{{ __('Full (no access)') }}</option>
              <option value="metadata_only">{{ __('Metadata only') }}</option>
              <option value="thumbnail_only">{{ __('Thumbnail only') }}</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="embargo_end_date" class="form-label">{{ __('End Date (optional)') }}</label>
            <input type="date" name="embargo_end_date" id="embargo_end_date" class="form-control">
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="d-flex justify-content-between">
      <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'dashboard']) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Cancel') }}
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-check me-1"></i>{{ __('Execute Batch Operation') }}
      </button>
    </div>

  </form>
</main>

<!-- Tom Select JS -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" {!! $csp_nonce !!}></script>

<script {!! $csp_nonce !!}>
document.addEventListener('DOMContentLoaded', function() {
    // Object select (multiple)
    new TomSelect('#object_select', {
        plugins: ['remove_button', 'clear_button'],
        maxItems: null,
        create: false,
        placeholder: '{{ __('Type to search for objects...') }}',
        render: {
            option: function(data, escape) {
                return '<div class="py-1">' + escape(data.text) + '</div>';
            },
            item: function(data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            }
        }
    });

    // Rights Holder (Donor) select
    new TomSelect('#rights_holder_id', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: '{{ __('Type to search for donors...') }}'
    });

    // Toggle options based on action
    var radios = document.querySelectorAll('input[name="batch_action"]');
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            var assignOptions = document.getElementById('assign_options');
            var embargoOptions = document.getElementById('embargo_options');

            if (this.value === 'assign') {
                assignOptions.style.display = 'block';
                embargoOptions.style.display = 'none';
            } else if (this.value === 'embargo') {
                assignOptions.style.display = 'none';
                embargoOptions.style.display = 'block';
            } else {
                assignOptions.style.display = 'none';
                embargoOptions.style.display = 'none';
            }
        });
    });
});
</script>
