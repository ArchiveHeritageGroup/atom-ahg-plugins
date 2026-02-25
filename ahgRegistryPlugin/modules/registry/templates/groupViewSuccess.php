<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $group['group']; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Group'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'groupBrowse'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8')],
]]); ?>

<?php if ($sf_user->hasFlash('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <?php echo htmlspecialchars($sf_user->getFlash('success'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Banner -->
<?php if (!empty($detail->banner_path)): ?>
<div class="mb-4 rounded-3 overflow-hidden" style="max-height: 200px;">
  <img src="<?php echo htmlspecialchars($detail->banner_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-100" style="object-fit: cover; max-height: 200px;">
</div>
<?php endif; ?>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <div class="d-flex align-items-start mb-4">
      <?php if (!empty($detail->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($detail->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 64px; height: 64px; object-fit: contain;">
      <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
        <i class="fas fa-users fa-2x text-muted"></i>
      </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <h1 class="h3 mb-1">
          <?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?>
          <?php if (!empty($detail->is_verified)): ?>
            <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
          <?php endif; ?>
        </h1>
        <div>
          <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->group_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($detail->is_virtual)): ?>
            <span class="badge bg-success"><?php echo __('Virtual'); ?></span>
          <?php endif; ?>
          <?php if (!empty($detail->city) || !empty($detail->country)): ?>
            <small class="text-muted ms-2">
              <i class="fas fa-map-marker-alt me-1"></i>
              <?php echo htmlspecialchars(implode(', ', array_filter([$detail->city ?? '', $detail->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
            </small>
          <?php endif; ?>
        </div>
      </div>
      <div class="ms-2">
        <?php if ($sf_user->isAuthenticated()): ?>
          <?php if ($isMember): ?>
            <?php
              $myEmail = strtolower(trim($sf_user->getAttribute('user_email', '')));
              $myMembership = $myEmail ? \Illuminate\Database\Capsule\Manager::table('registry_user_group_member')
                  ->where('group_id', $detail->id)->where('email', $myEmail)->where('is_active', 1)->first() : null;
              $notifOn = $myMembership && !empty($myMembership->email_notifications);
            ?>
            <form method="post" action="/registry/groups/<?php echo htmlspecialchars($detail->slug, ENT_QUOTES, 'UTF-8'); ?>/notifications" class="d-inline">
              <button type="submit" class="btn btn-sm btn-outline-<?php echo $notifOn ? 'success' : 'secondary'; ?>" title="<?php echo $notifOn ? __('Notifications ON — click to disable') : __('Notifications OFF — click to enable'); ?>">
                <i class="fas fa-<?php echo $notifOn ? 'bell' : 'bell-slash'; ?>"></i>
              </button>
            </form>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupLeave', 'slug' => $detail->slug]); ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('<?php echo __('Are you sure you want to leave this group?'); ?>');">
              <i class="fas fa-sign-out-alt me-1"></i> <?php echo __('Leave'); ?>
            </a>
          <?php else: ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupJoin', 'slug' => $detail->slug]); ?>" class="btn btn-primary btn-sm">
              <i class="fas fa-sign-in-alt me-1"></i> <?php echo __('Join'); ?>
            </a>
          <?php endif; ?>
        <?php else: ?>
          <a href="/registry/login?redirect=<?php echo urlencode('/registry/groups/' . $detail->slug . '/join'); ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-sign-in-alt me-1"></i> <?php echo __('Join'); ?>
          </a>
        <?php endif; ?>
      <?php if ($sf_user->isAuthenticated() && $sf_user->hasCredential('administrator')): ?>
        <div class="ms-2 border-start ps-2">
          <a href="/registry/admin/groups/<?php echo (int) $detail->id; ?>/edit" class="btn btn-sm btn-outline-warning" title="<?php echo __('Admin Edit'); ?>">
            <i class="fas fa-edit"></i>
          </a>
          <a href="/registry/admin/groups/<?php echo (int) $detail->id; ?>/members" class="btn btn-sm btn-outline-warning" title="<?php echo __('Manage Members'); ?>">
            <i class="fas fa-users-cog"></i>
          </a>
        </div>
      <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($detail->description)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('About'); ?></h2>
      <div><?php echo nl2br(htmlspecialchars($detail->description, ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>

    <!-- Focus areas -->
    <?php if (!empty($detail->focus_areas)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('Focus Areas'); ?></h2>
      <?php
        $rawFocusAreas = sfOutputEscaper::unescape($detail->focus_areas);
        $areas = is_string($rawFocusAreas) ? json_decode($rawFocusAreas, true) : (array) $rawFocusAreas;
        if (is_array($areas)):
          foreach ($areas as $area): ?>
            <span class="badge bg-info text-dark me-1 mb-1"><?php echo htmlspecialchars($area, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>

    <!-- Pinned discussions -->
    <?php if (!empty($group['pinned_discussions'])): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('Pinned Discussions'); ?></h2>
      <div class="list-group">
        <?php foreach ($group['pinned_discussions'] as $disc): ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $detail->slug, 'id' => (int) $disc->id]); ?>" class="list-group-item list-group-item-action">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <i class="fas fa-thumbtack text-warning me-1"></i>
              <strong><?php echo htmlspecialchars($disc->title, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <span class="badge bg-secondary"><?php echo (int) $disc->reply_count; ?> <?php echo __('replies'); ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent discussions -->
    <?php if (!empty($group['recent_discussions'])): ?>
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0"><?php echo __('Recent Discussions'); ?></h2>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $detail->slug]); ?>" class="btn btn-sm btn-outline-primary"><?php echo __('View All'); ?></a>
      </div>
      <div class="list-group">
        <?php $count = 0; foreach ($group['recent_discussions'] as $disc): if ($count++ >= 5) break; ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionView', 'slug' => $detail->slug, 'id' => (int) $disc->id]); ?>" class="list-group-item list-group-item-action">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="mb-1"><?php echo htmlspecialchars($disc->title, ENT_QUOTES, 'UTF-8'); ?></h6>
              <small class="text-muted">
                <?php echo htmlspecialchars($disc->author_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                &middot; <?php echo date('M j, Y', strtotime($disc->created_at)); ?>
              </small>
            </div>
            <div class="text-end text-nowrap ms-2">
              <span class="badge bg-secondary"><?php echo (int) $disc->reply_count; ?></span>
              <br><small class="text-muted"><i class="fas fa-eye"></i> <?php echo (int) ($disc->view_count ?? 0); ?></small>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Meeting info -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Meeting Info'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->meeting_frequency)): ?>
        <li class="list-group-item">
          <i class="fas fa-clock me-2 text-muted"></i>
          <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->meeting_frequency)), ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->meeting_format)): ?>
        <li class="list-group-item">
          <i class="fas fa-video me-2 text-muted"></i>
          <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $detail->meeting_format)), ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->meeting_platform)): ?>
        <li class="list-group-item">
          <i class="fas fa-desktop me-2 text-muted"></i>
          <?php echo htmlspecialchars($detail->meeting_platform, ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->next_meeting_at)): ?>
        <li class="list-group-item list-group-item-warning">
          <i class="fas fa-calendar-alt me-2"></i>
          <strong><?php echo __('Next Meeting'); ?></strong><br>
          <?php echo date('M j, Y g:i A', strtotime($detail->next_meeting_at)); ?>
          <?php if (!empty($detail->next_meeting_details)): ?>
            <br><small><?php echo htmlspecialchars($detail->next_meeting_details, ENT_QUOTES, 'UTF-8'); ?></small>
          <?php endif; ?>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Communication links -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Communication'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->mailing_list_url)): ?>
        <li class="list-group-item">
          <a href="<?php echo htmlspecialchars($detail->mailing_list_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <i class="fas fa-envelope me-2"></i> <?php echo __('Mailing List'); ?>
          </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->slack_url)): ?>
        <li class="list-group-item">
          <a href="<?php echo htmlspecialchars($detail->slack_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <i class="fab fa-slack me-2"></i> <?php echo __('Slack'); ?>
          </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->discord_url)): ?>
        <li class="list-group-item">
          <a href="<?php echo htmlspecialchars($detail->discord_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <i class="fab fa-discord me-2"></i> <?php echo __('Discord'); ?>
          </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->forum_url)): ?>
        <li class="list-group-item">
          <a href="<?php echo htmlspecialchars($detail->forum_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <i class="fas fa-comments me-2"></i> <?php echo __('Forum'); ?>
          </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->website)): ?>
        <li class="list-group-item">
          <a href="<?php echo htmlspecialchars($detail->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <i class="fas fa-globe me-2"></i> <?php echo __('Website'); ?>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Members -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('Members'); ?>
        <span class="badge bg-primary"><?php echo (int) ($detail->member_count ?? 0); ?></span>
      </div>
      <div class="card-body text-center">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupMembers', 'slug' => $detail->slug]); ?>" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-users me-1"></i> <?php echo __('View Members'); ?>
        </a>
      </div>
    </div>

    <!-- Quick links -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Quick Links'); ?></div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionList', 'slug' => $detail->slug]); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-comments me-2"></i> <?php echo __('Discussions'); ?>
        </a>
        <?php if ($isMember): ?>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'discussionNew', 'slug' => $detail->slug]); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-plus me-2"></i> <?php echo __('New Discussion'); ?>
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>
