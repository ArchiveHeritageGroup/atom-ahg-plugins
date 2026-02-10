@extends('layouts.page')

@section('title', __('Edit Heritage Asset'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>{{ __('Edit Heritage Asset') }}</h1>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>{{ __('Back to View') }}
            </a>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form method="post">
        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Basic Information') }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Linked Record') }}</label>
                                <input type="hidden" name="object_id" value="{{ $asset->object_id }}">
                                <div class="form-control-plaintext border rounded px-3 py-2 bg-light">{{ $objectTitle ?? 'Not linked' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Accounting Standard') }}</label>
                                <select name="accounting_standard_id" class="form-select">
                                    <option value="">{{ __('-- Select Standard --') }}</option>
                                    @foreach($standards as $s)
                                        <option value="{{ $s->id }}" {{ $asset->accounting_standard_id == $s->id ? 'selected' : '' }}>{{ $s->code . ' - ' . $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Asset Class') }}</label>
                                <select name="asset_class_id" class="form-select">
                                    <option value="">{{ __('-- Select Class --') }}</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}" {{ $asset->asset_class_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Sub-class') }}</label>
                                <input type="text" name="asset_sub_class" class="form-control" value="{{ $asset->asset_sub_class ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recognition -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Recognition') }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Recognition Status') }}</label>
                                <select name="recognition_status" class="form-select">
                                    <option value="pending" {{ $asset->recognition_status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                    <option value="recognised" {{ $asset->recognition_status == 'recognised' ? 'selected' : '' }}>{{ __('Recognised') }}</option>
                                    <option value="not_recognised" {{ $asset->recognition_status == 'not_recognised' ? 'selected' : '' }}>{{ __('Not Recognised') }}</option>
                                    <option value="derecognised" {{ $asset->recognition_status == 'derecognised' ? 'selected' : '' }}>{{ __('Derecognised') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Recognition Date') }}</label>
                                <input type="date" name="recognition_date" class="form-control" value="{{ $asset->recognition_date ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Measurement Basis') }}</label>
                                <select name="measurement_basis" class="form-select">
                                    <option value="cost" {{ $asset->measurement_basis == 'cost' ? 'selected' : '' }}>{{ __('Cost') }}</option>
                                    <option value="fair_value" {{ $asset->measurement_basis == 'fair_value' ? 'selected' : '' }}>{{ __('Fair Value') }}</option>
                                    <option value="nominal" {{ $asset->measurement_basis == 'nominal' ? 'selected' : '' }}>{{ __('Nominal') }}</option>
                                    <option value="not_practicable" {{ $asset->measurement_basis == 'not_practicable' ? 'selected' : '' }}>{{ __('Not Practicable') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Recognition Status Reason') }}</label>
                                <textarea name="recognition_status_reason" class="form-control" rows="2">{{ $asset->recognition_status_reason ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acquisition -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Acquisition') }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Acquisition Method') }}</label>
                                <select name="acquisition_method" class="form-select">
                                    <option value="">{{ __('-- Select --') }}</option>
                                    @foreach(['purchase', 'donation', 'bequest', 'transfer', 'found', 'exchange', 'other'] as $m)
                                        <option value="{{ $m }}" {{ $asset->acquisition_method == $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Acquisition Date') }}</label>
                                <input type="date" name="acquisition_date" class="form-control" value="{{ $asset->acquisition_date ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Acquisition Cost') }}</label>
                                <input type="number" step="0.01" name="acquisition_cost" class="form-control" value="{{ $asset->acquisition_cost ?? '0.00' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Fair Value at Acquisition') }}</label>
                                <input type="number" step="0.01" name="fair_value_at_acquisition" class="form-control" value="{{ $asset->fair_value_at_acquisition ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Nominal Value') }}</label>
                                <input type="number" step="0.01" name="nominal_value" class="form-control" value="{{ $asset->nominal_value ?? '1.00' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Donor Name') }}</label>
                                <div class="position-relative">
                                    <input type="text" name="donor_name" id="donorSearch" class="form-control" value="{{ $asset->donor_name ?? '' }}" autocomplete="off">
                                    <div id="donorResults" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Donor Restrictions') }}</label>
                                <textarea name="donor_restrictions" class="form-control" rows="2">{{ $asset->donor_restrictions ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Values -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Carrying Amounts') }}</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Initial Carrying Amount') }}</label>
                                <input type="number" step="0.01" name="initial_carrying_amount" class="form-control" value="{{ $asset->initial_carrying_amount ?? '0.00' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Current Carrying Amount') }}</label>
                                <input type="number" step="0.01" name="current_carrying_amount" class="form-control" value="{{ $asset->current_carrying_amount ?? '0.00' }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Heritage Information -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Heritage Information') }}</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Significance') }}</label>
                            <select name="heritage_significance" class="form-select">
                                <option value="">{{ __('-- Select --') }}</option>
                                @foreach(['exceptional', 'high', 'medium', 'low'] as $sig)
                                    <option value="{{ $sig }}" {{ $asset->heritage_significance == $sig ? 'selected' : '' }}>{{ ucfirst($sig) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Significance Statement') }}</label>
                            <textarea name="significance_statement" class="form-control" rows="3">{{ $asset->significance_statement ?? '' }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Current Location') }}</label>
                            <input type="text" name="current_location" class="form-control" value="{{ $asset->current_location ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Condition') }}</label>
                            <select name="condition_rating" class="form-select">
                                <option value="">{{ __('-- Select --') }}</option>
                                @foreach(['excellent', 'good', 'fair', 'poor', 'critical'] as $cond)
                                    <option value="{{ $cond }}" {{ $asset->condition_rating == $cond ? 'selected' : '' }}>{{ ucfirst($cond) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Insurance -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Insurance') }}</h5></div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="checkbox" name="insurance_required" class="form-check-input" value="1" {{ $asset->insurance_required ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Insurance Required') }}</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Insurance Value') }}</label>
                            <input type="number" step="0.01" name="insurance_value" class="form-control" value="{{ $asset->insurance_value ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Policy Number') }}</label>
                            <input type="text" name="insurance_policy_number" class="form-control" value="{{ $asset->insurance_policy_number ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Provider') }}</label>
                            <input type="text" name="insurance_provider" class="form-control" value="{{ $asset->insurance_provider ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Expiry Date') }}</label>
                            <input type="date" name="insurance_expiry_date" class="form-control" value="{{ $asset->insurance_expiry_date ?? '' }}">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0">{{ __('Notes') }}</h5></div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="4">{{ $asset->notes ?? '' }}</textarea>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-warning btn-lg"><i class="fas fa-save me-2"></i>{{ __('Update Asset') }}</button>
                    <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </div>
        </div>
    </form>
</div>

<style {!! $csp_nonce !!}>
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.autocomplete-dropdown .ac-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}
.autocomplete-dropdown .ac-item:hover {
    background-color: #f5f5f5;
}
.autocomplete-dropdown .ac-item:last-child {
    border-bottom: none;
}
</style>

<script {!! $csp_nonce !!}>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('donorSearch');
    var resultsDiv = document.getElementById('donorResults');
    var debounceTimer;

    if (!searchInput || !resultsDiv) return;

    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(function() {
            fetch('{{ url_for(["module" => "heritageApi", "action" => "actorAutocomplete"]) }}?term=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.length === 0) {
                        resultsDiv.style.display = 'none';
                        return;
                    }
                    resultsDiv.innerHTML = data.map(function(item) {
                        return '<div class="ac-item" data-value="' + item.value.replace(/"/g, '&quot;') + '">' + item.label + '</div>';
                    }).join('');
                    resultsDiv.style.display = 'block';
                })
                .catch(function() { resultsDiv.style.display = 'none'; });
        }, 300);
    });

    resultsDiv.addEventListener('click', function(e) {
        if (e.target.classList.contains('ac-item')) {
            searchInput.value = e.target.dataset.value;
            resultsDiv.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#donorSearch') && !e.target.closest('#donorResults')) {
            resultsDiv.style.display = 'none';
        }
    });
});
</script>
@endsection
