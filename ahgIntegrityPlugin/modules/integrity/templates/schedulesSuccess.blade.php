@php
$schedules = $schedules ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clock me-2"></i>{{ __('Verification Schedules') }}</h1>
    <div>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'scheduleEdit']) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>{{ __('New Schedule') }}
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>{{ __('Name') }}</th>
              <th>{{ __('Scope') }}</th>
              <th>{{ __('Frequency') }}</th>
              <th>{{ __('Algorithm') }}</th>
              <th>{{ __('Batch') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Last Run') }}</th>
              <th>{{ __('Next Run') }}</th>
              <th>{{ __('Runs') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($schedules as $s)
              <tr>
                <td>{{ $s->id }}</td>
                <td>
                  <a href="{{ url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id]) }}">
                    {{ $s->name }}
                  </a>
                </td>
                <td>
                  <span class="badge bg-secondary">{{ $s->scope_type }}</span>
                  @if ($s->repository_id)
                    <small class="text-muted">repo #{{ $s->repository_id }}</small>
                  @endif
                </td>
                <td>{{ $s->frequency }}</td>
                <td><code>{{ $s->algorithm }}</code></td>
                <td>{{ $s->batch_size ?: 'All' }}</td>
                <td>
                  @if ($s->is_enabled)
                    <span class="badge bg-success">{{ __('Enabled') }}</span>
                  @else
                    <span class="badge bg-secondary">{{ __('Disabled') }}</span>
                  @endif
                </td>
                <td>{{ $s->last_run_at ?? '—' }}</td>
                <td>{{ $s->next_run_at ?? '—' }}</td>
                <td>{{ $s->total_runs }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary btn-sm js-toggle-schedule" data-id="{{ $s->id }}" title="{{ __('Toggle') }}">
                      <i class="fas {{ $s->is_enabled ? 'fa-pause' : 'fa-play' }}"></i>
                    </button>
                    <button class="btn btn-outline-success btn-sm js-run-schedule" data-id="{{ $s->id }}" title="{{ __('Run Now') }}">
                      <i class="fas fa-bolt"></i>
                    </button>
                    <a href="{{ url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id]) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Edit') }}">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn btn-outline-danger btn-sm js-delete-schedule" data-id="{{ $s->id }}" title="{{ __('Delete') }}">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-muted text-center py-3">{{ __('No schedules configured. Create one to begin automated verification.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.js-toggle-schedule').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id = this.dataset.id;
      fetch('/api/integrity/schedule/' + id + '/toggle', {method: 'POST'})
        .then(function(r) { return r.json(); })
        .then(function() { location.reload(); });
    });
  });
  document.querySelectorAll('.js-run-schedule').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Run this schedule now?')) return;
      var id = this.dataset.id;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
      fetch('/api/integrity/schedule/' + id + '/run', {method: 'POST'})
        .then(function(r) { return r.json(); })
        .then(function(data) { location.reload(); })
        .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="fas fa-bolt"></i>'; });
    });
  });
  document.querySelectorAll('.js-delete-schedule').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Delete this schedule? This cannot be undone.')) return;
      var id = this.dataset.id;
      fetch('/api/integrity/schedule/' + id + '/delete', {method: 'POST'})
        .then(function(r) { return r.json(); })
        .then(function() { location.reload(); });
    });
  });
});
</script>
