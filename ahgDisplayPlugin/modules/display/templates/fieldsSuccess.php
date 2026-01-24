<?php use_helper('Display'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'display', 'action' => 'index']); ?>">Display</a></li>
        <li class="breadcrumb-item active">Field Mappings</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-list text-primary me-2"></i>Field Mappings</h1>

<p class="text-muted">These fields map display elements to existing AtoM database tables and columns.</p>

<?php foreach ($fieldGroups as $group): ?>
<?php 
$groupFields = array_filter($fields, fn($f) => $f->field_group === $group);
if (empty($groupFields)) continue;
?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-<?php echo match($group) {
                'identity' => 'id-card',
                'description' => 'align-left',
                'context' => 'history',
                'access' => 'lock-open',
                'technical' => 'cog',
                'admin' => 'user-shield',
                default => 'list',
            }; ?> me-2"></i>
            <?php echo ucfirst($group); ?> Fields
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Field</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Source Table</th>
                    <th>Source Column</th>
                    <th>ISAD</th>
                    <th>Spectrum</th>
                    <th>DC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupFields as $f): ?>
                <tr>
                    <td><strong><?php echo $f->name; ?></strong></td>
                    <td><code><?php echo $f->code; ?></code></td>
                    <td><span class="badge bg-secondary"><?php echo $f->data_type; ?></span></td>
                    <td><code class="small"><?php echo $f->source_table ?: '-'; ?></code></td>
                    <td><code class="small"><?php echo $f->source_column ?: '-'; ?></code></td>
                    <td><small><?php echo $f->isad_element ?: '-'; ?></small></td>
                    <td><small><?php echo $f->spectrum_unit ?: '-'; ?></small></td>
                    <td><small><?php echo $f->dc_element ?: '-'; ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
