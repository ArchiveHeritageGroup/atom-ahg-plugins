<?php

/**
 * Shared left-hand navigation for the heritage accounting screens.
 *
 * The accounting, GRAP compliance and reporting screens live in three separate
 * modules with no navigation between them, so each was a dead end - you had to
 * know the URL. This partial ties them together the way the Heratio build does.
 *
 * Include from any of those modules:
 *   <?php include_partial('heritageAccounting/accountingMenu') ?>
 *
 * The current page is highlighted by comparing module + action, so a section
 * stays marked while you are inside it.
 */
$currentModule = $sf_context->getModuleName();
$currentAction = $sf_context->getActionName();

$menuSections = [
    __('Heritage Accounting') => [
        ['module' => 'heritageAccounting', 'action' => 'dashboard', 'icon' => 'fa-tachometer-alt', 'label' => __('Dashboard')],
        ['module' => 'heritageAccounting', 'action' => 'browse', 'icon' => 'fa-list', 'label' => __('Browse Assets')],
        ['module' => 'heritageAccounting', 'action' => 'add', 'icon' => 'fa-plus', 'label' => __('Add Asset')],
        ['module' => 'heritageAccounting', 'action' => 'settings', 'icon' => 'fa-cog', 'label' => __('Settings')],
    ],
    __('GRAP 103 Compliance') => [
        ['module' => 'grapCompliance', 'action' => 'dashboard', 'icon' => 'fa-balance-scale', 'label' => __('Compliance Dashboard')],
        ['module' => 'grapCompliance', 'action' => 'batchCheck', 'icon' => 'fa-check-double', 'label' => __('Batch Check')],
        ['module' => 'grapCompliance', 'action' => 'nationalTreasuryReport', 'icon' => 'fa-file-alt', 'label' => __('Treasury Report')],
    ],
    __('Reports') => [
        ['module' => 'heritageReport', 'action' => 'index', 'icon' => 'fa-chart-bar', 'label' => __('Reports Index')],
        ['module' => 'heritageReport', 'action' => 'assetRegister', 'icon' => 'fa-clipboard-list', 'label' => __('Asset Register')],
        ['module' => 'heritageReport', 'action' => 'movement', 'icon' => 'fa-exchange-alt', 'label' => __('Movement Report')],
        ['module' => 'heritageReport', 'action' => 'valuation', 'icon' => 'fa-dollar-sign', 'label' => __('Valuation Report')],
    ],
];
?>

<nav class="list-group mb-3 sticky-top" style="top: 1rem;" aria-label="<?php echo __('Heritage accounting navigation') ?>">
  <?php foreach ($menuSections as $section => $links): ?>
    <div class="list-group-item list-group-item-dark fw-bold small text-uppercase"><?php echo $section ?></div>
    <?php foreach ($links as $link): ?>
      <?php $isCurrent = ($currentModule === $link['module'] && $currentAction === $link['action']); ?>
      <a href="<?php echo url_for(['module' => $link['module'], 'action' => $link['action']]) ?>"
         class="list-group-item list-group-item-action d-flex align-items-center<?php echo $isCurrent ? ' active' : '' ?>"
         <?php echo $isCurrent ? 'aria-current="page"' : '' ?>>
        <i class="fas <?php echo $link['icon'] ?> me-2" style="width:18px;text-align:center;" aria-hidden="true"></i><?php echo $link['label'] ?>
      </a>
    <?php endforeach; ?>
  <?php endforeach; ?>
</nav>
