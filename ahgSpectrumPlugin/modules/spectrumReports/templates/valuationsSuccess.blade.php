@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <h4>{{ __('Summary') }}</h4>
    <p><strong>{{ $summary['total'] }}</strong> {{ __('valuations') }}</p>
    <p><strong>R {{ number_format($summary['totalValue'], 2) }}</strong><br><small>{{ __('Total Value') }}</small></p>
    <hr>
    <a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'index']) }}" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</a>
</div>
@endsection

@section('title')
<h1><i class="fas fa-dollar-sign"></i> {{ __('Valuations Report') }}</h1>
@endsection

@section('content')
@if(empty($valuations))
<div class="alert alert-info">{{ __('No valuations recorded.') }}</div>
@else
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th>{{ __('Object') }}</th><th>{{ __('Date') }}</th><th>{{ __('Value') }}</th><th>{{ __('Type') }}</th><th>{{ __('Valuator') }}</th></tr>
        </thead>
        <tbody>
        @foreach($valuations as $v)
        <tr>
            <td>@if($v->slug)<a href="/{{ $v->slug }}">{{ $v->title ?? 'Untitled' }}</a>@else - @endif</td>
            <td>{{ $v->valuation_date ?? '-' }}</td>
            <td><strong>R {{ number_format($v->valuation_amount ?? 0, 2) }}</strong></td>
            <td>{{ $v->valuation_type ?? '-' }}</td>
            <td>{{ $v->valuer_name ?? $v->valued_by ?? '-' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
