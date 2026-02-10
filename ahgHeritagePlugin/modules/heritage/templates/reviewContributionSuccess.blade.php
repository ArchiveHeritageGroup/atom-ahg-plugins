@extends('layouts.page')

@php
// Helper to convert Symfony escaped arrays to plain arrays
$toArray = function($val) use (&$toArray) {
    if (is_array($val)) {
        return array_map($toArray, $val);
    }
    if ($val instanceof Traversable) {
        return array_map($toArray, iterator_to_array($val));
    }
    return $val;
};

$contribution = $toArray($contribution ?? []);
$content = $contribution['content'] ?? [];
$item = $contribution['item'] ?? [];
$contributor = $contribution['contributor'] ?? [];
$type = $contribution['type'] ?? [];
$versions = $contribution['versions'] ?? [];
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-clipboard-check me-2"></i>Review Contribution
</h1>
@endsection

@section('sidebar')
<!-- Item Context -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-file-earmark me-2"></i>Item Context</h6>
    </div>
    @if (!empty($item['thumbnail']))
    <img src="{{ $item['thumbnail'] }}"
         class="card-img-top"
         alt="{{ $item['title'] ?? 'Item' }}"
         onerror="this.style.display='none'">
    @endif
    <div class="card-body">
        <h6 class="card-title">{{ $item['title'] ?? 'Untitled' }}</h6>
        @if (!empty($item['description']))
        <p class="card-text small text-muted">
            {{ substr(strip_tags($item['description']), 0, 200) }}...
        </p>
        @endif
        <a href="{{ url_for(['module' => 'informationobject', 'slug' => $item['slug']]) }}"
           class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="fas fa-box-arrow-up-right me-1"></i>View Record
        </a>
    </div>
</div>

<!-- Contributor Info -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Contributor</h6>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            @if (!empty($contributor['avatar_url']))
            <img src="{{ $contributor['avatar_url'] }}"
                 class="rounded-circle me-2" width="40" height="40" alt="">
            @else
            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                <i class="fas fa-user text-primary"></i>
            </div>
            @endif
            <div>
                <strong>{{ $contributor['display_name'] }}</strong>
                <div>
                    <span class="badge bg-{{ match($contributor['trust_level']) {
                        'expert' => 'primary',
                        'trusted' => 'success',
                        'contributor' => 'info',
                        default => 'secondary'
                    } }}">
                        {{ ucfirst($contributor['trust_level']) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="small text-muted">
            <div class="d-flex justify-content-between mb-1">
                <span>Approved:</span>
                <strong class="text-success">{{ $contributor['approved_count'] }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span>Total:</span>
                <strong>{{ $contributor['total_count'] }}</strong>
            </div>
            <div class="d-flex justify-content-between">
                <span>Approval Rate:</span>
                <strong>{{ $contributor['approval_rate'] }}%</strong>
            </div>
        </div>
    </div>
</div>

<!-- Version History -->
@if (count($versions) > 1)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0"><i class="fas fa-clock-history me-2"></i>Version History</h6>
    </div>
    <ul class="list-group list-group-flush">
        @foreach ($versions as $v)
        <li class="list-group-item">
            <div class="d-flex justify-content-between">
                <strong>v{{ $v['version_number'] }}</strong>
                <small class="text-muted">{{ date('M d H:i', strtotime($v['created_at'])) }}</small>
            </div>
            <small class="text-muted">{{ $v['change_summary'] ?? 'No summary' }}</small>
        </li>
        @endforeach
    </ul>
</div>
@endif
@endsection

@section('content')
<!-- Main Content -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="fas {{ $type['icon'] }} fs-4 text-{{ $type['color'] }} me-2"></i>
            <h5 class="mb-0">{{ $type['name'] }}</h5>
        </div>
        <span class="badge bg-warning">Pending Review</span>
    </div>
    <div class="card-body">
        <!-- Contribution Meta -->
        <div class="row mb-4">
            <div class="col-md-4">
                <small class="text-muted d-block">Submitted</small>
                <strong>{{ date('M d, Y H:i', strtotime($contribution['created_at'])) }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Version</small>
                <strong>v{{ $contribution['version_number'] }}</strong>
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Points Value</small>
                <strong class="text-success">+{{ $type['points_value'] }} pts</strong>
            </div>
        </div>

        <hr>

        <!-- Contribution Content -->
        <h6 class="text-muted mb-3">Contribution Content</h6>

        @if ($type['code'] === 'transcription')
        <div class="bg-light border rounded p-3 font-monospace" style="white-space: pre-wrap;">{{ $content['text'] ?? '' }}</div>
        @if (!empty($content['notes']))
        <div class="mt-2">
            <small class="text-muted"><strong>Notes:</strong> {{ $content['notes'] }}</small>
        </div>
        @endif

        @elseif ($type['code'] === 'identification')
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Name</label>
                    <div class="fw-bold">{{ $content['name'] ?? '' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Relationship</label>
                    <div>{{ ucfirst($content['relationship'] ?? 'Not specified') }}</div>
                </div>
            </div>
            @if (!empty($content['position']))
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Position</label>
                    <div>{{ $content['position'] }}</div>
                </div>
            </div>
            @endif
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Confidence</label>
                    <div>
                        @php
                        $confidence = $content['confidence'] ?? 'possible';
                        $confBadge = match($confidence) {
                            'certain' => 'success',
                            'likely' => 'info',
                            default => 'warning'
                        };
                        @endphp
                        <span class="badge bg-{{ $confBadge }}">{{ ucfirst($confidence) }}</span>
                    </div>
                </div>
            </div>
        </div>
        @if (!empty($content['source']))
        <div class="bg-light rounded p-3">
            <small class="text-muted d-block mb-1">Source/Evidence:</small>
            {{ $content['source'] }}
        </div>
        @endif

        @elseif ($type['code'] === 'context')
        <div class="mb-3">
            <span class="badge bg-secondary">{{ ucfirst($content['context_type'] ?? 'general') }}</span>
        </div>
        <div class="bg-light border rounded p-3">
            {!! nl2br(e($content['text'] ?? '')) !!}
        </div>
        @if (!empty($content['source']))
        <div class="mt-2">
            <small class="text-muted"><strong>Source:</strong> {{ $content['source'] }}</small>
        </div>
        @endif

        @elseif ($type['code'] === 'correction')
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Field</label>
                    <div class="fw-bold">{{ ucfirst($content['field'] ?? '') }}</div>
                </div>
            </div>
        </div>
        @if (!empty($content['current_value']))
        <div class="mb-3">
            <label class="form-label small text-muted">Current Value</label>
            <div class="bg-danger bg-opacity-10 border border-danger rounded p-2">
                {{ $content['current_value'] }}
            </div>
        </div>
        @endif
        <div class="mb-3">
            <label class="form-label small text-muted">Suggested Correction</label>
            <div class="bg-success bg-opacity-10 border border-success rounded p-2">
                {{ $content['suggestion'] ?? '' }}
            </div>
        </div>
        <div>
            <label class="form-label small text-muted">Reason</label>
            <div class="bg-light rounded p-2">
                {{ $content['reason'] ?? '' }}
            </div>
        </div>

        @elseif ($type['code'] === 'translation')
        <div class="mb-3">
            <label class="form-label small text-muted">Target Language</label>
            <span class="badge bg-primary">{{ $content['target_language'] ?? '' }}</span>
        </div>
        <div class="bg-light border rounded p-3">
            {!! nl2br(e($content['text'] ?? '')) !!}
        </div>

        @elseif ($type['code'] === 'tag')
        <div class="d-flex flex-wrap gap-2">
            @foreach ($content['tags'] ?? [] as $tag)
            <span class="badge bg-primary fs-6">{{ $tag }}</span>
            @endforeach
        </div>

        @else
        <pre class="bg-light rounded p-3">{{ json_encode($content, JSON_PRETTY_PRINT) }}</pre>
        @endif
    </div>
</div>

<!-- Review Form -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-check2-square me-2"></i>Decision</h5>
    </div>
    <div class="card-body">
        <form method="post" action="{{ url_for(['module' => 'heritage', 'action' => 'reviewContribution', 'id' => $contribution['id']]) }}">
            <div class="mb-3">
                <label for="notes" class="form-label">Review Notes (optional)</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"
                          placeholder="Add any notes for the contributor..."></textarea>
                <div class="form-text">These notes will be visible to the contributor.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="decision" value="approve" class="btn btn-success btn-lg flex-fill">
                    <i class="fas fa-check-circle me-2"></i>Approve
                </button>
                <button type="submit" name="decision" value="reject" class="btn btn-danger btn-lg flex-fill">
                    <i class="fas fa-times-circle me-2"></i>Reject
                </button>
            </div>
        </form>

        <hr class="my-4">

        <a href="{{ url_for(['module' => 'heritage', 'action' => 'reviewQueue']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Queue
        </a>
    </div>
</div>
@endsection
