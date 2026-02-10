@extends('layouts.page')

@section('title', __('Add Journal Entry'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-book me-2"></i>{{ __('Add Journal Entry') }}</h1>
            <p class="text-muted">{{ $asset->object_identifier ?? '' }} - {{ $asset->object_title ?? 'Untitled' }}</p>
        </div>
    </div>

    @if (isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">{{ __('Journal Entry Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Journal Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="journal_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Journal Type') }} <span class="text-danger">*</span></label>
                        <select name="journal_type" class="form-select" required>
                            <option value="">{{ __('-- Select --') }}</option>
                            <option value="recognition">{{ __('Recognition') }}</option>
                            <option value="revaluation">{{ __('Revaluation') }}</option>
                            <option value="depreciation">{{ __('Depreciation') }}</option>
                            <option value="impairment">{{ __('Impairment') }}</option>
                            <option value="impairment_reversal">{{ __('Impairment Reversal') }}</option>
                            <option value="derecognition">{{ __('Derecognition') }}</option>
                            <option value="adjustment">{{ __('Adjustment') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Journal Number') }}</label>
                        <input type="text" name="journal_number" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Debit Account') }}</label>
                        <input type="text" name="debit_account" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Credit Account') }}</label>
                        <input type="text" name="credit_account" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Debit Amount') }}</label>
                        <input type="number" step="0.01" name="debit_amount" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Credit Amount') }}</label>
                        <input type="number" step="0.01" name="credit_amount" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Prepared By') }}</label>
                        <input type="text" name="prepared_by" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>{{ __('Save Journal Entry') }}</button>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
