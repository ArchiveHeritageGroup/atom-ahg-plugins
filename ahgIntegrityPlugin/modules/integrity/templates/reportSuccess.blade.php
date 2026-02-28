@php
$stats = $stats ?? [];
$outcomeBreakdown = $outcomeBreakdown ?? [];
$monthlyTrend = $monthlyTrend ?? [];
@endphp

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-bar me-2"></i>{{ __('Integrity Report') }}</h1>
    <a href="{{ url_for(['module' => 'integrity', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
  </div>

  {{-- Summary cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Master Objects') }}</h6>
          <h3 class="mb-0">{{ number_format($stats['total_master_objects'] ?? 0) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Verifications') }}</h6>
          <h3 class="mb-0">{{ number_format($stats['total_verifications'] ?? 0) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Pass Rate') }}</h6>
          <h3 class="mb-0 {{ ($stats['pass_rate'] ?? 100) < 95 ? 'text-warning' : 'text-success' }}">
            {{ ($stats['pass_rate'] ?? null) !== null ? $stats['pass_rate'] . '%' : 'N/A' }}
          </h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted">{{ __('Dead Letters') }}</h6>
          <h3 class="mb-0 {{ ($stats['open_dead_letters'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ $stats['open_dead_letters'] ?? 0 }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    {{-- Outcome breakdown --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Outcome Breakdown (All Time)') }}</h5></div>
        <div class="card-body">
          @if (!empty($outcomeBreakdown))
            @php $total = array_sum($outcomeBreakdown); @endphp
            @foreach ($outcomeBreakdown as $outcome => $count)
              @php $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0; @endphp
              <div class="mb-2">
                <div class="d-flex justify-content-between small">
                  <span class="badge {{ $outcome === 'pass' ? 'bg-success' : 'bg-danger' }}">{{ $outcome }}</span>
                  <span>{{ number_format($count) }} ({{ $pct }}%)</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar {{ $outcome === 'pass' ? 'bg-success' : 'bg-danger' }}" style="width: {{ $pct }}%"></div>
                </div>
              </div>
            @endforeach
          @else
            <p class="text-muted mb-0">{{ __('No verification data yet.') }}</p>
          @endif
        </div>
      </div>
    </div>

    {{-- Monthly trend --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0">{{ __('Monthly Trend (12 months)') }}</h5></div>
        <div class="card-body">
          @if (!empty($monthlyTrend))
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead>
                  <tr>
                    <th>{{ __('Month') }}</th>
                    <th class="text-end">{{ __('Total') }}</th>
                    <th class="text-end">{{ __('Passed') }}</th>
                    <th class="text-end">{{ __('Failed') }}</th>
                    <th class="text-end">{{ __('Rate') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($monthlyTrend as $m)
                    @php $rate = $m->total > 0 ? round(($m->passed / $m->total) * 100, 1) : 0; @endphp
                    <tr>
                      <td>{{ $m->month }}</td>
                      <td class="text-end">{{ number_format($m->total) }}</td>
                      <td class="text-end text-success">{{ number_format($m->passed) }}</td>
                      <td class="text-end text-danger">{{ number_format($m->failed) }}</td>
                      <td class="text-end {{ $rate < 95 ? 'text-warning fw-bold' : 'text-success' }}">{{ $rate }}%</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-muted mb-0">{{ __('No monthly data yet.') }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- CLI reference --}}
  <div class="card">
    <div class="card-header"><h5 class="mb-0">{{ __('CLI Report Commands') }}</h5></div>
    <div class="card-body">
      <pre class="bg-dark text-light p-3 rounded mb-0"><code>php symfony integrity:report --summary
php symfony integrity:report --dead-letter
php symfony integrity:report --date-from=2026-01-01 --date-to=2026-02-28
php symfony integrity:report --summary --format=json
php symfony integrity:report --summary --format=csv</code></pre>
    </div>
  </div>
</main>
