<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new archival description') : __('Edit archival description'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($io['title'] ?: __('Untitled')); ?>
      </span>
    <?php } ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <?php $rawIo = $sf_data->getRaw('io'); ?>

  <form method="post" action="<?php echo $isNew ? url_for('@io_add_override') : url_for('@io_edit_override?slug=' . $rawIo['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="parentId" value="<?php echo (int) $rawIo['parentId']; ?>">

    <div class="accordion mb-3" id="isadAccordion">

      <!-- 1. Identity Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse" aria-expanded="true" aria-controls="identity-collapse">
            <?php echo __('Identity area'); ?>
          </button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse show" aria-labelledby="identity-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="identifier" class="form-label"><?php echo __('Identifier'); ?></label>
              <input type="text" class="form-control" id="identifier" name="identifier"
                     value="<?php echo esc_specialchars($rawIo['identifier']); ?>">
            </div>

            <div class="mb-3">
              <label for="title" class="form-label">
                <?php echo __('Title'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="title" name="title"
                     value="<?php echo esc_specialchars($rawIo['title']); ?>" required>
            </div>

            <!-- Events (dates) multi-row -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Date(s)'); ?></label>
              <?php $rawEventTypes = $sf_data->getRaw('eventTypes'); ?>
              <?php $rawEvents = $rawIo['events']; ?>
              <table class="table table-sm" id="events-table">
                <thead>
                  <tr>
                    <th><?php echo __('Type'); ?></th>
                    <th><?php echo __('Date'); ?></th>
                    <th><?php echo __('Start'); ?></th>
                    <th><?php echo __('End'); ?></th>
                    <th><?php echo __('Actor'); ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($rawEvents)) { ?>
                    <?php foreach ($rawEvents as $idx => $evt) { ?>
                      <tr>
                        <td>
                          <select class="form-select form-select-sm" name="events[<?php echo $idx; ?>][typeId]">
                            <option value=""><?php echo __('- Select -'); ?></option>
                            <?php foreach ($rawEventTypes as $et) { ?>
                              <option value="<?php echo $et->id; ?>" <?php echo ($et->id == ($evt->type_id ?? '')) ? 'selected' : ''; ?>>
                                <?php echo esc_specialchars($et->name ?? ''); ?>
                              </option>
                            <?php } ?>
                          </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="events[<?php echo $idx; ?>][date]" value="<?php echo esc_specialchars($evt->date ?? ''); ?>" placeholder="<?php echo __('e.g. ca. 1900'); ?>"></td>
                        <td><input type="date" class="form-control form-control-sm" name="events[<?php echo $idx; ?>][startDate]" value="<?php echo esc_specialchars($evt->start_date ?? ''); ?>"></td>
                        <td><input type="date" class="form-control form-control-sm" name="events[<?php echo $idx; ?>][endDate]" value="<?php echo esc_specialchars($evt->end_date ?? ''); ?>"></td>
                        <td>
                          <input type="text" class="form-control form-control-sm actor-autocomplete" name="events[<?php echo $idx; ?>][actorName]" value="<?php echo esc_specialchars($evt->actor_name ?? ''); ?>" placeholder="<?php echo __('Actor name'); ?>">
                          <input type="hidden" name="events[<?php echo $idx; ?>][actorId]" value="<?php echo (int) ($evt->actor_id ?? 0); ?>">
                        </td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __('Remove'); ?></button></td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="add-event-row"><?php echo __('Add date'); ?></button>
            </div>

            <?php $rawLevels = $sf_data->getRaw('levels'); ?>
            <div class="mb-3">
              <label for="levelOfDescriptionId" class="form-label">
                <?php echo __('Level of description'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <select class="form-select" id="levelOfDescriptionId" name="levelOfDescriptionId" required>
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawLevels as $level) { ?>
                  <option value="<?php echo $level->id; ?>"
                          <?php echo ($level->id == $rawIo['levelOfDescriptionId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($level->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="extentAndMedium" class="form-label"><?php echo __('Extent and medium'); ?></label>
              <textarea class="form-control" id="extentAndMedium" name="extentAndMedium" rows="3"><?php echo esc_specialchars($rawIo['extentAndMedium']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 2. Context Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="context-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#context-collapse" aria-expanded="false" aria-controls="context-collapse">
            <?php echo __('Context area'); ?>
          </button>
        </h2>
        <div id="context-collapse" class="accordion-collapse collapse" aria-labelledby="context-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="repositoryName" class="form-label"><?php echo __('Repository'); ?></label>
              <input type="text" class="form-control repository-autocomplete" id="repositoryName" name="repositoryName"
                     value="<?php echo esc_specialchars($rawIo['repositoryName'] ?? ''); ?>" placeholder="<?php echo __('Type to search...'); ?>">
              <input type="hidden" id="repositoryId" name="repositoryId" value="<?php echo (int) ($rawIo['repositoryId'] ?? 0); ?>">
            </div>

            <div class="mb-3">
              <label for="archivalHistory" class="form-label"><?php echo __('Archival history'); ?></label>
              <textarea class="form-control" id="archivalHistory" name="archivalHistory" rows="3"><?php echo esc_specialchars($rawIo['archivalHistory']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="acquisition" class="form-label"><?php echo __('Immediate source of acquisition or transfer'); ?></label>
              <textarea class="form-control" id="acquisition" name="acquisition" rows="3"><?php echo esc_specialchars($rawIo['acquisition']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 3. Content and Structure Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="content-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#content-collapse" aria-expanded="false" aria-controls="content-collapse">
            <?php echo __('Content and structure area'); ?>
          </button>
        </h2>
        <div id="content-collapse" class="accordion-collapse collapse" aria-labelledby="content-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="scopeAndContent" class="form-label"><?php echo __('Scope and content'); ?></label>
              <textarea class="form-control" id="scopeAndContent" name="scopeAndContent" rows="4"><?php echo esc_specialchars($rawIo['scopeAndContent']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="appraisal" class="form-label"><?php echo __('Appraisal, destruction and scheduling information'); ?></label>
              <textarea class="form-control" id="appraisal" name="appraisal" rows="3"><?php echo esc_specialchars($rawIo['appraisal']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="accruals" class="form-label"><?php echo __('Accruals'); ?></label>
              <textarea class="form-control" id="accruals" name="accruals" rows="3"><?php echo esc_specialchars($rawIo['accruals']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="arrangement" class="form-label"><?php echo __('System of arrangement'); ?></label>
              <textarea class="form-control" id="arrangement" name="arrangement" rows="3"><?php echo esc_specialchars($rawIo['arrangement']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 4. Conditions of Access and Use Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="access-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#access-collapse" aria-expanded="false" aria-controls="access-collapse">
            <?php echo __('Conditions of access and use area'); ?>
          </button>
        </h2>
        <div id="access-collapse" class="accordion-collapse collapse" aria-labelledby="access-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="accessConditions" class="form-label"><?php echo __('Conditions governing access'); ?></label>
              <textarea class="form-control" id="accessConditions" name="accessConditions" rows="3"><?php echo esc_specialchars($rawIo['accessConditions']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="reproductionConditions" class="form-label"><?php echo __('Conditions governing reproduction'); ?></label>
              <textarea class="form-control" id="reproductionConditions" name="reproductionConditions" rows="3"><?php echo esc_specialchars($rawIo['reproductionConditions']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="physicalCharacteristics" class="form-label"><?php echo __('Physical characteristics and technical requirements'); ?></label>
              <textarea class="form-control" id="physicalCharacteristics" name="physicalCharacteristics" rows="3"><?php echo esc_specialchars($rawIo['physicalCharacteristics']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="findingAids" class="form-label"><?php echo __('Finding aids'); ?></label>
              <textarea class="form-control" id="findingAids" name="findingAids" rows="3"><?php echo esc_specialchars($rawIo['findingAids']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 5. Allied Materials Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="allied-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#allied-collapse" aria-expanded="false" aria-controls="allied-collapse">
            <?php echo __('Allied materials area'); ?>
          </button>
        </h2>
        <div id="allied-collapse" class="accordion-collapse collapse" aria-labelledby="allied-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="locationOfOriginals" class="form-label"><?php echo __('Existence and location of originals'); ?></label>
              <textarea class="form-control" id="locationOfOriginals" name="locationOfOriginals" rows="3"><?php echo esc_specialchars($rawIo['locationOfOriginals']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="locationOfCopies" class="form-label"><?php echo __('Existence and location of copies'); ?></label>
              <textarea class="form-control" id="locationOfCopies" name="locationOfCopies" rows="3"><?php echo esc_specialchars($rawIo['locationOfCopies']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="relatedUnitsOfDescription" class="form-label"><?php echo __('Related units of description'); ?></label>
              <textarea class="form-control" id="relatedUnitsOfDescription" name="relatedUnitsOfDescription" rows="3"><?php echo esc_specialchars($rawIo['relatedUnitsOfDescription']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 6. Notes Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="notes-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#notes-collapse" aria-expanded="false" aria-controls="notes-collapse">
            <?php echo __('Notes area'); ?>
          </button>
        </h2>
        <div id="notes-collapse" class="accordion-collapse collapse" aria-labelledby="notes-heading">
          <div class="accordion-body">

            <?php $rawNoteTypes = $sf_data->getRaw('noteTypes'); ?>
            <?php $rawNotes = $rawIo['notes']; ?>
            <table class="table table-sm" id="notes-table">
              <thead>
                <tr>
                  <th style="width:30%"><?php echo __('Type'); ?></th>
                  <th><?php echo __('Content'); ?></th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($rawNotes)) { ?>
                  <?php foreach ($rawNotes as $nIdx => $note) { ?>
                    <tr>
                      <td>
                        <select class="form-select form-select-sm" name="notes[<?php echo $nIdx; ?>][typeId]">
                          <option value=""><?php echo __('- Select -'); ?></option>
                          <?php foreach ($rawNoteTypes as $nt) { ?>
                            <option value="<?php echo $nt->id; ?>" <?php echo ($nt->id == ($note->type_id ?? '')) ? 'selected' : ''; ?>>
                              <?php echo esc_specialchars($nt->name ?? ''); ?>
                            </option>
                          <?php } ?>
                        </select>
                      </td>
                      <td><textarea class="form-control form-control-sm" name="notes[<?php echo $nIdx; ?>][content]" rows="2"><?php echo esc_specialchars($note->content ?? ''); ?></textarea></td>
                      <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __('Remove'); ?></button></td>
                    </tr>
                  <?php } ?>
                <?php } ?>
              </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-note-row"><?php echo __('Add note'); ?></button>

          </div>
        </div>
      </div>

      <!-- 7. Access Points Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="ap-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ap-collapse" aria-expanded="false" aria-controls="ap-collapse">
            <?php echo __('Access points'); ?>
          </button>
        </h2>
        <div id="ap-collapse" class="accordion-collapse collapse" aria-labelledby="ap-heading">
          <div class="accordion-body">

            <!-- Subject access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Subject access points'); ?></label>
              <?php $rawSubjectAPs = $rawIo['subjectAccessPoints']; ?>
              <div id="subject-ap-list">
                <?php if (!empty($rawSubjectAPs)) { ?>
                  <?php foreach ($rawSubjectAPs as $sIdx => $sap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control term-autocomplete" data-taxonomy="35" value="<?php echo esc_specialchars($sap->term_name ?? ''); ?>" readonly>
                      <input type="hidden" name="subjectAccessPointIds[]" value="<?php echo (int) $sap->term_id; ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="35" data-target="subject-ap-list" data-name="subjectAccessPointIds[]" placeholder="<?php echo __('Type to add subject...'); ?>">
              </div>
            </div>

            <!-- Place access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Place access points'); ?></label>
              <?php $rawPlaceAPs = $rawIo['placeAccessPoints']; ?>
              <div id="place-ap-list">
                <?php if (!empty($rawPlaceAPs)) { ?>
                  <?php foreach ($rawPlaceAPs as $pap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($pap->term_name ?? ''); ?>" readonly>
                      <input type="hidden" name="placeAccessPointIds[]" value="<?php echo (int) $pap->term_id; ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="42" data-target="place-ap-list" data-name="placeAccessPointIds[]" placeholder="<?php echo __('Type to add place...'); ?>">
              </div>
            </div>

            <!-- Genre access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Genre access points'); ?></label>
              <?php $rawGenreAPs = $rawIo['genreAccessPoints']; ?>
              <div id="genre-ap-list">
                <?php if (!empty($rawGenreAPs)) { ?>
                  <?php foreach ($rawGenreAPs as $gap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($gap->term_name ?? ''); ?>" readonly>
                      <input type="hidden" name="genreAccessPointIds[]" value="<?php echo (int) $gap->term_id; ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control term-autocomplete-add" data-taxonomy="78" data-target="genre-ap-list" data-name="genreAccessPointIds[]" placeholder="<?php echo __('Type to add genre...'); ?>">
              </div>
            </div>

            <!-- Name access points -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Name access points'); ?></label>
              <?php $rawNameAPs = $rawIo['nameAccessPoints']; ?>
              <div id="name-ap-list">
                <?php if (!empty($rawNameAPs)) { ?>
                  <?php foreach ($rawNameAPs as $nIdx => $nap) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($nap->actor_name ?? ''); ?>" readonly>
                      <input type="hidden" name="nameAccessPoints[<?php echo $nIdx; ?>][actorId]" value="<?php echo (int) $nap->actor_id; ?>">
                      <input type="hidden" name="nameAccessPoints[<?php echo $nIdx; ?>][actorName]" value="<?php echo esc_specialchars($nap->actor_name ?? ''); ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control actor-autocomplete-add" data-target="name-ap-list" placeholder="<?php echo __('Type to add name...'); ?>">
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- 8. Description Control Area -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="control-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#control-collapse" aria-expanded="false" aria-controls="control-collapse">
            <?php echo __('Description control area'); ?>
          </button>
        </h2>
        <div id="control-collapse" class="accordion-collapse collapse" aria-labelledby="control-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="descriptionIdentifier" class="form-label"><?php echo __('Description identifier'); ?></label>
              <input type="text" class="form-control" id="descriptionIdentifier" name="descriptionIdentifier"
                     value="<?php echo esc_specialchars($rawIo['descriptionIdentifier']); ?>">
            </div>

            <div class="mb-3">
              <label for="institutionResponsibleIdentifier" class="form-label"><?php echo __('Institution identifier'); ?></label>
              <textarea class="form-control" id="institutionResponsibleIdentifier" name="institutionResponsibleIdentifier" rows="2"><?php echo esc_specialchars($rawIo['institutionResponsibleIdentifier']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="rules" class="form-label"><?php echo __('Rules or conventions'); ?></label>
              <textarea class="form-control" id="rules" name="rules" rows="3"><?php echo esc_specialchars($rawIo['rules']); ?></textarea>
            </div>

            <?php $rawDescStatuses = $sf_data->getRaw('descriptionStatuses'); ?>
            <div class="mb-3">
              <label for="descriptionStatusId" class="form-label"><?php echo __('Status of description'); ?></label>
              <select class="form-select" id="descriptionStatusId" name="descriptionStatusId">
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawDescStatuses as $status) { ?>
                  <option value="<?php echo $status->id; ?>"
                          <?php echo ($status->id == $rawIo['descriptionStatusId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($status->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <?php $rawDescDetails = $sf_data->getRaw('descriptionDetails'); ?>
            <div class="mb-3">
              <label for="descriptionDetailId" class="form-label"><?php echo __('Level of detail'); ?></label>
              <select class="form-select" id="descriptionDetailId" name="descriptionDetailId">
                <option value=""><?php echo __('- Select -'); ?></option>
                <?php foreach ($rawDescDetails as $detail) { ?>
                  <option value="<?php echo $detail->id; ?>"
                          <?php echo ($detail->id == $rawIo['descriptionDetailId']) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($detail->name ?? ''); ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="revisionHistory" class="form-label"><?php echo __('Dates of creation, revision and deletion'); ?></label>
              <textarea class="form-control" id="revisionHistory" name="revisionHistory" rows="3"><?php echo esc_specialchars($rawIo['revisionHistory']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="sources" class="form-label"><?php echo __('Sources'); ?></label>
              <textarea class="form-control" id="sources" name="sources" rows="3"><?php echo esc_specialchars($rawIo['sources']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

    </div>

    <!-- Admin area -->
    <div class="card mb-3">
      <div class="card-body">

        <?php if (!$isNew && $rawIo['parentTitle']) { ?>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Part of'); ?></label>
            <p class="form-control-plaintext">
              <a href="<?php echo url_for('/' . $rawIo['parentSlug']); ?>">
                <?php echo esc_specialchars($rawIo['parentTitle']); ?>
              </a>
            </p>
          </div>
        <?php } ?>

        <?php $rawPubStatuses = $sf_data->getRaw('publicationStatuses'); ?>
        <div class="mb-3">
          <label for="publicationStatusId" class="form-label"><?php echo __('Publication status'); ?></label>
          <select class="form-select" id="publicationStatusId" name="publicationStatusId">
            <?php foreach ($rawPubStatuses as $ps) { ?>
              <option value="<?php echo $ps->id; ?>"
                      <?php echo ($ps->id == $rawIo['publicationStatusId']) ? 'selected' : ''; ?>>
                <?php echo esc_specialchars($ps->name); ?>
              </option>
            <?php } ?>
          </select>
        </div>

      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '/' . $rawIo['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      <?php } else { ?>
        <li><?php echo link_to(__('Cancel'), '/', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Create'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script <?php echo $na; ?>>
(function() {
  'use strict';

  // ── Event rows ─────────────────────────────────────────────────────
  var eventsBody = document.querySelector('#events-table tbody');
  var addEventBtn = document.getElementById('add-event-row');
  var eventIdx = eventsBody ? eventsBody.querySelectorAll('tr').length : 0;

  // Event type options HTML (cached from first select or built from data)
  var eventTypeOptions = '';
  var firstEvtSelect = document.querySelector('#events-table select');
  if (firstEvtSelect) {
    eventTypeOptions = firstEvtSelect.innerHTML;
  } else {
    eventTypeOptions = '<option value=""><?php echo __("- Select -"); ?></option>';
    <?php foreach ($rawEventTypes as $et) { ?>
    eventTypeOptions += '<option value="<?php echo $et->id; ?>"><?php echo esc_specialchars($et->name ?? ""); ?></option>';
    <?php } ?>
  }

  if (addEventBtn) {
    addEventBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><select class="form-select form-select-sm" name="events[' + eventIdx + '][typeId]">' + eventTypeOptions + '</select></td>' +
        '<td><input type="text" class="form-control form-control-sm" name="events[' + eventIdx + '][date]" placeholder="<?php echo __("e.g. ca. 1900"); ?>"></td>' +
        '<td><input type="date" class="form-control form-control-sm" name="events[' + eventIdx + '][startDate]"></td>' +
        '<td><input type="date" class="form-control form-control-sm" name="events[' + eventIdx + '][endDate]"></td>' +
        '<td><input type="text" class="form-control form-control-sm actor-autocomplete" name="events[' + eventIdx + '][actorName]" placeholder="<?php echo __("Actor name"); ?>">' +
            '<input type="hidden" name="events[' + eventIdx + '][actorId]" value="0"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      eventsBody.appendChild(tr);
      eventIdx++;
    });
  }

  // ── Note rows ──────────────────────────────────────────────────────
  var notesBody = document.querySelector('#notes-table tbody');
  var addNoteBtn = document.getElementById('add-note-row');
  var noteIdx = notesBody ? notesBody.querySelectorAll('tr').length : 0;

  var noteTypeOptions = '';
  var firstNoteSelect = document.querySelector('#notes-table select');
  if (firstNoteSelect) {
    noteTypeOptions = firstNoteSelect.innerHTML;
  } else {
    noteTypeOptions = '<option value=""><?php echo __("- Select -"); ?></option>';
    <?php foreach ($rawNoteTypes as $nt) { ?>
    noteTypeOptions += '<option value="<?php echo $nt->id; ?>"><?php echo esc_specialchars($nt->name ?? ""); ?></option>';
    <?php } ?>
  }

  if (addNoteBtn) {
    addNoteBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><select class="form-select form-select-sm" name="notes[' + noteIdx + '][typeId]">' + noteTypeOptions + '</select></td>' +
        '<td><textarea class="form-control form-control-sm" name="notes[' + noteIdx + '][content]" rows="2"></textarea></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      notesBody.appendChild(tr);
      noteIdx++;
    });
  }

  // ── Remove row delegation ──────────────────────────────────────────
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-row')) {
      var tr = e.target.closest('tr');
      if (tr) tr.remove();
    }
    if (e.target.classList.contains('btn-remove-ap')) {
      var group = e.target.closest('.input-group');
      if (group) group.remove();
    }
  });

  // ── Actor autocomplete (simple AJAX) ───────────────────────────────
  // Uses the ahgActorManagePlugin autocomplete endpoint if available,
  // falls back to base AtoM actor autocomplete.
  function setupActorAutocomplete(input) {
    var timeout = null;
    var dropdown = null;

    input.addEventListener('input', function() {
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdown(); return; }

      timeout = setTimeout(function() {
        fetch('/actor/autocomplete?query=' + encodeURIComponent(q) + '&limit=10')
          .then(function(r) { return r.json(); })
          .then(function(results) {
            removeDropdown();
            if (!results || !results.length) return;
            dropdown = document.createElement('div');
            dropdown.className = 'list-group position-absolute w-100';
            dropdown.style.zIndex = '1050';
            results.forEach(function(item) {
              var a = document.createElement('a');
              a.className = 'list-group-item list-group-item-action py-1 small';
              a.href = '#';
              a.textContent = item.name || item.authorized_form_of_name || '';
              a.addEventListener('click', function(e) {
                e.preventDefault();
                input.value = a.textContent;
                // Set hidden actor ID
                var hiddenId = input.parentNode.querySelector('input[type=hidden]');
                if (hiddenId) hiddenId.value = item.id;
                removeDropdown();
              });
              dropdown.appendChild(a);
            });
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(dropdown);
          })
          .catch(function() {});
      }, 300);
    });

    function removeDropdown() {
      if (dropdown && dropdown.parentNode) {
        dropdown.parentNode.removeChild(dropdown);
      }
      dropdown = null;
    }

    document.addEventListener('click', function(e) {
      if (!input.contains(e.target) && (!dropdown || !dropdown.contains(e.target))) {
        removeDropdown();
      }
    });
  }

  // Initialize actor autocomplete on existing inputs
  document.querySelectorAll('.actor-autocomplete').forEach(setupActorAutocomplete);

  // ── Name access point add ──────────────────────────────────────────
  document.querySelectorAll('.actor-autocomplete-add').forEach(function(input) {
    var timeout = null;
    var dropdown = null;
    var targetId = input.getAttribute('data-target');

    input.addEventListener('input', function() {
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdown(); return; }

      timeout = setTimeout(function() {
        fetch('/actor/autocomplete?query=' + encodeURIComponent(q) + '&limit=10')
          .then(function(r) { return r.json(); })
          .then(function(results) {
            removeDropdown();
            if (!results || !results.length) return;
            dropdown = document.createElement('div');
            dropdown.className = 'list-group position-absolute w-100';
            dropdown.style.zIndex = '1050';
            results.forEach(function(item) {
              var a = document.createElement('a');
              a.className = 'list-group-item list-group-item-action py-1 small';
              a.href = '#';
              a.textContent = item.name || item.authorized_form_of_name || '';
              a.addEventListener('click', function(e) {
                e.preventDefault();
                addNameAP(targetId, item.id, a.textContent);
                input.value = '';
                removeDropdown();
              });
              dropdown.appendChild(a);
            });
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(dropdown);
          })
          .catch(function() {});
      }, 300);
    });

    function removeDropdown() {
      if (dropdown && dropdown.parentNode) dropdown.parentNode.removeChild(dropdown);
      dropdown = null;
    }
  });

  var nameApIdx = document.querySelectorAll('#name-ap-list .input-group').length;
  function addNameAP(targetId, actorId, actorName) {
    var list = document.getElementById(targetId);
    var div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML =
      '<input type="text" class="form-control" value="' + escHtml(actorName) + '" readonly>' +
      '<input type="hidden" name="nameAccessPoints[' + nameApIdx + '][actorId]" value="' + actorId + '">' +
      '<input type="hidden" name="nameAccessPoints[' + nameApIdx + '][actorName]" value="' + escHtml(actorName) + '">' +
      '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
    list.appendChild(div);
    nameApIdx++;
  }

  // ── Term access point add ──────────────────────────────────────────
  document.querySelectorAll('.term-autocomplete-add').forEach(function(input) {
    var timeout = null;
    var dropdown = null;
    var taxonomy = input.getAttribute('data-taxonomy');
    var targetId = input.getAttribute('data-target');
    var fieldName = input.getAttribute('data-name');

    input.addEventListener('input', function() {
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdown(); return; }

      timeout = setTimeout(function() {
        fetch('/taxonomy/' + taxonomy + '/autocomplete?query=' + encodeURIComponent(q) + '&limit=10')
          .then(function(r) { return r.json(); })
          .then(function(results) {
            removeDropdown();
            if (!results || !results.length) return;
            dropdown = document.createElement('div');
            dropdown.className = 'list-group position-absolute w-100';
            dropdown.style.zIndex = '1050';
            results.forEach(function(item) {
              var a = document.createElement('a');
              a.className = 'list-group-item list-group-item-action py-1 small';
              a.href = '#';
              a.textContent = item.name || '';
              a.addEventListener('click', function(e) {
                e.preventDefault();
                addTermAP(targetId, fieldName, item.id, a.textContent);
                input.value = '';
                removeDropdown();
              });
              dropdown.appendChild(a);
            });
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(dropdown);
          })
          .catch(function() {});
      }, 300);
    });

    function removeDropdown() {
      if (dropdown && dropdown.parentNode) dropdown.parentNode.removeChild(dropdown);
      dropdown = null;
    }
  });

  function addTermAP(targetId, fieldName, termId, termName) {
    var list = document.getElementById(targetId);
    var div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML =
      '<input type="text" class="form-control" value="' + escHtml(termName) + '" readonly>' +
      '<input type="hidden" name="' + fieldName + '" value="' + termId + '">' +
      '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
    list.appendChild(div);
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // Observe dynamically added actor-autocomplete inputs
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
      m.addedNodes.forEach(function(node) {
        if (node.nodeType !== 1) return;
        node.querySelectorAll && node.querySelectorAll('.actor-autocomplete').forEach(setupActorAutocomplete);
      });
    });
  });
  observer.observe(document.getElementById('editForm'), { childList: true, subtree: true });

})();
</script>

<?php end_slot(); ?>
