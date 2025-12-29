<?php
/**
 * Custom Quick Links - Hardcoded (no database)
 * Add RIC and AHG Admin menu items directly
 */

$customQuickLinks = [];

// Only show to authenticated users
if ($sf_user->isAuthenticated()) {
    
    // AHG Admin - for administrators only
    if ($sf_user->isAdministrator()) {
        $customQuickLinks[] = [
            'label' => 'AHG Admin',
            'icon'  => 'fa-cogs',
            'url'   => url_for(['module' => 'settings', 'action' => 'ahgDashboard']),
            'title' => 'AHG Settings Dashboard',
        ];
    }
    
    // RIC Explorer - for all authenticated users
    $customQuickLinks[] = [
        'label' => 'RIC',
        'icon'  => 'fa-project-diagram',
        'url'   => url_for(['module' => 'arRicExplorer', 'action' => 'index']),
        'title' => 'Records in Contexts Explorer',
    ];
}

// Render the custom links
foreach ($customQuickLinks as $link): ?>
  <li>
    <a href="<?php echo $link['url']; ?>" title="<?php echo $link['title']; ?>">
      <i class="fas <?php echo $link['icon']; ?>" aria-hidden="true"></i>
      <span class="visually-hidden"><?php echo $link['label']; ?></span>
    </a>
  </li>
<?php endforeach; ?>
