@extends('layouts.page')

@section('title', __('Heritage Accounting Settings'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i>{{ __('Heritage Accounting Settings') }}</h1>
                <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardList']) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-1"></i>{{ __('Manage Standards') }}
                </a>
            </div>
        </div>
    </div>

    @if($sf_user->hasFlash('notice'))
        <div class="alert alert-success">{!! $sf_user->getFlash('notice') !!}</div>
    @endif

    <form method="post">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>{{ __('Accounting Standards') }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">{{ __('Enable or disable accounting standards for your organization. At least one standard should be enabled.') }}</p>

                <div class="row">
                    @foreach($standards as $std)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 {{ $std->is_active ? 'border-success' : 'border-secondary' }}">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               name="enabled[]"
                                               value="{{ $std->id }}"
                                               id="std_{{ $std->id }}"
                                               {{ $std->is_active ? 'checked' : '' }}
                                               >
                                        <label class="form-check-label" for="std_{{ $std->id }}">
                                            <strong>{{ $std->code }}</strong>
                                        </label>
                                    </div>
                                    <p class="mb-1 mt-2">{{ $std->name }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-globe me-1"></i>{{ $std->country }}
                                    </small>
                                    <div class="mt-2">
                                        @if($std->capitalisation_required)
                                            <span class="badge bg-warning text-dark">Capitalisation Required</span>
                                        @else
                                            <span class="badge bg-info">Capitalisation Optional</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> <em>Required</em> means capitalisation is mandatory under that accounting standard. <em>Optional</em> means capitalisation is at the organisation's discretion. The checkboxes control which standards are available for use in your system.
            </div>
            <div class="card-footer">
                <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) }}" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
                </a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>{{ __('Save Settings') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
