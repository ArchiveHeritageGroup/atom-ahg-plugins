<?php use_helper('Date') ?>

<?php include_partial('donorAgreement/form', [
    'agreement' => $agreement ?? null,
    'types' => $types ?? [],
    'statuses' => $statuses ?? [],
    'donor' => $donor ?? null,
    'donorId' => $donorId ?? null,
    'documents' => [],
    'title' => __('Add Agreement'),
    'donors' => $donors ?? [],
    'action' => url_for(['module' => 'donorAgreement', 'action' => 'add'])
]) ?>
