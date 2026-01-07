<?php use_helper('Date') ?>
<?php include_partial('donorAgreement/form', [
    'agreement' => $agreement ?? null,
    'types' => $types ?? [],
    'statuses' => $statuses ?? [],
    'donor' => $donor ?? null,
    'donorId' => $donorId ?? null,
    'documents' => [],
    'donors' => $donors ?? [],
    'title' => __('Add Agreement'),
    'action' => url_for(['module' => 'donorAgreement', 'action' => 'add'])
]) ?>
