@php
$run = $run ?? null;
$ledgerEntries = $ledgerEntries ?? [];
$outcomeBreakdown = $outcomeBreakdown ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-play-circle me-2"></i>{{ __('Run') }} #{{ $run->id ?? '' }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'runs']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Runs') }}
    </a>
  </div>

  @if ($run)
  {{-- Run summary --}}
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Run Details') }}</h5></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">{{ __('Status') }}</dt>
            <dd class="col-sm-8">
              @php
                $statusClass = match($run->status) {
                    'completed' => 'bg-success', 'running' => 'bg-info', 'partial' => 'bg-warning',
                    'failed' => 'bg-danger', 'timeout' => 'bg-warning', default => 'bg-secondary',
                };
              @endphp
              <span class="badge {{ $statusClass }}">{{ $run->status }}</span>
            </dd>
            <dt class="col-sm-4">{{ __('Schedule') }}</dt>
            <dd class="col-sm-8">{{ $run->schedule_name ?? '—' }}</dd>
            <dt class="col-sm-4">{{ __('Algorithm') }}</dt>
            <dd class="col-sm-8"><code>{{ $run->algorithm }}</code></dd>
            <dt class="col-sm-4">{{ __('Triggered') }}</dt>
            <dd class="col-sm-8">{{ $run->triggered_by }} {{ $run->triggered_by_user ? '(' . $run->triggered_by_user . ')' : '' }}</dd>
            <dt class="col-sm-4">{{ __('Started') }}</dt>
            <dd class="col-sm-8">{{ $run->started_at }}</dd>
            <dt class="col-sm-4">{{ __('Completed') }}</dt>
            <dd class="col-sm-8">{{ $run->completed_at ?? '—' }}</dd>
            @if ($run->error_message)
              <dt class="col-sm-4">{{ __('Error') }}</dt>
              <dd class="col-sm-8 text-danger">{{ $run->error_message }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Counters') }}</h5></div>
        <div class="card-body">
          <div class="row g-2 text-center">
            <div class="col-4"><div class="p-2 bg-light rounded"><small class="text-muted d-block">{{ __('Scanned') }}</small><strong>{{ number_format($run->objects_scanned) }}</strong></div></div>
            <div class="col-4"><div class="p-2 bg-light rounded"><small class="text-muted d-block">{{ __('Passed') }}</small><strong class="text-success">{{ number_format($run->objects_passed) }}</strong></div></div>
            <div class="col-4"><div class="p-2 bg-light rounded"><small class="text-muted d-block">{{ __('Failed') }}</small><strong class="text-danger">{{ number_format($run->objects_failed) }}</strong></div></div>
            <div class="col-4"><div class="p-2 bg-light rounded"><small class="text-muted d-block">{{ __('Missing') }}</small><strong>{{ number_format($run->objects_missing) }}</strong></div></div>
            <div class="col-4"><div class="p-2 bg-light rounded"><small class="text-muted d-block">{{ __('Errors') }}</small><strong>{{ number_format($run->objects_error) }}</strong></div></div>
            <div class="col-4"><div class="p-2 bg-light rounded"><small class="text-muted d-block">{{ __('Skipped') }}</small><strong>{{ number_format($run->objects_skipped) }}</strong></div></div>
          </div>

          @if (!empty($outcomeBreakdown))
            <hr>
            <h6>{{ __('Outcome Breakdown') }}</h6>
            @foreach ($outcomeBreakdown as $outcome => $cnt)
              <span class="badge {{ $outcome === 'pass' ? 'bg-success' : 'bg-danger' }} me-1">{{ $outcome }}: {{ $cnt }}</span>
            @endforeach
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Ledger entries for this run --}}
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Verification Entries') }} <small class="text-muted">({{ count($ledgerEntries) }})</small></h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>{{ __('Object') }}</th>
              <th>{{ __('Outcome') }}</th>
              <th>{{ __('File') }}</th>
              <th>{{ __('Size') }}</th>
              <th>{{ __('Hash Match') }}</th>
              <th>{{ __('Duration') }}</th>
              <th>{{ __('Verified') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ledgerEntries as $e)
              <tr class="{{ $e->outcome !== 'pass' ? 'table-danger' : '' }}">
                <td>{{ $e->digital_object_id }}</td>
                <td><span class="badge {{ $e->outcome === 'pass' ? 'bg-success' : 'bg-danger' }}">{{ $e->outcome }}</span></td>
                <td class="text-truncate" style="max-width:300px" title="{{ $e->file_path ?? '' }}">{{ basename($e->file_path ?? '—') }}</td>
                <td>{{ $e->file_size ? number_format($e->file_size) : '—' }}</td>
                <td>
                  @if ($e->hash_match === null) —
                  @elseif ($e->hash_match) <i class="fas fa-check text-success"></i>
                  @else <i class="fas fa-times text-danger"></i>
                  @endif
                </td>
                <td>{{ $e->duration_ms ? $e->duration_ms . 'ms' : '—' }}</td>
                <td>{{ $e->verified_at }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-muted text-center py-3">{{ __('No entries.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif
</main>
