@php
$stats = $stats ?? (object)[];
$recentRuns = $recentRuns ?? [];
$recentFailures = $recentFailures ?? [];
$passRate = $stats->pass_rate ?? null;
$outcomes = $stats->recent_outcomes ?? [];
$backlog = $backlog ?? 0;
$throughput = $throughput ?? [];
$dailyTrend = $dailyTrend ?? [];
$repoBreakdown = $repoBreakdown ?? [];
$failureBreakdown = $failureBreakdown ?? [];
$formatBreakdown = $formatBreakdown ?? [];
$storageGrowth = $storageGrowth ?? [];
$repositories = $repositories ?? [];
$filterRepositoryId = $filterRepositoryId ?? null;
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-shield-alt me-2"></i>{{ __('Integrity Dashboard') }}</h1>
    <div>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'export']) }}" class="btn btn-outline-success btn-sm me-1">
        <i class="fas fa-download me-1"></i>{{ __('Export') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'policies']) }}" class="btn btn-outline-warning btn-sm me-1">
        <i class="fas fa-archive me-1"></i>{{ __('Policies') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'holds']) }}" class="btn btn-outline-danger btn-sm me-1">
        <i class="fas fa-lock me-1"></i>{{ __('Holds') }}
      </a>
      <a href="{{ url_for(['module' => 'integrity', 'action' => 'alerts']) }}" class="btn btn-outline-dark btn-sm me-1">
        <i class="fas fa-bell me-1"></i>{{ __('Alerts') }}
      </a>
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

  {{-- Repository filter --}}
  @if (!empty($repositories))
  <form method="get" action="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label small mb-0">{{ __('Filter by Repository') }}</label>
        <select name="repository_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">{{ __('All Repositories') }}</option>
          @foreach ($repositories as $repo)
            <option value="{{ $repo->id }}" {{ $filterRepositoryId == $repo->id ? 'selected' : '' }}>{{ $repo->name }}</option>
          @endforeach
        </select>
      </div>
      @if ($filterRepositoryId)
        <div class="col-auto">
          <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear Filter') }}</a>
        </div>
      @endif
    </div>
  </form>
  @endif

  {{-- Stats cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-2">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Master Objects') }}</h6>
          <h2 class="mb-0">{{ number_format($stats->total_master_objects ?? 0) }}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Total Verifications') }}</h6>
          <h2 class="mb-0">{{ number_format($stats->total_verifications ?? 0) }}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100 {{ $passRate !== null && $passRate < 95 ? 'border-warning' : '' }}">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Pass Rate') }}</h6>
          <h2 class="mb-0 {{ $passRate !== null && $passRate < 95 ? 'text-warning' : 'text-success' }}">
            {{ $passRate !== null ? $passRate . '%' : 'N/A' }}
          </h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
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
    <div class="col-md-2">
      <div class="card text-center h-100 {{ $backlog > 0 ? 'border-info' : '' }}">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Never Verified') }}</h6>
          <h2 class="mb-0 {{ $backlog > 0 ? 'text-info' : 'text-success' }}">{{ number_format($backlog) }}</h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Throughput (7d)') }}</h6>
          <h3 class="mb-0">{{ number_format($throughput['objects_per_hour'] ?? 0) }} <small class="text-muted">obj/hr</small></h3>
          <small class="text-muted">{{ $throughput['gb_per_hour'] ?? 0 }} GB/hr</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Storage Growth KPI --}}
  @if (!empty($storageGrowth['daily']))
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Storage Scanned (30d)') }}</h6>
          <h3 class="mb-0">{{ number_format(($storageGrowth['total_bytes'] ?? 0) / 1073741824, 1) }} GB</h3>
          <small class="text-muted">{{ __('Avg') }}: {{ $storageGrowth['avg_gb_per_day'] ?? 0 }} GB/day</small>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Daily Trend (30 days) — Chart.js --}}
  @if (!empty($dailyTrend))
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Daily Verification Trend (30 days)') }}</h5>
    </div>
    <div class="card-body">
      <canvas id="dailyTrendChart" height="80"></canvas>
    </div>
  </div>
  @endif

  <div class="row g-3 mb-4">
    {{-- Repository Breakdown --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Repository Breakdown') }}</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Repository') }}</th>
                  <th class="text-end">{{ __('Total') }}</th>
                  <th class="text-end">{{ __('Passed') }}</th>
                  <th class="text-end">{{ __('Failed') }}</th>
                  <th class="text-end">{{ __('Pass Rate') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($repoBreakdown as $repo)
                  <tr>
                    <td>
                      <a href="{{ url_for(['module' => 'integrity', 'action' => 'index', 'repository_id' => $repo->repository_id]) }}">{{ $repo->repo_name }}</a>
                    </td>
                    <td class="text-end">{{ number_format($repo->total) }}</td>
                    <td class="text-end">{{ number_format($repo->passed) }}</td>
                    <td class="text-end {{ $repo->failed > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($repo->failed) }}</td>
                    <td class="text-end {{ $repo->pass_rate < 95 ? 'text-warning fw-bold' : 'text-success' }}">{{ $repo->pass_rate }}%</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-muted text-center py-3">{{ __('No repository data yet.') }}</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Failure Type Breakdown --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Failure Type Breakdown (30 days)') }}</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Outcome') }}</th>
                  <th class="text-end">{{ __('Count') }}</th>
                  <th class="text-end">{{ __('Percentage') }}</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $failTotal = array_sum(array_map(fn($f) => $f->cnt ?? 0, $failureBreakdown));
                @endphp
                @forelse ($failureBreakdown as $fb)
                  <tr>
                    <td><span class="badge bg-danger">{{ $fb->outcome }}</span></td>
                    <td class="text-end">{{ number_format($fb->cnt) }}</td>
                    <td class="text-end">{{ $failTotal > 0 ? round(($fb->cnt / $failTotal) * 100, 1) : 0 }}%</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-muted text-center py-3">{{ __('No failures in the last 30 days.') }}</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Format Breakdown --}}
  @if (!empty($formatBreakdown))
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Format Breakdown') }}</h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Format') }}</th>
                  <th class="text-end">{{ __('Total') }}</th>
                  <th class="text-end">{{ __('Passed') }}</th>
                  <th class="text-end">{{ __('Failed') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($formatBreakdown as $fmt)
                  <tr>
                    <td>{{ $fmt->format_name }}</td>
                    <td class="text-end">{{ number_format($fmt->total) }}</td>
                    <td class="text-end">{{ number_format($fmt->passed) }}</td>
                    <td class="text-end {{ $fmt->failed > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($fmt->failed) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

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
                <td>{{ $run->schedule_name ?? "\xE2\x80\x94" }}</td>
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
                <td class="text-truncate" style="max-width:400px">{{ $f->file_path ?? "\xE2\x80\x94" }}</td>
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

{{-- Chart.js for daily trend --}}
@if (!empty($dailyTrend))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" @cspNonce></script>
<script @cspNonce>
(function() {
  var trendData = @php echo json_encode(array_map(function($d) {
    return ['day' => substr($d->day, 5), 'passed' => (int)$d->passed, 'failed' => (int)$d->failed];
  }, $dailyTrend)); @endphp;

  var ctx = document.getElementById('dailyTrendChart');
  if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: trendData.map(function(d) { return d.day; }),
        datasets: [
          { label: 'Passed', data: trendData.map(function(d) { return d.passed; }), backgroundColor: '#198754', borderRadius: 2 },
          { label: 'Failed', data: trendData.map(function(d) { return d.failed; }), backgroundColor: '#dc3545', borderRadius: 2 }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
      }
    });
  }
})();
</script>
@endif
