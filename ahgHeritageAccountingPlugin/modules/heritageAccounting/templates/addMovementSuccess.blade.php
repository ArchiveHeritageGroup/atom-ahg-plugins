@extends('layouts.page')

@section('title', __('Add Movement'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-exchange-alt me-2"></i>{{ __('Add Movement') }}</h1>
            <p class="text-muted">{{ $asset->object_identifier ?? '' }} - {{ $asset->object_title ?? 'Untitled' }}</p>
        </div>
    </div>

    @if (isset($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">{{ __('Movement Details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Movement Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Movement Type') }} <span class="text-danger">*</span></label>
                        <select name="movement_type" class="form-select" required>
                            <option value="">{{ __('-- Select --') }}</option>
                            <option value="loan_out">{{ __('Loan Out') }}</option>
                            <option value="loan_return">{{ __('Loan Return') }}</option>
                            <option value="transfer">{{ __('Transfer') }}</option>
                            <option value="exhibition">{{ __('Exhibition') }}</option>
                            <option value="conservation">{{ __('Conservation') }}</option>
                            <option value="storage_change">{{ __('Storage Change') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" name="amount" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('From Location') }}</label>
                        <input type="text" name="from_location" class="form-control" value="{{ $asset->current_location ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('To Location') }}</label>
                        <input type="text" name="to_location" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Reference Number') }}</label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Authorized By') }}</label>
                        <input type="text" name="authorized_by" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i>{{ __('Save Movement') }}</button>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
@endsection
