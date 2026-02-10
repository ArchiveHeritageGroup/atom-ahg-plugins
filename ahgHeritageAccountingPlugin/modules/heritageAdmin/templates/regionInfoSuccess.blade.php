@extends('layouts.page')

@section('title'){{ $region->region_name }}@endsection

@section('content')
@php
$countries = is_array($region->countries) ? $region->countries : [];
@endphp

<h1><i class="fas fa-globe me-2"></i>{{ $region->region_name }}</h1>

<p class="text-muted mb-4">
  <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regions']) }}">
    <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Regions') }}
  </a>
</p>

<!-- Status Banner -->
@if($isActive)
  <div class="alert alert-primary">
    <i class="fas fa-check-circle me-2"></i>
    <strong>{{ __('This is the currently active region for compliance checking.') }}</strong>
  </div>
@elseif($region->is_installed)
  <div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    {{ __('This region is installed and ready to use.') }}
    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionSetActive', 'region' => $region->region_code]) }}" class="alert-link ms-2">
      {{ __('Set as active') }} <i class="fas fa-arrow-right"></i>
    </a>
  </div>
@else
  <div class="alert alert-secondary">
    <i class="fas fa-info-circle me-2"></i>
    {{ __('This region is not installed.') }}
    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionInstall', 'region' => $region->region_code]) }}" class="alert-link ms-2">
      {{ __('Install now') }} <i class="fas fa-arrow-right"></i>
    </a>
  </div>
@endif

<div class="row">
  <!-- Region Details Card -->
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-info-circle me-2"></i>{{ __('Region Details') }}
      </div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tr>
            <th class="w-40">{{ __('Code') }}</th>
            <td><code>{{ $region->region_code }}</code></td>
          </tr>
          <tr>
            <th>{{ __('Name') }}</th>
            <td>{{ $region->region_name }}</td>
          </tr>
          <tr>
            <th>{{ __('Default Currency') }}</th>
            <td><span class="badge bg-primary">{{ $region->default_currency }}</span></td>
          </tr>
          <tr>
            <th>{{ __('Financial Year Start') }}</th>
            <td>{{ $region->financial_year_start }}</td>
          </tr>
          <tr>
            <th>{{ __('Regulatory Body') }}</th>
            <td>{{ $region->regulatory_body }}</td>
          </tr>
          <tr>
            <th>{{ __('Countries') }}</th>
            <td>
              @foreach($countries as $country)
                <span class="badge bg-light text-dark me-1 mb-1">{{ $country }}</span>
              @endforeach
            </td>
          </tr>
          @if($region->is_installed && $region->installed_at)
            <tr>
              <th>{{ __('Installed') }}</th>
              <td>{{ $region->installed_at }}</td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  <!-- Accounting Standard Card -->
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header">
        <i class="fas fa-balance-scale me-2"></i>{{ __('Accounting Standard') }}
      </div>
      <div class="card-body">
        @if($standard)
          <table class="table table-borderless mb-0">
            <tr>
              <th class="w-40">{{ __('Code') }}</th>
              <td><span class="badge bg-success">{{ $standard->code }}</span></td>
            </tr>
            <tr>
              <th>{{ __('Name') }}</th>
              <td>{{ $standard->name }}</td>
            </tr>
            <tr>
              <th>{{ __('Description') }}</th>
              <td class="small">{{ $standard->description }}</td>
            </tr>
            <tr>
              <th>{{ __('Capitalisation') }}</th>
              <td>
                @if($standard->capitalisation_required)
                  <span class="badge bg-warning text-dark">Required</span>
                @else
                  <span class="badge bg-secondary">Optional</span>
                @endif
              </td>
            </tr>
            @if($standard->valuation_methods)
              @php $methods = json_decode($standard->valuation_methods, true) ?: []; @endphp
              <tr>
                <th>{{ __('Valuation Methods') }}</th>
                <td>
                  @foreach($methods as $method)
                    <span class="badge bg-light text-dark me-1 mb-1">{{ $method }}</span>
                  @endforeach
                </td>
              </tr>
            @endif
          </table>
        @else
          <p class="text-muted mb-0">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ __('Standard not installed. Install the region to add the accounting standard.') }}
          </p>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Compliance Rules -->
@if($region->is_installed && count($rules) > 0)
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>
        <i class="fas fa-check-square me-2"></i>{{ __('Compliance Rules') }}
        <span class="badge bg-secondary ms-2">{{ count($rules) }}</span>
      </span>
      <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $standard->id]) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-pencil-alt me-1"></i>{{ __('Manage Rules') }}
      </a>
    </div>
    <div class="card-body p-0">
      @php
        $rulesByCategory = [];
        foreach ($rules as $rule) {
            $cat = $rule->category ?? 'other';
            if (!isset($rulesByCategory[$cat])) {
                $rulesByCategory[$cat] = [];
            }
            $rulesByCategory[$cat][] = $rule;
        }
      @endphp

      <div class="accordion accordion-flush" id="rulesAccordion">
        @foreach($rulesByCategory as $category => $categoryRules)
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $category }}">
                <span class="text-capitalize">{{ $category }}</span>
                <span class="badge bg-secondary ms-2">{{ count($categoryRules) }}</span>
              </button>
            </h2>
            <div id="collapse-{{ $category }}" class="accordion-collapse collapse" data-bs-parent="#rulesAccordion">
              <div class="accordion-body p-0">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>{{ __('Code') }}</th>
                      <th>{{ __('Name') }}</th>
                      <th>{{ __('Severity') }}</th>
                      <th>{{ __('Reference') }}</th>
                      <th>{{ __('Status') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($categoryRules as $rule)
                      <tr>
                        <td><code>{{ $rule->code }}</code></td>
                        <td>{{ $rule->name }}</td>
                        <td>
                          @php
                            $severityClass = match($rule->severity) {
                                'error' => 'danger',
                                'warning' => 'warning',
                                default => 'info'
                            };
                          @endphp
                          <span class="badge bg-{{ $severityClass }}">{{ $rule->severity }}</span>
                        </td>
                        <td class="small text-muted">{{ $rule->reference ?? '-' }}</td>
                        <td>
                          @if($rule->is_active)
                            <span class="badge bg-success">Active</span>
                          @else
                            <span class="badge bg-secondary">Inactive</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif

<!-- Actions -->
<div class="mt-4">
  @if($region->is_installed && !$isActive)
    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionSetActive', 'region' => $region->region_code]) }}"
       class="btn btn-primary"
       onclick="return confirm('Set {{ $region->region_name }} as the active region?');">
      <i class="fas fa-check-circle me-1"></i>{{ __('Set as Active Region') }}
    </a>
    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionUninstall', 'region' => $region->region_code]) }}"
       class="btn btn-outline-danger"
       onclick="return confirm('Uninstall {{ $region->region_name }}? This will remove the standard and all compliance rules.');">
      <i class="fas fa-trash me-1"></i>{{ __('Uninstall Region') }}
    </a>
  @elseif(!$region->is_installed)
    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'regionInstall', 'region' => $region->region_code]) }}"
       class="btn btn-success"
       onclick="return confirm('Install {{ $region->region_name }}?');">
      <i class="fas fa-download me-1"></i>{{ __('Install Region') }}
    </a>
  @endif
</div>
@endsection
