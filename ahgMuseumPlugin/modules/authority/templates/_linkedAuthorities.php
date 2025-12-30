<?php
/**
 * Linked Authorities Component
 * 
 * Displays linked external authorities for an actor record.
 * Include this component in actor view templates.
 * 
 * Usage in template:
 *   <?php include_component('authority', 'linkedAuthorities', ['actor' => $resource]); ?>
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */
?>

<?php
$service = new arAuthorityLinkageService();
$authorities = $service->getActorAuthorities($actor->id);
$sources = arAuthorityLinkageService::$sources;

if (empty($authorities)) {
    return;
}
?>

<section class="linked-authorities-section">
    <h3><?php echo __('External Authorities'); ?></h3>
    
    <div class="authority-links">
        <?php foreach ($authorities as $sourceId => $auth): ?>
            <div class="authority-link-item">
                <a href="<?php echo $auth['uri']; ?>" target="_blank" rel="noopener noreferrer" 
                   class="authority-link" title="<?php echo $sources[$sourceId]['fullName']; ?>">
                    <i class="fa <?php echo $sources[$sourceId]['icon']; ?>"></i>
                    <span class="authority-name"><?php echo $sources[$sourceId]['label']; ?></span>
                    <span class="authority-id"><?php echo $auth['id']; ?></span>
                    <i class="fa fa-external-link"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($sf_user->isAuthenticated()): ?>
        <div class="authority-manage">
            <a href="<?php echo url_for(['module' => 'authority', 'action' => 'link', 'slug' => $actor->slug]); ?>" 
               class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-link"></i> <?php echo __('Manage Authority Links'); ?>
            </a>
        </div>
    <?php endif; ?>
</section>

<style>
.linked-authorities-section {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.linked-authorities-section h3 {
    font-size: 16px;
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.authority-links {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 15px;
}

.authority-link-item {
    flex: 0 0 auto;
}

.authority-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    color: #2c3e50;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s;
}

.authority-link:hover {
    border-color: #3498db;
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
}

.authority-link i:first-child {
    color: #3498db;
    font-size: 16px;
}

.authority-name {
    font-weight: 600;
}

.authority-id {
    color: #7f8c8d;
    font-family: monospace;
    font-size: 12px;
}

.authority-link .fa-external-link {
    font-size: 10px;
    color: #bdc3c7;
}

.authority-manage {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e0e0e0;
}
</style>
