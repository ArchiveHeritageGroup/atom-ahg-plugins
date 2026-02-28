@php
$stats = $stats ?? (object)[];
$recentRuns = $recentRuns ?? [];
$recentFailures = $recentFailures ?? [];
$passRate = $stats->pass_rate ?? null;
$outcomes = $stats->recent_outcomes ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-shield-alt me-2"></i>{{ __('Integrity Dashboard') }}</h1>
    <div>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'schedules']) }}" class="btn btn-outline-primary btn-sm me-1">
        <i class="fas fa-clock me-1"></i>{{ __('Schedules') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'ledger']) }}" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-book me-1"></i>{{ __('Ledger') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'report']) }}" class="btn btn-outline-info btn-sm">
        <i class="fas fa-chart-bar me-1"></i>{{ __('Report') }}
      </a>
    </div>
  </div>

  {{-- Stats cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Master Objects') }}</h6>
          <h2 class="mb-0">{{ number_format($stats->total_master_objects ?? 0) }}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Total Verifications') }}</h6>
          <h2 class="mb-0">{{ number_format($stats->total_verifications ?? 0) }}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100 {{ $passRate !== null && $passRate < 95 ? 'border-warning' : '' }}">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Pass Rate') }}</h6>
          <h2 class="mb-0 {{ $passRate !== null && $passRate < 95 ? 'text-warning' : 'text-success' }}">
            {{ $passRate !== null ? $passRate . '%' : 'N/A' }}
          </h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100 {{ ($stats->open_dead_letters ?? 0) > 0 ? 'border-danger' : '' }}">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Open Dead Letters') }}</h6>
          <h2 class="mb-0 {{ ($stats->open_dead_letters ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
            {{ $stats->open_dead_letters ?? 0 }}
          </h2>
          @if (($stats->open_dead_letters ?? 0) > 0)
            <a href="{{ url_for(['module' => 'integrity', 'action' => 'deadLetter']) }}" class="small">{{ __('View') }}</a>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Schedule summary --}}
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Schedules') }}</h5>
          <span class="badge bg-primary">{{ ($stats->enabled_schedules ?? 0) }}/{{ ($stats->schedule_count ?? 0) }} {{ __('enabled') }}</span>
        </div>
        <div class="card-body">
          @if (!empty($outcomes))
            <h6>{{ __('Outcomes (30 days)') }}</h6>
            <ul class="list-unstyled mb-0">
              @foreach ($outcomes as $outcome => $count)
                <li>
                  <span class="badge {{ $outcome === 'pass' ? 'bg-success' : 'bg-danger' }} me-1">{{ $outcome }}</span>
                  {{ number_format($count) }}
                </li>
              @endforeach
            </ul>
          @else
            <p class="text-muted mb-0">{{ __('No verification data in the last 30 days.') }}</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Last Run') }}</h5>
          <a href="{{ url_for(['module' => 'integrity', 'action' => 'runs']) }}" class="small">{{ __('View all') }}</a>
        </div>
        <div class="card-body">
          @if ($stats->last_run ?? null)
            @php $lr = $stats->last_run; @endphp
            <p class="mb-1">
              <span class="badge {{ $lr->status === 'completed' ? 'bg-success' : 'bg-warning' }}">{{ $lr->status }}</span>
              {{ $lr->started_at }}
            </p>
            <p class="mb-0 text-muted">
              {{ __('Scanned') }}: {{ $lr->objects_scanned ?? 0 }} |
              {{ __('Passed') }}: {{ $lr->objects_passed ?? 0 }} |
              {{ __('Failed') }}: {{ $lr->objects_failed ?? 0 }}
            </p>
          @else
            <p class="text-muted mb-0">{{ __('No runs yet.') }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Recent runs table --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Recent Runs') }}</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>{{ __('Schedule') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Scanned') }}</th>
              <th>{{ __('Passed') }}</th>
              <th>{{ __('Failed') }}</th>
              <th>{{ __('Triggered') }}</th>
              <th>{{ __('Started') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recentRuns as $run)
              <tr>
                <td><a href="{{ url_for(['module' => 'integrity', 'action' => 'runDetail', 'id' => $run->id]) }}">{{ $run->id }}</a></td>
                <td>{{ $run->schedule_name ?? '—' }}</td>
                <td><span class="badge {{ $run->status === 'completed' ? 'bg-success' : ($run->status === 'running' ? 'bg-info' : 'bg-warning') }}">{{ $run->status }}</span></td>
                <td>{{ number_format($run->objects_scanned) }}</td>
                <td>{{ number_format($run->objects_passed) }}</td>
                <td class="{{ $run->objects_failed > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($run->objects_failed) }}</td>
                <td>{{ $run->triggered_by }}</td>
                <td>{{ $run->started_at }}</td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-muted text-center py-3">{{ __('No runs recorded yet.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Recent failures --}}
  @if (!empty($recentFailures))
  <div class="card">
    <div class="card-header bg-danger text-white">
      <h5 class="mb-0">{{ __('Recent Failures') }}</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>{{ __('Object') }}</th>
              <th>{{ __('Outcome') }}</th>
              <th>{{ __('File Path') }}</th>
              <th>{{ __('Verified') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($recentFailures as $f)
              <tr>
                <td>{{ $f->digital_object_id }}</td>
                <td><span class="badge bg-danger">{{ $f->outcome }}</span></td>
                <td class="text-truncate" style="max-width:400px">{{ $f->file_path ?? '—' }}</td>
                <td>{{ $f->verified_at }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif
</main>
