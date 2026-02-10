<!-- Tom Select CSS -->
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">

<main id="content" class="container-xxl py-4">
  <h1 class="mb-4"><i class="fas fa-download me-2"></i>{{ __('Export Rights Data') }}</h1>

  <div class="row">
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Export Single Object') }}</h5>
        </div>
        <div class="card-body">
          <form method="get" action="{{ url_for(['module' => 'extendedRights', 'action' => 'export']) }}">
            <div class="mb-3">
              <label for="single_id" class="form-label">{{ __('Search and select object') }}</label>
              <select name="id" id="single_id" class="form-select" placeholder="{{ __('Type to search...') }}">
                <option value="">{{ __('-- Select an object --') }}</option>
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
            </div>
            <div class="mb-3">
              <label class="form-label">{{ __('Format') }}</label>
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
              <i class="fas fa-download me-1"></i>{{ __('Export') }}
            </button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Bulk Export') }}</h5>
        </div>
        <div class="card-body">
          <form method="get" action="{{ url_for(['module' => 'extendedRights', 'action' => 'export']) }}">
            <input type="hidden" name="format" value="csv">
            <div class="mb-3">
              <label for="bulk_select" class="form-label">{{ __('Search and select multiple objects') }}</label>
              <select name="ids[]" id="bulk_select" multiple class="form-select" placeholder="{{ __('Type to search...') }}">
                @if (isset($topLevelRecords) && count($topLevelRecords) > 0)
                  @foreach ($topLevelRecords as $record)
                    <option value="{{ $record->id }}">
                      {{ $record->title ?? 'Untitled' }}
                      @if (!empty($record->identifier)) [{{ $record->identifier }}]@endif
                    </option>
                  @endforeach
                @endif
              </select>
              <small class="text-muted">{{ __('Leave empty to export all objects with rights.') }}</small>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-download me-1"></i>{{ __('Export as CSV') }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Export Statistics') }}</h5>
    </div>
    <div class="card-body">
      @php
      $totalWithRights = is_array($stats) ? ($stats['total_with_rights'] ?? 0) : ($stats->total_with_rights ?? 0);
      $inheritedRights = is_array($stats) ? ($stats['inherited_rights'] ?? 0) : ($stats->inherited_rights ?? 0);
      @endphp
      <p>{{ __('Total objects with extended rights') }}: <strong>{{ number_format($totalWithRights) }}</strong></p>
      <p>{{ __('Objects with inherited rights') }}: <strong>{{ number_format($inheritedRights) }}</strong></p>
    </div>
  </div>

  <div class="mt-3">
    <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'dashboard']) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
    </a>
  </div>
</main>

<!-- Tom Select JS -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js"></script>

<script {!! $csp_nonce !!}>
document.addEventListener('DOMContentLoaded', function() {
    // Single object select
    new TomSelect('#single_id', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: '{{ __('Type to search for an object...') }}'
    });

    // Bulk select with multiple
    new TomSelect('#bulk_select', {
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
});
</script>
