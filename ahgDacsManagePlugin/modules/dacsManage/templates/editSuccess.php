<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new archival description') : __('Edit archival description'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($io['title'] ?: __('Untitled')); ?>
        <span class="badge bg-secondary ms-1">DACS</span>
      </span>
    <?php } else { ?>
      <span class="small" id="heading-label">
        <span class="badge bg-secondary">DACS</span>
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
  <?php $rawLangChoices = $sf_data->getRaw('languageChoices'); ?>
  <?php $rawScriptChoices = $sf_data->getRaw('scriptChoices'); ?>

  <form method="post" action="<?php echo $isNew ? url_for('@io_add_override') : url_for('@io_edit_override?slug=' . $rawIo['slug']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>
    <input type="hidden" name="parentId" value="<?php echo (int) $rawIo['parentId']; ?>">

    <div class="accordion mb-3" id="dacsAccordion">

      <!-- 1. Identity Elements -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse" aria-expanded="true" aria-controls="identity-collapse">
            <?php echo __('Identity elements'); ?>
          </button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse show" aria-labelledby="identity-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="identifier" class="form-label"><?php echo __('Identifier'); ?></label>
              <div class="input-group">
                <input type="text" class="form-control" id="identifier" name="identifier"
                       value="<?php echo esc_specialchars($rawIo['identifier']); ?>">
                <button type="button" class="btn btn-outline-secondary" id="generate-identifier"
                        data-url="<?php echo url_for('@io_generate_identifier'); ?>">
                  <i class="fas fa-cog me-1" aria-hidden="true"></i><?php echo __('Generate'); ?>
                </button>
              </div>
              <div class="form-text text-muted small">
                <?php echo __('Scheme: Archive Standard'); ?> <code>{REPO}/{FONDS}/{SEQ:4}</code>
                — <?php echo __('select a repository first, then click Generate'); ?>
              </div>
            </div>

            <!-- Alternative identifiers -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Alternative identifier(s)'); ?></label>
              <?php $rawAltIds = $rawIo['alternativeIdentifiers'] ?? []; ?>
              <table class="table table-sm" id="altids-table">
                <thead>
                  <tr>
                    <th><?php echo __('Label'); ?></th>
                    <th><?php echo __('Value'); ?></th>
                    <th style="width:80px"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($rawAltIds)) { ?>
                    <?php foreach ($rawAltIds as $aiIdx => $ai) { ?>
                      <tr>
                        <td><input type="text" class="form-control form-control-sm" name="altIds[<?php echo $aiIdx; ?>][label]" value="<?php echo esc_specialchars($ai->label ?? ''); ?>" placeholder="<?php echo __('e.g. Former reference'); ?>"></td>
                        <td><input type="text" class="form-control form-control-sm" name="altIds[<?php echo $aiIdx; ?>][value]" value="<?php echo esc_specialchars($ai->value ?? ''); ?>"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __('Remove'); ?></button></td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="add-altid-row"><?php echo __('Add alternative identifier'); ?></button>
            </div>

            <div class="mb-3">
              <label for="repositoryName" class="form-label"><?php echo __('Repository'); ?></label>
              <input type="text" class="form-control repository-autocomplete" id="repositoryName" name="repositoryName"
                     value="<?php echo esc_specialchars($rawIo['repositoryName'] ?? ''); ?>" placeholder="<?php echo __('Type to search...'); ?>">
              <input type="hidden" id="repositoryId" name="repositoryId" value="<?php echo (int) ($rawIo['repositoryId'] ?? 0); ?>">
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

            <div class="mb-3">
              <label for="extentAndMedium" class="form-label"><?php echo __('Extent'); ?></label>
              <textarea class="form-control" id="extentAndMedium" name="extentAndMedium" rows="3"><?php echo esc_specialchars($rawIo['extentAndMedium']); ?></textarea>
            </div>

            <!-- Name of creator(s) -->
            <input type="hidden" name="_creatorsIncluded" value="1">
            <div class="mb-3">
              <label class="form-label"><?php echo __('Name of creator(s)'); ?></label>
              <?php $rawCreators = $rawIo['creators'] ?? []; ?>
              <div id="creator-list">
                <?php if (!empty($rawCreators)) { ?>
                  <?php foreach ($rawCreators as $cIdx => $creator) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <input type="text" class="form-control" value="<?php echo esc_specialchars($creator->actor_name ?? ''); ?>" readonly>
                      <input type="hidden" name="creators[<?php echo $cIdx; ?>][actorId]" value="<?php echo (int) ($creator->actor_id ?? 0); ?>">
                      <input type="hidden" name="creators[<?php echo $cIdx; ?>][actorName]" value="<?php echo esc_specialchars($creator->actor_name ?? ''); ?>">
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input type="text" class="form-control actor-autocomplete-add" data-target="creator-list" data-field="creators" placeholder="<?php echo __('Type to add creator...'); ?>">
              </div>
            </div>

            <!-- Add new child levels (edit only) -->
            <?php if (!$isNew) { ?>
              <div class="mb-3">
                <label class="form-label"><?php echo __('Add new child levels'); ?></label>
                <table class="table table-sm" id="childlevels-table">
                  <thead>
                    <tr>
                      <th><?php echo __('Identifier'); ?></th>
                      <th><?php echo __('Level'); ?></th>
                      <th><?php echo __('Title'); ?></th>
                      <th style="width:80px"></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-childlevel-row"><?php echo __('Add child level'); ?></button>
              </div>
            <?php } ?>

          </div>
        </div>
      </div>

      <!-- 2. Content and Structure Elements -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="content-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#content-collapse" aria-expanded="false" aria-controls="content-collapse">
            <?php echo __('Content and structure elements'); ?>
          </button>
        </h2>
        <div id="content-collapse" class="accordion-collapse collapse" aria-labelledby="content-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="scopeAndContent" class="form-label"><?php echo __('Scope and content'); ?></label>
              <textarea class="form-control" id="scopeAndContent" name="scopeAndContent" rows="4"><?php echo esc_specialchars($rawIo['scopeAndContent']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="arrangement" class="form-label"><?php echo __('System of arrangement'); ?></label>
              <textarea class="form-control" id="arrangement" name="arrangement" rows="3"><?php echo esc_specialchars($rawIo['arrangement']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 3. Conditions of Access and Use Elements -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="access-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#access-collapse" aria-expanded="false" aria-controls="access-collapse">
            <?php echo __('Conditions of access and use elements'); ?>
          </button>
        </h2>
        <div id="access-collapse" class="accordion-collapse collapse" aria-labelledby="access-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="accessConditions" class="form-label"><?php echo __('Conditions governing access'); ?></label>
              <textarea class="form-control" id="accessConditions" name="accessConditions" rows="3"><?php echo esc_specialchars($rawIo['accessConditions']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="physicalCharacteristics" class="form-label"><?php echo __('Physical access'); ?></label>
              <textarea class="form-control" id="physicalCharacteristics" name="physicalCharacteristics" rows="3"><?php echo esc_specialchars($rawIo['physicalCharacteristics']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="technicalAccess" class="form-label"><?php echo __('Technical access'); ?></label>
              <textarea class="form-control" id="technicalAccess" name="technicalAccess" rows="3"><?php echo esc_specialchars($rawIo['stringProperties']['technicalAccess'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="reproductionConditions" class="form-label"><?php echo __('Conditions governing reproduction'); ?></label>
              <textarea class="form-control" id="reproductionConditions" name="reproductionConditions" rows="3"><?php echo esc_specialchars($rawIo['reproductionConditions']); ?></textarea>
            </div>

            <!-- Language(s) of material -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Language(s) of material'); ?></label>
              <?php $rawLangs = $rawIo['languages'] ?? []; ?>
              <div id="languages-list">
                <?php if (!empty($rawLangs)) { ?>
                  <?php foreach ($rawLangs as $lIdx => $langCode) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <select class="form-select form-select-sm" name="languages[]">
                        <?php foreach ($rawLangChoices as $code => $name) { ?>
                          <option value="<?php echo $code; ?>" <?php echo ($code === $langCode) ? 'selected' : ''; ?>><?php echo esc_specialchars($name); ?></option>
                        <?php } ?>
                      </select>
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary btn-add-lang-row" data-target="languages-list" data-name="languages[]"><?php echo __('Add language'); ?></button>
            </div>

            <!-- Script(s) of material -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Script(s) of material'); ?></label>
              <?php $rawScripts = $rawIo['scripts'] ?? []; ?>
              <div id="scripts-list">
                <?php if (!empty($rawScripts)) { ?>
                  <?php foreach ($rawScripts as $sIdx => $scriptCode) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <select class="form-select form-select-sm" name="scripts[]">
                        <?php foreach ($rawScriptChoices as $code => $name) { ?>
                          <option value="<?php echo $code; ?>" <?php echo ($code === $scriptCode) ? 'selected' : ''; ?>><?php echo esc_specialchars($name); ?></option>
                        <?php } ?>
                      </select>
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary btn-add-script-row" data-target="scripts-list" data-name="scripts[]"><?php echo __('Add script'); ?></button>
            </div>

            <!-- Language and script notes -->
            <div class="mb-3">
              <label for="languageNotes" class="form-label"><?php echo __('Language and script notes'); ?></label>
              <textarea class="form-control" id="languageNotes" name="languageNotes" rows="3"><?php echo esc_specialchars($rawIo['languageNotes'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="findingAids" class="form-label"><?php echo __('Finding aids'); ?></label>
              <textarea class="form-control" id="findingAids" name="findingAids" rows="3"><?php echo esc_specialchars($rawIo['findingAids']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 4. Acquisition and Appraisal Elements -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="acquisition-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acquisition-collapse" aria-expanded="false" aria-controls="acquisition-collapse">
            <?php echo __('Acquisition and appraisal elements'); ?>
          </button>
        </h2>
        <div id="acquisition-collapse" class="accordion-collapse collapse" aria-labelledby="acquisition-heading">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="archivalHistory" class="form-label"><?php echo __('Custodial history'); ?></label>
              <textarea class="form-control" id="archivalHistory" name="archivalHistory" rows="3"><?php echo esc_specialchars($rawIo['archivalHistory']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="acquisition" class="form-label"><?php echo __('Immediate source of acquisition'); ?></label>
              <textarea class="form-control" id="acquisition" name="acquisition" rows="3"><?php echo esc_specialchars($rawIo['acquisition']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="appraisal" class="form-label"><?php echo __('Appraisal, destruction and scheduling'); ?></label>
              <textarea class="form-control" id="appraisal" name="appraisal" rows="3"><?php echo esc_specialchars($rawIo['appraisal']); ?></textarea>
            </div>

            <div class="mb-3">
              <label for="accruals" class="form-label"><?php echo __('Accruals'); ?></label>
              <textarea class="form-control" id="accruals" name="accruals" rows="3"><?php echo esc_specialchars($rawIo['accruals']); ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 5. Related Materials Elements -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="related-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#related-collapse" aria-expanded="false" aria-controls="related-collapse">
            <?php echo __('Related materials elements'); ?>
          </button>
        </h2>
        <div id="related-collapse" class="accordion-collapse collapse" aria-labelledby="related-heading">
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

            <!-- Publication notes -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Publication notes'); ?></label>
              <?php $rawPubNotes = $rawIo['publicationNotes'] ?? []; ?>
              <div id="pubnotes-list">
                <?php if (!empty($rawPubNotes)) { ?>
                  <?php foreach ($rawPubNotes as $pnIdx => $pn) { ?>
                    <div class="mb-1">
                      <div class="input-group input-group-sm">
                        <textarea class="form-control form-control-sm" name="publicationNotes[<?php echo $pnIdx; ?>][content]" rows="2"><?php echo esc_specialchars($pn->content ?? ''); ?></textarea>
                        <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                      </div>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="add-pubnote-row"><?php echo __('Add publication note'); ?></button>
            </div>

          </div>
        </div>
      </div>

      <!-- 6. Notes Element -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="notes-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#notes-collapse" aria-expanded="false" aria-controls="notes-collapse">
            <?php echo __('Notes element'); ?>
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

      <!-- 7. Description Control Element -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="control-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#control-collapse" aria-expanded="false" aria-controls="control-collapse">
            <?php echo __('Description control element'); ?>
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

            <!-- Language(s) of description -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Language(s) of description'); ?></label>
              <?php $rawLangsOfDesc = $rawIo['languagesOfDescription'] ?? []; ?>
              <div id="langs-of-desc-list">
                <?php if (!empty($rawLangsOfDesc)) { ?>
                  <?php foreach ($rawLangsOfDesc as $ldCode) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <select class="form-select form-select-sm" name="languagesOfDescription[]">
                        <?php foreach ($rawLangChoices as $code => $name) { ?>
                          <option value="<?php echo $code; ?>" <?php echo ($code === $ldCode) ? 'selected' : ''; ?>><?php echo esc_specialchars($name); ?></option>
                        <?php } ?>
                      </select>
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary btn-add-lang-row" data-target="langs-of-desc-list" data-name="languagesOfDescription[]"><?php echo __('Add language'); ?></button>
            </div>

            <!-- Script(s) of description -->
            <div class="mb-3">
              <label class="form-label"><?php echo __('Script(s) of description'); ?></label>
              <?php $rawScriptsOfDesc = $rawIo['scriptsOfDescription'] ?? []; ?>
              <div id="scripts-of-desc-list">
                <?php if (!empty($rawScriptsOfDesc)) { ?>
                  <?php foreach ($rawScriptsOfDesc as $sdCode) { ?>
                    <div class="input-group input-group-sm mb-1">
                      <select class="form-select form-select-sm" name="scriptsOfDescription[]">
                        <?php foreach ($rawScriptChoices as $code => $name) { ?>
                          <option value="<?php echo $code; ?>" <?php echo ($code === $sdCode) ? 'selected' : ''; ?>><?php echo esc_specialchars($name); ?></option>
                        <?php } ?>
                      </select>
                      <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary btn-add-script-row" data-target="scripts-of-desc-list" data-name="scriptsOfDescription[]"><?php echo __('Add script'); ?></button>
            </div>

            <!-- Archivist's notes -->
            <div class="mb-3">
              <label class="form-label"><?php echo __("Archivist's notes"); ?></label>
              <?php $rawArchNotes = $rawIo['archivistNotes'] ?? []; ?>
              <div id="archnotes-list">
                <?php if (!empty($rawArchNotes)) { ?>
                  <?php foreach ($rawArchNotes as $anIdx => $an) { ?>
                    <div class="mb-1">
                      <div class="input-group input-group-sm">
                        <textarea class="form-control form-control-sm" name="archivistNotes[<?php echo $anIdx; ?>][content]" rows="2"><?php echo esc_specialchars($an->content ?? ''); ?></textarea>
                        <button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __('Remove'); ?></button>
                      </div>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="add-archnote-row"><?php echo __("Add archivist's note"); ?></button>
            </div>

          </div>
        </div>
      </div>

      <!-- 8. Access Points -->
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

    </div>

    <!-- Admin area -->
    <div class="card mb-3">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Administration area'); ?></h5></div>
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

        <!-- Display standard -->
        <?php $rawDisplayStandards = $sf_data->getRaw('displayStandards'); ?>
        <div class="mb-3">
          <label for="displayStandardId" class="form-label"><?php echo __('Display standard'); ?></label>
          <select class="form-select" id="displayStandardId" name="displayStandardId">
            <option value=""><?php echo __('- Use global default -'); ?></option>
            <?php foreach ($rawDisplayStandards as $ds) { ?>
              <option value="<?php echo $ds->id; ?>"
                      <?php echo ($ds->id == ($rawIo['displayStandardId'] ?? '')) ? 'selected' : ''; ?>>
                <?php echo esc_specialchars($ds->name ?? ''); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <?php if (!$isNew) { ?>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="updateDescendants" name="updateDescendants" value="1">
            <label class="form-check-label" for="updateDescendants">
              <?php echo __('Make this the default for existing children'); ?>
            </label>
          </div>
        <?php } ?>

        <!-- Source language (read-only) -->
        <div class="mb-3">
          <label class="form-label"><?php echo __('Source language'); ?></label>
          <p class="form-control-plaintext"><?php echo esc_specialchars($rawIo['sourceCulture'] ?? ''); ?></p>
        </div>

        <!-- Last updated (read-only) -->
        <?php if (!$isNew && !empty($rawIo['updatedAt'])) { ?>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Last updated'); ?></label>
            <p class="form-control-plaintext"><?php echo esc_specialchars($rawIo['updatedAt']); ?></p>
          </div>
        <?php } ?>

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

  // -- AJAX endpoint URLs ------------------------------------------------
  var ACTOR_AC_URL = '<?php echo url_for("@io_actor_autocomplete"); ?>';
  var REPO_AC_URL = '<?php echo url_for("@io_repository_autocomplete"); ?>';
  var TERM_AC_URL = '<?php echo url_for("@io_term_autocomplete"); ?>';

  // -- Utility -----------------------------------------------------------
  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // -- Generic dropdown helper -------------------------------------------
  function showDropdown(input, results, onSelect) {
    var dropdown = document.createElement('div');
    dropdown.className = 'list-group position-absolute w-100 ac-dropdown';
    dropdown.style.zIndex = '1050';
    results.forEach(function(item) {
      var a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action py-1 small';
      a.href = '#';
      a.textContent = item.name || '';
      a.addEventListener('click', function(e) {
        e.preventDefault();
        onSelect(item);
        removeDropdownsFor(input);
      });
      dropdown.appendChild(a);
    });
    input.parentNode.style.position = 'relative';
    removeDropdownsFor(input);
    input.parentNode.appendChild(dropdown);
  }

  function removeDropdownsFor(input) {
    var existing = input.parentNode.querySelectorAll('.ac-dropdown');
    existing.forEach(function(el) { el.remove(); });
  }

  // Global click to close dropdowns
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.ac-dropdown') && !e.target.classList.contains('actor-autocomplete')
        && !e.target.classList.contains('actor-autocomplete-add')
        && !e.target.classList.contains('repository-autocomplete')
        && !e.target.classList.contains('term-autocomplete-add')) {
      document.querySelectorAll('.ac-dropdown').forEach(function(d) { d.remove(); });
    }
  });

  // -- Debounced fetch helper --------------------------------------------
  function setupAutocomplete(input, buildUrl, onSelect) {
    var timeout = null;
    input.addEventListener('input', function() {
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { removeDropdownsFor(input); return; }
      timeout = setTimeout(function() {
        fetch(buildUrl(q))
          .then(function(r) { return r.json(); })
          .then(function(results) {
            if (!results || !results.length) { removeDropdownsFor(input); return; }
            showDropdown(input, results, onSelect);
          })
          .catch(function() { removeDropdownsFor(input); });
      }, 300);
    });
  }

  // -- Generate identifier button ----------------------------------------
  var genBtn = document.getElementById('generate-identifier');
  if (genBtn) {
    genBtn.addEventListener('click', function() {
      var repoId = document.getElementById('repositoryId').value || '0';
      var parentId = document.querySelector('input[name="parentId"]').value || '0';
      if (!repoId || repoId === '0') {
        alert('<?php echo __("Please select a repository first."); ?>');
        return;
      }
      var url = genBtn.getAttribute('data-url')
        + '?repositoryId=' + encodeURIComponent(repoId)
        + '&parentId=' + encodeURIComponent(parentId);
      genBtn.disabled = true;
      fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.error) {
            alert(data.error);
          } else if (data.identifier) {
            document.getElementById('identifier').value = data.identifier;
          } else {
            alert('<?php echo __("Could not generate identifier."); ?>');
          }
        })
        .catch(function() { alert('<?php echo __("Failed to generate identifier."); ?>'); })
        .finally(function() { genBtn.disabled = false; });
    });
  }

  // -- Event rows --------------------------------------------------------
  var eventsBody = document.querySelector('#events-table tbody');
  var addEventBtn = document.getElementById('add-event-row');
  var eventIdx = eventsBody ? eventsBody.querySelectorAll('tr').length : 0;

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
      initActorAutocomplete(tr.querySelector('.actor-autocomplete'));
      eventIdx++;
    });
  }

  // -- Note rows ---------------------------------------------------------
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

  // -- Alternative identifier rows ---------------------------------------
  var altIdsBody = document.querySelector('#altids-table tbody');
  var addAltIdBtn = document.getElementById('add-altid-row');
  var altIdIdx = altIdsBody ? altIdsBody.querySelectorAll('tr').length : 0;

  if (addAltIdBtn) {
    addAltIdBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="text" class="form-control form-control-sm" name="altIds[' + altIdIdx + '][label]" placeholder="<?php echo __("e.g. Former reference"); ?>"></td>' +
        '<td><input type="text" class="form-control form-control-sm" name="altIds[' + altIdIdx + '][value]"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      altIdsBody.appendChild(tr);
      altIdIdx++;
    });
  }

  // -- Child level rows --------------------------------------------------
  var childBody = document.querySelector('#childlevels-table tbody');
  var addChildBtn = document.getElementById('add-childlevel-row');
  var childIdx = 0;

  var levelOptions = '<option value=""><?php echo __("- Select -"); ?></option>';
  <?php foreach ($rawLevels as $lvl) { ?>
  levelOptions += '<option value="<?php echo $lvl->id; ?>"><?php echo esc_specialchars($lvl->name ?? ""); ?></option>';
  <?php } ?>

  if (addChildBtn) {
    addChildBtn.addEventListener('click', function() {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><input type="text" class="form-control form-control-sm" name="childLevels[' + childIdx + '][identifier]" placeholder="<?php echo __("Identifier"); ?>"></td>' +
        '<td><select class="form-select form-select-sm" name="childLevels[' + childIdx + '][levelOfDescriptionId]">' + levelOptions + '</select></td>' +
        '<td><input type="text" class="form-control form-control-sm" name="childLevels[' + childIdx + '][title]" placeholder="<?php echo __("Title"); ?>"></td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><?php echo __("Remove"); ?></button></td>';
      childBody.appendChild(tr);
      childIdx++;
    });
  }

  // -- Publication note rows ---------------------------------------------
  var pubNotesList = document.getElementById('pubnotes-list');
  var addPubNoteBtn = document.getElementById('add-pubnote-row');
  var pubNoteIdx = pubNotesList ? pubNotesList.querySelectorAll('.input-group').length : 0;

  if (addPubNoteBtn) {
    addPubNoteBtn.addEventListener('click', function() {
      var wrapper = document.createElement('div');
      wrapper.className = 'mb-1';
      wrapper.innerHTML =
        '<div class="input-group input-group-sm">' +
        '<textarea class="form-control form-control-sm" name="publicationNotes[' + pubNoteIdx + '][content]" rows="2"></textarea>' +
        '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>' +
        '</div>';
      pubNotesList.appendChild(wrapper);
      pubNoteIdx++;
    });
  }

  // -- Archivist note rows -----------------------------------------------
  var archNotesList = document.getElementById('archnotes-list');
  var addArchNoteBtn = document.getElementById('add-archnote-row');
  var archNoteIdx = archNotesList ? archNotesList.querySelectorAll('.input-group').length : 0;

  if (addArchNoteBtn) {
    addArchNoteBtn.addEventListener('click', function() {
      var wrapper = document.createElement('div');
      wrapper.className = 'mb-1';
      wrapper.innerHTML =
        '<div class="input-group input-group-sm">' +
        '<textarea class="form-control form-control-sm" name="archivistNotes[' + archNoteIdx + '][content]" rows="2"></textarea>' +
        '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>' +
        '</div>';
      archNotesList.appendChild(wrapper);
      archNoteIdx++;
    });
  }

  // -- Language/script dropdown rows -------------------------------------
  var langOptions = '';
  <?php foreach ($rawLangChoices as $code => $name) { ?>
  langOptions += '<option value="<?php echo $code; ?>"><?php echo esc_specialchars($name); ?></option>';
  <?php } ?>

  var scriptOptions = '';
  <?php foreach ($rawScriptChoices as $code => $name) { ?>
  scriptOptions += '<option value="<?php echo $code; ?>"><?php echo esc_specialchars($name); ?></option>';
  <?php } ?>

  document.querySelectorAll('.btn-add-lang-row').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var list = document.getElementById(btn.getAttribute('data-target'));
      var div = document.createElement('div');
      div.className = 'input-group input-group-sm mb-1';
      div.innerHTML =
        '<select class="form-select form-select-sm" name="' + btn.getAttribute('data-name') + '">' + langOptions + '</select>' +
        '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
      list.appendChild(div);
    });
  });

  document.querySelectorAll('.btn-add-script-row').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var list = document.getElementById(btn.getAttribute('data-target'));
      var div = document.createElement('div');
      div.className = 'input-group input-group-sm mb-1';
      div.innerHTML =
        '<select class="form-select form-select-sm" name="' + btn.getAttribute('data-name') + '">' + scriptOptions + '</select>' +
        '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
      list.appendChild(div);
    });
  });

  // -- Remove row delegation ---------------------------------------------
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-row')) {
      var tr = e.target.closest('tr');
      if (tr) tr.remove();
    }
    if (e.target.classList.contains('btn-remove-ap')) {
      var group = e.target.closest('.input-group');
      var wrapper = group ? group.closest('.mb-1') : null;
      if (wrapper && wrapper.parentNode.id) {
        wrapper.remove();
      } else if (group) {
        group.remove();
      }
    }
  });

  // -- Actor autocomplete (events -- inline, sets hidden actorId) --------
  function initActorAutocomplete(input) {
    setupAutocomplete(input,
      function(q) { return ACTOR_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        input.value = item.name;
        var hiddenId = input.parentNode.querySelector('input[type=hidden]');
        if (hiddenId) hiddenId.value = item.id;
      }
    );
  }

  document.querySelectorAll('.actor-autocomplete').forEach(initActorAutocomplete);

  // -- Repository autocomplete -------------------------------------------
  var repoInput = document.getElementById('repositoryName');
  if (repoInput) {
    setupAutocomplete(repoInput,
      function(q) { return REPO_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        repoInput.value = item.name;
        document.getElementById('repositoryId').value = item.id;
      }
    );
  }

  // -- Name access point / Creator add -----------------------------------
  var nameApIdx = document.querySelectorAll('#name-ap-list .input-group').length;
  var creatorIdx = document.querySelectorAll('#creator-list .input-group').length;

  document.querySelectorAll('.actor-autocomplete-add').forEach(function(input) {
    var targetId = input.getAttribute('data-target');
    var fieldType = input.getAttribute('data-field');

    setupAutocomplete(input,
      function(q) { return ACTOR_AC_URL + '?query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        if (fieldType === 'creators') {
          addCreator(targetId, item.id, item.name);
        } else {
          addNameAP(targetId, item.id, item.name);
        }
        input.value = '';
      }
    );
  });

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

  function addCreator(targetId, actorId, actorName) {
    var list = document.getElementById(targetId);
    var div = document.createElement('div');
    div.className = 'input-group input-group-sm mb-1';
    div.innerHTML =
      '<input type="text" class="form-control" value="' + escHtml(actorName) + '" readonly>' +
      '<input type="hidden" name="creators[' + creatorIdx + '][actorId]" value="' + actorId + '">' +
      '<input type="hidden" name="creators[' + creatorIdx + '][actorName]" value="' + escHtml(actorName) + '">' +
      '<button type="button" class="btn btn-outline-danger btn-remove-ap"><?php echo __("Remove"); ?></button>';
    list.appendChild(div);
    creatorIdx++;
  }

  // -- Term access point add (subject, place, genre) ---------------------
  document.querySelectorAll('.term-autocomplete-add').forEach(function(input) {
    var taxonomy = input.getAttribute('data-taxonomy');
    var targetId = input.getAttribute('data-target');
    var fieldName = input.getAttribute('data-name');

    setupAutocomplete(input,
      function(q) { return TERM_AC_URL + '?taxonomy=' + taxonomy + '&query=' + encodeURIComponent(q) + '&limit=10'; },
      function(item) {
        addTermAP(targetId, fieldName, item.id, item.name);
        input.value = '';
      }
    );
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

})();
</script>

<?php end_slot(); ?>
