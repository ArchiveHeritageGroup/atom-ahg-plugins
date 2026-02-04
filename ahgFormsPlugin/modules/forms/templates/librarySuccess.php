<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-book me-2"></i>Template Library</h1>
            <p class="text-muted">Pre-built form templates ready to install</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-info"><?php echo $sf_user->getFlash('notice') ?></div>
    <?php endif ?>

    <div class="row">
        <?php foreach ($library as $item): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($item['name']) ?>
                            <?php if ($item['installed']): ?>
                                <span class="badge bg-success">Installed</span>
                            <?php endif ?>
                        </h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($item['description']) ?></p>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-list me-1"></i><?php echo $item['fields'] ?> fields
                            </small>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <?php if ($item['installed']): ?>
                            <span class="text-success"><i class="fas fa-check me-1"></i>Already installed</span>
                        <?php else: ?>
                            <form method="post" action="<?php echo url_for(['module' => 'forms', 'action' => 'libraryInstall', 'id' => $item['id']]) ?>" style="display:inline;">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download me-1"></i> Install
                                </button>
                            </form>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h5><i class="fas fa-info-circle me-2"></i>About Template Library</h5>
            <p class="mb-0">
                These pre-built templates follow international standards and best practices.
                Install them to quickly set up common form configurations, then customize as needed.
            </p>
        </div>
    </div>
</div>
