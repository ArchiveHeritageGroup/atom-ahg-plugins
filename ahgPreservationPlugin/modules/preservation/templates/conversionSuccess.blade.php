@extends('layouts.page')

@section('title')
<h1><i class="bi bi-arrow-repeat text-primary me-2"></i>{{ __('Format Conversion') }}</h1>
@endsection

@section('content')

<!-- Conversion Tools Status -->
<div class="row mb-4">
    @foreach($tools as $name => $info)
    <div class="col-md-3">
        <div class="card {{ $info['available'] ? 'border-success' : 'border-secondary' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">{{ $name }}</h6>
                    @if($info['available'])
                        <span class="badge bg-success">Available</span>
                    @else
                        <span class="badge bg-secondary">Not Installed</span>
                    @endif
                </div>
                <small class="text-muted">
                    @php
                        $formats = is_array($info['formats']) ? $info['formats'] : iterator_to_array($info['formats']);
                    @endphp
                    {{ implode(', ', array_slice($formats, 0, 5)) }}
                    @if(count($formats) > 5)...@endif
                </small>
                @if($info['available'] && !empty($info['version']))
                <br><small class="text-success">{{ substr($info['version'], 0, 30) }}</small>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Quick Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
        </a>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'formats']) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-code me-1"></i>{{ __('Format Registry') }}
        </a>
    </div>
    <div class="text-end">
        <span class="text-muted">{{ __('Pending conversions:') }}</span>
        <span class="badge bg-{{ $pendingConversions > 0 ? 'warning' : 'success' }} ms-2">
            {{ number_format($pendingConversions) }}
        </span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Completed') }}</h6>
                        <h2 class="mb-0">{{ number_format($conversionStats['completed'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Processing') }}</h6>
                        <h2 class="mb-0">{{ number_format($conversionStats['processing'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Pending') }}</h6>
                        <h2 class="mb-0">{{ number_format($conversionStats['pending'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-clock fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Failed') }}</h6>
                        <h2 class="mb-0">{{ number_format($conversionStats['failed'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-x-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CLI Commands -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i>{{ __('CLI Commands') }}
    </div>
    <div class="card-body">
        <p class="mb-2">Run format conversions from the command line:</p>
        <pre class="bg-dark text-light p-3 rounded mb-0"><code># Show available tools and statistics
php symfony preservation:convert --status

# Preview conversions (dry run)
php symfony preservation:convert --dry-run

# Convert specific object to TIFF
php symfony preservation:convert --object-id=123 --format=tiff

# Batch convert JPEG images to TIFF
php symfony preservation:convert --mime-type=image/jpeg --format=tiff --limit=50</code></pre>
    </div>
</div>

<!-- Supported Conversions -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-diagram-3 me-2"></i>{{ __('Supported Conversions') }}
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6><i class="bi bi-image me-2"></i>Images (ImageMagick)</h6>
                <ul class="small mb-3">
                    <li>JPEG, PNG, BMP, GIF &rarr; TIFF (uncompressed)</li>
                </ul>

                <h6><i class="bi bi-music-note-beamed me-2"></i>Audio (FFmpeg)</h6>
                <ul class="small mb-3">
                    <li>MP3, AAC, OGG &rarr; WAV (PCM)</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6><i class="bi bi-file-earmark-pdf me-2"></i>Documents</h6>
                <ul class="small mb-3">
                    <li>PDF &rarr; PDF/A (Ghostscript)</li>
                    <li>DOC, DOCX, XLS, PPT &rarr; PDF/A (LibreOffice)</li>
                </ul>

                <h6><i class="bi bi-film me-2"></i>Video (FFmpeg)</h6>
                <ul class="small mb-0">
                    <li>Various &rarr; MKV/FFV1 (lossless)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recent Conversions Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-ul me-2"></i>{{ __('Recent Conversions') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('File') }}</th>
                    <th>{{ __('Source') }}</th>
                    <th>{{ __('Target') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Tool') }}</th>
                    <th>{{ __('Created') }}</th>
                </tr>
            </thead>
            <tbody>
                @if(empty($recentConversions))
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        {{ __('No format conversions performed yet') }}
                    </td>
                </tr>
                @else
                    @foreach($recentConversions as $conv)
                    <tr>
                        <td>
                            <a href="{{ url_for(['module' => 'preservation', 'action' => 'object', 'id' => $conv->digital_object_id]) }}">
                                {{ substr($conv->filename ?? 'Unknown', 0, 30) }}
                            </a>
                        </td>
                        <td><small>{{ $conv->source_format ?? '-' }}</small></td>
                        <td><small>{{ $conv->target_format ?? '-' }}</small></td>
                        <td>
                            @if($conv->status === 'completed')
                                <span class="badge bg-success">Completed</span>
                            @elseif($conv->status === 'processing')
                                <span class="badge bg-info">Processing</span>
                            @elseif($conv->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($conv->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($conv->status) }}</span>
                            @endif
                        </td>
                        <td><small>{{ $conv->conversion_tool ?? '-' }}</small></td>
                        <td><small class="text-muted">{{ date('Y-m-d H:i', strtotime($conv->created_at)) }}</small></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
