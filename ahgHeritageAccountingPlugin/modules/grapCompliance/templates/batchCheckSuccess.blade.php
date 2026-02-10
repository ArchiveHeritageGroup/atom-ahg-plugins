@extends('layouts.page')

@section('title', __('GRAP 103 Batch Compliance Check'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-check-double me-2"></i>{{ __('Batch Compliance Check') }}</h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>{{ __('Print') }}</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <span>{{ __('Checked %1% GRAP Assets', ['%1%' => count($results)]) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Identifier') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th class="text-center">{{ __('Score') }}</th>
                            <th class="text-center">{{ __('Passed') }}</th>
                            <th class="text-center">{{ __('Warnings') }}</th>
                            <th class="text-center">{{ __('Failed') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            @php $asset = $result['asset']; $compliance = $result['compliance']; @endphp
                            <tr>
                                <td>
                                    <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) }}">
                                        {{ $asset->object_identifier ?: 'N/A' }}
                                    </a>
                                </td>
                                <td>{{ $asset->object_title ?: '-' }}</td>
                                <td>{{ $asset->class_name ?: '-' }}</td>
                                <td class="text-center">
                                    <strong class="{{ $compliance['summary']['score'] >= 80 ? 'text-success' : ($compliance['summary']['score'] >= 50 ? 'text-warning' : 'text-danger') }}">
                                        {{ $compliance['summary']['score'] }}%
                                    </strong>
                                </td>
                                <td class="text-center text-success">{{ $compliance['summary']['passed'] }}</td>
                                <td class="text-center text-warning">{{ $compliance['summary']['warnings'] }}</td>
                                <td class="text-center text-danger">{{ $compliance['summary']['failed'] }}</td>
                                <td class="text-center">
                                    @if($compliance['summary']['status'] == 'compliant')
                                        <span class="badge bg-success">{{ __('Compliant') }}</span>
                                    @elseif($compliance['summary']['status'] == 'partially_compliant')
                                        <span class="badge bg-warning text-dark">{{ __('Partial') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('Non-Compliant') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ url_for(['module' => 'grapCompliance', 'action' => 'check', 'id' => $asset->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View Details') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        @if(empty($results))
                            <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No GRAP assets to check.') }}</td></tr>
                        @endif
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
