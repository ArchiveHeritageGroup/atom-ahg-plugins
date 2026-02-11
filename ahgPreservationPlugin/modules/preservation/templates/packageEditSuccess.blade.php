@extends('layouts.page')

@section('title')
<h1>
    <i class="bi bi-archive text-primary me-2"></i>
    {{ $package ? __('Edit Package') : __('Create Package') }}
</h1>
@endsection

@section('content')

@if($sf_user->hasFlash('notice'))
<div class="alert alert-success alert-dismissible fade show">
    {!! $sf_user->getFlash('notice') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($sf_user->hasFlash('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {!! $sf_user->getFlash('error') !!}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-{{ $package ? 'pencil' : 'plus-lg' }} me-2"></i>
                {{ $package ? __('Package Details') : __('New Package') }}
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="form_action" value="{{ $package ? 'update' : 'create' }}">

                    <div class="mb-3">
                        <label class="form-label">{{ __('Package Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="{{ $package->name ?? '' }}"
                               placeholder="{{ __('e.g., Annual Reports 2024 SIP') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="{{ __('Brief description of package contents') }}">{{ $package->description ?? '' }}</textarea>
                    </div>

                    @if(!$package)
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Package Type') }} <span class="text-danger">*</span></label>
                            <select name="package_type" class="form-select" required>
                                <option value="">{{ __('Select type...') }}</option>
                                <option value="sip">SIP - Submission Information Package</option>
                                <option value="aip">AIP - Archival Information Package</option>
                                <option value="dip">DIP - Dissemination Information Package</option>
                            </select>
                            <div class="form-text">{{ __('SIP for ingest, AIP for storage, DIP for access') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Package Format') }}</label>
                            <select name="package_format" class="form-select">
                                <option value="bagit" selected>BagIt (Recommended)</option>
                                <option value="zip">ZIP Archive</option>
                                <option value="tar">TAR Archive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Checksum Algorithm') }}</label>
                        <select name="manifest_algorithm" class="form-select">
                            <option value="sha256" selected>SHA-256 (Recommended)</option>
                            <option value="sha512">SHA-512</option>
                            <option value="sha1">SHA-1</option>
                            <option value="md5">MD5</option>
                        </select>
                    </div>
                    @else
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Package Type') }}</label>
                            <input type="text" class="form-control" disabled value="{{ strtoupper($package->package_type) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Status') }}</label>
                            <input type="text" class="form-control" disabled value="{{ ucfirst($package->status) }}">
                        </div>
                    </div>
                    @endif

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Originator') }}</label>
                        <input type="text" name="originator" class="form-control"
                               value="{{ $package->originator ?? '' }}"
                               placeholder="{{ __('Organization creating this package') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Submission Agreement') }}</label>
                        <input type="text" name="submission_agreement" class="form-control"
                               value="{{ $package->submission_agreement ?? '' }}"
                               placeholder="{{ __('Reference to submission agreement') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Retention Period') }}</label>
                        <input type="text" name="retention_period" class="form-control"
                               value="{{ $package->retention_period ?? '' }}"
                               placeholder="{{ __('e.g., Permanent, 10 years, etc.') }}">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ url_for(['module' => 'preservation', 'action' => 'packages']) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>{{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ $package ? __('Save Changes') : __('Create Package') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($package && 'draft' === $package->status)
        <!-- Add Objects Section -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-plus me-2"></i>{{ __('Package Objects') }}</span>
                <span class="badge bg-primary">{{ count($objects) }} objects</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('Add Digital Object') }}</label>
                    <div class="input-group">
                        <input type="number" id="objectIdInput" class="form-control" placeholder="{{ __('Enter digital object ID') }}">
                        <button type="button" class="btn btn-outline-primary" onclick="addObject()">
                            <i class="bi bi-plus-lg me-1"></i>{{ __('Add') }}
                        </button>
                    </div>
                    <div class="form-text">{{ __('Enter the ID of a digital object to add to this package') }}</div>
                </div>

                @if(!empty($objects))
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('File') }}</th>
                                <th>{{ __('Format') }}</th>
                                <th>{{ __('Size') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($objects as $obj)
                            <tr id="obj-row-{{ $obj->digital_object_id }}">
                                <td>
                                    {{ $obj->file_name }}
                                    <br>
                                    <small class="text-muted">{{ $obj->information_object_title ?? 'No title' }}</small>
                                </td>
                                <td>
                                    @if($obj->puid)
                                    <span class="badge bg-info">{{ $obj->puid }}</span>
                                    @endif
                                    <small class="text-muted d-block">{{ $obj->mime_type ?? 'Unknown' }}</small>
                                </td>
                                <td>{{ $obj->file_size ? formatBytes($obj->file_size) : '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeObject({{ $obj->digital_object_id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-3">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    {{ __('No objects added yet') }}
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        @if($package)
        <!-- Package Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>{{ __('Package Info') }}
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('UUID') }}</dt>
                    <dd class="col-sm-8"><code class="small">{{ $package->uuid }}</code></dd>

                    <dt class="col-sm-4">{{ __('Format') }}</dt>
                    <dd class="col-sm-8">{{ ucfirst($package->package_format) }}</dd>

                    <dt class="col-sm-4">{{ __('Algorithm') }}</dt>
                    <dd class="col-sm-8">{{ strtoupper($package->manifest_algorithm) }}</dd>

                    <dt class="col-sm-4">{{ __('Objects') }}</dt>
                    <dd class="col-sm-8">{{ number_format($package->object_count) }}</dd>

                    <dt class="col-sm-4">{{ __('Size') }}</dt>
                    <dd class="col-sm-8">{{ $package->total_size ? formatBytes($package->total_size) : '-' }}</dd>

                    @if($package->package_checksum)
                    <dt class="col-sm-4">{{ __('Checksum') }}</dt>
                    <dd class="col-sm-8"><code class="small">{{ substr($package->package_checksum, 0, 16) }}...</code></dd>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>{{ __('Actions') }}
            </div>
            <div class="card-body">
                @if('draft' === $package->status && $package->object_count > 0)
                <button type="button" class="btn btn-success w-100 mb-2" onclick="buildPackage()">
                    <i class="bi bi-hammer me-1"></i>{{ __('Build Package') }}
                </button>
                @endif

                @if('complete' === $package->status)
                <button type="button" class="btn btn-primary w-100 mb-2" onclick="validatePackage()">
                    <i class="bi bi-check-circle me-1"></i>{{ __('Validate Package') }}
                </button>
                @endif

                @if(in_array($package->status, ['complete', 'validated']))
                <button type="button" class="btn btn-info w-100 mb-2" onclick="exportPackage()">
                    <i class="bi bi-box-arrow-up me-1"></i>{{ __('Export Package') }}
                </button>
                @endif

                @if($package->export_path)
                <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageDownload', 'id' => $package->id]) }}" class="btn btn-outline-success w-100 mb-2">
                    <i class="bi bi-download me-1"></i>{{ __('Download Export') }}
                </a>
                @endif

                @if('sip' === $package->package_type && in_array($package->status, ['validated', 'exported']))
                <hr>
                <button type="button" class="btn btn-outline-primary w-100" onclick="convertPackage('aip')">
                    <i class="bi bi-arrow-right-circle me-1"></i>{{ __('Convert to AIP') }}
                </button>
                @endif

                @if('aip' === $package->package_type && in_array($package->status, ['validated', 'exported']))
                <hr>
                <button type="button" class="btn btn-outline-warning w-100" onclick="convertPackage('dip')">
                    <i class="bi bi-arrow-right-circle me-1"></i>{{ __('Create DIP') }}
                </button>
                @endif

                @if('draft' === $package->status)
                <hr>
                <button type="button" class="btn btn-outline-danger w-100" onclick="deletePackage()">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete Package') }}
                </button>
                @endif
            </div>
        </div>

        <!-- Recent Events -->
        @if(!empty($events))
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>{{ __('Recent Events') }}
            </div>
            <ul class="list-group list-group-flush">
                @foreach(array_slice($events, 0, 5) as $event)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-{{ 'success' === $event->event_outcome ? 'success' : ('failure' === $event->event_outcome ? 'danger' : 'secondary') }}">
                            {{ $event->event_type }}
                        </span>
                        <small class="text-muted">{{ date('Y-m-d H:i', strtotime($event->event_datetime)) }}</small>
                    </div>
                    @if($event->event_detail)
                    <small class="text-muted d-block mt-1">{{ substr($event->event_detail, 0, 50) }}</small>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @else
        <!-- Help Card for New Package -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-question-circle me-2"></i>{{ __('OAIS Package Types') }}
            </div>
            <div class="card-body">
                <h6 class="text-info"><i class="bi bi-box-arrow-in-right me-1"></i>SIP - Submission</h6>
                <p class="small text-muted mb-3">Package used to submit content to the archive. Contains the digital objects and metadata.</p>

                <h6 class="text-success"><i class="bi bi-safe me-1"></i>AIP - Archival</h6>
                <p class="small text-muted mb-3">Package stored in the archive for long-term preservation. Created from a validated SIP.</p>

                <h6 class="text-warning"><i class="bi bi-box-arrow-right me-1"></i>DIP - Dissemination</h6>
                <p class="small text-muted mb-0">Package created for user access. Derived from an AIP with access-optimized formats.</p>
            </div>
        </div>
        @endif
    </div>
</div>

@php
function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
@endphp

@if($package)
<script {!! $csp_nonce !!}>
const packageId = {{ $package->id }};

function addObject() {
    const objectId = document.getElementById('objectIdInput').value;
    if (!objectId) {
        alert('Please enter an object ID');
        return;
    }

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageAddObject']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&object_id=${objectId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function removeObject(objectId) {
    if (!confirm('Remove this object from the package?')) return;

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageRemoveObject']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&object_id=${objectId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('obj-row-' + objectId).remove();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function buildPackage() {
    if (!confirm('Build the BagIt package? This will copy all files.')) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Building...';

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageBuild']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Package built successfully!\nFiles: ' + data.files + '\nSize: ' + formatBytesJs(data.size));
            location.reload();
        } else {
            alert('Error: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-hammer me-1"></i>Build Package';
        }
    });
}

function validatePackage() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validating...';

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageValidate']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            alert('Validation passed! ' + data.validated_files + ' files verified.');
            location.reload();
        } else {
            alert('Validation failed!\n\n' + data.errors.join('\n'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Validate Package';
        }
    });
}

function exportPackage() {
    const format = prompt('Export format (zip, tar, tar.gz):', 'zip');
    if (!format) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Exporting...';

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageExport']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&format=${format}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Export completed!\nFormat: ' + data.format + '\nSize: ' + formatBytesJs(data.size));
            location.reload();
        } else {
            alert('Error: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-up me-1"></i>Export Package';
        }
    });
}

function convertPackage(targetType) {
    const typeName = targetType === 'aip' ? 'AIP' : 'DIP';
    if (!confirm(`Convert this package to ${typeName}?`)) return;

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageConvert']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}&target_type=${targetType}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`${typeName} created successfully!`);
            window.location.href = '{{ url_for(['module' => 'preservation', 'action' => 'packageEdit']) }}?id=' + data.new_package_id;
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function deletePackage() {
    if (!confirm('Are you sure you want to delete this package? This cannot be undone.')) return;

    fetch('{{ url_for(['module' => 'preservation', 'action' => 'apiPackageDelete']) }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `package_id=${packageId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '{{ url_for(['module' => 'preservation', 'action' => 'packages']) }}';
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function formatBytesJs(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' bytes';
}
</script>
@endif

@endsection
