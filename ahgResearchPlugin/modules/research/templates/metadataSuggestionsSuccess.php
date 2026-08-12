<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'metadataSuggestions', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$suggestions = sfOutputEscaper::unescape($suggestions ?? []);
$counts = sfOutputEscaper::unescape($counts ?? ['open' => 0, 'accepted' => 0, 'rejected' => 0]);
$status = $filterStatus ?? 'open';
$suggestUrl = url_for(['module' => 'research', 'action' => 'metadataSuggestions']);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active"><?php echo __('Metadata Suggestions'); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h2"><i class="fas fa-lightbulb me-2"></i><?php echo __('Metadata Suggestions'); ?></h1>
</div>

<?php if ($msg = $sf_user->getFlash('success')): ?>
    <div class="alert alert-success py-2"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>
<?php if ($msg = $sf_user->getFlash('error')): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<p class="text-muted small">
    <?php echo __('Corrections and additions proposed by researchers while working offline. Accepting or rejecting records your decision — it does not change the catalogue automatically; apply an accepted change to the record yourself.'); ?>
</p>

<ul class="nav nav-pills mb-3">
    <?php foreach (['open' => 'Open', 'accepted' => 'Accepted', 'rejected' => 'Rejected'] as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $status === $key ? 'active' : ''; ?>"
               href="<?php echo $suggestUrl . '?status=' . $key; ?>">
                <?php echo __($label); ?> <span class="badge bg-light text-dark"><?php echo (int) ($counts[$key] ?? 0); ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (empty($suggestions)): ?>
    <div class="text-center text-muted py-5">
        <i class="fas fa-inbox fa-2x mb-2"></i>
        <p><?php echo __('No'); ?> <?php echo htmlspecialchars($status); ?> <?php echo __('suggestions.'); ?></p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th><?php echo __('Record'); ?></th>
                    <th><?php echo __('Field'); ?></th>
                    <th><?php echo __('Suggestion'); ?></th>
                    <th><?php echo __('Researcher'); ?></th>
                    <th><?php echo __('When'); ?></th>
                    <?php if ($status === 'open'): ?><th class="text-end"><?php echo __('Action'); ?></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suggestions as $s): ?>
                    <tr>
                        <td>
                            <?php if (!empty($s->record_slug)): ?>
                                <a href="/index.php/<?php echo htmlspecialchars($s->record_slug); ?>" target="_blank"><?php echo htmlspecialchars($s->record_title ?: ('#' . $s->object_id)); ?></a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($s->record_title ?: ('#' . $s->object_id)); ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($s->field); ?></span></td>
                        <td data-ahg-style="max-width:340px;white-space:pre-wrap"><?php echo htmlspecialchars($s->suggestion); ?></td>
                        <td class="small"><?php echo htmlspecialchars(trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: '—'); ?></td>
                        <td class="small text-muted"><?php echo $s->created_at ? date('d M Y', strtotime($s->created_at)) : ''; ?></td>
                        <?php if ($status === 'open'): ?>
                            <td class="text-end">
                                <form method="post" action="<?php echo $suggestUrl; ?>" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo (int) $s->id; ?>">
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit" name="do" value="accept" class="btn btn-sm btn-success" title="<?php echo __('Accept'); ?>"><i class="fas fa-check"></i></button>
                                    <button type="submit" name="do" value="reject" class="btn btn-sm btn-outline-danger" title="<?php echo __('Reject'); ?>"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
