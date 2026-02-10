@extends('layouts.page')

@section('title', __('National Treasury Report'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="fas fa-file-alt me-2"></i>{{ __('National Treasury Report') }}</h1>
                <p class="text-muted mb-0">{{ __('GRAP 103 Heritage Assets - Financial Year %1%', ['%1%' => $financialYear]) }}</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>{{ __('Print') }}</button>
        </div>
    </div>

    <!-- Summary by Class -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">{{ __('Summary by Asset Class') }}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Asset Class') }}</th>
                        <th class="text-end">{{ __('Count') }}</th>
                        <th class="text-end">{{ __('Total Carrying Amount') }}</th>
                        <th class="text-end">{{ __('Total Impairment') }}</th>
                        <th class="text-end">{{ __('Revaluation Surplus') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = 0; $grandImpairment = 0; $grandSurplus = 0; $grandCount = 0; @endphp
                    @foreach($byClass as $class)
                        @php
                        $grandTotal += $class->total_value;
                        $grandImpairment += $class->total_impairment;
                        $grandSurplus += $class->total_surplus;
                        $grandCount += $class->count;
                        @endphp
                        <tr>
                            <td>{{ $class->class_name ?: 'Unclassified' }}</td>
                            <td class="text-end">{{ number_format($class->count) }}</td>
                            <td class="text-end">{{ number_format($class->total_value, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($class->total_impairment, 2) }}</td>
                            <td class="text-end text-success">{{ number_format($class->total_surplus, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td>{{ __('Grand Total') }}</td>
                        <td class="text-end">{{ number_format($grandCount) }}</td>
                        <td class="text-end">{{ number_format($grandTotal, 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($grandImpairment, 2) }}</td>
                        <td class="text-end text-success">{{ number_format($grandSurplus, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Detailed Asset List -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">{{ __('Heritage Asset Register') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Asset ID') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Basis') }}</th>
                            <th class="text-end">{{ __('Cost') }}</th>
                            <th class="text-end">{{ __('Carrying Amount') }}</th>
                            <th>{{ __('Acquisition Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
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
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-print-none">
        <a href="{{ url_for(['module' => 'grapCompliance', 'action' => 'dashboard']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to GRAP Dashboard') }}
        </a>
    </div>
</div>
@endsection
