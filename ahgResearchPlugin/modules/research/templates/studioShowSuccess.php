<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$project   = sfOutputEscaper::unescape($project);
$artefact  = sfOutputEscaper::unescape($artefact);
$citations = sfOutputEscaper::unescape($citations ?? []);
$body      = (string) ($artefact->body ?? '');
$nonce     = sfConfig::get('csp_nonce', '');
$nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'studio', 'projectId' => $project->id]); ?>">Studio</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($artefact->title ?: 'Artefact'); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1"><?php echo htmlspecialchars($artefact->title ?: 'Untitled artefact'); ?></h1>
        <div class="small text-muted">
            <span class="badge bg-primary me-1"><?php echo htmlspecialchars($artefact->output_type); ?></span>
            <?php echo htmlspecialchars($artefact->model ?? '-'); ?> &middot;
            <?php echo (int) $artefact->tokens_used; ?> tokens &middot;
            <?php echo number_format(($artefact->generation_time_ms ?? 0) / 1000, 1); ?>s &middot;
            <?php echo htmlspecialchars(date('j M Y H:i', strtotime($artefact->created_at))); ?>
        </div>
    </div>
    <div>
        <?php if ($artefact->output_type === 'spreadsheet' && $artefact->xlsx_path): ?>
            <a class="btn btn-success me-1" href="<?php echo url_for(['module' => 'research', 'action' => 'studioDownload', 'projectId' => $project->id, 'artefactId' => $artefact->id]); ?>">
                <i class="fas fa-file-excel me-1"></i> Download .xlsx
            </a>
        <?php endif; ?>
        <?php if ($artefact->output_type === 'audio' && $artefact->audio_url): ?>
            <a class="btn btn-success me-1" href="<?php echo htmlspecialchars($artefact->audio_url); ?>">
                <i class="fas fa-volume-up me-1"></i> Listen
            </a>
        <?php endif; ?>
        <form method="post" action="<?php echo url_for(['module' => 'research', 'action' => 'studioDelete', 'projectId' => $project->id, 'artefactId' => $artefact->id]); ?>" class="d-inline">
            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this artefact?');">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

<?php if ($artefact->status === 'error'): ?>
    <div class="alert alert-warning">
        <strong>Generation issue:</strong> <?php echo nl2br(htmlspecialchars($artefact->error_text ?: 'Unknown error')); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <?php if ($artefact->output_type === 'diagram'): ?>
                    <div class="markdown-body" id="studio-body">
                        <pre class="bg-light p-3"><?php echo htmlspecialchars($body); ?></pre>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js" <?php echo $nonceAttr; ?>></script>
                    <script <?php echo $nonceAttr; ?>>mermaid.initialize({ startOnLoad: true });</script>
                <?php elseif ($artefact->output_type === 'spreadsheet'): ?>
                    <div class="markdown-body" id="studio-body">
                        <pre class="bg-light p-3" style="white-space:pre-wrap"><?php echo htmlspecialchars($body); ?></pre>
                    </div>
                <?php else: ?>
                    <div class="markdown-body" id="studio-body" style="white-space:pre-wrap; line-height:1.6"><?php echo htmlspecialchars($body); ?></div>
                <?php endif; ?>

                <?php if (!empty($artefact->audio_transcript) && $artefact->output_type === 'audio'): ?>
                    <hr>
                    <h6>Audio transcript</h6>
                    <pre class="bg-light p-3" style="white-space:pre-wrap"><?php echo htmlspecialchars($artefact->audio_transcript); ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-quote-right me-2"></i>Sources cited</h5></div>
            <div class="card-body">
                <?php if (empty($citations)): ?>
                    <p class="text-muted mb-0 small">No [N] citation markers found in the artefact body.</p>
                <?php else: ?>
                    <ul id="studio-citations" class="list-unstyled mb-0">
                        <?php foreach ($citations as $c): ?>
                            <li data-citation-n="<?php echo (int) $c['n']; ?>" class="mb-3 pb-2 border-bottom">
                                <strong>[<?php echo (int) $c['n']; ?>] <?php echo htmlspecialchars($c['title']); ?></strong>
                                <?php if (!empty($c['reference'])): ?>
                                    <div class="small text-muted">Ref: <?php echo htmlspecialchars($c['reference']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($c['snippet'])): ?>
                                    <div class="small text-muted citation-snippet mt-1"><?php echo htmlspecialchars($c['snippet']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($c['url'])): ?>
                                    <a href="<?php echo htmlspecialchars($c['url']); ?>" class="small">Open source &raquo;</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style <?php echo $nonceAttr; ?>>
    .citation-marker { color: #0d6efd; font-weight: 600; text-decoration: none; padding: 0 2px; border-radius: 2px; }
    .citation-marker:hover { background: #e7f1ff; }
    .citation-flash { background: #fff3cd; transition: background 0.4s ease; }
</style>

<?php use_javascript('/plugins/ahgResearchPlugin/web/js/citation-popover.js') ?>
