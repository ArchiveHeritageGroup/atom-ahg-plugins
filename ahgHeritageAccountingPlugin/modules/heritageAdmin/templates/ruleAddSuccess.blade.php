@extends('layouts.page')

@section('title', __('Compliance Rule'))

@section('content')
@php
$isEdit = isset($rule) && $rule;
@endphp

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleList']) }}" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0">
            <i class="fas fa-clipboard-check me-2"></i>
            {{ $isEdit ? __('Edit Compliance Rule') : __('Add Compliance Rule') }}
        </h1>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Rule Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Accounting Standard') }} <span class="text-danger">*</span></label>
                                <select name="standard_id" class="form-select" required>
                                    <option value="">{{ __('Select Standard...') }}</option>
                                    @foreach($standards as $s)
                                    <option value="{{ $s->id }}"
                                        {{ ($isEdit && $rule->standard_id == $s->id) || (!$isEdit && $preselectedStandard == $s->id) ? 'selected' : '' }}>
                                        {{ $s->code }} - {{ $s->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ ($isEdit && $rule->category === $cat) ? 'selected' : '' }}>
                                        {{ ucfirst($cat) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Rule Code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required
                                       value="{{ $isEdit ? $rule->code : '' }}"
                                       placeholder="e.g. REC001" maxlength="50" style="text-transform: uppercase;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Rule Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   value="{{ $isEdit ? $rule->name : '' }}"
                                   placeholder="e.g. Asset Class Required">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="2">{{ $isEdit ? $rule->description : '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Error Message') }} <span class="text-danger">*</span></label>
                            <input type="text" name="error_message" class="form-control" required
                                   value="{{ $isEdit ? $rule->error_message : '' }}"
                                   placeholder="Message shown when rule fails">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Standard Reference') }}</label>
                                <input type="text" name="reference" class="form-control"
                                       value="{{ $isEdit ? $rule->reference : '' }}"
                                       placeholder="e.g. GRAP 103.14">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Sort Order') }}</label>
                                <input type="number" name="sort_order" class="form-control"
                                       value="{{ $isEdit ? $rule->sort_order : 0 }}" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Validation Logic') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Check Type') }} <span class="text-danger">*</span></label>
                                <select name="check_type" class="form-select" required>
                                    @foreach($checkTypes as $ct)
                                    <option value="{{ $ct }}" {{ ($isEdit && $rule->check_type === $ct) ? 'selected' : '' }}>
                                        {{ $ct }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Field Name') }}</label>
                                <input type="text" name="field_name" class="form-control"
                                       value="{{ $isEdit ? $rule->field_name : '' }}"
                                       placeholder="e.g. asset_class_id">
                                <small class="text-muted">{{ __('Database field to check') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Condition') }}</label>
                                <input type="text" name="condition" class="form-control"
                                       value="{{ $isEdit ? $rule->condition : '' }}"
                                       placeholder="e.g. >0, >=1, !=0">
                                <small class="text-muted">{{ __('For value_check type') }}</small>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0">
                            <strong>{{ __('Available Fields:') }}</strong>
                            <code>asset_class_id</code>, <code>recognition_date</code>, <code>recognition_status</code>,
                            <code>measurement_basis</code>, <code>acquisition_date</code>, <code>acquisition_cost</code>,
                            <code>current_carrying_amount</code>, <code>fair_value_at_acquisition</code>,
                            <code>heritage_significance</code>, <code>significance_statement</code>,
                            <code>restrictions_on_use</code>, <code>restrictions_on_disposal</code>,
                            <code>conservation_requirements</code>, <code>insurance_value</code>
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
                        <div class="mb-3">
                            <label class="form-label">{{ __('Severity') }}</label>
                            @foreach($severities as $sev)
                            @php
                            $colors = ['error' => 'danger', 'warning' => 'warning', 'info' => 'secondary'];
                            @endphp
                            <div class="form-check">
                                <input type="radio" name="severity" value="{{ $sev }}"
                                       class="form-check-input" id="sev_{{ $sev }}"
                                       {{ ($isEdit && $rule->severity === $sev) || (!$isEdit && $sev === 'error') ? 'checked' : '' }}>
                                <label class="form-check-label" for="sev_{{ $sev }}">
                                    <span class="badge bg-{{ $colors[$sev] }}">{{ ucfirst($sev) }}</span>
                                    @if($sev === 'error') - Must fix
                                    @elseif($sev === 'warning') - Should fix
                                    @else - Best practice
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   {{ (!$isEdit || $rule->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>{{ __('Active') }}</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-1"></i>{{ $isEdit ? __('Update Rule') : __('Add Rule') }}
                    </button>
                    <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleList']) }}" class="btn btn-outline-secondary">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
