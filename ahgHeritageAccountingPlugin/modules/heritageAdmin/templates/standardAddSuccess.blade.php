@extends('layouts.page')

@section('title', __('Accounting Standard'))

@section('content')
@php
$isEdit = isset($standard) && $standard;
@endphp

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardList']) }}" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0">
            <i class="fas fa-balance-scale me-2"></i>
            {{ $isEdit ? __('Edit Accounting Standard') : __('Add Accounting Standard') }}
        </h1>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Standard Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required
                                       value="{{ $isEdit ? $standard->code : '' }}"
                                       placeholder="e.g. GRAP103, IPSAS45" maxlength="20"
                                       style="text-transform: uppercase;">
                                <small class="text-muted">{{ __('Unique identifier, uppercase') }}</small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="{{ $isEdit ? $standard->name : '' }}"
                                       placeholder="e.g. GRAP 103 Heritage Assets">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Country / Region') }} <span class="text-danger">*</span></label>
                                <input type="text" name="country" class="form-control" required
                                       value="{{ $isEdit ? $standard->country : '' }}"
                                       placeholder="e.g. South Africa, International">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Sort Order') }}</label>
                                <input type="number" name="sort_order" class="form-control"
                                       value="{{ $isEdit ? $standard->sort_order : 99 }}" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="{{ __('Brief description of the standard and its applicability...') }}">{{ $isEdit ? $standard->description : '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Requirements') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Valuation Methods Allowed') }}</label>
                            @php
                            $selectedMethods = $isEdit && $standard->valuation_methods
                                ? json_decode($standard->valuation_methods, true)
                                : [];
                            @endphp
                            <div class="row">
                                @foreach($valuationMethods as $key => $label)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="valuation_methods[]" value="{{ $key }}"
                                               class="form-check-input" id="vm_{{ $key }}"
                                               {{ in_array($key, $selectedMethods) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vm_{{ $key }}">
                                            {{ __($label) }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Disclosure Requirements') }}</label>
                            <textarea name="disclosure_requirements" class="form-control" rows="5"
                                      placeholder="{{ __('One requirement per line...') }}">@if($isEdit && $standard->disclosure_requirements)@php
                                $reqs = json_decode($standard->disclosure_requirements, true);
                                echo is_array($reqs) ? implode("\n", $reqs) : '';
                            @endphp @endif</textarea>
                            <small class="text-muted">{{ __('Enter one disclosure requirement per line') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Settings') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   {{ (!$isEdit || $standard->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>{{ __('Active') }}</strong>
                                <br><small class="text-muted">{{ __('Available for selection') }}</small>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="capitalisation_required" value="1" class="form-check-input" id="capitalisation_required"
                                   {{ ($isEdit && $standard->capitalisation_required) ? 'checked' : '' }}>
                            <label class="form-check-label" for="capitalisation_required">
                                <strong>{{ __('Capitalisation Required') }}</strong>
                                <br><small class="text-muted">{{ __('Monetary value must be recorded') }}</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-1"></i>{{ $isEdit ? __('Update Standard') : __('Add Standard') }}
                    </button>
                    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardList']) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
