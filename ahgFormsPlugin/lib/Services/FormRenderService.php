<?php

namespace ahgFormsPlugin\Services;

/**
 * FormRenderService - Renders a resolved form template into a usable HTML edit form.
 *
 * Consumes the template structure produced by FormService::getTemplate()
 * (config: layout/tabs/sections + a fields Collection) and emits Bootstrap 5
 * markup matching the ahgThemeB5Plugin look. Field controls are named
 * field[<field_name>] so FormSubmitService can resolve each posted value back
 * to its ahg_form_field_mapping target.
 *
 * Pure string rendering — no inline <script>/<style> (CSP-safe); tab switching
 * relies on Bootstrap 5 data attributes only.
 */
class FormRenderService
{
    /** @var string Bootstrap column class per declared field width */
    private const WIDTH_COLS = [
        'full' => 'col-12',
        'half' => 'col-md-6',
        'third' => 'col-md-4',
        'quarter' => 'col-md-3',
    ];

    /**
     * Render the full set of fields for a template.
     *
     * @param object $template Template object (with ->config and ->fields)
     * @param array  $values   Prefill values keyed by field_name
     *
     * @return string HTML for the form body (without the surrounding <form> tag)
     */
    public function renderFields(object $template, array $values = []): string
    {
        $fields = $template->fields ?? [];
        // Normalise Collection -> array
        if (is_object($fields) && method_exists($fields, 'all')) {
            $fields = $fields->all();
        }

        $config = $template->config ?? null;
        $layout = is_object($config) ? ($config->layout ?? 'single') : 'single';

        if ('tabs' === $layout) {
            return $this->renderTabbed($fields, $values);
        }

        return $this->renderSectioned($fields, $values);
    }

    /**
     * Render a single-layout (no tabs) form, grouping fields by section.
     */
    private function renderSectioned(array $fields, array $values): string
    {
        $bySection = [];
        foreach ($fields as $field) {
            $bySection[$field->section_name ?? ''][] = $field;
        }

        $html = '';
        foreach ($bySection as $section => $sectionFields) {
            if ('' !== (string) $section) {
                $html .= '<h5 class="border-bottom pb-2 mt-4 mb-3">' . $this->esc($this->humanize($section)) . '</h5>';
            }
            $html .= '<div class="row g-3">';
            foreach ($sectionFields as $field) {
                $html .= $this->renderField($field, $values[$field->field_name] ?? null);
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render a tabbed form (config.layout === 'tabs').
     */
    private function renderTabbed(array $fields, array $values): string
    {
        $byTab = [];
        foreach ($fields as $field) {
            $byTab[$field->tab_name ?: 'General'][] = $field;
        }

        if (empty($byTab)) {
            return '';
        }

        $tabNames = array_keys($byTab);
        $navId = 'ahgFormsTabs';

        // Tab nav
        $nav = '<ul class="nav nav-tabs mb-3" id="' . $navId . '" role="tablist">';
        $first = true;
        foreach ($tabNames as $i => $tab) {
            $paneId = $navId . '-pane-' . $i;
            $nav .= '<li class="nav-item" role="presentation">'
                . '<button class="nav-link' . ($first ? ' active' : '') . '" '
                . 'id="' . $paneId . '-tab" data-bs-toggle="tab" data-bs-target="#' . $paneId . '" '
                . 'type="button" role="tab" aria-controls="' . $paneId . '" '
                . 'aria-selected="' . ($first ? 'true' : 'false') . '">'
                . $this->esc($tab) . '</button></li>';
            $first = false;
        }
        $nav .= '</ul>';

        // Tab panes
        $panes = '<div class="tab-content">';
        $first = true;
        foreach ($tabNames as $i => $tab) {
            $paneId = $navId . '-pane-' . $i;
            $panes .= '<div class="tab-pane fade' . ($first ? ' show active' : '') . '" '
                . 'id="' . $paneId . '" role="tabpanel" aria-labelledby="' . $paneId . '-tab" tabindex="0">';
            $panes .= $this->renderSectioned($byTab[$tab], $values);
            $panes .= '</div>';
            $first = false;
        }
        $panes .= '</div>';

        return $nav . $panes;
    }

    /**
     * Render one field (label + control), wrapped in a width column.
     *
     * @param object     $field Field definition
     * @param mixed|null $value Prefill value
     */
    private function renderField(object $field, $value): string
    {
        $type = $field->field_type ?? 'text';

        // Structural pseudo-fields span the full row.
        if ('heading' === $type) {
            return '<div class="col-12"><h5 class="border-bottom pb-2 mt-3">' . $this->esc($field->label) . '</h5></div>';
        }
        if ('divider' === $type) {
            return '<div class="col-12"><hr class="my-3"></div>';
        }
        if ('hidden' === $type) {
            return '<input type="hidden" name="field[' . $this->esc($field->field_name) . ']" value="' . $this->esc((string) ($value ?? $field->default_value ?? '')) . '">';
        }

        $colClass = self::WIDTH_COLS[$field->width ?? 'full'] ?? 'col-12';
        $control = $this->renderControl($field, $value);

        $label = '<label class="form-label" for="ahg_field_' . $this->esc($field->field_name) . '">'
            . $this->esc($field->label)
            . ($field->is_required ? ' <span class="text-danger">*</span>' : '')
            . '</label>';

        $help = '';
        if (!empty($field->help_text)) {
            $help = '<div class="form-text">' . $this->esc($field->help_text) . '</div>';
        }

        return '<div class="' . $colClass . '">' . $label . $control . $help . '</div>';
    }

    /**
     * Render the input control for a field type.
     */
    private function renderControl(object $field, $value): string
    {
        $name = 'field[' . $this->esc($field->field_name) . ']';
        $id = 'ahg_field_' . $this->esc($field->field_name);
        $type = $field->field_type ?? 'text';
        $required = $field->is_required ? ' required' : '';
        $readonly = $field->is_readonly ? ' readonly' : '';
        $disabled = $field->is_readonly ? ' disabled' : '';
        $placeholder = ' placeholder="' . $this->esc($field->placeholder ?? '') . '"';
        $val = $value ?? $field->default_value ?? '';

        switch ($type) {
            case 'textarea':
                return '<textarea class="form-control" id="' . $id . '" name="' . $name . '" rows="4"'
                    . $placeholder . $required . $readonly . '>' . $this->esc((string) $val) . '</textarea>';

            case 'richtext':
                // Textarea fallback (CSP-safe); theme may progressively enhance via data attr.
                return '<textarea class="form-control ahg-richtext" data-richtext="1" id="' . $id . '" name="' . $name . '" rows="6"'
                    . $placeholder . $required . $readonly . '>' . $this->esc((string) $val) . '</textarea>';

            case 'date':
                return '<input type="date" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $this->esc((string) $val) . '"' . $required . $readonly . '>';

            case 'daterange':
                $start = is_array($val) ? ($val['start'] ?? '') : '';
                $end = is_array($val) ? ($val['end'] ?? '') : '';

                return '<div class="row g-2">'
                    . '<div class="col-6"><input type="date" class="form-control" name="field[' . $this->esc($field->field_name) . '][start]" value="' . $this->esc((string) $start) . '" aria-label="Start date"' . $readonly . '></div>'
                    . '<div class="col-6"><input type="date" class="form-control" name="field[' . $this->esc($field->field_name) . '][end]" value="' . $this->esc((string) $end) . '" aria-label="End date"' . $readonly . '></div>'
                    . '</div>';

            case 'number':
                return '<input type="number" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $this->esc((string) $val) . '"' . $placeholder . $required . $readonly . '>';

            case 'url':
                return '<input type="url" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $this->esc((string) $val) . '"' . $placeholder . $required . $readonly . '>';

            case 'select':
            case 'autocomplete':
                return $this->renderSelect($field, $id, $name, $val, false, $required, $disabled);

            case 'multiselect':
                return $this->renderSelect($field, $id, $name . '[]', $val, true, $required, $disabled);

            case 'checkbox':
            case 'boolean':
                $checked = $val ? ' checked' : '';

                return '<div class="form-check"><input type="checkbox" class="form-check-input" id="' . $id . '" name="' . $name . '" value="1"' . $checked . $disabled . '>'
                    . '<label class="form-check-label" for="' . $id . '">' . $this->esc($field->placeholder ?: 'Yes') . '</label></div>';

            case 'radio':
                return $this->renderRadios($field, $name, $val, $disabled);

            case 'file':
                return '<input type="file" class="form-control" id="' . $id . '" name="' . $name . '"' . $required . $disabled . '>';

            case 'text':
            default:
                return '<input type="text" class="form-control" id="' . $id . '" name="' . $name . '" value="' . $this->esc((string) $val) . '"' . $placeholder . $required . $readonly . '>';
        }
    }

    /**
     * Render a <select> (single or multiple) from a field's options.
     */
    private function renderSelect(object $field, string $id, string $name, $val, bool $multiple, string $required, string $disabled): string
    {
        $options = $this->fieldOptions($field);
        $selected = $multiple ? (is_array($val) ? array_map('strval', $val) : []) : [(string) $val];

        $html = '<select class="form-select" id="' . $id . '" name="' . $name . '"' . ($multiple ? ' multiple' : '') . $required . $disabled . '>';
        if (!$multiple) {
            $html .= '<option value="">-- Select --</option>';
        }
        foreach ($options as $opt) {
            $optVal = is_object($opt) ? ($opt->value ?? '') : ($opt['value'] ?? '');
            $optLabel = is_object($opt) ? ($opt->label ?? $optVal) : ($opt['label'] ?? $optVal);
            $isSel = in_array((string) $optVal, $selected, true) ? ' selected' : '';
            $html .= '<option value="' . $this->esc((string) $optVal) . '"' . $isSel . '>' . $this->esc((string) $optLabel) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Render radio buttons from a field's options.
     */
    private function renderRadios(object $field, string $name, $val, string $disabled): string
    {
        $options = $this->fieldOptions($field);
        $html = '';
        foreach ($options as $i => $opt) {
            $optVal = is_object($opt) ? ($opt->value ?? '') : ($opt['value'] ?? '');
            $optLabel = is_object($opt) ? ($opt->label ?? $optVal) : ($opt['label'] ?? $optVal);
            $rid = 'ahg_radio_' . $this->esc($field->field_name) . '_' . $i;
            $checked = ((string) $val === (string) $optVal) ? ' checked' : '';
            $html .= '<div class="form-check"><input class="form-check-input" type="radio" name="' . $name . '" id="' . $rid . '" value="' . $this->esc((string) $optVal) . '"' . $checked . $disabled . '>'
                . '<label class="form-check-label" for="' . $rid . '">' . $this->esc((string) $optLabel) . '</label></div>';
        }

        return $html;
    }

    /**
     * Normalise a field's options to an iterable array.
     *
     * @return array
     */
    private function fieldOptions(object $field): array
    {
        $options = $field->options ?? null;
        if (is_object($options) && method_exists($options, 'all')) {
            $options = $options->all();
        }
        if (is_string($options)) {
            $options = json_decode($options) ?: [];
        }

        return is_array($options) ? $options : [];
    }

    /**
     * HTML-escape helper.
     */
    private function esc($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Turn a snake/slug section name into a human-readable heading.
     */
    private function humanize(string $value): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $value));
    }
}
