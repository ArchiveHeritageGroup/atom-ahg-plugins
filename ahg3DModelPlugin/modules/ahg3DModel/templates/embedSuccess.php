<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Model Viewer</title>
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }
        
        model-viewer {
            width: 100%;
            height: 100%;
            --poster-color: transparent;
        }
        
        .hotspot {
            display: block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            background-color: var(--hotspot-color, #1a73e8);
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .hotspot:hover { transform: scale(1.2); }
        
        .hotspot-annotation {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            min-width: 120px;
            max-width: 200px;
            font-size: 13px;
            z-index: 100;
            margin-bottom: 8px;
        }
        
        .hotspot:hover .hotspot-annotation,
        .hotspot:focus .hotspot-annotation { display: block; }
        
        .hotspot-annotation strong {
            display: block;
            margin-bottom: 3px;
            color: #333;
        }
        
        .hotspot-annotation p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        
        .ar-button {
            position: absolute;
            bottom: 16px;
            left: 16px;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            background: #1a73e8;
            color: white;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }
        
        .progress-bar {
            display: block;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(255,255,255,0.2);
        }
        
        .progress-bar .update-bar {
            height: 100%;
            background: #1a73e8;
        }
        
        .error-message {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #721c24;
            background: #f8d7da;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php
$model = $sf_data->getRaw('model');
$hotspots = $sf_data->getRaw('hotspots');
$viewerType = $sf_data->getRaw('viewerType');

if (!$model): ?>
<div class="error-message">
    <p>Model not found</p>
</div>
<?php else:
    $baseUrl = sfContext::getInstance()->getRequest()->getUriPrefix();
    $modelUrl = "{$baseUrl}/uploads/{$model->file_path}";
    $posterUrl = $model->poster_image ? "{$baseUrl}/uploads/{$model->poster_image}" : '';
    $arAttrs = $model->ar_enabled ? 'ar ar-modes="webxr scene-viewer quick-look"' : '';
    $autoRotate = $model->auto_rotate ? 'auto-rotate' : '';
?>
<model-viewer
    id="embed-viewer"
    src="<?php echo $modelUrl ?>"
    <?php if ($posterUrl): ?>poster="<?php echo $posterUrl ?>"<?php endif ?>
    alt="<?php echo esc_entities($model->alt_text ?: $model->title ?: '3D Model') ?>"
    camera-controls
    touch-action="pan-y"
    <?php echo $arAttrs ?>
    <?php echo $autoRotate ?>
    rotation-per-second="<?php echo $model->rotation_speed ?>deg"
    camera-orbit="<?php echo $model->camera_orbit ?>"
    field-of-view="<?php echo $model->field_of_view ?>"
    exposure="<?php echo $model->exposure ?>"
    shadow-intensity="<?php echo $model->shadow_intensity ?>"
    <?php if ($model->environment_image): ?>environment-image="/uploads/<?php echo $model->environment_image ?>"<?php endif ?>
    style="background-color: <?php echo $model->background_color ?>;"
>
    <?php foreach ($hotspots as $hotspot): ?>
    <button class="hotspot" 
            slot="hotspot-<?php echo $hotspot->id ?>"
            data-position="<?php echo $hotspot->position_x ?>m <?php echo $hotspot->position_y ?>m <?php echo $hotspot->position_z ?>m"
            data-normal="<?php echo $hotspot->normal_x ?>m <?php echo $hotspot->normal_y ?>m <?php echo $hotspot->normal_z ?>m"
            style="--hotspot-color: <?php echo $hotspot->color ?>;">
        <div class="hotspot-annotation">
            <?php if ($hotspot->title): ?>
            <strong><?php echo esc_entities($hotspot->title) ?></strong>
            <?php endif ?>
            <?php if ($hotspot->description): ?>
            <p><?php echo esc_entities($hotspot->description) ?></p>
            <?php endif ?>
        </div>
    </button>
    <?php endforeach ?>

    <?php if ($model->ar_enabled): ?>
    <button slot="ar-button" class="ar-button">View in AR</button>
    <?php endif ?>

    <div class="progress-bar" slot="progress-bar">
        <div class="update-bar"></div>
    </div>
</model-viewer>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
// Handle hotspot clicks for links
document.querySelectorAll('.hotspot[data-link]').forEach(hotspot => {
    hotspot.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.dataset.link;
        const target = this.dataset.target || '_blank';
        if (url) {
            window.parent.postMessage({type: 'hotspot-link', url: url, target: target}, '*');
        }
    });
});
</script>
<?php endif ?>
</body>
</html>
