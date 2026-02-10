@extends('layouts.page')

@section('title', __('Asset Register Report'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-list me-2"></i>{{ __('Asset Register Report') }}</h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>{{ __('Print') }}</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Standard') }}</label>
                    <select name="standard_id" class="form-select">
                        <option value="">{{ __('All Standards') }}</option>
                        @foreach($standards as $s)
                            <option value="{{ $s->id }}" {{ ($filters['standard_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Class') }}</label>
                    <select name="class_id" class="form-select">
                        <option value="">{{ __('All Classes') }}</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ ($filters['class_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="recognised" {{ ($filters['recognition_status'] ?? '') == 'recognised' ? 'selected' : '' }}>{{ __('Recognised') }}</option>
                        <option value="not_recognised" {{ ($filters['recognition_status'] ?? '') == 'not_recognised' ? 'selected' : '' }}>{{ __('Not Recognised') }}</option>
                        <option value="pending" {{ ($filters['recognition_status'] ?? '') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <span>{{ __('Total: %1% assets', ['%1%' => count($assets)]) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Identifier') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Basis') }}</th>
                            <th class="text-end">{{ __('Acquisition Cost') }}</th>
                            <th class="text-end">{{ __('Carrying Amount') }}</th>
                            <th>{{ __('Acquisition Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalCarrying = 0; $totalAcquisition = 0; @endphp
                        @foreach($assets as $asset)
                            @php $totalCarrying += $asset->current_carrying_amount; $totalAcquisition += $asset->acquisition_cost; @endphp
                            <tr>
                                <td>{{ $asset->object_identifier ?: '-' }}</td>
                                <td>{{ $asset->object_title ?: '-' }}</td>
                                <td>{{ $asset->class_name ?: '-' }}</td>
                                <td>{!! ucfirst(str_replace('_', ' ', $asset->recognition_status)) !!}</td>
                                <td>{!! ucfirst($asset->measurement_basis ?: '-') !!}</td>
                                <td class="text-end">{{ number_format($asset->acquisition_cost, 2) }}</td>
                                <td class="text-end">{{ number_format($asset->current_carrying_amount, 2) }}</td>
                                <td>{!! $asset->acquisition_date ?: '-' !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">{{ __('Totals:') }}</td>
                            <td class="text-end">{{ number_format($totalAcquisition, 2) }}</td>
                            <td class="text-end">{{ number_format($totalCarrying, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-print-none">
        <a href="{{ url_for(['module' => 'heritageReport', 'action' => 'index']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Reports') }}
        </a>
    </div>
</div>
@endsection
