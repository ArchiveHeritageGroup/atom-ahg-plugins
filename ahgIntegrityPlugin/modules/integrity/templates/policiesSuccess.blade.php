@php
$policies = $policies ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-archive me-2"></i>{{ __('Retention Policies') }}</h1>
    <div>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'policyEdit']) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>{{ __('New Policy') }}
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>{{ __('ID') }}</th>
              <th>{{ __('Name') }}</th>
              <th>{{ __('Retention') }}</th>
              <th>{{ __('Trigger') }}</th>
              <th>{{ __('Scope') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($policies as $p)
              <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->retention_period_days > 0 ? $p->retention_period_days . ' days' : 'Indefinite' }}</td>
                <td><span class="badge bg-secondary">{{ $p->trigger_type }}</span></td>
                <td>{{ $p->scope_type }}{{ $p->repository_id ? " (repo #{$p->repository_id})" : '' }}</td>
                <td>
                  <span class="badge {{ $p->is_enabled ? 'bg-success' : 'bg-secondary' }}">
                    {{ $p->is_enabled ? __('Enabled') : __('Disabled') }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" data-policy-toggle="{{ $p->id }}" title="{{ __('Toggle') }}">
                    <i class="fas {{ $p->is_enabled ? 'fa-pause' : 'fa-play' }}"></i>
                  </button>
                  <a href="{{ url_for(['module' => 'integrity', 'action' => 'policyEdit', 'id' => $p->id]) }}" class="btn btn-sm btn-outline-secondary me-1" title="{{ __('Edit') }}">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-danger" data-policy-delete="{{ $p->id }}" title="{{ __('Delete') }}">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-muted text-center py-3">{{ __('No retention policies defined.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script @cspNonce>
document.querySelectorAll('[data-policy-toggle]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.getAttribute('data-policy-toggle');
    fetch('/api/integrity/policy/' + id + '/toggle', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
  });
});
document.querySelectorAll('[data-policy-delete]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('Delete this policy?')) return;
    var id = this.getAttribute('data-policy-delete');
    fetch('/api/integrity/policy/' + id + '/delete', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
  });
});
</script>
