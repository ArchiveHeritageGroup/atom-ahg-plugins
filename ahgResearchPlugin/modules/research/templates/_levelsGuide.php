<?php
/**
 * Research mode guide (cloned from Heratio levels_guide.blade.php).
 *
 * Inline, wizard-style overview of the three research modes shown side by side
 * as step-cards, with the researcher's current mode highlighted. Anchored at
 * id="research-modes" so the sidebar "?" link can jump straight to it.
 * Self-looks-up the current mode so callers need not pass it.
 */
$expLevel = null;
if ($sf_user->isAuthenticated()) {
    try {
        $expLevel = \Illuminate\Database\Capsule\Manager::table('research_researcher')
            ->where('user_id', $sf_user->getAttribute('user_id'))
            ->value('experience_level');
    } catch (\Throwable $e) {
        $expLevel = null;
    }
}
$expLevel = $expLevel ?: 'intermediate';

$modes = [
    'beginning' => [
        'n' => 1,
        'label' => __('Beginning'),
        'icon' => 'fa-seedling',
        'who' => __('New or occasional researchers who want to create a simple project and collect a few key items quickly.'),
        'steps' => [
            __('Create a Project (title + brief abstract)'),
            __('Import 3-5 references into Bibliography'),
            __('Upload 1-2 sources and save to an Evidence Set'),
            __('Make notes in a Notebook entry'),
            __('Export a bibliography for your draft'),
        ],
    ],
    'intermediate' => [
        'n' => 2,
        'label' => __('Intermediate'),
        'icon' => 'fa-diagram-project',
        'who' => __('Researchers running ongoing projects that need structured evidence capture, drafting and project management.'),
        'steps' => [
            __('Set up Project metadata and a Data Management Plan'),
            __('Ingest and tag sources; capture bibliographic metadata'),
            __('Record claims with supporting evidence and link sources'),
            __('Draft sections and keep a Research Journal'),
            __('Run source assessments as needed'),
        ],
    ],
    'advanced' => [
        'n' => 3,
        'label' => __('Advanced'),
        'icon' => 'fa-award',
        'who' => __('Power users preparing publication-ready, reproducible outputs with cross-fonds analysis and impact tracking.'),
        'steps' => [
            __('Finalize manuscript sections and review any AI-assisted drafts'),
            __('Resolve flagged conflicts via the Validation Queue'),
            __('Run a Cross-fonds Query and review Analytics'),
            __('Apply ODRL rights policies to outputs'),
            __('Publish via the Journal Builder and track impact'),
        ],
    ],
];
?>
<div id="research-modes" class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-route me-2"></i><?php echo __('Research mode guide'); ?></span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4"><?php echo __('Pick the mode that fits you from the sidebar. Here is what each one is for and a short workflow - a map, not steps to click through.'); ?></p>

        <div class="row g-3 align-items-stretch">
            <?php foreach ($modes as $key => $m): ?>
            <?php $isCurrent = ($expLevel === $key); ?>
            <div class="col-md-4">
                <div class="card h-100 <?php echo $isCurrent ? 'border-primary shadow-sm' : 'border-light'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge rounded-pill <?php echo $isCurrent ? 'bg-primary' : 'bg-secondary'; ?> me-2"><?php echo $m['n']; ?></span>
                            <h5 class="mb-0"><i class="fas <?php echo $m['icon']; ?> me-1"></i><?php echo $m['label']; ?></h5>
                            <?php if ($isCurrent): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-auto"><?php echo __('Your mode'); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="small text-muted"><?php echo $m['who']; ?></p>
                        <ol class="small ps-3 mb-0">
                            <?php foreach ($m['steps'] as $step): ?>
                            <li class="mb-1"><?php echo $step; ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-4">
        <h6 class="small text-uppercase text-muted"><?php echo __('Quick tips'); ?></h6>
        <ul class="small mb-0">
            <li><?php echo __('Switch modes any time from the "Research mode" selector in the sidebar.'); ?></li>
            <li><?php echo __('Beginning keeps the essentials; Intermediate adds the working tools; Advanced reveals everything.'); ?></li>
            <li><?php echo __('Changing mode only changes what is shown - nothing you have created is removed.'); ?></li>
        </ul>
    </div>
</div>
