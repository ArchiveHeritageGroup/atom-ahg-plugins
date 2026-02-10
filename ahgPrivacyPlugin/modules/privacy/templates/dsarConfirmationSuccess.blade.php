@extends('layouts.page')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>{{ __('Request Submitted Successfully') }}</h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-envelope-open-text fa-4x text-success mb-4"></i>
                    
                    <h4>{{ __('Thank you for your request') }}</h4>
                    
                    <div class="bg-light p-3 rounded my-4">
                        <p class="mb-1">{{ __('Your Reference Number:') }}</p>
                        <h3 class="text-primary mb-0">{{ $dsar->reference_number }}</h3>
                    </div>

                    <p class="mb-1"><strong>{{ __('Due Date:') }}</strong> {{ $dsar->due_date }}</p>
                    <p class="text-muted">{{ __('We will respond within 30 days as required by POPIA.') }}</p>

                    <hr class="my-4">

                    <p>{{ __('A confirmation email has been sent to:') }}</p>
                    <p><strong>{{ $dsar->requestor_email }}</strong></p>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="{{ url_for(['module' => 'privacy', 'action' => 'dsarStatus']) }}" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>{{ __('Check Status') }}
                        </a>
                        <a href="{{ url_for(['module' => 'privacy', 'action' => 'index']) }}" class="btn btn-secondary">
                            {{ __('Back to Privacy') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
