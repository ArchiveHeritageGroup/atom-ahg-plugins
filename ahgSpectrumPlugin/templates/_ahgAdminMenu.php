<?php
/**
 * AHG Admin Menu Integration
 * 
 * Add this partial to your AHG theme's layout to integrate
 * the centralized settings menu into AtoM's admin area.
 * 
 * Usage: Include in your theme's _header.php or navigation template
 * <?php include_partial('ahgAdminMenu'); ?>
 */
?>

<?php if ($sf_user->isAdministrator()): ?>
<li class="dropdown ahg-admin-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-archive"></i>
        <?php echo __('AHG Plugins'); ?>
        <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <!-- AHG Settings -->
        <li class="dropdown-header"><?php echo __('Settings'); ?></li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings']); ?>">
                <i class="fas fa-cogs fa-fw"></i> <?php echo __('AHG Settings'); ?>
            </a>
        </li>
        
        <li role="separator" class="divider"></li>
        
        <!-- Spectrum Collections -->
        <li class="dropdown-header"><?php echo __('Collections Management'); ?></li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'spectrum']); ?>">
                <i class="fas fa-archive fa-fw"></i> <?php echo __('Spectrum Settings'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>">
                <i class="fas fa-tachometer-alt fa-fw"></i> <?php echo __('Spectrum Dashboard'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'myTasks']); ?>">
                <i class="fas fa-clipboard-list fa-fw"></i> <?php echo __('My Tasks'); ?>
                <?php
                // Show count badge if user is authenticated
                if ($sf_user->isAuthenticated()) {
                    $userId = $sf_user->getAttribute('user_id');
                    if ($userId) {
                        $taskCount = \Illuminate\Database\Capsule\Manager::table('spectrum_workflow_state')
                            ->where('assigned_to', $userId)
                            ->count();
                        if ($taskCount > 0) {
                            echo '<span class="badge bg-primary ms-1">' . $taskCount . '</span>';
                        }
                    }
                }
                ?>
            </a>
        </li>

        <li role="separator" class="divider"></li>

        <!-- Data Protection -->
        <li class="dropdown-header"><?php echo __('Data Protection'); ?></li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'data_protection']); ?>">
                <i class="fas fa-shield-alt fa-fw"></i> <?php echo __('Compliance Settings'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo url_for(['module' => 'accessRequest', 'action' => 'index']); ?>">
                <i class="fas fa-file-alt fa-fw"></i> <?php echo __('Access Requests'); ?>
            </a>
        </li>
        
        <li role="separator" class="divider"></li>
        
        <!-- Media & IIIF -->
        <li class="dropdown-header"><?php echo __('Media'); ?></li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'media']); ?>">
                <i class="fas fa-play-circle fa-fw"></i> <?php echo __('Media Player'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'iiif']); ?>">
                <i class="fas fa-images fa-fw"></i> <?php echo __('IIIF Viewer'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'photos']); ?>">
                <i class="fas fa-camera fa-fw"></i> <?php echo __('Photo Settings'); ?>
            </a>
        </li>
        
        <li role="separator" class="divider"></li>
        
        <!-- Jobs & Maintenance -->
        <li class="dropdown-header"><?php echo __('Maintenance'); ?></li>
        <li>
            <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'jobs']); ?>">
                <i class="fas fa-tasks fa-fw"></i> <?php echo __('Background Jobs'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo url_for(['module' => 'jobs', 'action' => 'browse']); ?>">
                <i class="fas fa-history fa-fw"></i> <?php echo __('Job History'); ?>
            </a>
        </li>
    </ul>
</li>
<?php endif; ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.ahg-admin-menu .dropdown-header {
    font-weight: 600;
    color: #6c757d;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 15px 4px;
}

.ahg-admin-menu .dropdown-menu {
    min-width: 220px;
}

.ahg-admin-menu .dropdown-menu a {
    padding: 8px 15px;
}

.ahg-admin-menu .dropdown-menu a i {
    margin-right: 8px;
    color: #6c757d;
}

.ahg-admin-menu .dropdown-menu a:hover i {
    color: inherit;
}

.ahg-admin-menu .divider {
    margin: 5px 0;
}
</style>
