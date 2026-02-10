@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <h4>{{ __('By Method') }}</h4>
    <ul class="list-unstyled">
    @foreach($byMethod as $m)
    <li>{{ ucfirst($m->acquisition_method ?? 'Unknown') }}: <strong>{{ $m->count }}</strong></li>
    @endforeach
    </ul>
    <hr>
    <a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'index']) }}" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</a>
</div>
@endsection

@section('title')
<h1><i class="fas fa-hand-holding"></i> {{ __('Acquisitions Report') }}</h1>
@endsection

@section('content')
@if(empty($acquisitions))
<div class="alert alert-info">{{ __('No acquisitions recorded.') }}</div>
@else
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th>{{ __('Object') }}</th><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th>{{ __('Source') }}</th></tr></thead>
        <tbody>
        @foreach($acquisitions as $a)
        <tr>
            <td>@if($a->slug)<a href="/{{ $a->slug }}">{{ $a->title ?? 'Untitled' }}</a>@else - @endif</td>
            <td>{{ $a->acquisition_date ?? '-' }}</td>
            <td>{{ $a->acquisition_method ?? '-' }}</td>
            <td>{{ $a->source ?? $a->acquired_from ?? '-' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
