<?php
/**
 * Entity Detail Panel partial.
 *
 * Displays detailed entity information including related entities and records.
 *
 * @var object $entity        Entity data
 * @var array  $relatedEntities Related entities from graph
 * @var array  $objects       Objects containing this entity
 */

use_helper('Heritage');

$typeColors = [
    'person' => '#4e79a7',
    'organization' => '#59a14f',
    'place' => '#e15759',
    'date' => '#b07aa1',
    'event' => '#76b7b2',
    'work' => '#ff9da7',
    'concept' => '#edc949',
];
?>

<div class="entity-panel">
    <!-- Entity Header -->
    <div class="d-flex align-items-start mb-3">
        <?php if (!empty($entity->image_url)): ?>
        <img src="<?php echo esc_specialchars($entity->image_url); ?>" alt="" class="rounded me-3" style="width: 64px; height: 64px; object-fit: cover;">
        <?php else: ?>
        <div class="rounded me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; background-color: <?php echo $typeColors[$entity->entity_type] ?? '#999'; ?>;">
            <i class="bi bi-<?php echo $entity->entity_type === 'person' ? 'person' : ($entity->entity_type === 'organization' ? 'building' : ($entity->entity_type === 'place' ? 'geo-alt' : 'calendar')); ?> text-white fs-3"></i>
        </div>
        <?php endif; ?>
        <div class="flex-grow-1">
            <h4 class="mb-1"><?php echo esc_specialchars($entity->display_label ?? $entity->canonical_value); ?></h4>
            <div class="d-flex gap-2">
                <span class="badge" style="background-color: <?php echo $typeColors[$entity->entity_type] ?? '#999'; ?>;">
                    <?php echo ucfirst(esc_specialchars($entity->entity_type)); ?>
                </span>
                <?php if ($entity->confidence_avg >= 0.9): ?>
                <span class="badge bg-success" title="High confidence">High Confidence</span>
                <?php elseif ($entity->confidence_avg >= 0.7): ?>
                <span class="badge bg-warning text-dark" title="Medium confidence">Medium Confidence</span>
                <?php else: ?>
                <span class="badge bg-secondary" title="Low confidence">Low Confidence</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Entity Description -->
    <?php if (!empty($entity->description)): ?>
    <p class="text-muted small mb-3"><?php echo esc_specialchars($entity->description); ?></p>
    <?php endif; ?>

    <!-- Entity Stats -->
    <div class="row text-center mb-4">
        <div class="col-4">
            <div class="border rounded p-2">
                <div class="fs-4 fw-bold text-primary"><?php echo number_format($entity->occurrence_count); ?></div>
                <div class="small text-muted">Occurrences</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-2">
                <div class="fs-4 fw-bold text-success"><?php echo count($relatedEntities ?? []); ?></div>
                <div class="small text-muted">Related</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded p-2">
                <div class="fs-4 fw-bold text-info"><?php echo number_format(round($entity->confidence_avg * 100)); ?>%</div>
                <div class="small text-muted">Confidence</div>
            </div>
        </div>
    </div>

    <!-- Authority Record Link -->
    <?php if (!empty($entity->actor_id) || !empty($entity->term_id)): ?>
    <div class="alert alert-info py-2 small mb-3">
        <i class="bi bi-link-45deg me-1"></i>
        <?php if (!empty($entity->actor_id)): ?>
        Linked to <a href="<?php echo url_for(['module' => 'actor', 'slug' => $entity->actor_id]); ?>">authority record</a>
        <?php elseif (!empty($entity->term_id)): ?>
        Linked to taxonomy term #<?php echo esc_specialchars($entity->term_id); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- External Identifiers -->
    <?php if (!empty($entity->wikidata_id) || !empty($entity->viaf_id)): ?>
    <div class="mb-3">
        <h6 class="text-muted small text-uppercase">External Links</h6>
        <?php if (!empty($entity->wikidata_id)): ?>
        <a href="https://www.wikidata.org/wiki/<?php echo esc_specialchars($entity->wikidata_id); ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1 mb-1">
            <i class="bi bi-box-arrow-up-right me-1"></i> Wikidata
        </a>
        <?php endif; ?>
        <?php if (!empty($entity->viaf_id)): ?>
        <a href="https://viaf.org/viaf/<?php echo esc_specialchars($entity->viaf_id); ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1 mb-1">
            <i class="bi bi-box-arrow-up-right me-1"></i> VIAF
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Related Entities -->
    <?php if (!empty($relatedEntities)): ?>
    <div class="mb-3">
        <h6 class="text-muted small text-uppercase mb-2">Related Entities</h6>
        <div class="d-flex flex-wrap gap-1">
            <?php foreach (array_slice($relatedEntities, 0, 10) as $related): ?>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'entity', 'type' => $related['entity_type'], 'value' => $related['value']]); ?>"
               class="badge text-decoration-none" style="background-color: <?php echo $typeColors[$related['entity_type']] ?? '#999'; ?>;">
                <?php echo esc_specialchars($related['label']); ?>
                <span class="badge bg-light text-dark ms-1"><?php echo $related['co_occurrences']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (count($relatedEntities) > 10): ?>
        <a href="#" class="small text-muted">+<?php echo count($relatedEntities) - 10; ?> more</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Associated Records -->
    <?php if (!empty($objects)): ?>
    <div class="mb-3">
        <h6 class="text-muted small text-uppercase mb-2">Found In</h6>
        <ul class="list-unstyled mb-0">
            <?php foreach (array_slice($objects, 0, 5) as $obj): ?>
            <li class="mb-2">
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $obj->slug]); ?>" class="text-decoration-none">
                    <i class="bi bi-file-earmark me-1 text-muted"></i>
                    <?php echo esc_specialchars($obj->title ?? 'Untitled'); ?>
                </a>
                <span class="badge bg-light text-muted ms-1" title="Confidence"><?php echo round($obj->confidence * 100); ?>%</span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (count($objects) > 5): ?>
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'ner_' . $entity->entity_type . '[]' => $entity->canonical_value]); ?>" class="small">
            View all <?php echo count($objects); ?> records <i class="bi bi-arrow-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="d-grid gap-2 mt-4">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'ner_' . $entity->entity_type . '[]' => $entity->canonical_value]); ?>"
           class="btn btn-primary">
            <i class="bi bi-search me-1"></i> Search Records with this Entity
        </a>
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'graph', 'focus' => $entity->id]); ?>"
           class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> View in Graph
        </a>
    </div>
</div>
