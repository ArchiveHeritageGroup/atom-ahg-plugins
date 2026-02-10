@extends('layouts.page')

@php
// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) {
    if (is_array($val)) return $val;
    if ($val instanceof Traversable) return iterator_to_array($val);
    return [];
};

$itemRaw = $toArray($itemData ?? []);
$item = $itemRaw['item'] ?? null;
$history = $toArray($itemRaw['history'] ?? []);
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-pencil-alt-square me-2"></i>Edit Item
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'item'])

@if ($item)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Item Info</h6>
    </div>
    <div class="card-body">
        <small class="text-muted d-block mb-2">Reference Code</small>
        <p class="mb-3">{{ $item->identifier ?? '-' }}</p>

        <small class="text-muted d-block mb-2">Level</small>
        <p class="mb-3">{{ $item->level_of_description ?? '-' }}</p>

        <small class="text-muted d-block mb-2">Created</small>
        <p class="mb-0">{{ date('M d, Y', strtotime($item->created_at)) }}</p>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Recent Changes</h6>
    </div>
    <div class="card-body p-0">
        @if (!empty($history))
        <div class="list-group list-group-flush">
            @foreach (array_slice($history, 0, 5) as $log)
            <div class="list-group-item">
                <small class="text-muted">{{ date('M d, H:i', strtotime($log->created_at)) }}</small>
                <br>
                <span class="badge bg-secondary">{{ $log->action }}</span>
                {{ $log->username ?? 'System' }}
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted text-center py-3 mb-0">No history.</p>
        @endif
    </div>
</div>
@endif
@endsection

@section('content')
@if (!$item)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    Item not found.
</div>
@else

<form method="post" action="">
    <!-- Identity Area (ISAD 3.1) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i>Identity Area (ISAD 3.1)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="identifier" class="form-label">Reference Code</label>
                    <input type="text" class="form-control" id="identifier" name="identifier"
                           value="{{ $item->identifier ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label for="level_of_description" class="form-label">Level of Description</label>
                    <select class="form-select" id="level_of_description" name="level_of_description">
                        <option value="">Select...</option>
                        @php $levels = ['Fonds', 'Sub-fonds', 'Collection', 'Series', 'Sub-series', 'File', 'Item']; @endphp
                        @foreach ($levels as $level)
                        <option value="{{ strtolower($level) }}" {{ ($item->level_of_description ?? '') === strtolower($level) ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date" class="form-label">Date(s)</label>
                    <input type="text" class="form-control" id="date" name="date"
                           value="{{ $item->date ?? '' }}"
                           placeholder="e.g., 1920-1945">
                </div>
                <div class="col-12">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="{{ $item->title ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label for="extent_and_medium" class="form-label">Extent and Medium</label>
                    <input type="text" class="form-control" id="extent_and_medium" name="extent_and_medium"
                           value="{{ $item->extent_and_medium ?? '' }}"
                           placeholder="e.g., 3 boxes, 250 photographs">
                </div>
            </div>
        </div>
    </div>

    <!-- Context Area (ISAD 3.2) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-diagram-3 me-2"></i>Context Area (ISAD 3.2)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="creator" class="form-label">Name of Creator(s)</label>
                    <input type="text" class="form-control" id="creator" name="creator"
                           value="{{ $item->creator ?? '' }}">
                </div>
                <div class="col-12">
                    <label for="administrative_history" class="form-label">Administrative/Biographical History</label>
                    <textarea class="form-control" id="administrative_history" name="administrative_history" rows="4">{{ $item->administrative_history ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="archival_history" class="form-label">Archival History</label>
                    <textarea class="form-control" id="archival_history" name="archival_history" rows="3">{{ $item->archival_history ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="immediate_source" class="form-label">Immediate Source of Acquisition</label>
                    <textarea class="form-control" id="immediate_source" name="immediate_source" rows="2">{{ $item->immediate_source ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Content and Structure Area (ISAD 3.3) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-file-text me-2"></i>Content and Structure (ISAD 3.3)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="scope_and_content" class="form-label">Scope and Content</label>
                    <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="5">{{ $item->scope_and_content ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="appraisal" class="form-label">Appraisal, Destruction and Scheduling</label>
                    <textarea class="form-control" id="appraisal" name="appraisal" rows="3">{{ $item->appraisal ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="accruals" class="form-label">Accruals</label>
                    <textarea class="form-control" id="accruals" name="accruals" rows="2">{{ $item->accruals ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="arrangement" class="form-label">System of Arrangement</label>
                    <textarea class="form-control" id="arrangement" name="arrangement" rows="3">{{ $item->arrangement ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Conditions of Access and Use (ISAD 3.4) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Access and Use (ISAD 3.4)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="access_conditions" class="form-label">Conditions Governing Access</label>
                    <textarea class="form-control" id="access_conditions" name="access_conditions" rows="3">{{ $item->access_conditions ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="reproduction_conditions" class="form-label">Conditions Governing Reproduction</label>
                    <textarea class="form-control" id="reproduction_conditions" name="reproduction_conditions" rows="3">{{ $item->reproduction_conditions ?? '' }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="language" class="form-label">Language/Script of Material</label>
                    <input type="text" class="form-control" id="language" name="language"
                           value="{{ $item->language ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label for="physical_characteristics" class="form-label">Physical Characteristics</label>
                    <input type="text" class="form-control" id="physical_characteristics" name="physical_characteristics"
                           value="{{ $item->physical_characteristics ?? '' }}">
                </div>
                <div class="col-12">
                    <label for="finding_aids" class="form-label">Finding Aids</label>
                    <textarea class="form-control" id="finding_aids" name="finding_aids" rows="2">{{ $item->finding_aids ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Allied Materials Area (ISAD 3.5) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-link-45deg me-2"></i>Allied Materials (ISAD 3.5)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="location_of_originals" class="form-label">Existence and Location of Originals</label>
                    <textarea class="form-control" id="location_of_originals" name="location_of_originals" rows="2">{{ $item->location_of_originals ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="location_of_copies" class="form-label">Existence and Location of Copies</label>
                    <textarea class="form-control" id="location_of_copies" name="location_of_copies" rows="2">{{ $item->location_of_copies ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="related_materials" class="form-label">Related Units of Description</label>
                    <textarea class="form-control" id="related_materials" name="related_materials" rows="2">{{ $item->related_materials ?? '' }}</textarea>
                </div>
                <div class="col-12">
                    <label for="publication_note" class="form-label">Publication Note</label>
                    <textarea class="form-control" id="publication_note" name="publication_note" rows="2">{{ $item->publication_note ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Area (ISAD 3.6) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-sticky me-2"></i>Notes (ISAD 3.6)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="general_note" class="form-label">General Note</label>
                    <textarea class="form-control" id="general_note" name="general_note" rows="4">{{ $item->general_note ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Description Control Area (ISAD 3.7) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0"><i class="fas fa-card-checklist me-2"></i>Description Control (ISAD 3.7)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="archivist_note" class="form-label">Archivist's Note</label>
                    <textarea class="form-control" id="archivist_note" name="archivist_note" rows="3">{{ $item->archivist_note ?? '' }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="rules_or_conventions" class="form-label">Rules or Conventions</label>
                    <textarea class="form-control" id="rules_or_conventions" name="rules_or_conventions" rows="3">{{ $item->rules_or_conventions ?? '' }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="date_of_description" class="form-label">Date(s) of Description</label>
                    <input type="text" class="form-control" id="date_of_description" name="date_of_description"
                           value="{{ $item->date_of_description ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="d-flex justify-content-between">
        <a href="{{ url_for(['module' => 'heritage', 'action' => 'custodianDashboard']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Cancel
        </a>
        <div>
            <button type="submit" name="action" value="save" class="btn btn-primary">
                <i class="fas fa-check me-2"></i>Save Changes
            </button>
            <button type="submit" name="action" value="save_continue" class="btn btn-outline-primary ms-2">
                Save & Continue Editing
            </button>
        </div>
    </div>
</form>

@endif
@endsection
