<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo __('External Research Access'); ?> - <?php echo sfConfig::get('app_ui_label', 'AtoM'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    body { background-color: #f8f9fa; }
    .external-header { background: linear-gradient(135deg, #0d6efd, #6610f2); color: #fff; }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="external-header py-4 mb-4">
    <div class="container">
      <div class="d-flex align-items-center">
        <i class="fas fa-archive fa-2x me-3"></i>
        <div>
          <h4 class="mb-0"><?php echo sfConfig::get('app_ui_label', 'AtoM'); ?></h4>
          <small class="opacity-75"><?php echo __('External Research Access'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="container mb-5">
    <?php
    // Determine access state
    $isAuthenticated = isset($externalUser) && $externalUser;
    $isExpired = isset($share) && ($share->status ?? '') === 'expired';
    $isRevoked = isset($share) && ($share->status ?? '') === 'revoked';
    $isInvalid = !isset($share) || !$share;
    ?>

    <?php if ($sf_user->hasFlash('success')): ?>
      <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if ($isInvalid): ?>
      <!-- Invalid Token -->
      <div class="text-center py-5">
        <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
        <h3><?php echo __('Invalid Access Link'); ?></h3>
        <p class="text-muted"><?php echo __('This share link is invalid or does not exist. Please check the link and try again.'); ?></p>
      </div>

    <?php elseif ($isExpired): ?>
      <!-- Expired -->
      <div class="text-center py-5">
        <i class="fas fa-clock fa-4x text-warning mb-3"></i>
        <h3><?php echo __('Access Expired'); ?></h3>
        <p class="text-muted"><?php echo __('This share link has expired. Please contact the project owner for a new link.'); ?></p>
      </div>

    <?php elseif ($isRevoked): ?>
      <!-- Revoked -->
      <div class="text-center py-5">
        <i class="fas fa-ban fa-4x text-danger mb-3"></i>
        <h3><?php echo __('Access Revoked'); ?></h3>
        <p class="text-muted"><?php echo __('This share link has been revoked by the project owner.'); ?></p>
      </div>

    <?php elseif (!$isAuthenticated): ?>
      <!-- Registration Form -->
      <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="card shadow-sm">
            <div class="card-header text-center">
              <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i><?php echo __('Register for Access'); ?></h5>
            </div>
            <div class="card-body">
              <?php if (!empty($share->project_title)): ?>
              <div class="alert alert-info">
                <strong><?php echo __('Project:'); ?></strong> <?php echo htmlspecialchars($share->project_title); ?>
                <?php if (!empty($share->sharer_first_name)): ?>
                  <br><small class="text-muted"><?php echo __('Shared by'); ?> <?php echo htmlspecialchars($share->sharer_first_name . ' ' . $share->sharer_last_name); ?></small>
                <?php endif; ?>
                <?php if (!empty($share->message)): ?>
                  <br><small><?php echo htmlspecialchars($share->message); ?></small>
                <?php endif; ?>
              </div>
              <?php endif; ?>

              <form method="post">
                <input type="hidden" name="form_action" value="register_external">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($sf_request->getParameter('token', '')); ?>">

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Full Name'); ?> *</label>
                  <input type="text" name="name" class="form-control" required placeholder="<?php echo __('Your full name'); ?>">
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Email Address'); ?> *</label>
                  <input type="email" name="email" class="form-control" required placeholder="<?php echo __('your@email.com'); ?>">
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('ORCID iD'); ?> <small class="text-muted">(<?php echo __('optional'); ?>)</small></label>
                  <input type="text" name="orcid_id" class="form-control" placeholder="0000-0000-0000-0000">
                  <div class="form-text"><?php echo __('Your ORCID iD helps verify your identity as a researcher.'); ?></div>
                </div>

                <div class="mb-3">
                  <label class="form-label"><?php echo __('Institution'); ?> <small class="text-muted">(<?php echo __('optional'); ?>)</small></label>
                  <input type="text" name="institution" class="form-control" placeholder="<?php echo __('Your institution or organization'); ?>">
                </div>

                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-1"></i><?php echo __('Request Access'); ?></button>
              </form>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- Authenticated External User View -->
      <div class="row">
        <div class="col-md-8">
          <!-- Project Details -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i><?php echo htmlspecialchars($share->project_title ?? __('Research Project')); ?></h5>
              <span class="badge bg-<?php echo match($share->share_type ?? 'view') {
                'view' => 'info', 'contribute' => 'warning', 'full' => 'success', default => 'secondary'
              }; ?>"><?php echo ucfirst($share->share_type ?? 'view'); ?> <?php echo __('Access'); ?></span>
            </div>
            <div class="card-body">
              <?php if (!empty($share->project_description)): ?>
                <p><?php echo nl2br(htmlspecialchars($share->project_description)); ?></p>
              <?php endif; ?>
              <small class="text-muted">
                <?php echo __('Shared by'); ?> <?php echo htmlspecialchars(($share->sharer_first_name ?? '') . ' ' . ($share->sharer_last_name ?? '')); ?>
                <?php if (!empty($share->institution_name)): ?>
                  | <?php echo htmlspecialchars($share->institution_name); ?>
                <?php endif; ?>
              </small>
            </div>
          </div>

          <!-- Resources List -->
          <?php if (!empty($resources)): ?>
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-archive me-2"></i><?php echo __('Linked Resources'); ?></h5></div>
            <div class="list-group list-group-flush">
              <?php foreach ($resources as $resource): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="badge bg-secondary me-2"><?php echo ucfirst($resource->resource_type ?? 'item'); ?></span>
                    <strong><?php echo htmlspecialchars($resource->title ?? __('Untitled')); ?></strong>
                  </div>
                  <small class="text-muted"><?php echo date('M j, Y', strtotime($resource->added_at ?? $resource->created_at ?? 'now')); ?></small>
                </div>
                <?php if (!empty($resource->notes)): ?>
                  <small class="text-muted"><?php echo htmlspecialchars($resource->notes); ?></small>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Notes / Contribution Form -->
          <?php if (($share->share_type ?? 'view') === 'contribute' || ($share->share_type ?? 'view') === 'full'): ?>
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-pen me-2"></i><?php echo __('Add Note'); ?></h5></div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="form_action" value="add_note">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($sf_request->getParameter('token', '')); ?>">
                <input type="hidden" name="access_token" value="<?php echo htmlspecialchars($sf_request->getParameter('access_token', '')); ?>">
                <div class="mb-3">
                  <label class="form-label"><?php echo __('Title'); ?></label>
                  <input type="text" name="title" class="form-control" placeholder="<?php echo __('Note title...'); ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label"><?php echo __('Content'); ?> *</label>
                  <textarea name="content" class="form-control" rows="4" required placeholder="<?php echo __('Your note or contribution...'); ?>"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i><?php echo __('Submit Note'); ?></button>
              </form>
            </div>
          </div>
          <?php endif; ?>

          <!-- Existing Notes (read-only) -->
          <?php if (!empty($notes)): ?>
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Notes'); ?></h5></div>
            <div class="list-group list-group-flush">
              <?php foreach ($notes as $note): ?>
              <div class="list-group-item">
                <?php if (!empty($note->title)): ?>
                  <strong><?php echo htmlspecialchars($note->title); ?></strong><br>
                <?php endif; ?>
                <p class="mb-1"><?php echo nl2br(htmlspecialchars($note->content ?? '')); ?></p>
                <small class="text-muted">
                  <?php echo htmlspecialchars($note->author_name ?? __('Unknown')); ?> - <?php echo date('M j, Y H:i', strtotime($note->created_at)); ?>
                </small>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="col-md-4">
          <!-- User Info -->
          <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Your Access'); ?></h6></div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('Name'); ?></span>
                <span><?php echo htmlspecialchars($externalUser->name ?? ''); ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('Email'); ?></span>
                <span><?php echo htmlspecialchars($externalUser->email ?? ''); ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('Role'); ?></span>
                <span class="badge bg-info"><?php echo ucfirst($externalUser->role ?? 'viewer'); ?></span>
              </li>
              <?php if (!empty($externalUser->orcid_id)): ?>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('ORCID'); ?></span>
                <span><?php echo htmlspecialchars($externalUser->orcid_id); ?></span>
              </li>
              <?php endif; ?>
            </ul>
          </div>

          <!-- Share Info -->
          <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Share Info'); ?></h6></div>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('Access Level'); ?></span>
                <span><?php echo ucfirst($share->share_type ?? 'view'); ?></span>
              </li>
              <?php if (!empty($share->expires_at)): ?>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('Expires'); ?></span>
                <span><?php echo date('M j, Y', strtotime($share->expires_at)); ?></span>
              </li>
              <?php endif; ?>
              <?php if (!empty($share->institution_name)): ?>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted"><?php echo __('Institution'); ?></span>
                <span><?php echo htmlspecialchars($share->institution_name); ?></span>
              </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
