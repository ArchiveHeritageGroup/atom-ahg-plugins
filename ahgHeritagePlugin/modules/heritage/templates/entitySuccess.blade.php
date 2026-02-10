@php
// Helper to unwrap Symfony escaper objects
$unwrap = function ($value) use (&$unwrap) {
    if ($value instanceof sfOutputEscaperObjectDecorator) {
        $raw = $value->getRawValue();
        return is_object($raw) ? $raw : $unwrap($raw);
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
    return $value;
};

// Unwrap data arrays
$objectsArray = $unwrap($objects ?? []);
$relatedEntitiesArray = $unwrap($relatedEntities ?? []);
$entityData = $unwrap($entity ?? null);

$typeColors = [
    'person' => '#4e79a7',
    'organization' => '#59a14f',
    'place' => '#e15759',
    'date' => '#b07aa1',
    'event' => '#76b7b2',
    'work' => '#ff9da7',
    'concept' => '#edc949',
];

$typeIcons = [
    'person' => 'bi-person-fill',
    'organization' => 'bi-building-fill',
    'place' => 'bi-geo-alt-fill',
    'date' => 'bi-calendar-fill',
    'event' => 'bi-calendar-event-fill',
    'work' => 'bi-file-richtext-fill',
    'concept' => 'bi-lightbulb-fill',
];
@endphp

<div class="heritage-entity-page py-4">
    <div class="container-xxl">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'heritage', 'action' => 'landing']) }}">Heritage</a></li>
                <li class="breadcrumb-item"><a href="{{ url_for(['module' => 'heritage', 'action' => 'graph']) }}">Knowledge Graph</a></li>
                <li class="breadcrumb-item active">{{ $entityData->canonical_value }}</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Entity Header Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <!-- Entity Icon -->
                            <div class="rounded-3 p-3 me-4 d-flex align-items-center justify-content-center"
                                 style="background-color: {{ $typeColors[$entityData->entity_type] ?? '#999' }}; min-width: 80px; min-height: 80px;">
                                <i class="{{ $typeIcons[$entityData->entity_type] ?? 'bi-tag-fill' }} text-white fs-1"></i>
                            </div>

                            <div class="flex-grow-1">
                                <h1 class="h2 mb-2">{{ $entityData->display_label ?? $entityData->canonical_value }}</h1>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge fs-6" style="background-color: {{ $typeColors[$entityData->entity_type] ?? '#999' }};">
                                        {{ ucfirst($entityData->entity_type) }}
                                    </span>
                                    <span class="badge bg-light text-dark fs-6">
                                        <i class="bi bi-file-earmark me-1"></i> {{ number_format($entityData->occurrence_count) }} records
                                    </span>
                                    @if ($entityData->confidence_avg >= 0.9)
                                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i> High Confidence</span>
                                    @elseif ($entityData->confidence_avg >= 0.7)
                                    <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-circle me-1"></i> Medium Confidence</span>
                                    @endif
                                </div>

                                @if (!empty($entityData->description))
                                <p class="lead mb-0">{{ $entityData->description }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Associated Records -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Records containing this entity</h4>
                        <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'ner_' . $entityData->entity_type . '[]' => $entityData->canonical_value]) }}"
                           class="btn btn-sm btn-outline-primary">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if (!empty($objectsArray))
                        <div class="list-group list-group-flush">
                            @foreach (array_slice($objectsArray, 0, 10) as $obj)
                            <a href="{{ url_for(['module' => 'informationobject', 'slug' => $obj->slug]) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $obj->title ?? 'Untitled' }}</h6>
                                    <small class="text-muted">
                                        @if ($obj->mention_count > 1)
                                        Mentioned {{ $obj->mention_count }} times
                                        @endif
                                    </small>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ round($obj->confidence * 100) }}%</span>
                            </a>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 mb-2 d-block"></i>
                            <p class="mb-0">No records found containing this entity.</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Related Entities -->
                @if (!empty($relatedEntitiesArray))
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-0">Related Entities</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($relatedEntitiesArray as $related)
                            <div class="col-md-6">
                                <div class="card h-100 border">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <span class="badge me-2" style="background-color: {{ $typeColors[$related['entity_type']] ?? '#999' }};">
                                                {{ ucfirst(substr($related['entity_type'], 0, 1)) }}
                                            </span>
                                            <a href="{{ url_for(['module' => 'heritage', 'action' => 'entity', 'type' => $related['entity_type'], 'value' => $related['value']]) }}"
                                               class="text-decoration-none fw-medium">
                                                {{ $related['label'] }}
                                            </a>
                                            <span class="badge bg-light text-muted ms-auto">{{ $related['co_occurrences'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ url_for(['module' => 'heritage', 'action' => 'search', 'ner_' . $entityData->entity_type . '[]' => $entityData->canonical_value]) }}"
                           class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Search All Records
                        </a>
                        <a href="{{ url_for(['module' => 'heritage', 'action' => 'graph', 'focus' => $entityData->id]) }}"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-diagram-3 me-1"></i> View in Graph
                        </a>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Details</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Type</dt>
                            <dd class="col-7">{{ ucfirst($entityData->entity_type) }}</dd>

                            <dt class="col-5 text-muted">Occurrences</dt>
                            <dd class="col-7">{{ number_format($entityData->occurrence_count) }}</dd>

                            <dt class="col-5 text-muted">Avg. Confidence</dt>
                            <dd class="col-7">{{ round($entityData->confidence_avg * 100) }}%</dd>

                            @if (!empty($entityData->first_seen_at))
                            <dt class="col-5 text-muted">First Seen</dt>
                            <dd class="col-7">{{ date('M j, Y', strtotime($entityData->first_seen_at)) }}</dd>
                            @endif

                            @if (!empty($entityData->last_seen_at))
                            <dt class="col-5 text-muted">Last Updated</dt>
                            <dd class="col-7">{{ date('M j, Y', strtotime($entityData->last_seen_at)) }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- External Links -->
                @if (!empty($entityData->wikidata_id) || !empty($entityData->viaf_id) || !empty($entityData->actor_id))
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">External Links</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        @if (!empty($entityData->actor_id))
                        <a href="{{ url_for(['module' => 'actor', 'slug' => $entityData->actor_id]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person me-1"></i> Authority Record
                        </a>
                        @endif
                        @if (!empty($entityData->wikidata_id))
                        <a href="https://www.wikidata.org/wiki/{{ $entityData->wikidata_id }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Wikidata
                        </a>
                        @endif
                        @if (!empty($entityData->viaf_id))
                        <a href="https://viaf.org/viaf/{{ $entityData->viaf_id }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> VIAF
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Related Types Distribution -->
                @if (!empty($relatedEntitiesArray))
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Connection Types</h5>
                    </div>
                    <div class="card-body">
                        @php
                        $typeCounts = [];
                        foreach ($relatedEntitiesArray as $r) {
                            $typeCounts[$r['entity_type']] = ($typeCounts[$r['entity_type']] ?? 0) + 1;
                        }
                        arsort($typeCounts);
                        @endphp
                        @foreach ($typeCounts as $type => $count)
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge me-2" style="background-color: {{ $typeColors[$type] ?? '#999' }}; width: 20px;">&nbsp;</span>
                            <span class="flex-grow-1">{{ ucfirst($type) }}</span>
                            <span class="badge bg-light text-dark">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
