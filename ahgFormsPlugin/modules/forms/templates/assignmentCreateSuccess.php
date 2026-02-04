<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-plus me-2"></i>Create Assignment</h1>
            <p class="text-muted">Assign a form template to specific contexts</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'forms', 'action' => 'assignments']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Form Template *</label>
                    <select name="template_id" class="form-select" required>
                        <option value="">Select template...</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template->id ?>">
                                <?php echo htmlspecialchars($template->name) ?> (<?php echo $template->form_type ?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Repository (optional)</label>
                    <select name="repository_id" class="form-select">
                        <option value="">All repositories</option>
                        <?php
                        $repos = \Illuminate\Database\Capsule\Manager::table('repository as r')
                            ->join('actor_i18n as ai', 'r.id', '=', 'ai.id')
                            ->where('ai.culture', 'en')
                            ->select('r.id', 'ai.authorized_form_of_name')
                            ->orderBy('ai.authorized_form_of_name')
                            ->get();
                        foreach ($repos as $repo):
                        ?>
                            <option value="<?php echo $repo->id ?>"><?php echo htmlspecialchars($repo->authorized_form_of_name) ?></option>
                        <?php endforeach ?>
                    </select>
                    <small class="text-muted">Leave empty to apply to all repositories</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Level of Description (optional)</label>
                    <select name="level_of_description_id" class="form-select">
                        <option value="">All levels</option>
                        <?php
                        $levels = \Illuminate\Database\Capsule\Manager::table('term as t')
                            ->join('term_i18n as ti', 't.id', '=', 'ti.id')
                            ->where('t.taxonomy_id', 157) // Level of Description taxonomy
                            ->where('ti.culture', 'en')
                            ->select('t.id', 'ti.name')
                            ->orderBy('ti.name')
                            ->get();
                        foreach ($levels as $level):
                        ?>
                            <option value="<?php echo $level->id ?>"><?php echo htmlspecialchars($level->name) ?></option>
                        <?php endforeach ?>
                    </select>
                    <small class="text-muted">Leave empty to apply to all levels</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <input type="number" name="priority" class="form-control" value="100" min="1" max="1000">
                    <small class="text-muted">Higher numbers = higher priority. When multiple assignments match, the highest priority wins.</small>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="inherit_to_children" class="form-check-input" id="inheritCheck">
                    <label class="form-check-label" for="inheritCheck">Inherit to child records</label>
                    <small class="d-block text-muted">Apply this template to child descriptions as well</small>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo url_for(['module' => 'forms', 'action' => 'assignments']) ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
