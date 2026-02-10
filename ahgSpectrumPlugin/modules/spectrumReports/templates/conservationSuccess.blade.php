@extends('layouts.page')

@section('sidebar')
<div class="sidebar-content">
    <a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'index']) }}" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</a>
</div>
@endsection

@section('title')
<h1><i class="fas fa-tools"></i> {{ __('Conservation Report') }}</h1>
@endsection

@section('content')
@if(empty($treatments))
<div class="alert alert-info">{{ __('No conservation treatments recorded.') }}</div>
@else
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark"><tr><th>{{ __('Object') }}</th><th>{{ __('Date') }}</th><th>{{ __('Treatment') }}</th><th>{{ __('Conservator') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
        @foreach($treatments as $t)
        <tr>
            <td>@if($t->slug)<a href="/{{ $t->slug }}">{{ $t->title ?? 'Untitled' }}</a>@else - @endif</td>
            <td>{{ $t->treatment_date ?? $t->created_at ?? '-' }}</td>
            <td>{{ $t->treatment_type ?? '-' }}</td>
            <td>{{ $t->conservator ?? '-' }}</td>
            <td><span class="badge bg-info">{{ $t->status ?? 'Complete' }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
