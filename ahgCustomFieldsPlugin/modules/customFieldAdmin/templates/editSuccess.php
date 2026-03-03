<?php use_helper('I18N'); ?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>
            <i class="bi bi-input-cursor-text"></i>
            <?php echo $definition ? 'Edit Field: ' . htmlspecialchars($definition->field_label) : 'Add Custom Field'; ?>
        </h2>
        <a href="<?php echo url_for(['module' => 'customFieldAdmin', 'action' => 'index']); ?>"
           class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php include_partial('customFieldAdmin/fieldForm', [
                'definition' => $definition,
                'entityTypes' => $entityTypes,
                'fieldTypes' => $fieldTypes,
                'taxonomies' => $taxonomies,
                'fieldGroups' => $fieldGroups,
            ]); ?>
        </div>
    </div>
</div>
