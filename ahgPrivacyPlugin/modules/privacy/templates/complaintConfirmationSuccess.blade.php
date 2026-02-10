@extends('layouts.page')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>{{ __('Complaint Submitted Successfully') }}</h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="fas fa-envelope-open-text fa-4x text-success mb-4"></i>
                    <h3>{{ __('Thank you for your submission') }}</h3>
                    <p class="lead">{{ __('Your complaint has been received and will be reviewed by our privacy team.') }}</p>
                    
                    <div class="alert alert-info d-inline-block">
                        <strong>{{ __('Reference Number:') }}</strong>
                        <span class="fs-4 ms-2">{{ $complaint->reference_number }}</span>
                    </div>

                    <p class="text-muted">{{ __('Please save this reference number. You will need it to check the status of your complaint.') }}</p>

                    <hr class="my-4">

                    <div class="row text-start">
                        <div class="col-md-6">
                            <p><strong>{{ __('Complaint Type:') }}</strong><br>
                            {{ ucwords(str_replace('_', ' ', $complaint->complaint_type)) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('Submitted:') }}</strong><br>
                            {{ date('d M Y H:i', strtotime($complaint->created_at)) }}</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-grid gap-2 d-md-flex justify-content-center">
                        <a href="{{ url_for(['module' => 'privacy', 'action' => 'index']) }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>{{ __('Return to Privacy Page') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
