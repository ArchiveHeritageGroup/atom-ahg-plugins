@extends('layouts.page')

@section('title')
<h1>
    @php
    $typeIcon = ['sip' => 'box-arrow-in-right', 'aip' => 'safe', 'dip' => 'box-arrow-right'][$package->package_type] ?? 'archive';
    $typeClass = ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$package->package_type] ?? 'secondary';
    @endphp
    <i class="bi bi-{{ $typeIcon }} text-{{ $typeClass }} me-2"></i>
    {{ $package->name }}
</h1>
@endsection

@section('content')

<!-- Navigation -->
<div class="mb-4">
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'packages']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Packages') }}
    </a>
    @if('draft' === $package->status)
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageEdit', 'id' => $package->id]) }}" class="btn btn-outline-primary ms-2">
        <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
    </a>
    @endif
    @if($package->export_path)
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageDownload', 'id' => $package->id]) }}" class="btn btn-success ms-2">
        <i class="bi bi-download me-1"></i>{{ __('Download Export') }}
    </a>
    @endif
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Package Details -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-archive me-2"></i>{{ __('Package Details') }}</span>
                @php
                $statusClass = [
                    'draft' => 'secondary',
                    'building' => 'warning',
                    'complete' => 'info',
                    'validated' => 'primary',
                    'exported' => 'success',
                    'error' => 'danger'
                ][$package->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $statusClass }} fs-6">{{ ucfirst($package->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>{{ __('UUID') }}</dt>
                            <dd><code>{{ $package->uuid }}</code></dd>

                            <dt>{{ __('Package Type') }}</dt>
                            <dd>
                                <span class="badge bg-{{ $typeClass }}">
                                    {{ strtoupper($package->package_type) }}
                                </span>
                                @php
                                $typeLabels = ['sip' => 'Submission Information Package', 'aip' => 'Archival Information Package', 'dip' => 'Dissemination Information Package'];
                                @endphp
                                {!! ' - ' . ($typeLabels[$package->package_type] ?? '') !!}
                            </dd>

                            <dt>{{ __('Package Format') }}</dt>
                            <dd>{{ ucfirst($package->package_format) }} ({{ strtoupper($package->manifest_algorithm) }})</dd>

                            @if($package->description)
                            <dt>{{ __('Description') }}</dt>
                            <dd>{!! nl2br(e($package->description)) !!}</dd>
                            @endif
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt>{{ __('Objects') }}</dt>
                            <dd>{{ number_format($package->object_count) }} files</dd>

                            <dt>{{ __('Total Size') }}</dt>
                            <dd>{{ $package->total_size ? formatBytes($package->total_size) : '-' }}</dd>

                            @if($package->package_checksum)
                            <dt>{{ __('Package Checksum') }}</dt>
                            <dd><code class="small">{{ $package->package_checksum }}</code></dd>
                            @endif

                            @if($package->originator)
                            <dt>{{ __('Originator') }}</dt>
                            <dd>{{ $package->originator }}</dd>
                            @endif

                            @if($package->submission_agreement)
                            <dt>{{ __('Submission Agreement') }}</dt>
                            <dd>{{ $package->submission_agreement }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Objects -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-files me-2"></i>{{ __('Package Objects') }}</span>
                <span class="badge bg-primary">{{ count($objects) }}</span>
            </div>
            @if(!empty($objects))
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('File') }}</th>
                            <th>{{ __('Format') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Checksum') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($objects as $i => $obj)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $obj->file_name }}</strong>
                                <br>
                                <small class="text-muted">{{ $obj->relative_path }}</small>
                                @if($obj->information_object_title)
                                <br>
                                <small class="text-info">{{ $obj->information_object_title }}</small>
                                @endif
                            </td>
                            <td>
                                @if($obj->puid)
                                <span class="badge bg-info">{{ $obj->puid }}</span>
                                @endif
                                <br>
                                <small class="text-muted">{{ $obj->mime_type ?? 'Unknown' }}</small>
                            </td>
                            <td>{{ $obj->file_size ? formatBytes($obj->file_size) : '-' }}</td>
                            <td>
                                @if($obj->checksum_value)
                                <code class="small">{{ substr($obj->checksum_value, 0, 12) }}...</code>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                {{ __('No objects in this package') }}
            </div>
            @endif
        </div>

        <!-- Package Events -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>{{ __('Package Events') }}
            </div>
            @if(!empty($events))
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Event') }}</th>
                            <th>{{ __('Detail') }}</th>
                            <th>{{ __('Outcome') }}</th>
                            <th>{{ __('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $event->event_type }}</span>
                            </td>
                            <td>
                                {{ $event->event_detail ?? '-' }}
                                @if($event->event_outcome_detail)
                                <br><small class="text-muted">{{ substr($event->event_outcome_detail, 0, 100) }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                $outcomeClass = ['success' => 'success', 'failure' => 'danger', 'warning' => 'warning'][$event->event_outcome] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $outcomeClass }}">{{ $event->event_outcome }}</span>
                            </td>
                            <td>
                                <small>{{ date('Y-m-d H:i:s', strtotime($event->event_datetime)) }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body text-center text-muted">
                {{ __('No events recorded') }}
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <!-- Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-calendar3 me-2"></i>{{ __('Timeline') }}
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ __('Created') }}</span>
                    <span>{{ date('Y-m-d H:i', strtotime($package->created_at)) }}</span>
                </li>
                @if($package->created_by)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ __('Created By') }}</span>
                    <span>{{ $package->created_by }}</span>
                </li>
                @endif
                @if($package->built_at)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ __('Built') }}</span>
                    <span class="text-success">{{ date('Y-m-d H:i', strtotime($package->built_at)) }}</span>
                </li>
                @endif
                @if($package->validated_at)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ __('Validated') }}</span>
                    <span class="text-primary">{{ date('Y-m-d H:i', strtotime($package->validated_at)) }}</span>
                </li>
                @endif
                @if($package->exported_at)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ __('Exported') }}</span>
                    <span class="text-success">{{ date('Y-m-d H:i', strtotime($package->exported_at)) }}</span>
                </li>
                @endif
            </ul>
        </div>

        <!-- File Paths -->
        @if($package->source_path || $package->export_path)
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-folder me-2"></i>{{ __('File Paths') }}
            </div>
            <div class="card-body">
                @if($package->source_path)
                <dt>{{ __('Source Path') }}</dt>
                <dd><code class="small">{{ $package->source_path }}</code></dd>
                @endif

                @if($package->export_path)
                <dt>{{ __('Export Path') }}</dt>
                <dd><code class="small">{{ $package->export_path }}</code></dd>
                @endif
            </div>
        </div>
        @endif

        <!-- Related Packages -->
        @if($parentPackage || !empty($childPackages))
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-diagram-3 me-2"></i>{{ __('Related Packages') }}
            </div>
            <ul class="list-group list-group-flush">
                @if($parentPackage)
                <li class="list-group-item">
                    <small class="text-muted d-block">{{ __('Parent Package') }}</small>
                    <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $parentPackage->id]) }}">
                        <i class="bi bi-arrow-up-circle me-1"></i>
                        {{ $parentPackage->name }}
                    </a>
                    <span class="badge bg-{{ ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$parentPackage->package_type] ?? 'secondary' }} ms-2">
                        {{ strtoupper($parentPackage->package_type) }}
                    </span>
                </li>
                @endif

                @foreach($childPackages as $child)
                <li class="list-group-item">
                    <small class="text-muted d-block">{{ __('Derived Package') }}</small>
                    <a href="{{ url_for(['module' => 'preservation', 'action' => 'packageView', 'id' => $child->id]) }}">
                        <i class="bi bi-arrow-down-circle me-1"></i>
                        {{ $child->name }}
                    </a>
                    <span class="badge bg-{{ ['sip' => 'info', 'aip' => 'success', 'dip' => 'warning'][$child->package_type] ?? 'secondary' }} ms-2">
                        {{ strtoupper($child->package_type) }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- BagIt Structure (if built) -->
        @if($package->source_path && is_dir($package->source_path))
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-code me-2"></i>{{ __('BagIt Structure') }}
            </div>
            <div class="card-body">
                <pre class="mb-0 small bg-light p-2 rounded">{{ $package->uuid }}/
  bagit.txt
  bag-info.txt
  manifest-{{ $package->manifest_algorithm }}.txt
  tagmanifest-{{ $package->manifest_algorithm }}.txt
  data/
@foreach(array_slice($objects, 0, 3) as $obj)
    {{ basename($obj->relative_path) }}
@endforeach
@if(count($objects) > 3)
    ... ({{ count($objects) - 3 }} more files)
@endif
</pre>
            </div>
        </div>
        @endif
    </div>
</div>

@php
if (!function_exists('formatBytes')) {
    function formatBytes($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
}
@endphp

@endsection
