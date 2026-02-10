@extends('layouts.page')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'index']) }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-globe me-2"></i>{{ __('Privacy Jurisdictions') }}</span>
        </div>
        <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionAdd']) }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>{{ __('Add Jurisdiction') }}
        </a>
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success">{{ $sf_user->getFlash('success') }}</div>
    @endif
    @if($sf_user->hasFlash('error'))
    <div class="alert alert-danger">{{ $sf_user->getFlash('error') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">{{ __('Order') }}</th>
                        <th width="80">{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Country') }}</th>
                        <th>{{ __('Region') }}</th>
                        <th width="80">{{ __('DSAR') }}</th>
                        <th width="80">{{ __('Breach') }}</th>
                        <th width="80">{{ __('Status') }}</th>
                        <th width="150"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jurisdictions as $j)
                    <tr class="{{ $j->is_active ? '' : 'table-secondary' }}">
                        <td>{{ $j->sort_order }}</td>
                        <td>
                            @if($j->icon)
                            <span class="fi fi-{{ $j->icon }} me-1"></span>
                            @endif
                            <code>{{ strtoupper($j->code) }}</code>
                        </td>
                        <td>
                            <strong>{{ $j->name }}</strong>
                            <br><small class="text-muted">{{ $j->full_name }}</small>
                        </td>
                        <td>{{ $j->country }}</td>
                        <td><span class="badge bg-info">{{ $j->region }}</span></td>
                        <td>{{ $j->dsar_days }} {{ __('days') }}</td>
                        <td>{{ $j->breach_hours ?: '-' }} {{ $j->breach_hours ? __('hrs') : '' }}</td>
                        <td class="text-center">
                            @if($j->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                            <span class="badge bg-danger">{{ __('Disabled') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionEdit', 'id' => $j->id]) }}" 
                               class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionToggle', 'id' => $j->id]) }}" 
                               class="btn btn-sm btn-outline-{{ $j->is_active ? 'warning' : 'success' }}" 
                               title="{{ $j->is_active ? __('Disable') : __('Enable') }}">
                                <i class="fas fa-{{ $j->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionDelete', 'id' => $j->id]) }}" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('{{ __('Delete this jurisdiction?') }}')"
                               title="{{ __('Delete') }}">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('About Jurisdictions') }}</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">{{ __('Each jurisdiction defines the data protection law applicable to your organization. Configure DSAR response times, breach notification requirements, and regulatory contacts for each jurisdiction you operate in.') }}</p>
        </div>
    </div>
</div>
@endsection
