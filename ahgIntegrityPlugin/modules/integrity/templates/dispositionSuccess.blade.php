@php
$queue = $queue ?? [];
$stats = $stats ?? [];
$policies = $policies ?? [];
$filterStatus = $filterStatus ?? '';
$filterPolicyId = $filterPolicyId ?? '';
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clipboard-check me-2"></i>{{ __('Disposition Queue') }}</h1>
    <div>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
      </a>
      <button class="btn btn-warning btn-sm" id="btnScanEligible">
        <i class="fas fa-search me-1"></i>{{ __('Scan for Eligible') }}
      </button>
    </div>
  </div>

  {{-- Status summary --}}
  @if (!empty($stats))
  <div class="row g-2 mb-3">
    @foreach ($stats as $status => $count)
      <div class="col-auto">
        <span class="badge bg-{{ $status === 'eligible' ? 'info' : ($status === 'approved' ? 'success' : ($status === 'held' ? 'danger' : 'secondary')) }} fs-6">
          {{ $status }}: {{ $count }}
        </span>
      </div>
    @endforeach
  </div>
  @endif

  {{-- Filters --}}
  <form class="row g-2 mb-3" method="get" action="{{ url_for(['module' => 'integrity', 'action' => 'disposition']) }}">
    <div class="col-auto">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">{{ __('All statuses') }}</option>
        @foreach (['eligible', 'pending_review', 'approved', 'rejected', 'held', 'disposed'] as $s)
          <option value="{{ $s }}" {{ $filterStatus === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-auto">
      <select name="policy_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">{{ __('All policies') }}</option>
        @foreach ($policies as $p)
          <option value="{{ $p->id }}" {{ $filterPolicyId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
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
              <th>{{ __('Policy') }}</th>
              <th>{{ __('IO ID') }}</th>
              <th>{{ __('DO ID') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Eligible At') }}</th>
              <th>{{ __('Reviewed By') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($queue as $item)
              <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->policy_name ?? "\xE2\x80\x94" }}</td>
                <td>{{ $item->information_object_id }}</td>
                <td>{{ $item->digital_object_id ?? "\xE2\x80\x94" }}</td>
                <td>
                  <span class="badge bg-{{ $item->status === 'eligible' ? 'info' : ($item->status === 'approved' ? 'success' : ($item->status === 'held' ? 'danger' : ($item->status === 'disposed' ? 'dark' : 'secondary'))) }}">
                    {{ $item->status }}
                  </span>
                </td>
                <td>{{ $item->eligible_at }}</td>
                <td>{{ $item->reviewed_by ?? "\xE2\x80\x94" }}</td>
                <td>
                  @if (in_array($item->status, ['eligible', 'pending_review']))
                    <button class="btn btn-sm btn-outline-success me-1" data-disposition-action="{{ $item->id }}" data-action-type="approved" title="{{ __('Approve') }}">
                      <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-disposition-action="{{ $item->id }}" data-action-type="rejected" title="{{ __('Reject') }}">
                      <i class="fas fa-times"></i>
                    </button>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-muted text-center py-3">{{ __('No disposition queue entries.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('btnScanEligible').addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Scanning...';
  fetch('/api/integrity/retention/scan', { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      alert('Scan complete: ' + (d.queued || 0) + ' items queued');
      location.reload();
    });
});
document.querySelectorAll('[data-disposition-action]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.getAttribute('data-disposition-action');
    var action = this.getAttribute('data-action-type');
    var notes = prompt('Notes (optional):') || '';
    fetch('/api/integrity/disposition/' + id + '/action', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'disposition_action=' + action + '&notes=' + encodeURIComponent(notes)
    }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
  });
});
</script>
