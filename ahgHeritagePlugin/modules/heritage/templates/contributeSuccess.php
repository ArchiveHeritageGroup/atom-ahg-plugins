<?php
/**
 * Contribution Form.
 */

decorate_with('layout_2col');
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-pencil-alt-square me-2"></i>Contribute
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<!-- Item Context Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Item Context</h6>
    </div>
    <?php if ($thumbnail): ?>
    <img src="<?php echo esc_specialchars($thumbnail); ?>"
         class="card-img-top"
         alt="<?php echo esc_specialchars($item->title ?? 'Item'); ?>"
         onerror="this.style.display='none'">
    <?php endif; ?>
    <div class="card-body">
        <h5 class="card-title"><?php echo esc_specialchars($item->title ?? 'Untitled'); ?></h5>
        <?php if (!empty($item->scope_and_content)): ?>
        <p class="card-text small text-muted">
            <?php echo esc_specialchars(substr(strip_tags($item->scope_and_content), 0, 200)); ?>...
        </p>
        <?php endif; ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $slug]); ?>"
           class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="fas fa-box-arrow-up-right me-1"></i>View Full Record
        </a>
    </div>
</div>

<!-- Existing Contributions -->
<?php if (!empty($existingContributions)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Previous Contributions</h6>
    </div>
    <ul class="list-group list-group-flush">
        <?php foreach (array_slice($existingContributions, 0, 5) as $contrib): ?>
        <li class="list-group-item">
            <div class="d-flex align-items-center mb-1">
                <i class="fas <?php echo $contrib['type']['icon']; ?> text-<?php echo $contrib['type']['color']; ?> me-2"></i>
                <small class="fw-bold"><?php echo esc_specialchars($contrib['type']['name']); ?></small>
            </div>
            <small class="text-muted">
                by <?php echo esc_specialchars($contrib['contributor']['display_name']); ?>
            </small>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<?php end_slot(); ?>

<!-- Main Content -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!$contributorId): ?>
        <!-- Not logged in -->
        <div class="text-center py-5">
            <i class="fas fa-user-lock display-1 text-muted"></i>
            <h3 class="h4 mt-3">Sign In to Contribute</h3>
            <p class="text-muted mb-4">
                You need a contributor account to submit contributions to our heritage collection.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="btn btn-primary">
                    <i class="fas fa-box-arrow-in-right me-2"></i>Sign In
                </a>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorRegister']); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Logged in - show contribution form -->
        <div class="mb-4">
            <p class="text-muted mb-0">
                Contributing as <strong><?php echo esc_specialchars($contributorName); ?></strong>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogout']); ?>" class="small ms-2">
                    (Sign out)
                </a>
            </p>
        </div>

        <!-- Contribution Type Tabs -->
        <ul class="nav nav-pills mb-4" id="contributionTabs" role="tablist">
            <?php foreach ($opportunities as $opp): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $selectedType === $opp['code'] ? 'active' : ''; ?> <?php echo !$opp['available'] ? 'disabled' : ''; ?>"
                        id="tab-<?php echo $opp['code']; ?>"
                        data-bs-toggle="pill"
                        data-bs-target="#form-<?php echo $opp['code']; ?>"
                        type="button"
                        role="tab"
                        <?php echo !$opp['available'] ? 'disabled title="' . esc_specialchars($opp['reason']) . '"' : ''; ?>>
                    <i class="fas <?php echo $opp['icon']; ?> me-1"></i>
                    <?php echo esc_specialchars($opp['name']); ?>
                    <?php if ($opp['existing_count'] > 0): ?>
                    <span class="badge bg-secondary ms-1"><?php echo $opp['existing_count']; ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Contribution Forms -->
        <div class="tab-content" id="contributionTabsContent">
            <?php foreach ($opportunities as $opp): ?>
            <div class="tab-pane fade <?php echo $selectedType === $opp['code'] ? 'show active' : ''; ?>"
                 id="form-<?php echo $opp['code']; ?>"
                 role="tabpanel">

                <?php if (!$opp['available']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i><?php echo esc_specialchars($opp['reason']); ?>
                </div>
                <?php else: ?>

                <form id="contribution-form-<?php echo $opp['code']; ?>" class="contribution-form">
                    <input type="hidden" name="item_id" value="<?php echo $item->id; ?>">
                    <input type="hidden" name="type_code" value="<?php echo $opp['code']; ?>">

                    <div class="mb-3">
                        <p class="text-muted"><?php echo esc_specialchars($opp['description']); ?></p>
                        <p class="small text-success">
                            <i class="fas fa-gift me-1"></i>Earn <?php echo $opp['points_value']; ?> points for this contribution
                        </p>
                    </div>

                    <?php if ($opp['code'] === 'transcription'): ?>
                    <!-- Transcription Form -->
                    <div class="mb-3">
                        <label for="transcription-text" class="form-label">Transcription <span class="text-danger">*</span></label>
                        <textarea class="form-control font-monospace" id="transcription-text" name="content[text]"
                                  rows="12" required minlength="10"
                                  placeholder="Type the text exactly as it appears in the document..."></textarea>
                        <div class="form-text">
                            Transcribe the text as accurately as possible. Use [...] for unclear words and [illegible] for unreadable sections.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="transcription-notes" class="form-label">Notes (optional)</label>
                        <input type="text" class="form-control" id="transcription-notes" name="content[notes]"
                               placeholder="Any notes about the transcription...">
                    </div>

                    <?php elseif ($opp['code'] === 'identification'): ?>
                    <!-- Identification Form -->
                    <div class="mb-3">
                        <label for="identification-name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="identification-name" name="content[name]"
                               required minlength="2"
                               placeholder="Full name of the person identified">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="identification-relationship" class="form-label">Relationship to Image</label>
                            <select class="form-select" id="identification-relationship" name="content[relationship]">
                                <option value="">Select...</option>
                                <option value="subject">Subject (pictured)</option>
                                <option value="photographer">Photographer</option>
                                <option value="owner">Owner/Donor</option>
                                <option value="mentioned">Mentioned in caption</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="identification-position" class="form-label">Position in Image</label>
                            <input type="text" class="form-control" id="identification-position" name="content[position]"
                                   placeholder="e.g., Front row, left">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="identification-confidence" class="form-label">How confident are you?</label>
                        <select class="form-select" id="identification-confidence" name="content[confidence]">
                            <option value="certain">Certain - I know this person</option>
                            <option value="likely">Likely - Based on strong evidence</option>
                            <option value="possible">Possible - Could be this person</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="identification-source" class="form-label">How do you know? (optional)</label>
                        <textarea class="form-control" id="identification-source" name="content[source]"
                                  rows="2" placeholder="e.g., Family member, historical research..."></textarea>
                    </div>

                    <?php elseif ($opp['code'] === 'context'): ?>
                    <!-- Context Form -->
                    <div class="mb-3">
                        <label for="context-type" class="form-label">Type of Context</label>
                        <select class="form-select" id="context-type" name="content[context_type]">
                            <option value="historical">Historical Background</option>
                            <option value="personal">Personal Memory/Story</option>
                            <option value="location">Location Information</option>
                            <option value="event">Event Details</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="context-text" class="form-label">Your Context <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="context-text" name="content[text]"
                                  rows="8" required minlength="20"
                                  placeholder="Share what you know about this item..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="context-source" class="form-label">Source (optional)</label>
                        <input type="text" class="form-control" id="context-source" name="content[source]"
                               placeholder="How do you know this information?">
                    </div>

                    <?php elseif ($opp['code'] === 'correction'): ?>
                    <!-- Correction Form -->
                    <div class="mb-3">
                        <label for="correction-field" class="form-label">Field to Correct <span class="text-danger">*</span></label>
                        <select class="form-select" id="correction-field" name="content[field]" required>
                            <option value="">Select field...</option>
                            <option value="title">Title</option>
                            <option value="date">Date</option>
                            <option value="description">Description</option>
                            <option value="names">Names Mentioned</option>
                            <option value="location">Location</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="correction-current" class="form-label">Current Value (optional)</label>
                        <input type="text" class="form-control" id="correction-current" name="content[current_value]"
                               placeholder="What is currently shown...">
                    </div>
                    <div class="mb-3">
                        <label for="correction-suggestion" class="form-label">Suggested Correction <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="correction-suggestion" name="content[suggestion]"
                                  rows="3" required
                                  placeholder="What should it be..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="correction-reason" class="form-label">Reason for Correction <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="correction-reason" name="content[reason]"
                                  rows="2" required
                                  placeholder="Why is this correction needed?"></textarea>
                    </div>

                    <?php elseif ($opp['code'] === 'translation'): ?>
                    <!-- Translation Form -->
                    <div class="mb-3">
                        <label for="translation-target" class="form-label">Target Language <span class="text-danger">*</span></label>
                        <select class="form-select" id="translation-target" name="content[target_language]" required>
                            <option value="">Select language...</option>
                            <option value="af">Afrikaans</option>
                            <option value="zu">Zulu</option>
                            <option value="xh">Xhosa</option>
                            <option value="st">Sesotho</option>
                            <option value="tn">Setswana</option>
                            <option value="ts">Tsonga</option>
                            <option value="ss">Swati</option>
                            <option value="ve">Venda</option>
                            <option value="nr">Ndebele</option>
                            <option value="en">English</option>
                            <option value="de">German</option>
                            <option value="fr">French</option>
                            <option value="pt">Portuguese</option>
                        </select>
                    </div>
                    <?php if (!empty($item->scope_and_content)): ?>
                    <div class="mb-3">
                        <label class="form-label">Source Text</label>
                        <div class="border rounded p-3 bg-light small">
                            <?php echo nl2br(esc_specialchars(substr($item->scope_and_content, 0, 500))); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="translation-text" class="form-label">Translation <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="translation-text" name="content[text]"
                                  rows="8" required
                                  placeholder="Your translation..."></textarea>
                    </div>

                    <?php elseif ($opp['code'] === 'tag'): ?>
                    <!-- Tag Form -->
                    <div class="mb-3">
                        <label for="tag-input" class="form-label">Tags <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tag-input"
                               placeholder="Type a tag and press Enter...">
                        <input type="hidden" id="tags-data" name="content[tags]" value="[]">
                        <div id="tag-container" class="mt-2"></div>
                        <div class="form-text">
                            Add relevant keywords that describe the content, people, places, or themes.
                        </div>
                    </div>
                    <div id="tag-suggestions" class="mb-3"></div>

                    <?php endif; ?>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $slug]); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-send me-1"></i>Submit Contribution
                        </button>
                    </div>
                </form>

                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="fas fa-check-circle display-1 text-success"></i>
                <h4 class="mt-3">Thank You!</h4>
                <p class="text-muted mb-4">
                    Your contribution has been submitted and is pending review.
                    We'll notify you when it's approved.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'myContributions']); ?>" class="btn btn-primary">
                        View My Contributions
                    </a>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $slug]); ?>" class="btn btn-outline-secondary">
                        Back to Item
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions
    document.querySelectorAll('.contribution-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const content = {};

            // Build content object from form fields
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('content[')) {
                    const fieldName = key.replace('content[', '').replace(']', '');
                    content[fieldName] = value;
                }
            }

            // Parse tags if present
            if (document.getElementById('tags-data')) {
                content.tags = JSON.parse(document.getElementById('tags-data').value || '[]');
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';

            try {
                const response = await fetch('<?php echo url_for(['module' => 'heritage', 'action' => 'apiSubmitContribution']); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: formData.get('item_id'),
                        type_code: formData.get('type_code'),
                        content: content
                    })
                });

                const result = await response.json();

                if (result.success) {
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else {
                    alert('Error: ' + (result.error || 'Failed to submit contribution'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });

    // Tag input handling
    const tagInput = document.getElementById('tag-input');
    const tagsData = document.getElementById('tags-data');
    const tagContainer = document.getElementById('tag-container');

    if (tagInput) {
        let tags = [];

        function renderTags() {
            tagContainer.innerHTML = tags.map((tag, i) =>
                `<span class="badge bg-primary me-1 mb-1">${tag}
                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.6em" onclick="removeTag(${i})"></button>
                </span>`
            ).join('');
            tagsData.value = JSON.stringify(tags);
        }

        window.removeTag = function(index) {
            tags.splice(index, 1);
            renderTags();
        };

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                e.preventDefault();
                const tag = this.value.trim();
                if (!tags.includes(tag)) {
                    tags.push(tag);
                    renderTags();
                }
                this.value = '';
            }
        });

        // Tag autocomplete
        let debounceTimer;
        tagInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) return;

            debounceTimer = setTimeout(async () => {
                const response = await fetch(`<?php echo url_for(['module' => 'heritage', 'action' => 'apiSuggestTags']); ?>?q=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    const suggestions = document.getElementById('tag-suggestions');
                    suggestions.innerHTML = '<small class="text-muted">Suggestions: </small>' +
                        result.data.slice(0, 5).map(tag =>
                            `<button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" onclick="addSuggestedTag('${tag}')">${tag}</button>`
                        ).join('');
                }
            }, 300);
        });

        window.addSuggestedTag = function(tag) {
            if (!tags.includes(tag)) {
                tags.push(tag);
                renderTags();
            }
            tagInput.value = '';
            document.getElementById('tag-suggestions').innerHTML = '';
        };
    }
});
</script>
