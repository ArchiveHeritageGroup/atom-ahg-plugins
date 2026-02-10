@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_consultations') }}">Consultations</a></li>
            <li class="breadcrumb-item active">{{ $id ? 'Edit' : 'Log' }} Consultation</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="bi bi-{{ $id ? 'pencil' : 'plus-circle' }} me-2"></i>
        {{ $id ? 'Edit Consultation' : 'Log Consultation' }}
    </h1>

    @if ($object)
        <div class="alert alert-info">
            <i class="bi bi-archive me-2"></i>
            <strong>Record:</strong>
            <a href="{{ url_for(['module' => 'informationobject', 'slug' => $object->slug]) }}">{{ $object->title ?? $object->identifier ?? 'Untitled' }}</a>
        </div>
    @endif

    <form method="post" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Consultation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Community <span class="text-danger">*</span></label>
                                <select name="community_id" class="form-select" required>
                                    <option value="">Select community</option>
                                    @foreach ($communities as $community)
                                        <option value="{{ $community->id }}" {{ ($consultation->community_id ?? '') == $community->id ? 'selected' : '' }}>
                                            {{ $community->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Consultation Type <span class="text-danger">*</span></label>
                                <select name="consultation_type" class="form-select" required>
                                    @foreach ($consultationTypes as $value => $label)
                                        <option value="{{ $value }}" {{ ($consultation->consultation_type ?? '') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="consultation_date" class="form-control" required value="{{ $consultation->consultation_date ?? date('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Method <span class="text-danger">*</span></label>
                                <select name="consultation_method" class="form-select" required>
                                    @foreach ($consultationMethods as $value => $label)
                                        <option value="{{ $value }}" {{ ($consultation->consultation_method ?? '') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" value="{{ $consultation->location ?? '' }}">
                            </div>
                        </div>

                        @if (!$object)
                            <div class="mb-3">
                                <label class="form-label">Linked Record (optional)</label>
                                <input type="number" name="information_object_id" class="form-control" value="{{ $consultation->information_object_id ?? $objectId ?? '' }}" placeholder="Information Object ID">
                                <div class="form-text">Enter an object ID if this consultation relates to a specific record</div>
                            </div>
                        @else
                            <input type="hidden" name="information_object_id" value="{{ $object->id }}">
                        @endif
                    </div>
                </div>

                <!-- Attendees -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Attendees</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Community Representatives</label>
                            <textarea name="community_representatives" class="form-control" rows="2" placeholder="Names of community members present">{{ $consultation->community_representatives ?? '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Institution Representatives</label>
                            <textarea name="institution_representatives" class="form-control" rows="2" placeholder="Names of institution staff present">{{ $consultation->institution_representatives ?? '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Other Attendees</label>
                            <textarea name="attendees" class="form-control" rows="2" placeholder="Any other attendees">{{ $consultation->attendees ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Summary & Outcomes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Summary & Outcomes</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Summary <span class="text-danger">*</span></label>
                            <textarea name="summary" class="form-control" rows="5" required placeholder="Describe what was discussed...">{{ $consultation->summary ?? '' }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Outcomes</label>
                            <textarea name="outcomes" class="form-control" rows="4" placeholder="What was decided or agreed upon...">{{ $consultation->outcomes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Follow-up -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Follow-up</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" name="follow_up_date" class="form-control" value="{{ $consultation->follow_up_date ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ ($consultation->status ?? 'completed') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Follow-up Notes</label>
                            <textarea name="follow_up_notes" class="form-control" rows="3" placeholder="What needs to happen next...">{{ $consultation->follow_up_notes ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            {{ $id ? 'Save Changes' : 'Log Consultation' }}
                        </button>
                        <a href="{{ url_for('@icip_consultations') }}" class="btn btn-outline-secondary w-100">
                            Cancel
                        </a>
                    </div>
                </div>

                <!-- Confidentiality -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Confidentiality</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input type="checkbox" name="is_confidential" value="1" class="form-check-input" id="isConfidential" {{ ($consultation->is_confidential ?? 0) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isConfidential">
                                Mark as Confidential
                            </label>
                        </div>
                        <div class="form-text">Confidential consultations are hidden from public reports and object ICIP views</div>
                    </div>
                </div>

                <!-- Type Guide -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Consultation Types</h6>
                    </div>
                    <div class="card-body small">
                        <dl class="mb-0">
                            <dt>Initial Contact</dt>
                            <dd class="text-muted">First contact with community</dd>

                            <dt>Consent Request</dt>
                            <dd class="text-muted">Seeking permission for use</dd>

                            <dt>Access Request</dt>
                            <dd class="text-muted">Request to view materials</dd>

                            <dt>Repatriation</dt>
                            <dd class="text-muted">Returning materials to community</dd>

                            <dt>Digitisation</dt>
                            <dd class="text-muted">Digital copying projects</dd>

                            <dt>Exhibition</dt>
                            <dd class="text-muted">Display of materials</dd>

                            <dt>Publication</dt>
                            <dd class="text-muted">Publishing materials</dd>

                            <dt>Research</dt>
                            <dd class="text-muted">Research using materials</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script {!! $csp_nonce !!}>
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
@endsection
