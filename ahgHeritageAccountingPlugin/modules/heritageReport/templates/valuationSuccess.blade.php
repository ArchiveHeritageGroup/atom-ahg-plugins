@extends('layouts.page')

@section('title', __('Valuation Report'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-dollar-sign me-2"></i>{{ __('Valuation Report') }}</h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>{{ __('Print') }}</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">{{ __('Assets with Valuations') }}</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Identifier') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Last Valuation') }}</th>
                            <th class="text-end">{{ __('Valuation Amount') }}</th>
                            <th class="text-end">{{ __('Carrying Amount') }}</th>
                            <th class="text-end">{{ __('Revaluation Surplus') }}</th>
                            <th class="text-end">{{ __('Impairment') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                            <tr>
                                <td>{{ $asset->identifier ?: '-' }}</td>
                                <td>{{ $asset->title ?: '-' }}</td>
                                <td>{{ $asset->class_name ?: '-' }}</td>
                                <td>{!! $asset->last_valuation_date !!}</td>
                                <td class="text-end">{{ number_format($asset->last_valuation_amount, 2) }}</td>
                                <td class="text-end">{{ number_format($asset->current_carrying_amount, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($asset->revaluation_surplus, 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($asset->impairment_loss, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
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
