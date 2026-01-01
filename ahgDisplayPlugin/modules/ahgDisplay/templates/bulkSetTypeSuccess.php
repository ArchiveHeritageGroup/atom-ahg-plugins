<?php use_helper('Display'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'index']); ?>">Display</a></li>
        <li class="breadcrumb-item active">Bulk Set Object Types</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-tags text-primary me-2"></i>Bulk Set Object Types</h1>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Select Collection and Type</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Top-Level Collection *</label>
                        <select name="parent_id" class="form-select" required>
                            <option value="">Select collection...</option>
                            <?php foreach ($collections as $c): ?>
                            <option value="<?php echo $c->id; ?>">
                                <?php echo $c->identifier ? $c->identifier . ' - ' : ''; ?><?php echo $c->title ?: 'Untitled'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">This will set the type for this collection and ALL descendants</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Object Type *</label>
                        <div class="row">
                            <?php foreach ($collectionTypes as $ct): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="radio" name="type" value="<?php echo $ct->code; ?>" 
                                           class="form-check-input" id="type_<?php echo $ct->code; ?>" required>
                                    <label class="form-check-label" for="type_<?php echo $ct->code; ?>">
                                        <i class="fas <?php echo $ct->icon; ?> me-1"></i>
                                        <?php echo $ct->name; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'ahgDisplay', 'action' => 'index']); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('This will update ALL objects in this collection. Continue?')">
                            <i class="fas fa-save me-1"></i> Apply to Collection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Object Types</h5>
            </div>
            <div class="card-body">
                <p>Object types determine how records are displayed:</p>
                <ul class="small">
                    <li><strong>Archive:</strong> ISAD(G) hierarchical view</li>
                    <li><strong>Museum:</strong> Spectrum object records</li>
                    <li><strong>Gallery:</strong> Artwork/artist focus</li>
                    <li><strong>Book Collection:</strong> Bibliographic view</li>
                    <li><strong>Photo Archive:</strong> Visual grid/lightbox</li>
                    <li><strong>Audiovisual:</strong> Media player focus</li>
                </ul>
                <p class="text-muted small mb-0">
                    Types are inherited by children. Setting a type on a fonds will apply to all series, files, and items within.
                </p>
            </div>
        </div>
    </div>
</div>
