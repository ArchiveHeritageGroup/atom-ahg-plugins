@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1><?php echo __('Portfolio Valuation Report'); ?></h1>
@endslot

@slot('content')
@php
  $flash = $sf_user->getFlash('notice', '');
  $totalValue = $report['total_value'] ?? 0;
  $byCurrency = $report['by_currency'] ?? [];
  $byType = $report['by_type'] ?? [];
  $accessionCount = $report['accession_count'] ?? 0;
@endphp

@if ($flash)
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  {{ $flash }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'accessionManage', 'action' => 'dashboard']) }}">{{ __('Accessions') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Valuation Report') }}</li>
  </ol>
</nav>

{{-- Summary Cards --}}
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card border-primary h-100">
      <div class="card-body text-center">
        <div class="text-muted small text-uppercase mb-1">{{ __('Total Portfolio Value') }}</div>
        <div class="display-6 fw-bold text-primary">
          {{ number_format($totalValue, 2) }}
        </div>
        <p class="text-muted small mb-0">{{ __('Aggregate across all currencies') }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-success h-100">
      <div class="card-body text-center">
        <div class="text-muted small text-uppercase mb-1">{{ __('Valued Accessions') }}</div>
        <div class="display-6 fw-bold text-success">
          {{ number_format($accessionCount) }}
        </div>
        <p class="text-muted small mb-0">{{ __('Accessions with at least one valuation') }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-info h-100">
      <div class="card-body text-center">
        <div class="text-muted small text-uppercase mb-1">{{ __('Currencies') }}</div>
        <div class="display-6 fw-bold text-info">
          {{ count($byCurrency) }}
        </div>
        <p class="text-muted small mb-0">{{ __('Distinct currencies in portfolio') }}</p>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  {{-- Breakdown by Currency --}}
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <i class="fas fa-money-bill-wave me-2"></i>{{ __('Breakdown by Currency') }}
      </div>
      <div class="card-body p-0">
        @if (count($byCurrency) > 0)
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Currency') }}</th>
                <th class="text-end">{{ __('Total Value') }}</th>
                <th class="text-end">{{ __('Share') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($byCurrency as $currency => $amount)
              <tr>
                <td>
                  <span class="fw-bold">{{ $currency }}</span>
                </td>
                <td class="text-end fw-bold">{{ number_format($amount, 2) }}</td>
                <td class="text-end">
                  @if ($totalValue > 0)
                  @php $pct = ($amount / $totalValue) * 100; @endphp
                  <div class="d-flex align-items-center justify-content-end">
                    <div class="progress me-2" style="width: 60px; height: 6px;">
                      <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="small">{{ number_format($pct, 1) }}%</span>
                  </div>
                  @else
                  <span class="text-muted">&mdash;</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">
          {{ __('No valuations recorded yet.') }}
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Breakdown by Type --}}
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">
        <i class="fas fa-tags me-2"></i>{{ __('Breakdown by Valuation Type') }}
      </div>
      <div class="card-body p-0">
        @php
          $typeLabels = [
              'initial' => __('Initial'),
              'revaluation' => __('Revaluation'),
              'impairment' => __('Impairment'),
              'disposal' => __('Disposal'),
          ];
          $typeBadgeColors = [
              'initial' => 'primary',
              'revaluation' => 'info',
              'impairment' => 'warning',
              'disposal' => 'danger',
          ];
        @endphp
        @if (count($byType) > 0)
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Type') }}</th>
                <th class="text-end">{{ __('Count') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($byType as $type => $count)
              <tr>
                <td>
                  <span class="badge bg-{{ $typeBadgeColors[$type] ?? 'secondary' }}">
                    {{ $typeLabels[$type] ?? ucfirst($type) }}
                  </span>
                </td>
                <td class="text-end">{{ number_format($count) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-4 text-muted">
          {{ __('No data available.') }}
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Recent Valuations Table --}}
<div class="card">
  <div class="card-header">
    <i class="fas fa-clock me-2"></i>{{ __('Recent Valuations') }}
  </div>
  <div class="card-body p-0">
    @if (is_array($recentValuations) && count($recentValuations) > 0)
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ __('Accession') }}</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Type') }}</th>
            <th class="text-end">{{ __('Amount') }}</th>
            <th>{{ __('Currency') }}</th>
            <th>{{ __('Valuer') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($recentValuations as $rv)
          <tr>
            <td>
              @if (!empty($rv->slug))
              <a href="{{ url_for('@accession_view_override?slug=' . $rv->slug) }}">
                {{ $rv->identifier ?? '&mdash;' }}
              </a>
              @else
              {{ $rv->identifier ?? '&mdash;' }}
              @endif
            </td>
            <td>
              @if (!empty($rv->accession_title))
              {{ \Illuminate\Support\Str::limit($rv->accession_title, 50) }}
              @else
              <span class="text-muted">&mdash;</span>
              @endif
            </td>
            <td>{{ date('d M Y', strtotime($rv->valuation_date)) }}</td>
            <td>
              <span class="badge bg-{{ $typeBadgeColors[$rv->valuation_type] ?? 'secondary' }}">
                {{ $typeLabels[$rv->valuation_type] ?? ucfirst($rv->valuation_type) }}
              </span>
            </td>
            <td class="text-end fw-bold">{{ number_format($rv->monetary_value, 2) }}</td>
            <td>{{ $rv->currency ?? 'ZAR' }}</td>
            <td>{{ $rv->valuer ?? '&mdash;' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="text-center py-4 text-muted">
      <i class="fas fa-coins fa-2x mb-2 d-block"></i>
      {{ __('No valuations recorded across the system yet.') }}
    </div>
    @endif
  </div>
</div>
@endslot
