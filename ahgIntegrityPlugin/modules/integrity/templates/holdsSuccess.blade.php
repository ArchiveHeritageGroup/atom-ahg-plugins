@php
$holds = $holds ?? [];
$filterStatus = $filterStatus ?? '';
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-lock me-2"></i>{{ __('Legal Holds') }}</h1>
    <div>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
      </a>
      <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#placeHoldModal">
        <i class="fas fa-plus me-1"></i>{{ __('Place Hold') }}
      </button>
    </div>
  </div>

  {{-- Filters --}}
  <form class="row g-2 mb-3" method="get" action="{{ url_for(['module' => 'integrity', 'action' => 'holds']) }}">
    <div class="col-auto">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">{{ __('All statuses') }}</option>
        <option value="active" {{ $filterStatus === 'active' ? 'selected' : '' }}>active</option>
        <option value="released" {{ $filterStatus === 'released' ? 'selected' : '' }}>released</option>
      </select>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>{{ __('ID') }}</th>
              <th>{{ __('IO ID') }}</th>
              <th>{{ __('Reason') }}</th>
              <th>{{ __('Placed By') }}</th>
              <th>{{ __('Placed At') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Released By') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($holds as $h)
              <tr>
                <td>{{ $h->id }}</td>
                <td>{{ $h->information_object_id }}</td>
                <td class="text-truncate" style="max-width:300px">{{ $h->reason }}</td>
                <td>{{ $h->placed_by }}</td>
                <td>{{ $h->placed_at }}</td>
                <td>
                  <span class="badge {{ $h->status === 'active' ? 'bg-danger' : 'bg-secondary' }}">{{ $h->status }}</span>
                </td>
                <td>{{ $h->released_by ?? "\xE2\x80\x94" }}</td>
                <td>
                  @if ($h->status === 'active')
                    <button class="btn btn-sm btn-outline-warning" data-hold-release="{{ $h->id }}" title="{{ __('Release') }}">
                      <i class="fas fa-unlock"></i>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-muted text-center py-3">{{ __('No legal holds.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

{{-- Place Hold Modal --}}
<div class="modal fade" id="placeHoldModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Place Legal Hold') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">{{ __('Information Object ID') }} *</label>
          <input type="number" id="holdIoId" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">{{ __('Reason') }} *</label>
          <textarea id="holdReason" class="form-control" rows="3" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-danger" id="btnPlaceHold">{{ __('Place Hold') }}</button>
      </div>
    </div>
  </div>
</div>

<script @cspNonce>
document.getElementById('btnPlaceHold').addEventListener('click', function() {
  var ioId = document.getElementById('holdIoId').value;
  var reason = document.getElementById('holdReason').value;
  if (!ioId || !reason) { alert('Both fields required'); return; }
  fetch('/api/integrity/hold/place', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'information_object_id=' + ioId + '&reason=' + encodeURIComponent(reason)
  }).then(function(r) { return r.json(); }).then(function(d) {
    if (d.success) { location.reload(); } else { alert(d.error || 'Failed'); }
  });
});
document.querySelectorAll('[data-hold-release]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('Release this hold?')) return;
    var id = this.getAttribute('data-hold-release');
    fetch('/api/integrity/hold/' + id + '/release', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
  });
});
</script>
