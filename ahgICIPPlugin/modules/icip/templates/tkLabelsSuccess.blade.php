@extends('layouts.page')

@section('content')
<div class="container-xxl">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url_for('@icip_dashboard') }}">ICIP</a></li>
            <li class="breadcrumb-item active">TK Labels</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-tag me-2"></i>
            Traditional Knowledge Labels
        </h1>
        <a href="https://localcontexts.org/labels/traditional-knowledge-labels/" target="_blank" class="btn btn-outline-info">
            <i class="bi bi-box-arrow-up-right me-1"></i>
            Local Contexts
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Label Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Available Label Types</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-primary mb-3">Traditional Knowledge (TK) Labels</h6>
                    <div class="row">
                        @foreach ($labelTypes as $type)
                            @if ($type->category === 'TK')
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="icip-tk-label-icon me-2" style="background-color: #8B4513; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                                            {{ strtoupper($type->code) }}
                                        </div>
                                        <div>
                                            <strong>{{ $type->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $type->description }}</small>
                                            @if ($type->local_contexts_url)
                                                <br><a href="{{ $type->local_contexts_url }}" target="_blank" class="small">Learn more</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <hr>

                    <h6 class="text-success mb-3">Biocultural (BC) Labels</h6>
                    <div class="row">
                        @foreach ($labelTypes as $type)
                            @if ($type->category === 'BC')
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="icip-tk-label-icon me-2" style="background-color: #228B22; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                                            {{ strtoupper($type->code) }}
                                        </div>
                                        <div>
                                            <strong>{{ $type->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $type->description }}</small>
                                            @if ($type->local_contexts_url)
                                                <br><a href="{{ $type->local_contexts_url }}" target="_blank" class="small">Learn more</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Label Applications</h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($recentLabels))
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-tag fs-1"></i>
                            <p class="mb-0 mt-2">No labels applied yet</p>
                            <p class="small">Labels can be applied from individual record ICIP pages</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Label</th>
                                        <th>Record</th>
                                        <th>Community</th>
                                        <th>Applied By</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentLabels as $label)
                                        <tr>
                                            <td>
                                                <span class="badge {{ $label->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' }}">
                                                    {{ strtoupper($label->label_code) }}
                                                </span>
                                                {{ $label->label_name }}
                                            </td>
                                            <td>
                                                @if ($label->slug)
                                                    <a href="{{ url_for('@icip_object?slug=' . $label->slug) }}">
                                                        {{ $label->object_title ?? 'Untitled' }}
                                                    </a>
                                                @else
                                                    {{ $label->object_title ?? 'Untitled' }}
                                                @endif
                                            </td>
                                            <td>{{ $label->community_name ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{ $label->applied_by === 'community' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ ucfirst($label->applied_by) }}
                                                </span>
                                            </td>
                                            <td>{{ date('j M Y', strtotime($label->created_at)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Usage Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Label Usage</h5>
                </div>
                <div class="card-body">
                    @if (empty($appliedLabels))
                        <p class="text-muted mb-0">No labels have been applied yet</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($appliedLabels as $stat)
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <span>
                                        <span class="badge {{ $stat->category === 'TK' ? 'icip-tk-label' : 'icip-bc-label' }} me-1">
                                            {{ strtoupper($stat->code) }}
                                        </span>
                                        {{ $stat->name }}
                                    </span>
                                    <span class="badge bg-primary">{{ $stat->usage_count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <!-- About TK Labels -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">About TK Labels</h5>
                </div>
                <div class="card-body small">
                    <p>Traditional Knowledge (TK) Labels are a digital tool developed by <strong>Local Contexts</strong> to help Indigenous communities manage their cultural heritage in digital environments.</p>

                    <p>Labels can be:</p>
                    <ul>
                        <li><strong>Applied by communities</strong> to express cultural protocols</li>
                        <li><strong>Applied by institutions</strong> to acknowledge Indigenous origin</li>
                    </ul>

                    <p class="mb-0">
                        <a href="https://localcontexts.org/" target="_blank">
                            Visit Local Contexts <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style {!! $csp_nonce !!}>
.icip-tk-label {
    background-color: #8B4513;
    color: white;
}
.icip-bc-label {
    background-color: #228B22;
    color: white;
}
</style>
@endsection
