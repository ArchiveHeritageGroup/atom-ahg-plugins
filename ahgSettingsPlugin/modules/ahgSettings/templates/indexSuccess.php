<?php slot('title', __('AHG Plugin Settings')); ?>
<?php
// Check if DAM features are explicitly enabled
$damEnabled = false;
try {
    $result = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
        ->where('setting_key', 'dam_tools_enabled')
        ->where('setting_group', 'general')
        ->first();
    $damEnabled = $result && $result->setting_value === '1';
} catch (Exception $e) {
    $damEnabled = false;
}

// Build complete list of all cards
$allCards = [];

// Add dynamic sections from controller
foreach ($sections as $key => $section) {
    $allCards[$section['label']] = [
        'label' => $section['label'],
        'icon' => $section['icon'],
        'icon_prefix' => 'fas',
        'description' => $section['description'],
        'url' => '/index.php/' . $section['url'],
        'color' => 'primary',
        'btn_text' => 'Configure',
        'btn_icon' => 'fa-cog'
    ];
}

// Add static cards
if ($damEnabled) {
    $allCards['Digital Asset Management'] = [
        'label' => 'Digital Asset Management',
        'icon' => 'fa-photo-video',
        'icon_prefix' => 'fas',
        'description' => 'PDF merge, digital objects, 3D viewer, and media tools',
        'url' => url_for(['module' => 'ahgSettings', 'action' => 'damTools']),
        'color' => 'info',
        'btn_text' => 'Open Tools',
        'btn_icon' => 'fa-tools'
    ];
}

$allCards['Heritage Platform'] = [
    'label' => 'Heritage Platform',
    'icon' => 'fa-landmark',
    'icon_prefix' => 'fas',
    'description' => 'Access control, analytics, branding, custodian tools, and community features',
    'url' => url_for(['module' => 'heritage', 'action' => 'adminDashboard']),
    'color' => 'warning',
    'btn_text' => 'Admin',
    'btn_icon' => 'fa-tools'
];

$allCards['Preservation & Backup'] = [
    'label' => 'Preservation & Backup',
    'icon' => 'fa-cloud-upload-alt',
    'icon_prefix' => 'fas',
    'description' => 'Configure backup replication targets, verify integrity, and manage preservation',
    'url' => url_for(['module' => 'ahgSettings', 'action' => 'preservation']),
    'color' => 'success',
    'btn_text' => 'Configure',
    'btn_icon' => 'fa-cog'
];

// Sort alphabetically by label
ksort($allCards);
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="mb-0"><i class="fas fa-cogs"></i> AHG Plugin Settings</h1>
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'global']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Admin Settings') ?>
    </a>
</div>
<p class="text-muted mb-4">Configure AHG theme and plugin settings</p>
<div class="row">
    <?php foreach ($allCards as $card): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm <?php echo $card['color'] !== 'primary' ? 'border-' . $card['color'] : ''; ?>">
            <div class="card-body text-center py-4">
                <div class="mb-3">
                    <i class="<?php echo $card['icon_prefix']; ?> <?php echo $card['icon']; ?> fa-3x text-<?php echo $card['color']; ?>"></i>
                </div>
                <h5 class="card-title"><?php echo __($card['label']); ?></h5>
                <p class="card-text text-muted small"><?php echo __($card['description']); ?></p>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="<?php echo $card['url']; ?>" class="btn btn-<?php echo $card['color']; ?>">
                    <i class="fas <?php echo $card['btn_icon']; ?>"></i> <?php echo __($card['btn_text']); ?>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($damEnabled): ?>
<!-- Quick Access - TIFF to PDF Merge -->
<div class="card mt-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Quick Access: TIFF to PDF Merge</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-0">
                    <strong>Create multi-page PDF documents from images</strong><br>
                    <small class="text-muted">Upload multiple TIFF, JPEG, or PNG files and merge them into a single PDF/A archival document.
                    Jobs run in the background and can be attached directly to archival records.</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-file-pdf me-1"></i> Create PDF
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
