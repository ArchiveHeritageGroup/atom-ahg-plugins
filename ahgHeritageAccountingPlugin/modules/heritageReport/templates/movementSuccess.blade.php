@extends('layouts.page')

@section('title', __('Movement Report'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-exchange-alt me-2"></i>{{ __('Movement Report') }}</h1>
            <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>{{ __('Print') }}</button>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('From') }}</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('To') }}</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">{{ __('Movements: %1% to %2%', ['%1%' => $from, '%2%' => $to]) }}</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Identifier') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('From') }}</th>
                            <th>{{ __('To') }}</th>
                            <th>{{ __('Authorized By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $m)
                            <tr>
                                <td>{!! $m->movement_date !!}</td>
                                <td>{{ $m->identifier ?: '-' }}</td>
                                <td>{{ $m->title ?: '-' }}</td>
                                <td><span class="badge bg-info">{!! ucfirst(str_replace('_', ' ', $m->movement_type)) !!}</span></td>
                                <td>{{ $m->from_location ?: '-' }}</td>
                                <td>{{ $m->to_location ?: '-' }}</td>
                                <td>{{ $m->authorized_by ?: '-' }}</td>
                            </tr>
                        @endforeach
                        @if(empty($movements))
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No movements in this period.') }}</td></tr>
                        @endif
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
