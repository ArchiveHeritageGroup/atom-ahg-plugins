<?php if ($format === 'json'): ?>
<?php echo json_encode($logs->items(), JSON_PRETTY_PRINT) ?>
<?php else: ?>
<?php
echo "id,uuid,created_at,user_id,username,ip_address,action,entity_type,entity_id,entity_slug,entity_title,status,security_classification,error_message\n";
foreach ($logs as $log) {
    echo implode(',', [
        $log->id,
        '"' . $log->uuid . '"',
        '"' . $log->created_at . '"',
        $log->user_id ?? '',
        '"' . ($log->username ?? '') . '"',
        '"' . ($log->ip_address ?? '') . '"',
        '"' . $log->action . '"',
        '"' . $log->entity_type . '"',
        $log->entity_id ?? '',
        '"' . ($log->entity_slug ?? '') . '"',
        '"' . str_replace('"', '""', $log->entity_title ?? '') . '"',
        '"' . $log->status . '"',
        '"' . ($log->security_classification ?? '') . '"',
        '"' . str_replace('"', '""', $log->error_message ?? '') . '"',
    ]) . "\n";
}
?>
<?php endif; ?>
