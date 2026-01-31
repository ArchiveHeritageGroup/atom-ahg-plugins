<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/admin') ?>">Settings</a></li>
            <li class="breadcrumb-item active">Bot List</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-robot me-2"></i>Bot Filter List</h1>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Configured Bot Patterns</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Pattern</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bots as $bot): ?>
                                    <tr>
                                        <td><?php echo esc_entities($bot->name) ?></td>
                                        <td><code class="small"><?php echo esc_entities($bot->pattern) ?></code></td>
                                        <td><span class="badge bg-info"><?php echo str_replace('_', ' ', $bot->category) ?></span></td>
                                        <td>
                                            <?php if ($bot->is_active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?php echo $bot->id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Toggle">
                                                    <i class="fas fa-toggle-<?php echo $bot->is_active ? 'on' : 'off' ?>"></i>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this bot pattern?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $bot->id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Bot Pattern</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., MyBot">
                        </div>

                        <div class="mb-3">
                            <label for="pattern" class="form-label">Regex Pattern *</label>
                            <input type="text" class="form-control" id="pattern" name="pattern" required placeholder="e.g., MyBot|mybot">
                            <small class="form-text text-muted">Case-insensitive regex to match user agent</small>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="search_engine">Search Engine</option>
                                <option value="social">Social Media</option>
                                <option value="monitoring">Monitoring</option>
                                <option value="crawler" selected>Crawler</option>
                                <option value="spam">Spam</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i>Add Pattern
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
