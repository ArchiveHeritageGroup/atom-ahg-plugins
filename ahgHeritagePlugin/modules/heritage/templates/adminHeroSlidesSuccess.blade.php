@extends('layouts.page')

@php
// Helper function to unwrap Symfony escaper objects
$unwrap = function ($value) use (&$unwrap) {
    if ($value instanceof sfOutputEscaperObjectDecorator) {
        $raw = $value->getRawValue();
        if (is_object($raw)) {
            return (array) $raw;
        }
        return $raw;
    }
    if ($value instanceof sfOutputEscaperArrayDecorator || $value instanceof Traversable) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = $unwrap($v);
        }
        return $result;
    }
    if (is_array($value)) {
        return array_map($unwrap, $value);
    }
    if (is_object($value)) {
        return (array) $value;
    }
    return $value;
};

$slidesArray = $unwrap($slides);
$editSlideData = isset($editSlide) && $editSlide ? $unwrap($editSlide) : null;
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-images me-2"></i>Hero Slides Management
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'hero-slides'])
@endsection

@section('content')
<div class="heritage-admin-hero-slides">

    <!-- Flash Messages -->
    @if ($sf_user->hasFlash('notice'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! $sf_user->getFlash('notice') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($sf_user->hasFlash('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! $sf_user->getFlash('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Add/Edit Slide Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0">
                {{ $editSlideData ? 'Edit Hero Slide' : 'Add New Hero Slide' }}
            </h2>
        </div>
        <div class="card-body">
            <form action="{{ url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']) }}"
                  method="post" enctype="multipart/form-data">
                <input type="hidden" name="slide_action" value="{{ $editSlideData ? 'update' : 'create' }}">
                @if ($editSlideData)
                    <input type="hidden" name="slide_id" value="{{ $editSlideData['id'] }}">
                @endif

                <div class="row">
                    <!-- Image Upload -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hero Image {{ $editSlideData ? '(leave empty to keep current)' : '*' }}</label>

                        @if ($editSlideData && !empty($editSlideData['image_path']))
                            <div class="mb-2">
                                <img src="{{ $editSlideData['image_path'] }}"
                                     class="img-thumbnail" style="max-height: 100px;" alt="Current image">
                                <br><small class="text-muted">Current image</small>
                            </div>
                        @endif

                        <input type="file" class="form-control mb-2" name="hero_image" id="hero_image"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text">Upload JPG, PNG, WebP, or GIF. Max 10MB. Recommended: 1920x1080px or larger.</div>

                        <div class="mt-2">
                            <label class="form-label small">Or enter image URL:</label>
                            <input type="url" class="form-control form-control-sm" name="image_url"
                                   placeholder="https://example.com/image.jpg"
                                   value="{{ ($editSlideData && strpos($editSlideData['image_path'] ?? '', 'http') === 0) ? $editSlideData['image_path'] : '' }}">
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="col-md-6 mb-3">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="{{ $editSlideData['title'] ?? '' }}"
                                   placeholder="Slide title (optional)">
                        </div>

                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" class="form-control" id="subtitle" name="subtitle"
                                   value="{{ $editSlideData['subtitle'] ?? '' }}"
                                   placeholder="Short subtitle">
                        </div>

                        <div class="mb-3">
                            <label for="image_alt" class="form-label">Image Alt Text</label>
                            <input type="text" class="form-control" id="image_alt" name="image_alt"
                                   value="{{ $editSlideData['image_alt'] ?? '' }}"
                                   placeholder="Describe the image for accessibility">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Description -->
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"
                                  placeholder="Optional longer description">{{ $editSlideData['description'] ?? '' }}</textarea>
                    </div>
                </div>

                <div class="row">
                    <!-- Visual Settings -->
                    <div class="col-md-4 mb-3">
                        <label for="overlay_type" class="form-label">Overlay Type</label>
                        <select class="form-select" id="overlay_type" name="overlay_type">
                            <option value="gradient" {{ ($editSlideData['overlay_type'] ?? 'gradient') === 'gradient' ? 'selected' : '' }}>Gradient</option>
                            <option value="solid" {{ ($editSlideData['overlay_type'] ?? '') === 'solid' ? 'selected' : '' }}>Solid Color</option>
                            <option value="none" {{ ($editSlideData['overlay_type'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="overlay_color" class="form-label">Overlay Color</label>
                        <input type="color" class="form-control form-control-color w-100" id="overlay_color" name="overlay_color"
                               value="{{ $editSlideData['overlay_color'] ?? '#000000' }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="overlay_opacity" class="form-label">Overlay Opacity</label>
                        <input type="range" class="form-range" id="overlay_opacity" name="overlay_opacity"
                               min="0" max="1" step="0.1"
                               value="{{ $editSlideData['overlay_opacity'] ?? 0.5 }}">
                        <small class="text-muted">Current: <span id="opacity_value">{{ ($editSlideData['overlay_opacity'] ?? 0.5) * 100 }}%</span></small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="text_position" class="form-label">Text Position</label>
                        <select class="form-select" id="text_position" name="text_position">
                            <option value="left" {{ ($editSlideData['text_position'] ?? 'left') === 'left' ? 'selected' : '' }}>Left</option>
                            <option value="center" {{ ($editSlideData['text_position'] ?? '') === 'center' ? 'selected' : '' }}>Center</option>
                            <option value="right" {{ ($editSlideData['text_position'] ?? '') === 'right' ? 'selected' : '' }}>Right</option>
                            <option value="bottom-left" {{ ($editSlideData['text_position'] ?? '') === 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                            <option value="bottom-right" {{ ($editSlideData['text_position'] ?? '') === 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="display_duration" class="form-label">Display Duration (seconds)</label>
                        <input type="number" class="form-control" id="display_duration" name="display_duration"
                               value="{{ $editSlideData['display_duration'] ?? 8 }}" min="3" max="30">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order"
                               value="{{ $editSlideData['display_order'] ?? 100 }}">
                    </div>
                </div>

                <!-- Call to Action -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cta_text" class="form-label">Button Text</label>
                        <input type="text" class="form-control" id="cta_text" name="cta_text"
                               value="{{ $editSlideData['cta_text'] ?? '' }}"
                               placeholder="e.g., Explore Collection">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="cta_url" class="form-label">Button URL</label>
                        <input type="text" class="form-control" id="cta_url" name="cta_url"
                               value="{{ $editSlideData['cta_url'] ?? '' }}"
                               placeholder="/heritage/search?q=...">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="cta_style" class="form-label">Button Style</label>
                        <select class="form-select" id="cta_style" name="cta_style">
                            <option value="primary" {{ ($editSlideData['cta_style'] ?? 'primary') === 'primary' ? 'selected' : '' }}>Primary (Blue)</option>
                            <option value="secondary" {{ ($editSlideData['cta_style'] ?? '') === 'secondary' ? 'selected' : '' }}>Secondary (Gray)</option>
                            <option value="light" {{ ($editSlideData['cta_style'] ?? '') === 'light' ? 'selected' : '' }}>Light (White)</option>
                            <option value="outline" {{ ($editSlideData['cta_style'] ?? '') === 'outline' ? 'selected' : '' }}>Outline</option>
                        </select>
                    </div>
                </div>

                <!-- Attribution -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="source_collection" class="form-label">Source Collection</label>
                        <input type="text" class="form-control" id="source_collection" name="source_collection"
                               value="{{ $editSlideData['source_collection'] ?? '' }}"
                               placeholder="Name of collection this image is from">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="photographer_credit" class="form-label">Photographer/Credit</label>
                        <input type="text" class="form-control" id="photographer_credit" name="photographer_credit"
                               value="{{ $editSlideData['photographer_credit'] ?? '' }}"
                               placeholder="Photo by...">
                    </div>
                </div>

                <!-- Options -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ken_burns" name="ken_burns" value="1"
                                   {{ ($editSlideData['ken_burns'] ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="ken_burns">Ken Burns Effect</label>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1"
                                   {{ ($editSlideData['is_enabled'] ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_enabled">Enabled</label>
                        </div>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date (optional)</label>
                        <input type="date" class="form-control" id="start_date" name="start_date"
                               value="{{ $editSlideData['start_date'] ?? '' }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date (optional)</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                               value="{{ $editSlideData['end_date'] ?? '' }}">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>{{ $editSlideData ? 'Update Slide' : 'Add Slide' }}
                    </button>
                    @if ($editSlideData)
                        <a href="{{ url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Current Slides -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Current Hero Slides</h2>
            <span class="badge bg-secondary">{{ count($slidesArray) }} slides</span>
        </div>
        <div class="card-body">
            @if (empty($slidesArray))
                <p class="text-muted text-center py-4">
                    <i class="fas fa-images fs-1 d-block mb-2"></i>
                    No hero slides configured yet. Add your first slide above.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Image</th>
                                <th>Title</th>
                                <th>Position</th>
                                <th>Order</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($slidesArray as $slide)
                                @php $slide = (array) $slide; @endphp
                                <tr>
                                    <td>
                                        @if (!empty($slide['image_path']))
                                            <img src="{{ $slide['image_path'] }}"
                                                 class="img-thumbnail" style="max-width: 80px; max-height: 50px; object-fit: cover;"
                                                 alt="{{ $slide['image_alt'] ?? 'Hero slide' }}">
                                        @else
                                            <span class="text-muted"><i class="fas fa-image"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $slide['title'] ?? '(No title)' }}</strong>
                                        @if (!empty($slide['subtitle']))
                                            <br><small class="text-muted">{{ $slide['subtitle'] }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $slide['text_position'] ?? 'left' }}</small></td>
                                    <td>{{ $slide['display_order'] }}</td>
                                    <td>{{ $slide['display_duration'] }}s</td>
                                    <td>
                                        @if ($slide['is_enabled'])
                                            <span class="badge bg-success">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary">Disabled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ url_for(['module' => 'heritage', 'action' => 'adminHeroSlides', 'edit' => $slide['id']]) }}"
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-pencil-alt me-1"></i>Edit
                                            </a>
                                            <form action="{{ url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']) }}"
                                                  method="post" class="d-inline">
                                                <input type="hidden" name="slide_action" value="toggle">
                                                <input type="hidden" name="slide_id" value="{{ $slide['id'] }}">
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $slide['is_enabled'] ? 'warning' : 'success' }}">
                                                    <i class="fas fa-{{ $slide['is_enabled'] ? 'eye-slash' : 'eye' }} me-1"></i>{{ $slide['is_enabled'] ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                            <form action="{{ url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']) }}"
                                                  method="post" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this slide?');">
                                                <input type="hidden" name="slide_action" value="delete">
                                                <input type="hidden" name="slide_id" value="{{ $slide['id'] }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
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

<script {!! $csp_nonce !!}>
document.getElementById('overlay_opacity').addEventListener('input', function() {
    document.getElementById('opacity_value').textContent = Math.round(this.value * 100) + '%';
});

// Show selected file name
document.getElementById('hero_image').addEventListener('change', function() {
    var fileName = this.files.length > 0 ? this.files[0].name : '';
    var label = document.getElementById('file_selected_name');
    if (!label) {
        label = document.createElement('small');
        label.id = 'file_selected_name';
        label.className = 'text-success d-block mt-1';
        this.parentNode.insertBefore(label, this.nextSibling);
    }
    if (fileName) {
        label.innerHTML = '<i class="fas fa-check-circle me-1"></i>Selected: ' + fileName;
    } else {
        label.textContent = '';
    }
});
</script>
@endsection
