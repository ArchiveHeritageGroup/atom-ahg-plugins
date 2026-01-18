<?php echo get_partial('header', ['title' => 'Orphan Works Due Diligence']); ?>

<div class="container-fluid">
  <div class="row">
    <?php include_partial('rightsAdmin/sidebar', ['active' => 'orphanWorks']); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-search me-2"></i><?php echo __('Orphan Works Due Diligence'); ?></h1>
        <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorkEdit']); ?>" class="btn btn-sm btn-primary">
          <i class="fas fa-plus me-1"></i> New Search
        </a>
      </div>

      <!-- Status Filter -->
      <div class="card mb-4">
        <div class="card-body py-2">
          <div class="btn-group" role="group">
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorks', 'status' => 'all']); ?>" 
               class="btn btn-<?php echo 'all' === $status ? 'dark' : 'outline-dark'; ?>">All</a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorks', 'status' => 'in_progress']); ?>" 
               class="btn btn-<?php echo 'in_progress' === $status ? 'warning' : 'outline-warning'; ?>">In Progress</a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorks', 'status' => 'completed']); ?>" 
               class="btn btn-<?php echo 'completed' === $status ? 'success' : 'outline-success'; ?>">Completed</a>
            <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorks', 'status' => 'rights_holder_found']); ?>" 
               class="btn btn-<?php echo 'rights_holder_found' === $status ? 'info' : 'outline-info'; ?>">Rights Holder Found</a>
          </div>
        </div>
      </div>

      <!-- Orphan Works Table -->
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Object</th>
                <th>Work Type</th>
                <th>Search Started</th>
                <th>Status</th>
                <th>Search Steps</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orphanWorks as $work): ?>
              <tr>
                <td>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $work->slug]); ?>">
                    <?php echo esc_entities($work->object_title ?: 'ID: '.$work->object_id); ?>
                  </a>
                </td>
                <td><?php echo ucfirst(str_replace('_', ' ', $work->work_type)); ?></td>
                <td><?php echo date('d M Y', strtotime($work->search_started_date)); ?></td>
                <td>
                  <span class="badge bg-<?php 
                    echo match($work->status) {
                      'in_progress' => 'warning',
                      'completed' => 'success',
                      'rights_holder_found' => 'info',
                      'abandoned' => 'secondary',
                      default => 'light'
                    };
                  ?>"><?php echo ucfirst(str_replace('_', ' ', $work->status)); ?></span>
                </td>
                <td>
                  <?php 
                  $stepCount = \Illuminate\Database\Capsule\Manager::table('rights_orphan_search_step')
                      ->where('orphan_work_id', $work->id)->count();
                  ?>
                  <span class="badge bg-secondary"><?php echo $stepCount; ?></span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorkEdit', 'id' => $work->id]); ?>" 
                       class="btn btn-outline-secondary" title="View/Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <?php if ('in_progress' === $work->status): ?>
                    <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'completeOrphanSearch', 'id' => $work->id]); ?>" 
                       class="btn btn-outline-success" title="Mark Complete"
                       onclick="return confirm('Mark this search as complete?');">
                      <i class="fas fa-check"></i>
                    </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($orphanWorks) === 0): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No orphan work searches found.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Info Panel -->
      <div class="card mt-4">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('About Orphan Works'); ?></h5>
        </div>
        <div class="card-body">
          <p>Orphan works are copyrighted works whose rights holders cannot be identified or located after a diligent search. 
          Before using an orphan work, institutions should conduct and document a thorough due diligence search.</p>
          
          <h6>Recommended Search Sources:</h6>
          <ul>
            <li>Copyright registries and databases</li>
            <li>Author/artist societies and collecting organizations</li>
            <li>Publisher records and catalogs</li>
            <li>Library and archive catalogs</li>
            <li>Internet searches</li>
            <li>Newspaper and publication archives</li>
          </ul>
          
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle me-1"></i>
            Document each search step thoroughly, including negative results. This documentation may be required to demonstrate due diligence.
          </p>
        </div>
      </div>

    </main>
  </div>
</div>
