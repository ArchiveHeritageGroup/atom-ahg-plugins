<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'dashboard']); ?>">Gallery</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'gallery', 'action' => 'artists']); ?>">Artists</a></li>
        <li class="breadcrumb-item active"><?php echo $artist->display_name; ?></li>
    </ol>
</nav>
<?php if ($sf_user->hasFlash('success')): ?><div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div><?php endif; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2 mb-1"><?php echo $artist->display_name; ?> <?php if ($artist->represented): ?><span class="badge bg-success">Represented</span><?php endif; ?></h1>
        <p class="text-muted mb-0"><?php echo $artist->nationality ?: ''; ?><?php if ($artist->birth_date): ?> (<?php echo date('Y', strtotime($artist->birth_date)); ?><?php echo $artist->death_date ? ' - ' . date('Y', strtotime($artist->death_date)) : ''; ?>)<?php endif; ?></p>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i>Biography</h5></div>
            <div class="card-body">
                <?php if ($artist->biography): ?><p><?php echo nl2br(htmlspecialchars($artist->biography)); ?></p><?php else: ?><p class="text-muted">No biography</p><?php endif; ?>
                <?php if ($artist->artist_statement): ?><h6>Artist Statement</h6><p><?php echo nl2br(htmlspecialchars($artist->artist_statement)); ?></p><?php endif; ?>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="80">Email:</th><td><?php echo $artist->email ? '<a href="mailto:'.$artist->email.'">'.$artist->email.'</a>' : '-'; ?></td></tr>
                    <tr><th>Phone:</th><td><?php echo $artist->phone ?: '-'; ?></td></tr>
                    <tr><th>Website:</th><td><?php echo $artist->website ? '<a href="'.$artist->website.'" target="_blank">'.$artist->website.'</a>' : '-'; ?></td></tr>
                </table>
            </div>
        </div>
        <?php if ($artist->represented): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-handshake me-2"></i>Representation</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th>Commission:</th><td><?php echo $artist->commission_rate ? $artist->commission_rate . '%' : '-'; ?></td></tr>
                    <tr><th>Terms:</th><td><?php echo $artist->representation_terms ?: '-'; ?></td></tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#works">Works (<?php echo isset($artist->works) ? count($artist->works) : 0; ?>)</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#exhibitions">Exhibition History (<?php echo count($artist->exhibitions); ?>)</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bibliography">Bibliography (<?php echo count($artist->bibliography); ?>)</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="works">
                <?php if (!empty($artist->works)): ?>
                    <div class="list-group">
                        <?php foreach ($artist->works as $w): ?>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $w->slug]); ?>" class="list-group-item list-group-item-action"><?php echo $w->title ?: 'Untitled'; ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">No works linked. Link works by setting this artist as creator.</div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="exhibitions">
                <div class="d-flex justify-content-end mb-2"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addExhModal"><i class="fas fa-plus"></i> Add</button></div>
                <?php if (!empty($artist->exhibitions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light"><tr><th>Year</th><th>Title</th><th>Venue</th><th>Type</th></tr></thead>
                            <tbody>
                                <?php foreach ($artist->exhibitions as $e): ?>
                                    <tr>
                                        <td><?php echo $e->start_date ? date('Y', strtotime($e->start_date)) : '-'; ?></td>
                                        <td><strong><?php echo $e->title; ?></strong></td>
                                        <td><?php echo $e->venue; ?><?php if ($e->city): ?>, <?php echo $e->city; ?><?php endif; ?></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst($e->exhibition_type); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">No exhibition history</div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="bibliography">
                <div class="d-flex justify-content-end mb-2"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBiblioModal"><i class="fas fa-plus"></i> Add</button></div>
                <?php if (!empty($artist->bibliography)): ?>
                    <div class="list-group">
                        <?php foreach ($artist->bibliography as $b): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo $b->title; ?></strong>
                                    <span class="badge bg-secondary"><?php echo ucfirst($b->entry_type); ?></span>
                                </div>
                                <small class="text-muted">
                                    <?php echo $b->author; ?><?php if ($b->publication): ?>, <em><?php echo $b->publication; ?></em><?php endif; ?><?php if ($b->publication_date): ?> (<?php echo date('Y', strtotime($b->publication_date)); ?>)<?php endif; ?>
                                </small>
                                <?php if ($b->url): ?><br><a href="<?php echo $b->url; ?>" target="_blank" class="small">View online</a><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">No bibliography entries</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Exhibition Modal -->
<div class="modal fade" id="addExhModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post"><input type="hidden" name="do" value="add_exhibition">
        <div class="modal-header"><h5 class="modal-title">Add Exhibition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="exh_title" class="form-control" required></div>
            <div class="row mb-3">
                <div class="col-6"><label class="form-label">Type</label><select name="exh_type" class="form-select"><option value="solo">Solo</option><option value="group" selected>Group</option><option value="duo">Duo</option><option value="retrospective">Retrospective</option></select></div>
                <div class="col-6"><label class="form-label">Venue</label><input type="text" name="exh_venue" class="form-control"></div>
            </div>
            <div class="row mb-3">
                <div class="col-6"><label class="form-label">City</label><input type="text" name="exh_city" class="form-control"></div>
                <div class="col-6"><label class="form-label">Country</label><input type="text" name="exh_country" class="form-control"></div>
            </div>
            <div class="row mb-3">
                <div class="col-6"><label class="form-label">Start Date</label><input type="date" name="exh_start" class="form-control"></div>
                <div class="col-6"><label class="form-label">End Date</label><input type="date" name="exh_end" class="form-control"></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div>
    </form>
</div></div></div>

<!-- Add Bibliography Modal -->
<div class="modal fade" id="addBiblioModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post"><input type="hidden" name="do" value="add_biblio">
        <div class="modal-header"><h5 class="modal-title">Add Bibliography Entry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-6"><label class="form-label">Type</label><select name="entry_type" class="form-select"><option value="article">Article</option><option value="book">Book</option><option value="catalog">Catalog</option><option value="review">Review</option><option value="interview">Interview</option><option value="website">Website</option></select></div>
                <div class="col-6"><label class="form-label">Date</label><input type="date" name="publication_date" class="form-control"></div>
            </div>
            <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="biblio_title" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Author</label><input type="text" name="author" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Publication</label><input type="text" name="publication" class="form-control"></div>
            <div class="mb-3"><label class="form-label">URL</label><input type="url" name="biblio_url" class="form-control"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add</button></div>
    </form>
</div></div></div>
