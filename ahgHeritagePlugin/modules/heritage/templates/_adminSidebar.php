<?php
/**
 * Admin Sidebar Partial.
 */
$active = $active ?? '';
?>
<div class="list-group mb-4">
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminDashboard']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminConfig']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'config' ? 'active' : ''; ?>">
        <i class="fas fa-sliders-h me-2"></i>Landing Config
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminFeatures']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'features' ? 'active' : ''; ?>">
        <i class="fas fa-toggle-on me-2"></i>Feature Toggles
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminBranding']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'branding' ? 'active' : ''; ?>">
        <i class="fas fa-palette me-2"></i>Branding
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'hero-slides' ? 'active' : ''; ?>">
        <i class="fas fa-images me-2"></i>Hero Slides
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminFeaturedCollections']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'featured' ? 'active' : ''; ?>">
        <i class="fas fa-star me-2"></i>Featured Collections
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminUsers']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'users' ? 'active' : ''; ?>">
        <i class="fas fa-users me-2"></i>Users
    </a>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold">Contributions</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'reviewQueue']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'contributions' ? 'active' : ''; ?>">
        <i class="fas fa-inbox me-2"></i>Review Queue
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'leaderboard']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'leaderboard' ? 'active' : ''; ?>">
        <i class="fas fa-trophy me-2"></i>Leaderboard
    </a>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold">Access Control</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminAccessRequests']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'access' ? 'active' : ''; ?>">
        <i class="fas fa-key me-2"></i>Access Requests
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminEmbargoes']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'embargoes' ? 'active' : ''; ?>">
        <i class="fas fa-lock me-2"></i>Embargoes
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminPopia']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'popia' ? 'active' : ''; ?>">
        <i class="fas fa-shield-alt-exclamation me-2"></i>POPIA Flags
    </a>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold">Custodian Tools</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianDashboard']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'custodian' ? 'active' : ''; ?>">
        <i class="fas fa-tools me-2"></i>Custodian Dashboard
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianBatch']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'batch' ? 'active' : ''; ?>">
        <i class="fas fa-layer-group me-2"></i>Batch Operations
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianHistory']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'history' ? 'active' : ''; ?>">
        <i class="fas fa-clock-history me-2"></i>Audit Trail
    </a>
</div>

<div class="list-group">
    <span class="list-group-item bg-light fw-bold">Analytics</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsDashboard']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'analytics' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsSearch']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'search-analytics' ? 'active' : ''; ?>">
        <i class="fas fa-search me-2"></i>Search Insights
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsAlerts']); ?>"
       class="list-group-item list-group-item-action <?php echo $active === 'alerts' ? 'active' : ''; ?>">
        <i class="fas fa-bell me-2"></i>Alerts
    </a>
</div>
