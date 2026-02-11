@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">

    @php
    // Decode JSON arrays for checkboxes
    $selectedEntityTypes = json_decode($settings['ner_entity_types'] ?? '[]', true) ?: [];
    $selectedSpellcheckFields = json_decode($settings['spellcheck_fields'] ?? '[]', true) ?: [];
    $selectedTranslationFields = json_decode($settings['translation_fields'] ?? '["title","scope_and_content"]', true) ?: [];
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="mb-0"><i class="fas fa-brain text-primary"></i> AI Services Settings</h1>
      <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>{{ __('Back to AHG Settings') }}
      </a>
    </div>

    <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'aiServices']) }}">

    <!-- General Settings -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>General Settings</h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Processing Mode</label>
          <div class="col-sm-9">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="processing_mode" id="mode_hybrid" value="hybrid"
                {{ ($settings['processing_mode'] ?? 'job') === 'hybrid' ? 'checked' : '' }}>
              <label class="form-check-label" for="mode_hybrid">
                <strong>Hybrid</strong> - Interactive for small docs, background for large
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="processing_mode" id="mode_job" value="job"
                {{ ($settings['processing_mode'] ?? 'job') === 'job' ? 'checked' : '' }}>
              <label class="form-check-label" for="mode_job">
                <strong>Background Job</strong> - Always queue via Gearman (recommended for production)
              </label>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">API Endpoint</label>
          <div class="col-sm-6">
            <input type="text" class="form-control" name="api_url"
              value="{{ htmlspecialchars($settings['api_url'] ?? '') }}">
            <small class="text-muted">URL of the AI service (e.g., http://localhost:5004/ai/v1)</small>
          </div>
          <div class="col-sm-3">
            <button type="button" class="btn btn-outline-info" onclick="testConnection()">
              <i class="fas fa-plug me-1"></i>Test Connection
            </button>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">API Key</label>
          <div class="col-sm-6">
            <input type="password" class="form-control" name="api_key" id="api_key"
              value="{{ htmlspecialchars($settings['api_key'] ?? '') }}">
          </div>
          <div class="col-sm-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePassword()">
              <i class="fas fa-eye"></i> Show
            </button>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Timeout (seconds)</label>
          <div class="col-sm-3">
            <input type="number" class="form-control" name="api_timeout" min="10" max="300"
              value="{{ htmlspecialchars($settings['api_timeout'] ?? '60') }}">
          </div>
        </div>
      </div>
    </div>

    <!-- NER Settings -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Named Entity Recognition (NER)</h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="ner_enabled" id="ner_enabled" value="1"
                {{ ($settings['ner_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-bold" for="ner_enabled">Enable NER</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">Extract people, organizations, places, and dates from records</small>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="auto_extract_on_upload" id="auto_extract" value="1"
                {{ ($settings['auto_extract_on_upload'] ?? '0') === '1' ? 'checked' : '' }}>
              <label class="form-check-label" for="auto_extract">Auto-extract on upload</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">Automatically queue NER job when documents are uploaded</small>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Entity Types</label>
          <div class="col-sm-9">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="entity_PERSON" id="entity_person" value="1"
                {{ in_array('PERSON', $selectedEntityTypes) ? 'checked' : '' }}>
              <label class="form-check-label" for="entity_person"><i class="fas fa-user me-1"></i>People</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="entity_ORG" id="entity_org" value="1"
                {{ in_array('ORG', $selectedEntityTypes) ? 'checked' : '' }}>
              <label class="form-check-label" for="entity_org"><i class="fas fa-building me-1"></i>Organizations</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="entity_GPE" id="entity_gpe" value="1"
                {{ in_array('GPE', $selectedEntityTypes) ? 'checked' : '' }}>
              <label class="form-check-label" for="entity_gpe"><i class="fas fa-map-marker-alt me-1"></i>Places</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="entity_DATE" id="entity_date" value="1"
                {{ in_array('DATE', $selectedEntityTypes) ? 'checked' : '' }}>
              <label class="form-check-label" for="entity_date"><i class="fas fa-calendar me-1"></i>Dates</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summarization Settings -->
    <div class="card mb-4">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Summarization</h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="summarizer_enabled" id="summarizer_enabled" value="1"
                {{ ($settings['summarizer_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-bold" for="summarizer_enabled">Enable Summarization</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">Generate summaries from document content</small>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Save Summary To</label>
          <div class="col-sm-6">
            <select class="form-select" name="summary_field">
              @foreach ($summaryFields as $value => $label)
              <option value="{{ $value }}" {{ ($settings['summary_field'] ?? 'scopeAndContent') === $value ? 'selected' : '' }}>
                {{ __($label) }}
              </option>
              @endforeach
            </select>
            <small class="text-muted">Which ISAD(G) field to populate with the generated summary</small>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Summary Length</label>
          <div class="col-sm-3">
            <div class="input-group">
              <span class="input-group-text">Min</span>
              <input type="number" class="form-control" name="summarizer_min_length" min="50" max="500"
                value="{{ htmlspecialchars($settings['summarizer_min_length'] ?? '100') }}">
            </div>
          </div>
          <div class="col-sm-3">
            <div class="input-group">
              <span class="input-group-text">Max</span>
              <input type="number" class="form-control" name="summarizer_max_length" min="100" max="2000"
                value="{{ htmlspecialchars($settings['summarizer_max_length'] ?? '500') }}">
            </div>
          </div>
          <div class="col-sm-3">
            <small class="text-muted">Characters</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Spell Check Settings -->
    <div class="card mb-4">
      <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-spell-check me-2"></i>Spell Check</h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="spellcheck_enabled" id="spellcheck_enabled" value="1"
                {{ ($settings['spellcheck_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-bold" for="spellcheck_enabled">Enable Spell Check</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">Check spelling in metadata fields</small>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Language</label>
          <div class="col-sm-6">
            <select class="form-select" name="spellcheck_language">
              @foreach ($spellcheckLanguages as $code => $label)
              <option value="{{ $code }}" {{ ($settings['spellcheck_language'] ?? 'en_ZA') === $code ? 'selected' : '' }}>
                {{ $label }}
              </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Check Fields</label>
          <div class="col-sm-9">
            @foreach ($spellcheckFields as $field => $label)
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="spellcheck_field_{{ $field }}"
                id="spellcheck_{{ $field }}" value="1"
                {{ in_array($field, $selectedSpellcheckFields) ? 'checked' : '' }}>
              <label class="form-check-label" for="spellcheck_{{ $field }}">{{ __($label) }}</label>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <!-- Machine Translation Settings -->
    <div class="card mb-4">
      <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-language me-2"></i>Machine Translation (OPUS-MT)</h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="translation_enabled" id="translation_enabled" value="1"
                {{ ($settings['translation_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-bold" for="translation_enabled">Enable Translation</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">Translate record metadata using OPUS-MT (offline, on-premise)</small>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">OPUS-MT Endpoint</label>
          <div class="col-sm-6">
            <input type="text" class="form-control" name="mt_endpoint"
              value="{{ htmlspecialchars($settings['mt_endpoint'] ?? 'http://127.0.0.1:5100/translate') }}">
            <small class="text-muted">OPUS-MT translation server URL</small>
          </div>
          <div class="col-sm-3">
            <button type="button" class="btn btn-outline-info" onclick="testTranslation()">
              <i class="fas fa-plug me-1"></i>Test
            </button>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Default Source Language</label>
          <div class="col-sm-4">
            <select class="form-select" name="translation_source_lang">
              @foreach ($translationLanguages as $code => $langData)
              <option value="{{ $code }}" {{ ($settings['translation_source_lang'] ?? 'en') === $code ? 'selected' : '' }}>
                {{ $langData['name'] }}
              </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Default Target Language</label>
          <div class="col-sm-4">
            <select class="form-select" name="translation_target_lang" id="translation_target_lang">
              @foreach ($translationLanguages as $code => $langData)
              <option value="{{ $code }}" data-culture="{{ $langData['culture'] }}"
                {{ ($settings['translation_target_lang'] ?? 'af') === $code ? 'selected' : '' }}>
                {{ $langData['name'] }} ({{ $langData['culture'] }})
              </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="translation_save_culture" id="translation_save_culture" value="1"
                {{ ($settings['translation_save_culture'] ?? '1') === '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-bold" for="translation_save_culture">Save with AtoM culture code</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">
              When enabled, translations will be saved in AtoM's <code>information_object_i18n</code> table
              with the language culture code (e.g., <code>af</code>, <code>zu</code>, <code>xh</code>).
              This allows multi-language support where users can switch between languages.
            </small>
          </div>
        </div>

        <hr class="my-4">

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Institution Sector</label>
          <div class="col-sm-4">
            <select class="form-select" name="translation_sector" id="translation_sector" onchange="updateFieldsForSector()">
              <option value="archives" {{ ($settings['translation_sector'] ?? 'archives') === 'archives' ? 'selected' : '' }}>
                Archives (ISAD(G))
              </option>
              <option value="library" {{ ($settings['translation_sector'] ?? 'archives') === 'library' ? 'selected' : '' }}>
                Library (MARC/Dublin Core)
              </option>
              <option value="museum" {{ ($settings['translation_sector'] ?? 'archives') === 'museum' ? 'selected' : '' }}>
                Museum (SPECTRUM)
              </option>
              <option value="gallery" {{ ($settings['translation_sector'] ?? 'archives') === 'gallery' ? 'selected' : '' }}>
                Gallery (Art Collection)
              </option>
              <option value="dam" {{ ($settings['translation_sector'] ?? 'archives') === 'dam' ? 'selected' : '' }}>
                DAM (Digital Asset Management)
              </option>
            </select>
            <small class="text-muted">Select your institution type to see relevant fields</small>
          </div>
        </div>

        <?php
        // Get saved field mappings (source -> target)
        $fieldMappings = json_decode($settings['translation_field_mappings'] ?? '{}', true) ?: [];

        // All available target fields in information_object_i18n
        $targetFields = [
            'title' => 'Title',
            'alternate_title' => 'Alternate Title',
            'edition' => 'Edition',
            'extent_and_medium' => 'Extent and Medium',
            'archival_history' => 'Archival History',
            'acquisition' => 'Acquisition',
            'scope_and_content' => 'Scope and Content',
            'appraisal' => 'Appraisal',
            'accruals' => 'Accruals',
            'arrangement' => 'Arrangement',
            'access_conditions' => 'Access Conditions',
            'reproduction_conditions' => 'Reproduction Conditions',
            'physical_characteristics' => 'Physical Characteristics',
            'finding_aids' => 'Finding Aids',
            'location_of_originals' => 'Location of Originals',
            'location_of_copies' => 'Location of Copies',
            'related_units_of_description' => 'Related Units of Description',
            'rules' => 'Rules',
            'sources' => 'Sources',
            'revision_history' => 'Revision History'
        ];
        ?>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Fields to Translate</label>
          <div class="col-sm-9">
            <p class="text-muted small mb-2">Select source fields and choose where to save the translation in the target language.</p>

            <!-- Archives fields -->
            <div id="sector-fields-archives" class="sector-fields">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th style="width:40px;"></th>
                    <th>Source Field</th>
                    <th><i class="fas fa-arrow-right text-muted"></i> Save To (Target)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach (['title' => 'Title', 'scope_and_content' => 'Scope and Content', 'archival_history' => 'Archival History', 'acquisition' => 'Source of Acquisition', 'arrangement' => 'Arrangement', 'access_conditions' => 'Access Conditions', 'reproduction_conditions' => 'Reproduction Conditions', 'finding_aids' => 'Finding Aids', 'related_units_of_description' => 'Related Units', 'appraisal' => 'Appraisal', 'accruals' => 'Accruals', 'physical_characteristics' => 'Physical Characteristics', 'location_of_originals' => 'Location of Originals', 'location_of_copies' => 'Location of Copies'] as $field => $label)
                  <tr>
                    <td>
                      <input class="form-check-input translate-field" type="checkbox" name="translate_field_{{ $field }}" id="translate_{{ $field }}" value="1"
                        {{ in_array($field, $selectedTranslationFields) ? 'checked' : '' }}>
                    </td>
                    <td><label class="form-check-label mb-0" for="translate_{{ $field }}">{{ $label }}</label></td>
                    <td>
                      <select class="form-select form-select-sm" name="translate_target_{{ $field }}">
                        @foreach ($targetFields as $targetField => $targetLabel)
                        <option value="{{ $targetField }}" {{ ($fieldMappings[$field] ?? $field) === $targetField ? 'selected' : '' }}>
                          {{ $targetLabel }}
                        </option>
                        @endforeach
                      </select>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- Library fields -->
            <div id="sector-fields-library" class="sector-fields" style="display:none;">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th style="width:40px;"></th>
                    <th>Source Field</th>
                    <th><i class="fas fa-arrow-right text-muted"></i> Save To (Target)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach (['title' => 'Title', 'alternate_title' => 'Alternate Title', 'edition' => 'Edition', 'extent_and_medium' => 'Extent and Medium', 'scope_and_content' => 'Abstract/Summary', 'access_conditions' => 'Access Conditions', 'reproduction_conditions' => 'Reproduction Conditions', 'physical_characteristics' => 'Physical Description', 'sources' => 'Sources'] as $field => $label)
                  <tr>
                    <td><input class="form-check-input translate-field" type="checkbox" name="translate_field_{{ $field }}" value="1"
                      {{ in_array($field, $selectedTranslationFields) ? 'checked' : '' }}></td>
                    <td>{{ $label }}</td>
                    <td>
                      <select class="form-select form-select-sm" name="translate_target_{{ $field }}">
                        @foreach ($targetFields as $targetField => $targetLabel)
                        <option value="{{ $targetField }}" {{ ($fieldMappings[$field] ?? $field) === $targetField ? 'selected' : '' }}>{{ $targetLabel }}</option>
                        @endforeach
                      </select>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- Museum fields -->
            <div id="sector-fields-museum" class="sector-fields" style="display:none;">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th style="width:40px;"></th>
                    <th>Source Field</th>
                    <th><i class="fas fa-arrow-right text-muted"></i> Save To (Target)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach (['title' => 'Object Name/Title', 'alternate_title' => 'Other Names', 'scope_and_content' => 'Description', 'archival_history' => 'Provenance', 'acquisition' => 'Acquisition Method', 'physical_characteristics' => 'Physical Description', 'access_conditions' => 'Display Conditions', 'location_of_originals' => 'Current Location', 'related_units_of_description' => 'Related Objects'] as $field => $label)
                  <tr>
                    <td><input class="form-check-input translate-field" type="checkbox" name="translate_field_{{ $field }}" value="1"
                      {{ in_array($field, $selectedTranslationFields) ? 'checked' : '' }}></td>
                    <td>{{ $label }}</td>
                    <td>
                      <select class="form-select form-select-sm" name="translate_target_{{ $field }}">
                        @foreach ($targetFields as $targetField => $targetLabel)
                        <option value="{{ $targetField }}" {{ ($fieldMappings[$field] ?? $field) === $targetField ? 'selected' : '' }}>{{ $targetLabel }}</option>
                        @endforeach
                      </select>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- Gallery fields -->
            <div id="sector-fields-gallery" class="sector-fields" style="display:none;">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th style="width:40px;"></th>
                    <th>Source Field</th>
                    <th><i class="fas fa-arrow-right text-muted"></i> Save To (Target)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach (['title' => 'Artwork Title', 'alternate_title' => 'Alternative Titles', 'scope_and_content' => 'Description/Statement', 'archival_history' => 'Provenance', 'acquisition' => 'Acquisition', 'physical_characteristics' => 'Medium and Dimensions', 'access_conditions' => 'Exhibition Conditions', 'reproduction_conditions' => 'Copyright/Reproduction', 'location_of_originals' => 'Current Location'] as $field => $label)
                  <tr>
                    <td><input class="form-check-input translate-field" type="checkbox" name="translate_field_{{ $field }}" value="1"
                      {{ in_array($field, $selectedTranslationFields) ? 'checked' : '' }}></td>
                    <td>{{ $label }}</td>
                    <td>
                      <select class="form-select form-select-sm" name="translate_target_{{ $field }}">
                        @foreach ($targetFields as $targetField => $targetLabel)
                        <option value="{{ $targetField }}" {{ ($fieldMappings[$field] ?? $field) === $targetField ? 'selected' : '' }}>{{ $targetLabel }}</option>
                        @endforeach
                      </select>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <!-- DAM fields -->
            <div id="sector-fields-dam" class="sector-fields" style="display:none;">
              <table class="table table-sm table-hover">
                <thead class="table-light">
                  <tr>
                    <th style="width:40px;"></th>
                    <th>Source Field</th>
                    <th><i class="fas fa-arrow-right text-muted"></i> Save To (Target)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach (['title' => 'Asset Title', 'alternate_title' => 'Alt Text', 'scope_and_content' => 'Description', 'access_conditions' => 'Usage Rights', 'reproduction_conditions' => 'License Terms', 'sources' => 'Source/Credits', 'finding_aids' => 'Keywords/Tags'] as $field => $label)
                  <tr>
                    <td><input class="form-check-input translate-field" type="checkbox" name="translate_field_{{ $field }}" value="1"
                      {{ in_array($field, $selectedTranslationFields) ? 'checked' : '' }}></td>
                    <td>{{ $label }}</td>
                    <td>
                      <select class="form-select form-select-sm" name="translate_target_{{ $field }}">
                        @foreach ($targetFields as $targetField => $targetLabel)
                        <option value="{{ $targetField }}" {{ ($fieldMappings[$field] ?? $field) === $targetField ? 'selected' : '' }}>{{ $targetLabel }}</option>
                        @endforeach
                      </select>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllFields()">Select All</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllFields()">Deselect All</button>
              <button type="button" class="btn btn-sm btn-outline-info" onclick="resetTargetFields()">Reset Targets to Same Field</button>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Translation Mode</label>
          <div class="col-sm-9">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="translation_mode" id="mode_review" value="review"
                {{ ($settings['translation_mode'] ?? 'review') === 'review' ? 'checked' : '' }}>
              <label class="form-check-label" for="mode_review">
                <strong>Review First</strong> - Save as draft for review before applying
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="translation_mode" id="mode_auto" value="auto"
                {{ ($settings['translation_mode'] ?? 'review') === 'auto' ? 'checked' : '' }}>
              <label class="form-check-label" for="mode_auto">
                <strong>Auto Apply</strong> - Immediately save translations to target language
              </label>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-sm-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="translation_overwrite" id="translation_overwrite" value="1"
                {{ ($settings['translation_overwrite'] ?? '0') === '1' ? 'checked' : '' }}>
              <label class="form-check-label" for="translation_overwrite">Overwrite existing</label>
            </div>
          </div>
          <div class="col-sm-9">
            <small class="text-muted">If target language field already has text, overwrite it with translation</small>
          </div>
        </div>

        <div class="row mb-3">
          <label class="col-sm-3 col-form-label">Timeout (seconds)</label>
          <div class="col-sm-3">
            <input type="number" class="form-control" name="mt_timeout" min="10" max="120"
              value="{{ htmlspecialchars($settings['mt_timeout'] ?? '30') }}">
          </div>
        </div>

        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Supported Languages:</strong> All 11 South African official languages (Afrikaans, Zulu, Xhosa, Sotho, Tswana, Swati, Venda, Tsonga, Ndebele),
          plus Swahili, Yoruba, Igbo, Hausa, Amharic, Dutch, French, German, Spanish, Portuguese, Arabic, and more.
          <br><small>OPUS-MT runs locally - no data leaves your server. Models download automatically on first use (~300-500MB each).</small>
        </div>
      </div>
    </div>

    <!-- Save Button -->
    <div class="d-flex justify-content-end gap-2 mb-4">
      <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="fas fa-times me-1"></i>Cancel
      </a>
      <button type="submit" class="btn btn-primary btn-lg">
        <i class="fas fa-save me-1"></i>Save Settings
      </button>
    </div>

    </form>

    <!-- Test Connection Result -->
    <div id="connectionResult" class="alert d-none mb-4"></div>

    <script {!! $csp_nonce !!}>
    // Initialize sector fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateFieldsForSector();

        // Add form submission handler to disable hidden sector checkboxes
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Disable all inputs in hidden sector divs so they don't get submitted
                document.querySelectorAll('.sector-fields').forEach(div => {
                    if (div.style.display === 'none') {
                        div.querySelectorAll('input, select').forEach(input => {
                            input.disabled = true;
                        });
                    }
                });
            });
        }
    });

    function updateFieldsForSector() {
        const sector = document.getElementById('translation_sector').value;
        // Hide all sector fields and re-enable their inputs
        document.querySelectorAll('.sector-fields').forEach(el => {
            el.style.display = 'none';
            // Re-enable inputs (in case they were disabled from a previous submission attempt)
            el.querySelectorAll('input, select').forEach(input => input.disabled = false);
        });
        // Show selected sector
        const sectorDiv = document.getElementById('sector-fields-' + sector);
        if (sectorDiv) {
            sectorDiv.style.display = 'block';
        }
    }

    function selectAllFields() {
        const sector = document.getElementById('translation_sector').value;
        const sectorDiv = document.getElementById('sector-fields-' + sector);
        if (sectorDiv) {
            sectorDiv.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
        }
    }

    function deselectAllFields() {
        const sector = document.getElementById('translation_sector').value;
        const sectorDiv = document.getElementById('sector-fields-' + sector);
        if (sectorDiv) {
            sectorDiv.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        }
    }

    function resetTargetFields() {
        const sector = document.getElementById('translation_sector').value;
        const sectorDiv = document.getElementById('sector-fields-' + sector);
        if (sectorDiv) {
            sectorDiv.querySelectorAll('select').forEach(select => {
                // Get the source field name from the select's name attribute
                const name = select.name;
                const sourceField = name.replace('translate_target_', '');
                // Set the target to the same as source
                select.value = sourceField;
            });
        }
    }

    function togglePassword() {
        const field = document.getElementById('api_key');
        const btn = event.target.closest('button');
        if (field.type === 'password') {
            field.type = 'text';
            btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
        } else {
            field.type = 'password';
            btn.innerHTML = '<i class="fas fa-eye"></i> Show';
        }
    }

    function testConnection() {
        const url = document.querySelector('input[name="api_url"]').value;
        const resultDiv = document.getElementById('connectionResult');

        resultDiv.className = 'alert alert-info';
        resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing connection...';
        resultDiv.classList.remove('d-none');

        fetch(url + '/health')
            .then(response => response.json())
            .then(data => {
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i><strong>Connection successful!</strong><br>' +
                    'NER Model: ' + (data.ner_model || 'N/A') + '<br>' +
                    'Summarizer: ' + (data.summarizer_model || 'N/A');
            })
            .catch(error => {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Connection failed!</strong><br>' +
                    'Error: ' + error.message + '<br>' +
                    'Make sure the AI service is running at: ' + url;
            });
    }

    function testTranslation() {
        const endpoint = document.querySelector('input[name="mt_endpoint"]').value;
        const baseUrl = endpoint.replace('/translate', '');
        const resultDiv = document.getElementById('connectionResult');

        resultDiv.className = 'alert alert-info';
        resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing OPUS-MT connection...';
        resultDiv.classList.remove('d-none');

        fetch(baseUrl + '/health')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    // Now test actual translation
                    return fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ source: 'en', target: 'af', text: 'Hello, how are you?' })
                    }).then(r => r.json()).then(t => {
                        resultDiv.className = 'alert alert-success';
                        resultDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i><strong>OPUS-MT Connection successful!</strong><br>' +
                            'Status: ' + data.status + '<br>' +
                            'Models loaded: ' + data.models_loaded + '<br>' +
                            '<strong>Test:</strong> "Hello, how are you?" &rarr; "' + t.translatedText + '"';
                    });
                }
            })
            .catch(error => {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>OPUS-MT Connection failed!</strong><br>' +
                    'Error: ' + error.message + '<br>' +
                    'Make sure OPUS-MT is running: <code>systemctl status opus-mt</code>';
            });
    }
    </script>

  </div>
</div>
@endsection
