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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condition Report - <?php echo $conditionCheck->identifier ?></title>
    <style>
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
        .photo-item img { max-width: 100%; height: auto; display: block; margin-bottom: 10px; }
        .photo-caption { font-size: 10pt; color: #666; }
        .photo-type { display: inline-block; padding: 2px 8px; background: #eee; font-size: 9pt; margin-bottom: 5px; }
        .annotation-list { margin-top: 10px; font-size: 10pt; }
        .annotation-item { padding: 3px 0; border-bottom: 1px dotted #ddd; }
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
        <div class="subtitle"><?php echo $conditionCheck->identifier ?> - <?php echo $conditionCheck->object_title ?></div>
    </div>
    
    <table class="meta-table">
        <tr>
            <td>Reference</td>
            <td><?php echo $conditionCheck->condition_reference ?: 'CC-' . $conditionCheck->id ?></td>
            <td>Date</td>
            <td><?php echo date('j F Y', strtotime($conditionCheck->check_date)) ?></td>
        </tr>
        <tr>
            <td>Checked By</td>
            <td><?php echo $conditionCheck->checked_by ?></td>
            <td>Overall Condition</td>
            <td><strong><?php echo ucfirst($conditionCheck->overall_condition ?: 'Not assessed') ?></strong></td>
        </tr>
        <tr>
            <td>Check Reason</td>
            <td colspan="3"><?php echo $conditionCheck->check_reason ?: '-' ?></td>
        </tr>
    </table>
    
    <?php if ($conditionCheck->condition_note): ?>
    <div class="section">
        <h2>Condition Notes</h2>
        <p><?php echo nl2br(esc_entities($conditionCheck->condition_note)) ?></p>
    </div>
    <?php endif ?>
    
    <?php if ($conditionCheck->recommended_treatment): ?>
    <div class="section">
        <h2>Recommended Treatment</h2>
        <p><?php echo nl2br(esc_entities($conditionCheck->recommended_treatment)) ?></p>
    </div>
    <?php endif ?>
    
    <div class="section">
        <h2>Condition Photos (<?php echo count($photos) ?>)</h2>
        
        <?php if (empty($photos)): ?>
        <p>No photos attached to this condition check.</p>
        <?php else: ?>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): 
                $annotations = json_decode($photo->annotation_data ?: '[]', true);
            ?>
            <div class="photo-item">
                <span class="photo-type"><?php echo $photoTypes[$photo->photo_type] ?? $photo->photo_type ?></span>
                <img src="/uploads/condition_photos/<?php echo $photo->filename ?>" alt="<?php echo esc_entities($photo->caption) ?>">
                <div class="photo-caption"><?php echo $photo->caption ?: 'No caption' ?></div>
                
                <?php if (!empty($annotations)): ?>
                <div class="annotation-list">
                    <strong>Annotations (<?php echo count($annotations) ?>):</strong>
                    <?php foreach ($annotations as $ann): ?>
                    <div class="annotation-item">
                        • <?php echo esc_entities($ann['label'] ?? 'Note') ?>
                        <?php if (!empty($ann['notes'])): ?>
                        : <?php echo esc_entities($ann['notes']) ?>
                        <?php endif ?>
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
        <p>Generated: <?php echo date('j F Y H:i') ?> | Spectrum 5.0 Condition Check Report</p>
    </div>
</body>
</html>
