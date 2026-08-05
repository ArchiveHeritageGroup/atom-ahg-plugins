@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <h4>{{ __('Heritage Assets') }}</h4>
    <ul class="list-unstyled">
        <li><a href="{{ url_for('@spectrum_index?slug=' . $resource->slug) }}"><i class="fas fa-arrow-left me-2"></i>{{ __('Back to Collections Procedures') }}</a></li>
        <li><a href="{{ url_for(['module' => 'spectrum', 'action' => 'dashboard']) }}"><i class="fas fa-layer-group me-2"></i>{{ __('Collections Dashboard') }}</a></li>
    </ul>
    <hr>
    <p class="small text-muted">
        {{ __('Heritage asset accounting complies with international standards including IPSAS 17/31 and local standards such as GRAP 103 (South Africa).') }}
    </p>
</div>
@endsection

@section('title')
<h1><i class="fas fa-landmark"></i> {{ __('Heritage Assets') }}</h1>
@endsection

@section('content')
<div class="heritage-assets-dashboard">
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        {{ __('Heritage Assets: Financial reporting for cultural property, museum collections, and archival materials per international accounting standards.') }}
    </div>

    <!-- This record's heritage asset values. The four institution-wide counters that
         used to sit here (Total Heritage Assets / Valued / Pending / Total Value) were
         computed from a single record, so they always read 1, 1 or 0, and 0 or 1 - a
         dashboard shape around one item. The collection-wide dashboard is a separate
         page; this one answers "what is recorded against this record". -->
    <?php if (!$grapData): ?>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo __('No heritage asset record exists for this item.'); ?>
      </div>
    <?php else: ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-coins me-2"></i><?php echo __('Valuation'); ?></h5>
        </div>
        <table class="table table-sm mb-0">
          <tbody>
            <?php
            $rows = [
                __('Current value')      => $grapData->current_value ?? null,
                __('Valuation date')     => $grapData->valuation_date ?? null,
                __('Acquisition date')   => $grapData->acquisition_date ?? null,
                __('Acquisition method') => $grapData->acquisition_method ?? null,
                __('Acquisition cost')   => $grapData->acquisition_cost ?? null,
                __('Insurance value')    => $grapData->insurance_value ?? null,
            ];
            foreach ($rows as $label => $value): ?>
              <tr>
                <th class="w-25"><?php echo $label; ?></th>
                <td><?php echo ('' === (string) $value || null === $value) ? '&mdash;' : esc_entities((string) $value); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- Compliance Status -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>{{ __('Compliance Checklist') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ __('Asset Register Complete') }}
                        <span class="badge bg-{{ ($assetRegisterComplete ?? false) ? 'success' : 'danger' }}">
                            {{ ($assetRegisterComplete ?? false) ? __('Yes') : __('No') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ __('Valuations Current (< 5 years)') }}
                        <span class="badge bg-{{ ($valuationsCurrent ?? false) ? 'success' : 'warning' }}">
                            {{ ($valuationsCurrent ?? false) ? __('Yes') : __('Review Needed') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ __('Condition Assessments') }}
                        <span class="badge bg-{{ ($conditionComplete ?? false) ? 'success' : 'warning' }}">
                            {{ ($conditionComplete ?? false) ? __('Complete') : __('Incomplete') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ __('Depreciation Recorded') }}
                        <span class="badge bg-{{ ($depreciationRecorded ?? false) ? 'success' : 'secondary' }}">
                            {{ ($depreciationRecorded ?? false) ? __('Yes') : __('N/A - Heritage') }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ __('Insurance Valuations') }}
                        <span class="badge bg-{{ ($insuranceComplete ?? false) ? 'success' : 'warning' }}">
                            {{ ($insuranceComplete ?? false) ? __('Complete') : __('Incomplete') }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>{{ __('Asset Categories') }}</h5>
                </div>
                <div class="card-body">
                    @if(!empty($categories))
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th class="text-end">{{ __('Count') }}</th>
                                <th class="text-end">{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                            <tr>
                                <td>{{ $cat['name'] ?? 'Uncategorized' }}</td>
                                <td class="text-end">{{ number_format($cat['count'] ?? 0) }}</td>
                                <td class="text-end">{{ number_format($cat['value'] ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted">{{ __('No category data available.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-export me-2"></i>{{ __('Export Heritage Assets Report') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="{{ url_for('@spectrum_grap_dashboard?slug=' . $resource->slug . '&export=csv') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-file-csv me-2"></i>{{ __('Export to CSV') }}
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ url_for('@spectrum_grap_dashboard?slug=' . $resource->slug . '&export=xlsx') }}" class="btn btn-outline-success w-100">
                        <i class="fas fa-file-excel me-2"></i>{{ __('Export to Excel') }}
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ url_for('@spectrum_grap_dashboard?slug=' . $resource->slug . '&export=pdf') }}" class="btn btn-outline-danger w-100">
                        <i class="fas fa-file-pdf me-2"></i>{{ __('Export to PDF') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style @cspNonce>
.heritage-assets-dashboard .card {
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.heritage-assets-dashboard .card-header {
    font-weight: bold;
}
</style>
@endsection
