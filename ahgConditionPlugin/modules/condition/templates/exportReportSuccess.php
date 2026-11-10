<?php
/**
 * Condition Report Export Template
 * Printable HTML format
 */
$photoTypes = [
    'general' => 'General',
    'detail' => 'Detail',
    'damage' => 'Damage',
    'before' => 'Before Treatment',
    'after' => 'After Treatment',
    'raking' => 'Raking Light',
    'uv' => 'UV Light',
];
// Raw rows: through the output escaper the annotations JSON arrives with &quot; and
// json_decode fails. Every value printed below is escaped explicitly.
$photos = isset($sf_data) ? $sf_data->getRaw('photos') : $photos;

/**
 * SVG overlay of a photo's annotations. Coordinates are stored in image pixels
 * (condition-annotator.js toJSON divides by the canvas scale), so the viewBox is the
 * image size and the overlay scales with the image.
 */
$annotationOverlay = function ($photo, array $annotations) {
    $w = (int) $photo->width;
    $h = (int) $photo->height;
    if ((!$w || !$h) && $photo->file_path) {
        $size = @getimagesize(sfConfig::get('sf_root_dir') . '/' . ltrim((string) $photo->file_path, '/'));
        if ($size) {
            [$w, $h] = $size;
        }
    }
    if (!$w || !$h || !$annotations) {
        return '';
    }
    $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES);
    $n = fn ($v) => round((float) $v, 1);
    $r = max(12, round(min($w, $h) / 45));
    $out = '';
    foreach (array_values($annotations) as $i => $ann) {
        $fd = $ann['fabricData'] ?? $ann;
        $type = $fd['type'] ?? ($ann['type'] ?? '');
        $c = $e($ann['color'] ?? ($fd['stroke'] ?? '#FF0000'));
        $x = $n($fd['left'] ?? 0);
        $y = $n($fd['top'] ?? 0);
        $line = 'fill="none" stroke="' . $c . '" stroke-width="3" vector-effect="non-scaling-stroke"';
        if ('ellipse' === $type || 'circle' === $type) {
            $rx = $n($fd['rx'] ?? (($fd['width'] ?? 50) / 2));
            $ry = $n($fd['ry'] ?? (($fd['height'] ?? 50) / 2));
            $out .= '<ellipse cx="' . ($x + $rx) . '" cy="' . ($y + $ry) . '" rx="' . $rx . '" ry="' . $ry . '" ' . $line . '/>';
        } elseif (in_array($type, ['i-text', 'text', 'group', 'marker'], true)) {
            // Text labels and numbered markers: shown by their number only.
        } else {
            // Rectangles, and the bounding box of arrows and freehand lines.
            $dash = 'rect' === $type ? '' : ' stroke-dasharray="8 5"';
            $out .= '<rect x="' . $x . '" y="' . $y . '" width="' . $n($fd['width'] ?? 0) . '" height="' . $n($fd['height'] ?? 0) . '" ' . $line . $dash . '/>';
        }
        $out .= '<circle cx="' . ($x + $r) . '" cy="' . ($y + $r) . '" r="' . $r . '" fill="' . $c . '"/>'
            . '<text x="' . ($x + $r) . '" y="' . ($y + $r) . '" font-size="' . round($r * 1.2) . '" font-family="Arial,sans-serif" font-weight="bold" fill="#fff" text-anchor="middle" dominant-baseline="central">' . ($i + 1) . '</text>';
    }

    return '<svg class="annotation-overlay" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" aria-hidden="true">' . $out . '</svg>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condition Report - <?php echo esc_entities($conditionCheck->identifier) ?></title>
    <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
        * { box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11pt; 
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 { margin: 0 0 5px 0; font-size: 18pt; }
        .header .subtitle { color: #666; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .meta-table td { padding: 5px 10px; border: 1px solid #ddd; }
        .meta-table td:first-child { font-weight: bold; width: 150px; background: #f5f5f5; }
        .section { margin-bottom: 25px; }
        .section h2 { font-size: 14pt; border-bottom: 1px solid #999; padding-bottom: 5px; margin-bottom: 10px; }
        .photo-grid { display: flex; flex-wrap: wrap; gap: 15px; }
        .photo-item { width: calc(50% - 10px); border: 1px solid #ddd; padding: 10px; }
        .photo-item img { max-width: 100%; height: auto; display: block; }
        .photo-frame { position: relative; margin-bottom: 10px; line-height: 0; }
        .annotation-overlay { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; }
        .photo-caption { font-size: 10pt; color: #666; }
        .photo-type { display: inline-block; padding: 2px 8px; background: #eee; font-size: 9pt; margin-bottom: 5px; }
        .annotation-list { margin-top: 10px; font-size: 10pt; }
        .annotation-item { padding: 3px 0; border-bottom: 1px dotted #ddd; }
        .ai-note { border: 2px solid #b8860b; background: #fff8e1; padding: 8px 12px; margin: 0 0 20px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #999; font-size: 9pt; color: #666; }
        @media print {
            body { padding: 0; }
            .photo-item { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Condition Check Report</h1>
        <div class="subtitle"><?php echo esc_entities($conditionCheck->identifier) ?> - <?php echo esc_entities($conditionCheck->object_title) ?></div>
    </div>
    <?php if (str_starts_with((string) $conditionCheck->checked_by, 'AI (')): ?>
    <p class="ai-note"><strong>AI suggestion - not verified.</strong> This assessment was made by an AI model from a photograph and has not been checked by a person.</p>
    <?php endif ?>
    
    <table class="meta-table">
        <tr>
            <td>Reference</td>
            <td><?php echo esc_entities($conditionCheck->condition_check_reference ?: $conditionCheck->condition_reference ?: 'CC-' . $conditionCheck->id) ?></td>
            <td>Date</td>
            <td><?php echo date('j F Y', strtotime($conditionCheck->check_date)) ?></td>
        </tr>
        <tr>
            <td>Checked By</td>
            <td><?php echo esc_entities($conditionCheck->checked_by) ?></td>
            <td>Overall Condition</td>
            <td><strong><?php echo esc_entities(ucfirst($conditionCheck->overall_condition ?: 'Not assessed')) ?></strong></td>
        </tr>
        <tr>
            <td>Check Reason</td>
            <td colspan="3"><?php echo esc_entities($conditionCheck->check_reason ?: '-') ?></td>
        </tr>
    </table>
    
    <?php if ($conditionCheck->completeness || $conditionCheck->treatment_priority || $conditionCheck->next_check_date): ?>
    <table class="meta-table">
        <tr>
            <td>Completeness</td>
            <td><?php echo esc_entities(ucfirst($conditionCheck->completeness ?: '-')) ?></td>
            <td>Treatment Priority</td>
            <td><?php echo esc_entities(ucfirst($conditionCheck->treatment_priority ?: '-')) ?></td>
        </tr>
        <tr>
            <td>Next Check</td>
            <td colspan="3"><?php echo $conditionCheck->next_check_date ? date('j F Y', strtotime($conditionCheck->next_check_date)) : '-' ?></td>
        </tr>
    </table>
    <?php endif ?>

    <?php // The assessment itself: the report carried only the notes and treatment before. ?>
    <?php foreach (['condition_description' => 'Condition Description', 'hazards_noted' => 'Hazards Noted', 'technical_assessment' => 'Technical Assessment'] as $k => $label): ?>
    <?php if (!empty($conditionCheck->$k)): ?>
    <div class="section">
        <h2><?php echo $label ?></h2>
        <p><?php echo nl2br(esc_entities($conditionCheck->$k)) ?></p>
    </div>
    <?php endif ?>
    <?php endforeach ?>

    <?php if ($conditionCheck->condition_note): ?>
    <div class="section">
        <h2>Condition Notes</h2>
        <p><?php echo nl2br(esc_entities($conditionCheck->condition_note)) ?></p>
    </div>
    <?php endif ?>
    
    <?php $treatment = $conditionCheck->recommended_treatment ?: $conditionCheck->recommendations; ?>
    <?php if ($treatment): ?>
    <div class="section">
        <h2>Recommended Treatment</h2>
        <p><?php echo nl2br(esc_entities($treatment)) ?></p>
    </div>
    <?php endif ?>

    <?php $care = array_filter(['Environment' => $conditionCheck->environment_recommendation, 'Handling' => $conditionCheck->handling_recommendation, 'Display' => $conditionCheck->display_recommendation, 'Storage' => $conditionCheck->storage_recommendation, 'Packing and Transport' => $conditionCheck->packing_recommendation]); ?>
    <?php if ($care): ?>
    <div class="section">
        <h2>Care Recommendations</h2>
        <table class="meta-table">
            <?php foreach ($care as $label => $text): ?>
            <tr><td><?php echo $label ?></td><td colspan="3"><?php echo nl2br(esc_entities($text)) ?></td></tr>
            <?php endforeach ?>
        </table>
    </div>
    <?php endif ?>
    
    <div class="section">
        <h2>Condition Photos (<?php echo count($photos) ?>)</h2>
        
        <?php if (empty($photos)): ?>
        <p>No photos attached to this condition check.</p>
        <?php else: ?>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): 
                // The column is `annotations`; reading `annotation_data` (no such column) left every report without its annotations.
                $annotations = json_decode($photo->annotations ?: '[]', true);
                $annotations = is_array($annotations) ? $annotations : [];
            ?>
            <div class="photo-item">
                <span class="photo-type"><?php echo esc_entities($photoTypes[$photo->photo_type] ?? $photo->photo_type) ?></span>
                <div class="photo-frame">
                    <img src="<?php echo url_for('@condition_photo_file?id=' . $photo->id) ?>" alt="<?php echo esc_entities($photo->caption) ?>">
                    <?php echo $annotationOverlay($photo, $annotations) ?>
                </div>
                <div class="photo-caption"><?php echo esc_entities($photo->caption ?: 'No caption') ?></div>
                
                <?php if (!empty($annotations)): ?>
                <div class="annotation-list">
                    <strong>Annotations (<?php echo count($annotations) ?>):</strong>
                    <?php foreach (array_values($annotations) as $i => $ann): ?>
                    <div class="annotation-item">
                        <?php echo $i + 1 ?>. <?php echo esc_entities($ann['label'] ?? 'Note') ?><?php if (!empty($ann['notes'])): ?>: <?php echo esc_entities($ann['notes']) ?><?php endif ?>
                        <?php if (!empty($ann['ai_generated'])): ?>
                        <em>(AI detected)</em>
                        <?php endif ?>
                    </div>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
        <?php endif ?>
    </div>
    
    <div class="section">
        <h2>Statistics</h2>
        <table class="meta-table">
            <tr>
                <td>Total Photos</td>
                <td><?php echo $stats['total_photos'] ?></td>
            </tr>
            <tr>
                <td>Annotated Photos</td>
                <td><?php echo $stats['annotated_photos'] ?></td>
            </tr>
            <tr>
                <td>Total Annotations</td>
                <td><?php echo $stats['total_annotations'] ?></td>
            </tr>
            <?php if (!empty($stats['by_type'])): ?>
            <tr>
                <td>By Category</td>
                <td>
                    <?php foreach ($stats['by_type'] as $type => $count): ?>
                    <?php echo ucfirst($type) ?>: <?php echo $count ?><br>
                    <?php endforeach ?>
                </td>
            </tr>
            <?php endif ?>
        </table>
    </div>
    
    <div class="footer">
        <p>Generated: <?php echo date('j F Y H:i') ?> | Collections Procedures Condition Check Report</p>
    </div>
</body>
</html>
