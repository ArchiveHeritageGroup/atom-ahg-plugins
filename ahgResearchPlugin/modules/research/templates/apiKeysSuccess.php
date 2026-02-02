<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">API Keys</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-key text-primary me-2"></i>API Keys</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateKeyModal">
        <i class="fas fa-plus me-1"></i> Generate Key
    </button>
</div>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    API keys allow you to access your research data programmatically. Keep your keys secure and never share them publicly.
    <br><strong>API Base URL:</strong> <code><?php echo sfConfig::get('app_siteBaseUrl', 'https://psis.theahg.co.za'); ?>/api/research</code>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (!empty($apiKeys)): ?>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Key Prefix</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key->name); ?></td>
                            <td><code><?php echo substr($key->api_key_hash, 0, 8); ?>...</code></td>
                            <td><?php echo date('M j, Y', strtotime($key->created_at)); ?></td>
                            <td><?php echo $key->last_used_at ? date('M j, Y H:i', strtotime($key->last_used_at)) : 'Never'; ?></td>
                            <td><?php echo $key->expires_at ? date('M j, Y', strtotime($key->expires_at)) : 'Never'; ?></td>
                            <td>
                                <?php if ($key->is_active): ?>
                                    <?php if ($key->expires_at && strtotime($key->expires_at) < time()): ?>
                                        <span class="badge bg-warning">Expired</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger">Revoked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($key->is_active): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Revoke this API key? This cannot be undone.')">
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="key_id" value="<?php echo $key->id; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-key fa-3x mb-3"></i>
                <h5>No API Keys</h5>
                <p>Generate an API key to access your research data programmatically.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateKeyModal">
                    <i class="fas fa-plus me-1"></i> Generate Key
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0">API Documentation</h5></div>
    <div class="card-body">
        <h6>Authentication</h6>
        <p>Include your API key in the <code>X-API-Key</code> header or as an <code>api_key</code> query parameter.</p>
        <pre class="bg-light p-3"><code>curl -H "X-API-Key: YOUR_API_KEY" https://example.com/api/research/profile</code></pre>

        <h6 class="mt-4">Available Endpoints</h6>
        <table class="table table-sm">
            <thead>
                <tr><th>Method</th><th>Endpoint</th><th>Description</th></tr>
            </thead>
            <tbody>
                <tr><td><span class="badge bg-success">GET</span></td><td>/profile</td><td>Get your researcher profile</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/projects</td><td>List your projects</td></tr>
                <tr><td><span class="badge bg-primary">POST</span></td><td>/projects</td><td>Create a project</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/collections</td><td>List your collections</td></tr>
                <tr><td><span class="badge bg-primary">POST</span></td><td>/collections</td><td>Create a collection</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/searches</td><td>List saved searches</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/bookings</td><td>List bookings</td></tr>
                <tr><td><span class="badge bg-primary">POST</span></td><td>/bookings</td><td>Create a booking</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/bibliographies</td><td>List bibliographies</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/annotations</td><td>List annotations</td></tr>
                <tr><td><span class="badge bg-success">GET</span></td><td>/stats</td><td>Get your usage statistics</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Generate Key Modal -->
<div class="modal fade" id="generateKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="generate">
                <div class="modal-header">
                    <h5 class="modal-title">Generate API Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Key Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Python Script">
                        <small class="text-muted">A descriptive name to identify this key</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiration Date</label>
                        <input type="date" name="expires_at" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        <small class="text-muted">Leave blank for no expiration</small>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        The API key will only be shown once after generation. Make sure to copy it immediately.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-key me-1"></i> Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>
