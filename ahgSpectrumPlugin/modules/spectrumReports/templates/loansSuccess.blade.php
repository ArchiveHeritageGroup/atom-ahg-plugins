@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <h4>{{ __('Summary') }}</h4>
    <ul class="list-unstyled">
        <li><i class="fas fa-arrow-down text-success me-2"></i>{{ __('Loans In:') }} {{ $summary['totalIn'] }}</li>
        <li><i class="fas fa-arrow-up text-warning me-2"></i>{{ __('Loans Out:') }} {{ $summary['totalOut'] }}</li>
    </ul>
    <hr>
    <a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'index']) }}" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</a>
</div>
@endsection

@section('title')
<h1><i class="fas fa-exchange-alt"></i> {{ __('Loans Report') }}</h1>
@endsection

@section('content')
<h4 class="text-success"><i class="fas fa-arrow-down me-2"></i>{{ __('Loans In') }} ({{ count($loansIn) }})</h4>
@if(empty($loansIn))
<p class="text-muted">{{ __('No loans in recorded.') }}</p>
@else
<div class="table-responsive mb-4">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th>{{ __('Object') }}</th><th>{{ __('Lender') }}</th><th>{{ __('Start') }}</th><th>{{ __('End') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @foreach($loansIn as $l)
        <tr>
            <td>@if($l->slug)<a href="/{{ $l->slug }}">{{ $l->title ?? 'Untitled' }}</a>@else - @endif</td>
            <td>{{ $l->lender_name ?? $l->lender ?? '-' }}</td>
            <td>{{ $l->loan_start_date ?? $l->start_date ?? '-' }}</td>
            <td>{{ $l->loan_end_date ?? $l->end_date ?? '-' }}</td>
            <td><span class="badge bg-info">{{ $l->status ?? 'Active' }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<h4 class="text-warning"><i class="fas fa-arrow-up me-2"></i>{{ __('Loans Out') }} ({{ count($loansOut) }})</h4>
@if(empty($loansOut))
<p class="text-muted">{{ __('No loans out recorded.') }}</p>
@else
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th>{{ __('Object') }}</th><th>{{ __('Borrower') }}</th><th>{{ __('Start') }}</th><th>{{ __('End') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @foreach($loansOut as $l)
        <tr>
            <td>@if($l->slug)<a href="/{{ $l->slug }}">{{ $l->title ?? 'Untitled' }}</a>@else - @endif</td>
            <td>{{ $l->borrower_name ?? $l->borrower ?? '-' }}</td>
            <td>{{ $l->loan_start_date ?? $l->start_date ?? '-' }}</td>
            <td>{{ $l->loan_end_date ?? $l->end_date ?? '-' }}</td>
            <td><span class="badge bg-warning">{{ $l->status ?? 'Active' }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
