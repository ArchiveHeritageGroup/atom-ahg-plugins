<?php
/**
 * Authority Resolution - three-region review screen (Task 5).
 *
 * Left   : source mention + context packet
 * Middle : ranked candidates from ahg_mention_candidate (composite_score DESC)
 * Right  : five action buttons (link / link different / create new / park / reject)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php
  $mention         = $sf_data->getRaw('mention');
  $context_row     = $sf_data->getRaw('context_row');
  $candidates      = $sf_data->getRaw('candidates');
  $nextPendingId   = $sf_data->getRaw('next_pending_id');
  $parkRow         = $sf_data->getRaw('park_row');
  $latestDecision  = $sf_data->getRaw('latest_decision');
  $placeCoords     = $sf_data->getRaw('place_coords');
  $sourceDoc       = $sf_data->getRaw('source_doc');
  $assignment      = $sf_data->getRaw('assignment');
  $archivists      = $sf_data->getRaw('archivists');

  $typeBadges = [
    'PERSON'      => 'primary',
    'ORG'         => 'info',
    'GPE'         => 'success',
    'LOC'         => 'success',
    'PLACE'       => 'success',
    'ISAD_PLACE'  => 'success',
  ];
  $stateBadges = [
    'pending'             => 'warning',
    'linked'              => 'success',
    'parked'              => 'info',
    'rejected'            => 'secondary',
    'new_record_created'  => 'primary',
  ];

  $coOccurring  = $context_row && $context_row->co_occurring_entities ? json_decode((string) $context_row->co_occurring_entities, true) : [];
  $nearbyDates  = $context_row && $context_row->nearby_dates ? json_decode((string) $context_row->nearby_dates, true) : [];
  $nearbyPlaces = $context_row && $context_row->nearby_places ? json_decode((string) $context_row->nearby_places, true) : [];
  $roleTokens   = $context_row && $context_row->role_language_tokens ? json_decode((string) $context_row->role_language_tokens, true) : [];

  $textBefore = $context_row ? (string) $context_row->surrounding_text_before : '';
  $textAfter  = $context_row ? (string) $context_row->surrounding_text_after : '';
?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-balance-scale me-2"></i>
    <?php echo __('Review mention'); ?>
    <span class="badge bg-<?php echo $typeBadges[$mention->entity_type] ?? 'secondary'; ?> ms-2">
      <?php echo htmlspecialchars($mention->entity_type); ?>
    </span>
    <span class="badge bg-<?php echo $stateBadges[$mention->state] ?? 'secondary'; ?> ms-1">
      <?php echo htmlspecialchars($mention->state); ?>
    </span>
  </h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ar_auth_res_index'); ?>"><?php echo __('Authority Resolution'); ?></a>
      </li>
      <li class="breadcrumb-item active">
        <?php echo __('Mention'); ?> #<?php echo (int) $mention->id; ?>
      </li>
    </ol>
  </nav>

  <?php $flashes = $sf_user->getFlash('notice'); if ($flashes): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars((string) $flashes); ?></div>
  <?php endif; ?>
  <?php $flashErr = $sf_user->getFlash('error'); if ($flashErr): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars((string) $flashErr); ?></div>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="row g-3">

  <!-- ================ LEFT: SOURCE + CONTEXT ================ -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-quote-left me-1"></i><?php echo __('Source mention'); ?></strong>
        <small class="text-muted">#<?php echo (int) $mention->id; ?></small>
      </div>
      <div class="card-body">
        <h4 class="mb-2"><?php echo htmlspecialchars((string) $mention->entity_value); ?></h4>

        <?php if ($mention->original_value && $mention->original_value !== $mention->entity_value): ?>
          <p class="small text-muted mb-1">
            <?php echo __('Original'); ?>: <code><?php echo htmlspecialchars((string) $mention->original_value); ?></code>
          </p>
        <?php endif; ?>

        <p class="small mb-2">
          <strong><?php echo __('Source'); ?>:</strong>
          <?php if ($mention->io_slug): ?>
            <a href="/<?php echo htmlspecialchars((string) $mention->io_slug); ?>" target="_blank" rel="noopener">
              <?php echo htmlspecialchars($mention->io_title ?: ('Object #' . (int) $mention->object_id)); ?>
              <i class="fas fa-external-link-alt fa-xs ms-1"></i>
            </a>
          <?php else: ?>
            <span class="text-muted">Object #<?php echo (int) $mention->object_id; ?></span>
          <?php endif; ?>
        </p>

        <?php if ($mention->confidence !== null): ?>
          <p class="small mb-2 text-muted">
            <?php echo __('NER confidence'); ?>: <?php echo number_format((float) $mention->confidence, 3); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong><i class="fas fa-stream me-1"></i><?php echo __('Context window'); ?></strong></div>
      <div class="card-body">
        <?php if (!$context_row): ?>
          <p class="text-muted small mb-0"><?php echo __('No context packet computed for this mention.'); ?></p>
        <?php else: ?>
          <div class="bg-light p-2 rounded small" style="font-family: monospace; line-height: 1.5;">
            <span class="text-muted">...<?php echo htmlspecialchars($textBefore); ?></span><mark class="bg-warning"><strong><?php echo htmlspecialchars((string) $mention->entity_value); ?></strong></mark><span class="text-muted"><?php echo htmlspecialchars($textAfter); ?>...</span>
          </div>
          <div class="row mt-2 small text-muted">
            <div class="col-6">
              <?php echo __('Offset'); ?>:
              <?php echo (int) $context_row->character_offset_start; ?>-<?php echo (int) $context_row->character_offset_end; ?>
            </div>
            <div class="col-6 text-end">
              <?php echo __('Paragraph'); ?>:
              <?php echo (int) $context_row->paragraph_offset_start; ?>-<?php echo (int) $context_row->paragraph_offset_end; ?>
            </div>
          </div>
        <?php endif; ?>
        <div class="mt-2 text-end d-flex flex-wrap justify-content-end gap-2">
          <button type="button" class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal" data-bs-target="#ar-context-modal">
            <i class="fas fa-search-plus me-1"></i><?php echo __('View full context'); ?>
          </button>
          <?php if ($sourceDoc): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#ar-full-text-modal">
              <i class="fas fa-expand-alt me-1"></i><?php echo __('View full document text'); ?>
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($coOccurring)): ?>
      <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-users me-1"></i><?php echo __('Co-occurring entities'); ?></strong></div>
        <ul class="list-group list-group-flush small">
          <?php foreach ($coOccurring as $e): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-1">
              <span>
                <span class="badge bg-<?php echo $typeBadges[$e['type'] ?? ''] ?? 'secondary'; ?> me-1"><?php echo htmlspecialchars((string) ($e['type'] ?? '')); ?></span>
                <?php echo htmlspecialchars((string) ($e['value'] ?? '')); ?>
              </span>
              <small class="text-muted">Δ <?php echo (int) ($e['distance_chars'] ?? 0); ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($nearbyDates)): ?>
      <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-calendar me-1"></i><?php echo __('Nearby dates'); ?></strong></div>
        <ul class="list-group list-group-flush small">
          <?php foreach ($nearbyDates as $d): ?>
            <li class="list-group-item d-flex justify-content-between py-1">
              <span><?php echo htmlspecialchars((string) ($d['value'] ?? '')); ?></span>
              <small class="text-muted">Δ <?php echo (int) ($d['distance_chars'] ?? 0); ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($nearbyPlaces)): ?>
      <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-map-marker-alt me-1"></i><?php echo __('Nearby places'); ?></strong></div>
        <ul class="list-group list-group-flush small">
          <?php foreach ($nearbyPlaces as $p): ?>
            <li class="list-group-item d-flex justify-content-between py-1">
              <span><?php echo htmlspecialchars((string) ($p['value'] ?? '')); ?></span>
              <small class="text-muted">Δ <?php echo (int) ($p['distance_chars'] ?? 0); ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($roleTokens)): ?>
      <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-tag me-1"></i><?php echo __('Role language'); ?></strong></div>
        <ul class="list-group list-group-flush small">
          <?php foreach ($roleTokens as $t): ?>
            <li class="list-group-item d-flex justify-content-between py-1">
              <span>
                <span class="badge bg-secondary me-1"><?php echo htmlspecialchars((string) ($t['kind'] ?? '')); ?></span>
                <?php echo htmlspecialchars((string) ($t['token'] ?? '')); ?>
              </span>
              <small class="text-muted">Δ <?php echo (int) ($t['distance_chars'] ?? 0); ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $occCount = $context_row ? 0 : 0; ?>
    <?php if ($context_row && $context_row->character_offset_start !== null):
      // ambiguity isn't a direct column but the co-occurring-list can give a hint;
      // we surface the occurrence count by re-running a cheap stripos check below
      // only when needed — for now expose the surrounding-text length as a proxy.
    endif; ?>
  </div>

  <!-- ================ MIDDLE: CANDIDATES ================ -->
  <div class="col-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0"><i class="fas fa-list-ol me-1"></i><?php echo __('Ranked candidates'); ?></h5>
      <span class="badge bg-secondary"><?php echo count($candidates); ?> <?php echo __('candidate(s)'); ?></span>
    </div>

    <?php if (count($candidates) === 0): ?>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <?php echo __('No candidates generated yet. Run auth-res:generate-candidates and auth-res:score-evidence for this mention.'); ?>
      </div>
    <?php else: ?>
      <?php $i = 0; foreach ($candidates as $cand):
        $coord = isset($placeCoords[(int) $cand->id]) ? $placeCoords[(int) $cand->id] : null;
        include_partial('authorityResolution/candidateCard', [
          'candidate' => $cand,
          'mention'   => $mention,
          'coord'     => $coord,
          'is_top'    => ($i === 0),
        ]);
        $i++;
      endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ================ RIGHT: ACTIONS ================ -->
  <div class="col-lg-3">
    <div class="card mb-3 sticky-top" style="top: 70px;">
      <div class="card-header"><strong><i class="fas fa-gavel me-1"></i><?php echo __('Decisions'); ?></strong></div>
      <div class="card-body d-grid gap-2">

        <?php if ($mention->state === 'pending'): ?>

          <?php $topCandidate = count($candidates) ? reset($candidates) : null; ?>
          <?php if ($topCandidate): ?>
            <form method="post" action="<?php echo url_for('@ar_auth_res_link?id=' . (int) $mention->id); ?>">
              <input type="hidden" name="candidate_id" value="<?php echo (int) $topCandidate->id; ?>">
              <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-check me-1"></i><?php echo __('Link (top candidate)'); ?>
              </button>
            </form>
          <?php else: ?>
            <button type="button" class="btn btn-success w-100" disabled>
              <i class="fas fa-check me-1"></i><?php echo __('Link'); ?>
              <br><small><?php echo __('no candidate available'); ?></small>
            </button>
          <?php endif; ?>

          <button type="button" class="btn btn-warning w-100"
                  data-bs-toggle="modal" data-bs-target="#ar-link-different-modal">
            <i class="fas fa-search me-1"></i><?php echo __('Link to different'); ?>
          </button>

          <a href="<?php echo url_for('@ar_auth_res_create_new?id=' . (int) $mention->id); ?>"
             class="btn btn-outline-success w-100">
            <i class="fas fa-plus me-1"></i><?php echo __('Create new authority record'); ?>
          </a>

          <button type="button" class="btn btn-info w-100"
                  data-bs-toggle="modal" data-bs-target="#ar-park-modal">
            <i class="fas fa-pause me-1"></i><?php echo __('Park'); ?>
          </button>

          <button type="button" class="btn btn-outline-danger w-100"
                  data-bs-toggle="modal" data-bs-target="#ar-reject-modal">
            <i class="fas fa-times me-1"></i><?php echo __('Reject'); ?>
          </button>

          <hr>
          <button type="button" class="btn btn-primary w-100"
                  data-bs-toggle="modal" data-bs-target="#ar-assign-modal">
            <i class="fas fa-user-plus me-1"></i><?php echo __('Assign'); ?>
            <?php if (!empty($assignment) && !empty($assignment->assigned_to_username)): ?>
              <br><small><?php echo __('to'); ?> <?php echo htmlspecialchars((string) $assignment->assigned_to_username); ?></small>
            <?php endif; ?>
          </button>

        <?php else: ?>

          <div class="alert alert-info mb-0">
            <strong><?php echo __('Already decided'); ?></strong><br>
            <small><?php echo __('State'); ?>: <code><?php echo htmlspecialchars($mention->state); ?></code></small>
            <?php if ($latestDecision): ?>
              <br><small><?php echo __('Decision'); ?>: <?php echo htmlspecialchars((string) $latestDecision->decision_type); ?></small>
              <br><small><?php echo __('At'); ?>: <?php echo htmlspecialchars((string) $latestDecision->decided_at); ?></small>
              <?php if ($latestDecision->fuseki_graph_uri): ?>
                <br><small class="text-success">
                  <i class="fas fa-check-circle"></i>
                  <?php echo __('Provenance written'); ?>
                </small>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <button type="button" class="btn btn-primary w-100 mt-2"
                  data-bs-toggle="modal" data-bs-target="#ar-assign-modal">
            <i class="fas fa-user-plus me-1"></i><?php echo __('Assign'); ?>
            <?php if (!empty($assignment) && !empty($assignment->assigned_to_username)): ?>
              <br><small><?php echo __('to'); ?> <?php echo htmlspecialchars((string) $assignment->assigned_to_username); ?></small>
            <?php endif; ?>
          </button>

        <?php endif; ?>

        <?php if ($nextPendingId): ?>
          <hr>
          <a href="<?php echo url_for('@ar_auth_res_review?id=' . (int) $nextPendingId); ?>"
             class="btn btn-sm btn-outline-secondary w-100">
            <i class="fas fa-forward me-1"></i><?php echo __('Skip to next pending'); ?> (#<?php echo (int) $nextPendingId; ?>)
          </a>
        <?php endif; ?>
        <a href="<?php echo url_for('@ar_auth_res_index'); ?>" class="btn btn-sm btn-link w-100">
          <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to queue'); ?>
        </a>
      </div>
    </div>

    <?php if ($parkRow): ?>
      <div class="card border-info mb-3">
        <div class="card-header bg-info text-white"><strong><i class="fas fa-pause-circle me-1"></i><?php echo __('Parked'); ?></strong></div>
        <div class="card-body small">
          <p class="mb-1"><strong><?php echo __('Reason'); ?>:</strong></p>
          <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars((string) $parkRow->reason)); ?></p>
          <p class="mb-0 small text-muted">
            <?php echo __('Parked at'); ?>: <?php echo htmlspecialchars((string) $parkRow->parked_at); ?>
            <?php if ((int) $parkRow->new_candidate_available === 1): ?>
              <br><span class="badge bg-warning text-dark"><?php echo __('New candidate available'); ?></span>
            <?php endif; ?>
          </p>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php include_partial('authorityResolution/linkDifferentModal', ['mention' => $mention]); ?>
<?php include_partial('authorityResolution/parkModal',          ['mention' => $mention]); ?>
<?php include_partial('authorityResolution/rejectModal',        ['mention' => $mention]); ?>
<?php include_partial('authorityResolution/assignModal', ['mention' => $mention, 'archivists' => $archivists, 'assignment' => $assignment]); ?>
<?php include_partial('authorityResolution/contextModal', ['mention' => $mention]); ?>

<?php if ($sourceDoc): ?>
<!-- Full source-document text modal — "view full document" from the context window -->
<div class="modal fade" id="ar-full-text-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-file-alt me-2"></i><?php echo __('Full document text'); ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
      </div>
      <div class="modal-body">
        <?php if (!empty($sourceDoc->title)): ?>
          <h6 class="text-muted mb-3"><?php echo htmlspecialchars((string) $sourceDoc->title); ?></h6>
        <?php endif; ?>
        <?php
          // HTML-escape first, then wrap occurrences of the mention value in
          // <mark> so the archivist can spot the entity in the running text.
          $fullText = htmlspecialchars((string) $sourceDoc->scope_and_content);
          $term = (string) $mention->entity_value;
          if ($term !== '') {
              $fullText = preg_replace(
                  '/(' . preg_quote(htmlspecialchars($term), '/') . ')/i',
                  '<mark class="bg-warning">$1</mark>',
                  $fullText
              );
          }
        ?>
        <div class="border rounded p-3 bg-light" style="white-space: pre-wrap; line-height: 1.6;"><?php echo $fullText; ?></div>
      </div>
      <div class="modal-footer">
        <?php if ($mention->io_slug): ?>
          <a href="/<?php echo htmlspecialchars((string) $mention->io_slug); ?>" target="_blank" rel="noopener"
             class="btn btn-outline-primary btn-sm">
            <i class="fas fa-external-link-alt me-1"></i><?php echo __('Open full record'); ?>
          </a>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? ' nonce="' . preg_replace('/^nonce=/', '', $n) . '"' : ''; ?>

<!-- Leaflet (CDN, no host modifications) for map previews on PLACE candidates -->
<?php if (!empty($placeCoords)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"<?php echo $nonceAttr; ?>></script>
<?php endif; ?>

<script<?php echo $nonceAttr; ?>>
document.addEventListener('DOMContentLoaded', function() {

  // ---- Leaflet maps for PLACE candidate coords ----
  if (typeof L !== 'undefined') {
    document.querySelectorAll('.ar-map').forEach(function(el) {
      var lat = parseFloat(el.getAttribute('data-lat'));
      var lng = parseFloat(el.getAttribute('data-lng'));
      if (isNaN(lat) || isNaN(lng)) return;
      var map = L.map(el, { zoomControl: false, attributionControl: false }).setView([lat, lng], 7);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: 'OpenStreetMap'
      }).addTo(map);
      L.marker([lat, lng]).addTo(map);
    });
  }

  // ---- Link-different typeahead ----
  var search = document.getElementById('ar-link-different-search');
  var results = document.getElementById('ar-link-different-results');
  var hiddenAuth = document.getElementById('ar-link-different-authority-id');
  var selected = document.getElementById('ar-link-different-selected');
  var selectedName = document.getElementById('ar-link-different-selected-name');
  var submitBtn = document.getElementById('ar-link-different-submit');

  if (search && results) {
    var debounceTimer = null;
    var entityType = search.getAttribute('data-entity-type');

    function doSearch() {
      var q = search.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }

      var url = '<?php echo url_for('@ar_auth_res_lookup'); ?>?q=' + encodeURIComponent(q)
              + '&type=' + encodeURIComponent(entityType);

      fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          results.innerHTML = '';
          if (!data.results || data.results.length === 0) {
            results.innerHTML = '<div class="list-group-item text-muted small">no results</div>';
            return;
          }
          data.results.forEach(function(row) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.innerHTML = '<strong>' + escapeHtml(row.display_name || '') + '</strong>'
              + ' <span class="badge bg-light text-dark border ms-1">' + escapeHtml(row.source || '') + '</span>';
            btn.addEventListener('click', function() {
              hiddenAuth.value = row.authority_id || '';
              selectedName.textContent = row.display_name || '';
              selected.classList.remove('d-none');
              submitBtn.disabled = !row.authority_id;
            });
            results.appendChild(btn);
          });
        })
        .catch(function() { results.innerHTML = '<div class="list-group-item text-danger small">lookup failed</div>'; });
    }

    function escapeHtml(s) {
      return String(s).replace(/[&<>"']/g, function(c) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
      });
    }

    search.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(doSearch, 250);
    });
  }

  // ---- "View full context" modal ----
  // Fetches the full source text + mention offsets, then builds the highlight
  // by slicing the RAW string at the character offsets, HTML-escaping each
  // slice, and concatenating with the wrapper tags (escape-then-splice - never
  // splice tags into already-escaped text).
  (function () {
    var ctxModalEl = document.getElementById('ar-context-modal');
    if (!ctxModalEl) { return; }

    var contextUrl = <?php echo json_encode(url_for('@ar_auth_res_context?id=' . (int) $mention->id)); ?>;
    var loadingEl  = document.getElementById('ar-context-loading');
    var errorEl    = document.getElementById('ar-context-error');
    var noteEl     = document.getElementById('ar-context-note');
    var bodyEl     = document.getElementById('ar-context-body');
    var loaded     = false;

    function esc(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
      });
    }

    function render(data) {
      var text = String(data.source_text || '');
      var oStart = data.offset_start, oEnd = data.offset_end;
      var pStart = data.paragraph_start, pEnd = data.paragraph_end;

      // Valid character offsets present and in range -> precise highlight.
      var haveOffsets = (oStart !== null && oStart !== undefined &&
                         oEnd !== null && oEnd !== undefined &&
                         oStart >= 0 && oEnd <= text.length && oStart < oEnd);
      // Paragraph offsets are character positions of the enclosing paragraph.
      var haveParas = (pStart !== null && pStart !== undefined &&
                       pEnd !== null && pEnd !== undefined &&
                       pStart >= 0 && pEnd <= text.length && pStart <= oStart &&
                       pEnd >= oEnd);

      var html;
      if (haveOffsets) {
        if (haveParas) {
          // [0,pStart) + <para>[pStart,oStart) + <mark>[oStart,oEnd)</mark> + [oEnd,pEnd)</para> + [pEnd,end)
          html = esc(text.slice(0, pStart))
               + '<span style="background-color: rgba(255,193,7,0.18);">'
               + esc(text.slice(pStart, oStart))
               + '<mark class="bg-warning">' + esc(text.slice(oStart, oEnd)) + '</mark>'
               + esc(text.slice(oEnd, pEnd))
               + '</span>'
               + esc(text.slice(pEnd));
        } else {
          html = esc(text.slice(0, oStart))
               + '<mark class="bg-warning">' + esc(text.slice(oStart, oEnd)) + '</mark>'
               + esc(text.slice(oEnd));
          noteEl.classList.remove('d-none');
        }
      } else {
        // No usable offsets - show the full text plus a note.
        html = esc(text);
        noteEl.classList.remove('d-none');
      }

      loadingEl.classList.add('d-none');
      bodyEl.innerHTML = html || '<span class="text-muted"><?php echo __('No source text available.'); ?></span>';
      bodyEl.classList.remove('d-none');
    }

    function load() {
      if (loaded) { return; }
      loaded = true;
      fetch(contextUrl, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            loadingEl.classList.add('d-none');
            errorEl.textContent = (data && data.error) ? data.error : '<?php echo __('Could not load context.'); ?>';
            errorEl.classList.remove('d-none');
            return;
          }
          render(data);
        })
        .catch(function () {
          loaded = false;
          loadingEl.classList.add('d-none');
          errorEl.textContent = '<?php echo __('Network error loading context.'); ?>';
          errorEl.classList.remove('d-none');
        });
    }

    ctxModalEl.addEventListener('show.bs.modal', load);
  })();

});
</script>

<?php end_slot(); ?>
