@php
$entries = $entries ?? [];
$statusCounts = $statusCounts ?? [];
$filterStatus = $filterStatus ?? null;
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-exclamation-triangle me-2"></i>{{ __('Dead Letter Queue') }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
  </div>

  {{-- Status summary --}}
  <div class="row g-3 mb-4">
    @foreach (['open' => 'danger', 'acknowledged' => 'warning', 'investigating' => 'info', 'resolved' => 'success', 'ignored' => 'secondary'] as $st => $color)
      <div class="col">
        <a href="{{ url_for(['module' => 'integrity', 'action' => 'deadLetter', 'status' => $st]) }}" class="card text-center text-decoration-none h-100 {{ $filterStatus === $st ? 'border-primary border-2' : '' }}">
          <div class="card-body py-2">
            <small class="text-muted d-block">{{ ucfirst($st) }}</small>
            <strong class="text-{{ $color }}">{{ $statusCounts[$st] ?? 0 }}</strong>
          </div>
        </a>
      </div>
    @endforeach
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>{{ __('Object') }}</th>
              <th>{{ __('Failure Type') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Failures') }}</th>
              <th>{{ __('First') }}</th>
              <th>{{ __('Last') }}</th>
              <th>{{ __('Retries') }}</th>
              <th>{{ __('Error') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($entries as $e)
              @php
                $badgeClass = match($e->status) {
                    'open' => 'bg-danger', 'acknowledged' => 'bg-warning', 'investigating' => 'bg-info',
                    'resolved' => 'bg-success', 'ignored' => 'bg-secondary', default => 'bg-secondary',
                };
              @endphp
              <tr>
                <td>{{ $e->id }}</td>
                <td>{{ $e->digital_object_id }}</td>
                <td><span class="badge bg-danger">{{ $e->failure_type }}</span></td>
                <td><span class="badge {{ $badgeClass }}">{{ $e->status }}</span></td>
                <td>{{ $e->consecutive_failures }}</td>
                <td>{{ $e->first_failure_at }}</td>
                <td>{{ $e->last_failure_at }}</td>
                <td>{{ $e->retry_count }}/{{ $e->max_retries }}</td>
                <td class="text-truncate" style="max-width:200px" title="{{ $e->last_error_detail ?? '' }}">{{ $e->last_error_detail ?? '—' }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    @if ($e->status === 'open')
                      <button class="btn btn-outline-warning btn-sm js-dl-action" data-id="{{ $e->id }}" data-action="acknowledge" title="{{ __('Acknowledge') }}">
                        <i class="fas fa-eye"></i>
                      </button>
                    @endif
                    @if (in_array($e->status, ['open', 'acknowledged']))
                      <button class="btn btn-outline-info btn-sm js-dl-action" data-id="{{ $e->id }}" data-action="investigate" title="{{ __('Investigate') }}">
                        <i class="fas fa-search"></i>
                      </button>
                    @endif
                    @if ($e->status !== 'resolved')
                      <button class="btn btn-outline-success btn-sm js-dl-action" data-id="{{ $e->id }}" data-action="resolve" title="{{ __('Resolve') }}">
                        <i class="fas fa-check"></i>
                      </button>
                    @endif
                    @if ($e->status !== 'ignored')
                      <button class="btn btn-outline-secondary btn-sm js-dl-action" data-id="{{ $e->id }}" data-action="ignore" title="{{ __('Ignore') }}">
                        <i class="fas fa-ban"></i>
                      </button>
                    @endif
                    @if (in_array($e->status, ['resolved', 'ignored']))
                      <button class="btn btn-outline-danger btn-sm js-dl-action" data-id="{{ $e->id }}" data-action="reopen" title="{{ __('Reopen') }}">
                        <i class="fas fa-redo"></i>
                      </button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="10" class="text-muted text-center py-3">{{ __('No dead letter entries.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.js-dl-action').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = this.dataset.id;
      var action = this.dataset.action;
      fetch('/api/integrity/dead-letter/' + id + '/action', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'dead_letter_action=' + encodeURIComponent(action)
      })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
    });
  });
});
</script>
