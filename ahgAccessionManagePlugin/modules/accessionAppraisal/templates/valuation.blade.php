@php decorate_with('layout_1col') @endphp

@slot('title')
  <h1>
    <?php echo __('Valuation History'); ?>
    <small class="text-muted">&mdash; {{ $accession['identifier'] ?? '' }}</small>
  </h1>
@endslot

@slot('content')
@php
  $flash = $sf_user->getFlash('notice', '');
  $accId = $accession['id'] ?? 0;
  $accTitle = $accession['title'] ?? '';

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
  $methodLabels = [
      'cost' => __('Cost'),
      'market' => __('Market'),
      'income' => __('Income'),
      'replacement' => __('Replacement'),
      'nominal' => __('Nominal'),
  ];
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
    <li class="breadcrumb-item"><a href="{{ url_for('@accession_view_override?slug=' . ($accession['slug'] ?? '')) }}">{{ $accession['identifier'] ?? '' }}</a></li>
    <li class="breadcrumb-item active">{{ __('Valuation History') }}</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="h4 mb-0">{{ $accession['identifier'] ?? '' }}</h2>
    @if (!empty($accTitle))
    <p class="text-muted mb-0">{{ $accTitle }}</p>
    @endif
  </div>
  <div class="btn-group">
    <a href="{{ url_for('@accession_appraisal_form?id=' . $accId) }}" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-clipboard-check me-1"></i>{{ __('Appraisals') }}
    </a>
    <a href="{{ url_for('@accession_valuation_report') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-chart-bar me-1"></i>{{ __('Portfolio Report') }}
    </a>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    {{-- Current Value Highlight --}}
    @if ($currentValuation)
    <div class="card mb-4 border-success">
      <div class="card-header bg-success text-white">
        <i class="fas fa-coins me-2"></i>{{ __('Current Value') }}
      </div>
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="display-6 fw-bold">
              {{ $currentValuation->currency ?? 'ZAR' }} {{ number_format($currentValuation->monetary_value, 2) }}
            </div>
          </div>
          <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
              <tr>
                <th class="text-muted pe-3">{{ __('Date') }}</th>
                <td>{{ date('d M Y', strtotime($currentValuation->valuation_date)) }}</td>
              </tr>
              <tr>
                <th class="text-muted pe-3">{{ __('Method') }}</th>
                <td>{{ $methodLabels[$currentValuation->method ?? ''] ?? ($currentValuation->method ? ucfirst($currentValuation->method) : '&mdash;') }}</td>
              </tr>
              <tr>
                <th class="text-muted pe-3">{{ __('Valuer') }}</th>
                <td>{{ $currentValuation->valuer ?? '&mdash;' }}</td>
              </tr>
              <tr>
                <th class="text-muted pe-3">{{ __('Type') }}</th>
                <td>
                  <span class="badge bg-{{ $typeBadgeColors[$currentValuation->valuation_type] ?? 'secondary' }}">
                    {{ $typeLabels[$currentValuation->valuation_type] ?? ucfirst($currentValuation->valuation_type) }}
                  </span>
                </td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- Valuation History Table --}}
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-history me-2"></i>{{ __('Valuation History') }}
      </div>
      <div class="card-body p-0">
        @if (is_array($valuations) && count($valuations) > 0)
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Type') }}</th>
                <th class="text-end">{{ __('Amount') }}</th>
                <th>{{ __('Currency') }}</th>
                <th>{{ __('Valuer') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Notes') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($valuations as $v)
              <tr>
                <td>{{ date('d M Y', strtotime($v->valuation_date)) }}</td>
                <td>
                  <span class="badge bg-{{ $typeBadgeColors[$v->valuation_type] ?? 'secondary' }}">
                    {{ $typeLabels[$v->valuation_type] ?? ucfirst($v->valuation_type) }}
                  </span>
                </td>
                <td class="text-end fw-bold">{{ number_format($v->monetary_value, 2) }}</td>
                <td>{{ $v->currency ?? 'ZAR' }}</td>
                <td>{{ $v->valuer ?? '&mdash;' }}</td>
                <td>{{ $methodLabels[$v->method ?? ''] ?? ($v->method ? ucfirst($v->method) : '&mdash;') }}</td>
                <td>
                  @if (!empty($v->notes))
                  <span class="text-muted small" title="{{ $v->notes }}">{{ \Illuminate\Support\Str::limit($v->notes, 40) }}</span>
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
          <i class="fas fa-coins fa-2x mb-2 d-block"></i>
          {{ __('No valuations recorded yet. Add one using the form.') }}
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    {{-- Add Valuation Form --}}
    <div class="card mb-4 border-primary">
      <div class="card-header bg-primary text-white">
        <i class="fas fa-plus-circle me-2"></i>{{ __('Add Valuation') }}
      </div>
      <div class="card-body">
        <form method="post" action="{{ url_for('@accession_valuation_add?id=' . $accId) }}">
          <div class="mb-3">
            <label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
            <select name="valuation_type" class="form-select" required>
              @foreach ($typeLabels as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Value') }} <span class="text-danger">*</span></label>
            <div class="input-group">
              <select name="currency" class="form-select" style="max-width: 80px;">
                @foreach (['ZAR', 'USD', 'EUR', 'GBP'] as $cur)
                <option value="{{ $cur }}">{{ $cur }}</option>
                @endforeach
              </select>
              <input type="number" name="monetary_value" class="form-control" step="0.01" placeholder="0.00" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Valuation Date') }} <span class="text-danger">*</span></label>
            <input type="date" name="valuation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Valuer') }}</label>
            <input type="text" name="valuer" class="form-control" placeholder="{{ __('Name of valuer or organisation') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Method') }}</label>
            <select name="method" class="form-select">
              <option value="">{{ __('-- Select --') }}</option>
              @foreach ($methodLabels as $val => $label)
              <option value="{{ $val }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Reference Document') }}</label>
            <input type="text" name="reference_document" class="form-control" placeholder="{{ __('Report number, file reference, etc.') }}">
          </div>

          <div class="mb-3">
            <label class="form-label">{{ __('Notes') }}</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('Additional notes about this valuation...') }}"></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-save me-1"></i>{{ __('Record Valuation') }}
          </button>
        </form>
      </div>
    </div>

    {{-- GRAP/IPSAS Info --}}
    <div class="card">
      <div class="card-header">
        <i class="fas fa-info-circle me-2"></i>{{ __('Compliance') }}
      </div>
      <div class="card-body">
        <p class="small text-muted mb-2">
          {{ __('Valuation records support compliance with:') }}
        </p>
        <ul class="list-unstyled small mb-0">
          <li><i class="fas fa-check text-success me-1"></i> {{ __('GRAP 103 (Heritage Assets)') }}</li>
          <li><i class="fas fa-check text-success me-1"></i> {{ __('IPSAS 45 (Property, Plant and Equipment)') }}</li>
          <li><i class="fas fa-check text-success me-1"></i> {{ __('NARSSA Audit Requirements') }}</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endslot
