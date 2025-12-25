<?php use_helper('Date') ?>
<?php include_partial('donorAgreement/form', [
    'agreement' => $agreement,
    'types' => $types,
    'statuses' => $statuses,
    'donor' => $donor ?? null,
    'donorId' => $donorId ?? null,
    'documents' => $documents ?? [],
    'linkedRecords' => $linkedRecords ?? [],
    'linkedAccessions' => $linkedAccessions ?? [],
    'reminders' => $reminders ?? [],
    'title' => __('Edit Agreement') . ': ' . esc_entities($agreement->agreement_number ?: $agreement->title),
    'action' => url_for(['module' => 'donorAgreement', 'action' => 'edit', 'id' => $agreement->id])
]) ?>
