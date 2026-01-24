<?php
// CSV Header
echo "Object Number,Title,Section,Display Location,Insurance Value,Condition,Notes\n";

// CSV Rows
foreach ($objects as $obj) {
    $row = [
        '"' . str_replace('"', '""', $obj['object_number'] ?? '') . '"',
        '"' . str_replace('"', '""', $obj['object_title'] ?? '') . '"',
        '"' . str_replace('"', '""', $obj['section_name'] ?? '') . '"',
        '"' . str_replace('"', '""', $obj['display_location'] ?? '') . '"',
        $obj['insurance_value'] ?? '',
        '"' . str_replace('"', '""', $obj['condition_status'] ?? '') . '"',
        '"' . str_replace('"', '""', $obj['display_notes'] ?? '') . '"',
    ];
    echo implode(',', $row) . "\n";
}
