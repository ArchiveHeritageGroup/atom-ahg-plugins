<?php
// Get raw data - avoid Symfony escaper
$logsRaw = sfOutputEscaper::unescape($logs);
$formatRaw = sfOutputEscaper::unescape($format);

// getFiltered returns array with 'data' key
$logsData = [];
if (is_array($logsRaw) && isset($logsRaw['data'])) {
    $logsData = $logsRaw['data'];
} elseif (is_object($logsRaw) && method_exists($logsRaw, 'toArray')) {
    $logsData = $logsRaw->toArray();
} elseif (is_array($logsRaw)) {
    $logsData = $logsRaw;
}

if ($formatRaw === 'json') {
    $output = [];
    foreach ($logsData as $log) {
        $output[] = (array)$log;
    }
    echo json_encode($output, JSON_PRETTY_PRINT);
} else {
    // CSV output
    echo "id,uuid,created_at,user_id,username,ip_address,action,entity_type,entity_id,entity_slug,entity_title,status\n";
    foreach ($logsData as $log) {
        if (is_object($log) && method_exists($log, 'toArray')) {
            $log = (object)$log->toArray();
        } else {
            $log = (object)$log;
        }
        $row = [
            $log->id ?? '',
            '"' . ($log->uuid ?? '') . '"',
            '"' . ($log->created_at ?? '') . '"',
            $log->user_id ?? '',
            '"' . addslashes($log->username ?? '') . '"',
            '"' . ($log->ip_address ?? '') . '"',
            '"' . ($log->action ?? '') . '"',
            '"' . ($log->entity_type ?? '') . '"',
            $log->entity_id ?? '',
            '"' . addslashes($log->entity_slug ?? '') . '"',
            '"' . addslashes($log->entity_title ?? '') . '"',
            '"' . ($log->status ?? '') . '"',
        ];
        echo implode(',', $row) . "\n";
    }
}
