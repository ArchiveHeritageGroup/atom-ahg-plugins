@extends('layouts.page')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-check2-circle me-2"></i>{{ __('Consent Records') }}</h1>
        <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'consentAdd']) }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>{{ __('Record Consent') }}</a>
    </div>
    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success">{{ $sf_user->getFlash('success') }}</div>
    @endif
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('Consent Records') }}</span>
            <span class="badge bg-secondary">{{ count($consents ?? []) }} {{ __('records') }}</span>
        </div>
        <div class="card-body p-0">
            @if(empty($consents))
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                <p class="mb-0">{{ __('No consent records found') }}</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Data Subject') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Purpose') }}</th>
                            <th>{{ __('Consent') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consents as $record)
                        <tr>
                            <td>{{ $record->data_subject_id ?? '' }}</td>
                            <td>{{ $record->subject_name ?? '-' }}</td>
                            <td>{{ $record->purpose ?? '' }}</td>
                            <td>{!! ($record->consent_given ?? 0) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' !!}</td>
                            <td>{{ $record->consent_date ?? '-' }}</td>
                            <td><span class="badge bg-{{ ($record->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($record->status ?? 'active') }}</span></td>
                            <td>
                                <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $record->id]) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'consentEdit', 'id' => $record->id]) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
