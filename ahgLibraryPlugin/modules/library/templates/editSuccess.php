<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo isset($resource->id) ? __('Edit %1%', ['%1%' => html_entity_decode($resource->title ?? '', ENT_QUOTES, 'UTF-8')]) : __('Add new library item'); ?></h1>
<?php end_slot(); ?>

<form method="post" action="<?php echo url_for(['module' => 'library', 'action' => 'edit', 'slug' => ($resource->slug ?? null)]); ?>" id="library-form">
<?php if ($sf_request->getParameter('parent')): ?>
<input type="hidden" name="parent" value="<?php echo $sf_request->getParameter('parent'); ?>">
<?php endif; ?>

  <div class="row">
    <div class="col-md-8">

      <!-- Basic Information -->
      <section class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="fas fa-book me-2"></i><?php echo __('Basic Information'); ?></h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label required"><?php echo __('Title'); ?></label>
            <input type="text" name="title" class="form-control" required
                   value="<?php echo esc_specialchars(html_entity_decode($resource->title ?? "", ENT_QUOTES, 'UTF-8')); ?>">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Subtitle'); ?></label>
              <input type="text" name="subtitle" class="form-control"
                     value="<?php echo esc_entities($libraryData['subtitle'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Identifier'); ?></label>
              <input type="text" name="identifier" class="form-control"
                     value="<?php echo esc_entities($resource->identifier ?? ''); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Statement of responsibility'); ?></label>
            <input type="text" name="responsibility_statement" class="form-control"
                   value="<?php echo esc_entities($libraryData['responsibility_statement'] ?? ''); ?>"
                   placeholder="e.g. by John Smith ; edited by Jane Doe">
            <div class="form-text"><?php echo __('Names and roles as they appear on the item'); ?></div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label required"><?php echo __('Level of description'); ?></label>
              <select name="level_of_description_id" class="form-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($levelOptions as $id => $name): ?>
                  <option value="<?php echo $id; ?>" <?php echo ($resource->levelOfDescriptionId ?? '') == $id ? 'selected' : ''; ?>>
                    <?php echo esc_entities($name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Material type'); ?></label>
              <select name="material_type" class="form-select">
                <option value="">-- Select --</option>
                <?php foreach ($materialTypes as $value => $label): ?>
                  <option value="<?php echo $value; ?>" <?php echo ($libraryData['material_type'] ?? '') == $value ? 'selected' : ''; ?>>
                    <?php echo esc_entities($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Language'); ?></label>
              <select name="language" class="form-select">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php foreach ($languageOptions as $code => $name): ?>
                  <option value="<?php echo $code; ?>" <?php echo ($libraryData['language'] ?? '') === $code ? 'selected' : ''; ?>>
                    <?php echo esc_entities($name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

        </div>
      </section>

      <!-- Creators/Authors -->
      <section class="card mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-users me-2"></i><?php echo __('Creators / Authors'); ?></h5>
          <button type="button" class="btn btn-sm btn-light" id="add-creator-btn">
            <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
          </button>
        </div>
        <div class="card-body">
          <div id="creators-container">
            <?php if (!empty($libraryData['creators'])): ?>
              <?php foreach ($libraryData['creators'] as $i => $creator): ?>
                <div class="row creator-row mb-2 align-items-center" data-index="<?php echo $i; ?>">
                  <div class="col-md-5">
                    <input type="text" name="creators[<?php echo $i; ?>][name]" class="form-control form-control-sm"
                           placeholder="<?php echo __('Name'); ?>" value="<?php echo esc_entities($creator['name'] ?? ''); ?>">
                  </div>
                  <div class="col-md-3">
                    <select name="creators[<?php echo $i; ?>][role]" class="form-select form-select-sm">
                      <option value="author" <?php echo ($creator['role'] ?? 'author') === 'author' ? 'selected' : ''; ?>><?php echo __('Author'); ?></option>
                      <option value="editor" <?php echo ($creator['role'] ?? '') === 'editor' ? 'selected' : ''; ?>><?php echo __('Editor'); ?></option>
                      <option value="translator" <?php echo ($creator['role'] ?? '') === 'translator' ? 'selected' : ''; ?>><?php echo __('Translator'); ?></option>
                      <option value="illustrator" <?php echo ($creator['role'] ?? '') === 'illustrator' ? 'selected' : ''; ?>><?php echo __('Illustrator'); ?></option>
                      <option value="compiler" <?php echo ($creator['role'] ?? '') === 'compiler' ? 'selected' : ''; ?>><?php echo __('Compiler'); ?></option>
                      <option value="contributor" <?php echo ($creator['role'] ?? '') === 'contributor' ? 'selected' : ''; ?>><?php echo __('Contributor'); ?></option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <input type="text" name="creators[<?php echo $i; ?>][authority_uri]" class="form-control form-control-sm"
                           placeholder="<?php echo __('Authority URI'); ?>" value="<?php echo esc_entities($creator['authority_uri'] ?? ''); ?>">
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-creator-btn w-100">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php if (empty($libraryData['creators'])): ?>
            <p class="text-muted small mb-0" id="no-creators-msg"><?php echo __('No creators added. Click "Add" or use ISBN lookup.'); ?></p>
          <?php endif; ?>
        </div>
      </section>

      <!-- Standard Identifiers -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-barcode me-2"></i><?php echo __('Standard Identifiers'); ?></h5>
        </div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-5 mb-3">
              <label class="form-label"><?php echo __('ISBN'); ?></label>
              <div class="input-group">
                <input type="text" name="isbn" id="isbn-input" class="form-control"
                       value="<?php echo esc_entities($libraryData['isbn'] ?? ''); ?>"
                       placeholder="978-0-123456-78-9">
                <button type="button" class="btn btn-primary" id="isbn-lookup" title="<?php echo __('Lookup ISBN and auto-fill form'); ?>">
                  <i class="fas fa-search me-1"></i><?php echo __('Lookup'); ?>
                </button>
              </div>
              <div class="form-text"><?php echo __('Enter ISBN and click Lookup to auto-fill'); ?></div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('ISSN'); ?></label>
              <input type="text" name="issn" class="form-control"
                     value="<?php echo esc_entities($libraryData['issn'] ?? ''); ?>"
                     placeholder="1234-5678">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('DOI'); ?></label>
              <input type="text" name="doi" class="form-control"
                     value="<?php echo esc_entities($libraryData['doi'] ?? ''); ?>"
                     placeholder="10.1000/xyz123">
            </div>
          </div>

          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('LCCN'); ?></label>
              <input type="text" name="lccn" class="form-control"
                     value="<?php echo esc_entities($libraryData['lccn'] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('OCLC Number'); ?></label>
              <input type="text" name="oclc_number" class="form-control"
                     value="<?php echo esc_entities($libraryData['oclc_number'] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Barcode'); ?></label>
              <input type="text" name="barcode" class="form-control"
                     value="<?php echo esc_entities($libraryData['barcode'] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Open Library ID'); ?></label>
              <input type="text" name="openlibrary_id" class="form-control"
                     value="<?php echo esc_entities($libraryData['openlibrary_id'] ?? ''); ?>"
                     placeholder="OL12345M">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Goodreads ID'); ?></label>
              <input type="text" name="goodreads_id" class="form-control"
                     value="<?php echo esc_entities($libraryData['goodreads_id'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('LibraryThing ID'); ?></label>
              <input type="text" name="librarything_id" class="form-control"
                     value="<?php echo esc_entities($libraryData['librarything_id'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Open Library URL'); ?></label>
              <div class="input-group">
                <input type="text" name="openlibrary_url" class="form-control"
                       value="<?php echo esc_entities($libraryData['openlibrary_url'] ?? ''); ?>">
                <?php if (!empty($libraryData['openlibrary_url'])): ?>
                  <a href="<?php echo esc_entities($libraryData['openlibrary_url']); ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="fas fa-external-link-alt"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- Classification -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Classification'); ?></h5>
        </div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Classification scheme'); ?></label>
              <select name="classification_scheme" class="form-select">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php foreach ($classificationSchemes as $value => $label): ?>
                  <option value="<?php echo $value; ?>" <?php echo ($libraryData['classification_scheme'] ?? '') == $value ? 'selected' : ''; ?>>
                    <?php echo esc_entities($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Call number (LC)'); ?></label>
              <input type="text" name="call_number" class="form-control"
                     value="<?php echo esc_entities($libraryData['call_number'] ?? ''); ?>"
                     placeholder="e.g. QA76.73.J38">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Dewey Decimal'); ?></label>
              <input type="text" name="dewey_decimal" class="form-control"
                     value="<?php echo esc_entities($libraryData['dewey_decimal'] ?? ''); ?>"
                     placeholder="e.g. 005.133">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Shelf location'); ?></label>
              <input type="text" name="shelf_location" class="form-control"
                     value="<?php echo esc_entities($libraryData['shelf_location'] ?? ''); ?>"
                     placeholder="e.g. Main Library, Floor 2, Section A">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Copy number'); ?></label>
              <input type="text" name="copy_number" class="form-control"
                     value="<?php echo esc_entities($libraryData['copy_number'] ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Volume'); ?></label>
              <input type="text" name="volume_designation" class="form-control"
                     value="<?php echo esc_entities($libraryData['volume_designation'] ?? ''); ?>">
            </div>
          </div>

        </div>
      </section>

      <!-- Publication Information -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-building me-2"></i><?php echo __('Publication Information'); ?></h5>
        </div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Publisher'); ?></label>
              <input type="text" name="publisher" class="form-control"
                     value="<?php echo esc_entities($libraryData['publisher'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Place of publication'); ?></label>
              <input type="text" name="publication_place" class="form-control"
                     value="<?php echo esc_entities($libraryData['publication_place'] ?? ''); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Publication date'); ?></label>
              <input type="text" name="publication_date" class="form-control"
                     value="<?php echo esc_entities($libraryData['publication_date'] ?? ''); ?>"
                     placeholder="e.g. 2023">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label"><?php echo __('Edition'); ?></label>
              <input type="text" name="edition" class="form-control"
                     value="<?php echo esc_entities($libraryData['edition'] ?? ''); ?>"
                     placeholder="e.g. 3rd ed.">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Edition statement'); ?></label>
              <input type="text" name="edition_statement" class="form-control"
                     value="<?php echo esc_entities($libraryData['edition_statement'] ?? ''); ?>"
                     placeholder="e.g. Revised and expanded">
            </div>
          </div>

          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label"><?php echo __('Series title'); ?></label>
              <input type="text" name="series_title" class="form-control"
                     value="<?php echo esc_entities($libraryData['series_title'] ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Series number'); ?></label>
              <input type="text" name="series_number" class="form-control"
                     value="<?php echo esc_entities($libraryData['series_number'] ?? ''); ?>"
                     placeholder="e.g. vol. 3">
            </div>
          </div>

        </div>
      </section>

      <!-- Physical Description -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-ruler me-2"></i><?php echo __('Physical Description'); ?></h5>
        </div>
        <div class="card-body">

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Pages'); ?></label>
              <input type="text" name="pagination" class="form-control"
                     value="<?php echo esc_entities($libraryData['pagination'] ?? ''); ?>"
                     placeholder="e.g. xiv, 350 p.">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Dimensions'); ?></label>
              <input type="text" name="dimensions" class="form-control"
                     value="<?php echo esc_entities($libraryData['dimensions'] ?? ''); ?>"
                     placeholder="e.g. 24 cm">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Physical details'); ?></label>
              <input type="text" name="physical_details" class="form-control"
                     value="<?php echo esc_entities($libraryData['physical_details'] ?? ''); ?>"
                     placeholder="e.g. ill., maps">
            </div>
          </div>

        </div>
      </section>

      <!-- Subjects -->
      <section class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-tags me-2"></i><?php echo __('Subjects'); ?></h5>
          <button type="button" class="btn btn-sm btn-light" id="add-subject-btn">
            <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
          </button>
        </div>
        <div class="card-body">
          <div id="subjects-container">
            <?php if (!empty($libraryData['subjects'])): ?>
              <?php foreach ($libraryData['subjects'] as $i => $subject): ?>
                <div class="row subject-row mb-2 align-items-center" data-index="<?php echo $i; ?>">
                  <div class="col-md-11">
                    <input type="text" name="subjects[<?php echo $i; ?>][heading]" class="form-control form-control-sm"
                           placeholder="<?php echo __('Subject heading'); ?>" value="<?php echo esc_entities($subject['heading'] ?? ''); ?>">
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn w-100">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <?php if (empty($libraryData['subjects'])): ?>
            <p class="text-muted small mb-0" id="no-subjects-msg"><?php echo __('No subjects added. Click "Add" or use ISBN lookup.'); ?></p>
          <?php endif; ?>
        </div>
      </section>

      <!-- Content/Summary -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-align-left me-2"></i><?php echo __('Content'); ?></h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label"><?php echo __('Summary / Abstract'); ?></label>
            <textarea name="summary" class="form-control" rows="4"><?php echo esc_entities($libraryData['summary'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Scope and content'); ?></label>
            <textarea name="scope_and_content" class="form-control" rows="3"><?php echo esc_entities($resource->getScopeAndContent(['cultureFallback' => true]) ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Table of contents'); ?></label>
            <textarea name="contents_note" class="form-control" rows="3"
                      placeholder="<?php echo __('Chapter listing or table of contents'); ?>"><?php echo esc_entities($libraryData['contents_note'] ?? ''); ?></textarea>
          </div>

        </div>
      </section>

      <!-- Notes -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Notes'); ?></h5>
        </div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label"><?php echo __('General note'); ?></label>
            <textarea name="general_note" class="form-control" rows="2"><?php echo esc_entities($libraryData['general_note'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Bibliography note'); ?></label>
            <textarea name="bibliography_note" class="form-control" rows="2"
                      placeholder="<?php echo __('e.g. Includes bibliographical references and index'); ?>"><?php echo esc_entities($libraryData['bibliography_note'] ?? ''); ?></textarea>
          </div>

        </div>
      </section>

    </div>

    <div class="col-md-4">

      <!-- Actions -->
      <section class="card mb-4 sticky-top" style="top: 1rem; z-index: 100;">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="fas fa-save me-2"></i><?php echo __('Actions'); ?></h5>
        </div>
        <div class="card-body">
          <button type="submit" class="btn btn-success w-100 mb-2">
            <i class="fas fa-save me-2"></i><?php echo __('Save'); ?>
          </button>

          <?php if (isset($resource->id)): ?>
            <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => ($resource->slug ?? '')]); ?>" class="btn btn-outline-secondary w-100 mb-2">
              <i class="fas fa-times me-2"></i><?php echo __('Cancel'); ?>
            </a>
          <?php else: ?>
            <a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100 mb-2">
              <i class="fas fa-times me-2"></i><?php echo __('Cancel'); ?>
            </a>
          <?php endif; ?>
        </div>
      </section>
      <!-- Digital Object / Cover -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-image me-2"></i><?php echo __('Cover / Digital Object'); ?></h5>
        </div>
        <div class="card-body text-center">
          <?php
            $digitalObject = isset($resource->id) ? $resource->getDigitalObject() : null;
            $isbn = $libraryData['isbn'] ?? '';
            $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
          ?>
          <?php if ($digitalObject): ?>
            <?php
              $mimeType = $digitalObject->mimeType ?? '';
              $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
              $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
              $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
              $refPath = $refObj ? $refObj->getFullPath() : null;
              $displayPath = $refPath ?: $thumbPath ?: $digitalObject->getFullPath();
              $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
              $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
              $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
              $refPath = $refObj ? $refObj->getFullPath() : null;
              $displayPath = $refPath ?: $thumbPath ?: $digitalObject->getFullPath();
              $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
              $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
              $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
              $refPath = $refObj ? $refObj->getFullPath() : null;
              $displayPath = $refPath ?: $thumbPath ?: $digitalObject->getFullPath();
              $thumbObj = $digitalObject->getRepresentationByUsage(QubitTerm::THUMBNAIL_ID);
              $refObj = $digitalObject->getRepresentationByUsage(QubitTerm::REFERENCE_ID);
              $thumbPath = $thumbObj ? $thumbObj->getFullPath() : null;
              $refPath = $refObj ? $refObj->getFullPath() : null;
              $displayPath = $refPath ?: $thumbPath ?: $digitalObject->getFullPath();
              if (strpos($mimeType, 'image') !== false && $displayPath): ?>
              <img src="<?php echo $displayPath; ?>" alt="Cover" class="img-fluid rounded shadow-sm mb-2" style="max-height: 200px;">
              <div class="mt-2">
                <a href="<?php echo url_for([$digitalObject, 'module' => 'digitalobject', 'action' => 'edit']); ?>" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
                </a>
              </div>
            <?php else: ?>
              <p class="text-muted mb-2"><?php echo $digitalObject->getName(); ?></p>
              <a href="<?php echo url_for([$digitalObject, 'module' => 'digitalobject', 'action' => 'edit']); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit me-1"></i><?php echo __('Edit digital object'); ?>
              </a>
            <?php endif; ?>
          <?php elseif (!empty($cleanIsbn)): ?>
            <div id="ol-cover-preview">
              <img src="/library/cover/<?php echo $cleanIsbn; ?>"
                   alt="Cover" class="img-fluid rounded shadow-sm mb-2" style="max-height: 200px;"
                   onerror="this.parentElement.innerHTML='<p class=\'text-muted\'>No Open Library cover found</p>'">
              <div class="mt-1"><small class="text-muted">Open Library Preview</small></div>
              <div class="mt-1"><small class="text-success"><i class="fas fa-info-circle me-1"></i>Will be saved to AtoM on save</small></div>
            </div>
            <?php if (isset($resource->id)): ?>
            <div class="mt-2">
              <a href="<?php echo url_for([$resource, 'module' => 'object', 'action' => 'addDigitalObject']); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-upload me-1"></i><?php echo __('Upload different cover'); ?>
              </a>
            </div>
            <?php endif; ?>
          <?php elseif (isset($resource->id)): ?>
            <p class="text-muted fst-italic mb-2"><?php echo __('Enter ISBN to preview Open Library cover'); ?></p>
            <a href="<?php echo url_for([$resource, 'module' => 'object', 'action' => 'addDigitalObject']); ?>" class="btn btn-sm btn-success">
              <i class="fas fa-upload me-1"></i><?php echo __('Upload cover'); ?>
            </a>
          <?php else: ?>
            <p class="text-muted fst-italic mb-0"><?php echo __('Save record first to upload cover'); ?></p>
          <?php endif; ?>
        </div>
      </section>
      <?php if (!empty($libraryData['ebook_preview_url'])): ?>
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-tablet-alt me-2"></i><?php echo __('E-book Access'); ?></h5>
        </div>
        <div class="card-body">
          <a href="<?php echo esc_entities($libraryData['ebook_preview_url']); ?>" target="_blank" class="btn btn-outline-primary w-100">
            <i class="fas fa-book-reader me-2"></i><?php echo __('Preview on Archive.org'); ?>
          </a>
          <input type="hidden" name="ebook_preview_url" value="<?php echo esc_entities($libraryData['ebook_preview_url']); ?>">
        </div>
      </section>
      <?php else: ?>
        <input type="hidden" name="ebook_preview_url" id="ebook-preview-url" value="">
      <?php endif; ?>

      <!-- Item Physical Location -->
      <?php if (file_exists(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_itemPhysicalLocation.php')) { include_partial('informationobject/itemPhysicalLocation', ['resource' => $resource, 'itemLocation' => $itemLocation]); } ?>
      <!-- Quick Links -->
      <section class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links'); ?></h5>
        </div>
        <div class="card-body">
          <a href="<?php echo url_for(['module' => 'library', 'action' => 'browse']); ?>" class="btn btn-outline-primary w-100 mb-2">
            <i class="fas fa-list me-2"></i><?php echo __('Browse library'); ?>
          </a>
          <?php if (isset($resource->id)): ?>
            <a href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'slug' => ($resource->slug ?? '')]); ?>" class="btn btn-outline-primary w-100 mb-2">
              <i class="fas fa-upload me-2"></i><?php echo __('Upload digital object'); ?>
            </a>
          <?php endif; ?>
        </div>
      </section>

    </div>
  </div>

</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    
    // ISBN Lookup
    var lookupBtn = document.getElementById('isbn-lookup');
    if (lookupBtn) {
        lookupBtn.addEventListener('click', async function() {
            var isbnInput = document.getElementById('isbn-input');
            var isbn = isbnInput.value.trim();
            if (!isbn) {
                alert('<?php echo __('Please enter an ISBN'); ?>');
                return;
            }

            var originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Looking up...'); ?>';

            try {
                var cleanIsbn = isbn.replace(/[\s-]/g, '');
                var response = await fetch('/index.php/ahgLibraryPlugin/isbnLookup?isbn=' + encodeURIComponent(cleanIsbn));
                var result = await response.json();
                

                if (result.success) {
                    var d = result.data;

                    // Extract all data
                    var title = d.title || '';
                    var authors = d.authors ? d.authors.map(function(a) { return {name: a.name, url: a.url}; }) : [];
                    var publisher = d.publishers && d.publishers[0] ? d.publishers[0].name : '';
                    var publishPlace = d.publish_places && d.publish_places[0] ? d.publish_places[0].name : '';
                    var date = d.publish_date || '';
                    var pages = d.number_of_pages || '';
                    var pagination = d.pagination || (pages ? pages + ' p.' : '');
                    var byStatement = d.by_statement || '';
                    var subjects = d.subjects ? d.subjects.slice(0, 10).map(function(s) { return {name: s.name, url: s.url}; }) : [];
                    var description = d.description || (result.preview && result.preview.description) || '';
                    var notes = d.notes || '';
                    var coverUrl = d.cover ? d.cover.medium : '';
                    var openLibraryUrl = d.url || '';
                    var openLibraryId = d.identifiers && d.identifiers.openlibrary ? d.identifiers.openlibrary[0] : '';
                    var lccn = d.identifiers && d.identifiers.lccn ? d.identifiers.lccn[0] : '';
                    var oclc = d.identifiers && d.identifiers.oclc ? d.identifiers.oclc[0] : '';
                    var goodreads = d.identifiers && d.identifiers.goodreads ? d.identifiers.goodreads[0] : '';
                    var librarything = d.identifiers && d.identifiers.librarything ? d.identifiers.librarything[0] : '';
                    var lcClass = d.classifications && d.classifications.lc_classifications ? d.classifications.lc_classifications[0] : '';
                    var dewey = d.classifications && d.classifications.dewey_decimal_class ? d.classifications.dewey_decimal_class[0] : '';
                    var ebookUrl = d.ebooks && d.ebooks[0] ? d.ebooks[0].preview_url : '';

                    var msg = '<?php echo __('Found'); ?>: ' + title + '\n';
                    if (authors.length) msg += '<?php echo __('By'); ?>: ' + authors.map(a => a.name).join(', ') + '\n';
                    if (publisher) msg += '<?php echo __('Publisher'); ?>: ' + publisher + '\n';
                    if (date) msg += '<?php echo __('Date'); ?>: ' + date + '\n';
                    if (pages) msg += '<?php echo __('Pages'); ?>: ' + pages + '\n';
                    if (subjects.length) msg += '<?php echo __('Subjects'); ?>: ' + subjects.slice(0,3).map(s => s.name).join(', ') + '...\n';
                    if (description) msg += '<?php echo __("Summary"); ?>: ' + description.substring(0, 150) + '...\n';
                    msg += '\n<?php echo __('Apply to form?'); ?>';

                    if (confirm(msg)) {
                        function setField(name, value) {
                            var field = document.querySelector('[name="' + name + '"]');
                            if (field && value) {
                                field.value = value;
                            }
                        }

                        // Fill all fields
                        setField('title', title);
                        setField('responsibility_statement', byStatement);
                        setField('publisher', publisher);
                        setField('publication_place', publishPlace);
                        setField('publication_date', date);
                        setField('pagination', pagination);
                        setField('lccn', lccn);
                        setField('oclc_number', oclc);
                        setField('openlibrary_id', openLibraryId);
                        setField('openlibrary_url', openLibraryUrl);
                        setField('goodreads_id', goodreads);
                        setField('librarything_id', librarything);
                        setField('call_number', lcClass);
                        setField('dewey_decimal', dewey);
                        setField('general_note', notes);
                        setField('summary', description);
                        setField('cover_url', coverUrl);
                        setField('ebook_preview_url', ebookUrl);

                        // Fill Authors with authority URIs
                        console.log("Authors data:", authors);
                        if (authors.length > 0) {
                            var container = document.getElementById('creators-container');
                            if (container) {
                                container.innerHTML = '';
                                var noMsg = document.getElementById('no-creators-msg');
                                if (noMsg) noMsg.remove();

                                authors.forEach(function(author, i) {
                                    var html = '<div class="row creator-row mb-2 align-items-center" data-index="' + i + '">' +
                                        '<div class="col-md-5"><input type="text" name="creators[' + i + '][name]" class="form-control form-control-sm" value="' + escapeHtml(author.name) + '"></div>' +
                                        '<div class="col-md-3"><select name="creators[' + i + '][role]" class="form-select form-select-sm"><option value="author" selected><?php echo __('Author'); ?></option><option value="editor"><?php echo __('Editor'); ?></option><option value="translator"><?php echo __('Translator'); ?></option></select></div>' +
                                        '<div class="col-md-3"><input type="text" name="creators[' + i + '][authority_uri]" class="form-control form-control-sm" value="' + escapeHtml(author.url || '') + '" placeholder="URI"></div>' +
                                        '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-creator-btn w-100"><i class="fas fa-times"></i></button></div></div>';
                                    container.insertAdjacentHTML('beforeend', html);
                                });
                                creatorIndex = authors.length;
                            }
                        }

                        // Fill Subjects with URIs
                        if (subjects.length > 0) {
                            var subContainer = document.getElementById('subjects-container');
                            if (subContainer) {
                                subContainer.innerHTML = '';
                                var noSubMsg = document.getElementById('no-subjects-msg');
                                if (noSubMsg) noSubMsg.remove();

                                subjects.forEach(function(subject, i) {
                                    var html = '<div class="row subject-row mb-2 align-items-center" data-index="' + i + '">' +
                                        '<div class="col-md-11"><input type="text" name="subjects[' + i + '][heading]" class="form-control form-control-sm" value="' + escapeHtml(subject.name) + '"></div>' +
                                        
                                        '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn w-100"><i class="fas fa-times"></i></button></div></div>';
                                    subContainer.insertAdjacentHTML('beforeend', html);
                                });
                                subjectIndex = subjects.length;
                            }
                        }

                        // Update cover preview
                        var coverPreview = document.getElementById('cover-preview');
                        if (coverPreview && coverUrl) {
                            coverPreview.innerHTML = 
                                '<img src="' + coverUrl + '" class="img-fluid rounded shadow-sm" style="max-height:250px">' +
                                '<div class="mt-2"><small class="text-muted">Open Library</small></div>' +
                                '<input type="hidden" name="cover_url" id="cover-url-input" value="' + escapeHtml(coverUrl) + '">';
                        }
                    }
                } else {
                    alert('<?php echo __('ISBN not found in Open Library'); ?>');
                }
            } catch (err) {
                console.error('ISBN lookup error:', err);
                alert('<?php echo __('Error looking up ISBN'); ?>: ' + err.message);
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    // Cover preview on ISBN blur
    var isbnInput = document.getElementById('isbn-input');
    if (isbnInput) {
        isbnInput.addEventListener('blur', function() {
            var isbn = this.value.replace(/[\s-]/g, '');
            if (isbn.length >= 10) {
                var coverPreview = document.getElementById('cover-preview');
                if (coverPreview) {
                    coverPreview.innerHTML =
                        '<img src="/library/cover/' + isbn + '" class="img-fluid rounded shadow-sm" style="max-height:250px" onerror="this.style.display=\'none\'">' +
                        '<div class="mt-2"><small class="text-muted">Open Library</small></div>' +
                        '<input type="hidden" name="cover_url" id="cover-url-input" value="">';
                }
            }
        });
    }

    // Creator management
    var creatorIndex = document.querySelectorAll('.creator-row').length;
    
    document.getElementById('add-creator-btn')?.addEventListener('click', function() {
        var container = document.getElementById('creators-container');
        var noMsg = document.getElementById('no-creators-msg');
        if (noMsg) noMsg.remove();

        var html = '<div class="row creator-row mb-2 align-items-center" data-index="' + creatorIndex + '">' +
            '<div class="col-md-5"><input type="text" name="creators[' + creatorIndex + '][name]" class="form-control form-control-sm" placeholder="<?php echo __('Name'); ?>"></div>' +
            '<div class="col-md-3"><select name="creators[' + creatorIndex + '][role]" class="form-select form-select-sm"><option value="author"><?php echo __('Author'); ?></option><option value="editor"><?php echo __('Editor'); ?></option><option value="translator"><?php echo __('Translator'); ?></option></select></div>' +
            '<div class="col-md-3"><input type="text" name="creators[' + creatorIndex + '][authority_uri]" class="form-control form-control-sm" placeholder="<?php echo __('Authority URI'); ?>"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-creator-btn w-100"><i class="fas fa-times"></i></button></div></div>';
        container.insertAdjacentHTML('beforeend', html);
        creatorIndex++;
        container.lastElementChild.querySelector('input').focus();
    });

    document.getElementById('creators-container')?.addEventListener('click', function(e) {
        if (e.target.closest('.remove-creator-btn')) {
            e.target.closest('.creator-row').remove();
        }
    });

    // Subject management
    var subjectIndex = document.querySelectorAll('.subject-row').length;
    
    document.getElementById('add-subject-btn')?.addEventListener('click', function() {
        var container = document.getElementById('subjects-container');
        var noMsg = document.getElementById('no-subjects-msg');
        if (noMsg) noMsg.remove();

        var html = '<div class="row subject-row mb-2 align-items-center" data-index="' + subjectIndex + '">' +
            '<div class="col-md-11"><input type="text" name="subjects[' + subjectIndex + '][heading]" class="form-control form-control-sm" placeholder="<?php echo __('Subject heading'); ?>"></div>' +
            
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-subject-btn w-100"><i class="fas fa-times"></i></button></div></div>';
        container.insertAdjacentHTML('beforeend', html);
        subjectIndex++;
        container.lastElementChild.querySelector('input').focus();
    });

    document.getElementById('subjects-container')?.addEventListener('click', function(e) {
        if (e.target.closest('.remove-subject-btn')) {
            e.target.closest('.subject-row').remove();
        }
    });

});
</script>
