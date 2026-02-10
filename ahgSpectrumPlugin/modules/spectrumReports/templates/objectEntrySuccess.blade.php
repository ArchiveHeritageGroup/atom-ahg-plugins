@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'index']) }}" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</a>
</div>
@endsection

@section('title')
<h1><i class="fas fa-sign-in-alt"></i> {{ __('Object Entry Report') }}</h1>
@endsection

@section('content')
@if(empty($entries))
<div class="alert alert-info">{{ __('No object entries recorded.') }}</div>
@else
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th>{{ __('Object') }}</th><th>{{ __('Entry Date') }}</th><th>{{ __('Entry Number') }}</th><th>{{ __('Depositor') }}</th><th>{{ __('Reason') }}</th></tr></thead>
        <tbody>
        @foreach($entries as $e)
        <tr>
            <td>@if($e->slug)<a href="/{{ $e->slug }}">{{ $e->title ?? 'Untitled' }}</a>@else - @endif</td>
            <td>{{ $e->entry_date ?? '-' }}</td>
            <td>{{ $e->entry_number ?? '-' }}</td>
            <td>{{ $e->depositor ?? '-' }}</td>
            <td>{{ $e->entry_reason ?? '-' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
