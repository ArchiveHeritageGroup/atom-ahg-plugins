<?php

namespace AhgCustomFieldsPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

class CustomFieldRenderService
{
    protected CustomFieldService $service;

    public function __construct()
    {
        $this->service = new CustomFieldService();
    }

    // ----------------------------------------------------------------
    // View rendering (read-only display)
    // ----------------------------------------------------------------

    /**
     * Render custom fields for entity view (read-only).
     */
    public function renderViewFields(string $entityType, int $objectId, bool $publicOnly = false): string
    {
        $grouped = $this->service->getDefinitionsByGroup($entityType);
        if (empty($grouped)) {
            return '';
        }

        $values = $this->service->getValuesForObject($objectId, $entityType);
        $html = '';

        foreach ($grouped as $groupLabel => $definitions) {
            $groupHtml = '';

            foreach ($definitions as $def) {
                if ($publicOnly && !$def->is_visible_public) {
                    continue;
                }

                $key = $def->field_key;
                $val = $values[$key] ?? null;

                if ($val === null || $val === '' || $val === []) {
                    continue;
                }

                $groupHtml .= $this->renderViewField($def, $val);
            }

            if (!empty($groupHtml)) {
                $safeGroup = htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8');
                $html .= '<div class="cf-view-group mb-3">';
                $html .= '<h6 class="cf-group-label text-muted">' . $safeGroup . '</h6>';
                $html .= '<dl class="cf-view-fields row mb-0">' . $groupHtml . '</dl>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Render a single field in view mode.
     */
    protected function renderViewField(object $def, $value): string
    {
        $label = htmlspecialchars($def->field_label, ENT_QUOTES, 'UTF-8');
        $html = '<dt class="col-sm-4">' . $label . '</dt>';
        $html .= '<dd class="col-sm-8">';

        if (is_array($value)) {
            // Repeatable field
            $items = array_map(function ($v) use ($def) {
                return $this->formatDisplayValue($def, $v);
            }, $value);
            $html .= implode('<br>', $items);
        } else {
            $html .= $this->formatDisplayValue($def, $value);
        }

        $html .= '</dd>';

        return $html;
    }

    /**
     * Format a single value for display.
     */
    protected function formatDisplayValue(object $def, $value): string
    {
        if ($value === null || $value === '') {
            return '<span class="text-muted">—</span>';
        }

        switch ($def->field_type) {
            case 'boolean':
                return $value ? '<i class="bi bi-check-circle text-success"></i> Yes'
                             : '<i class="bi bi-x-circle text-muted"></i> No';

            case 'url':
                $safe = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

                return '<a href="' . $safe . '" target="_blank" rel="noopener">'
                    . $safe . ' <i class="bi bi-box-arrow-up-right"></i></a>';

            case 'dropdown':
                return $this->resolveDropdownLabel($def->dropdown_taxonomy, $value);

            case 'date':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            case 'number':
                return htmlspecialchars(rtrim(rtrim(number_format((float) $value, 4), '0'), '.'), ENT_QUOTES, 'UTF-8');

            case 'textarea':
                return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));

            default:
                return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
    }

    // ----------------------------------------------------------------
    // Edit rendering (form inputs)
    // ----------------------------------------------------------------

    /**
     * Render custom fields for entity edit form.
     */
    public function renderEditFields(string $entityType, int $objectId): string
    {
        $grouped = $this->service->getDefinitionsByGroup($entityType);
        if (empty($grouped)) {
            return '';
        }

        $values = $this->service->getValuesForObject($objectId, $entityType);
        $html = '';

        foreach ($grouped as $groupLabel => $definitions) {
            $groupHtml = '';

            foreach ($definitions as $def) {
                if (!$def->is_visible_edit) {
                    continue;
                }

                $key = $def->field_key;
                $val = $values[$key] ?? ($def->default_value ?? null);

                if ($def->is_repeatable) {
                    $arrVal = is_array($val) ? $val : ($val !== null ? [$val] : ['']);
                    $groupHtml .= $this->getRepeatableInput($def, $arrVal);
                } else {
                    $groupHtml .= $this->getFieldInput($def, $val);
                }
            }

            if (!empty($groupHtml)) {
                $safeGroup = htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8');
                $html .= '<fieldset class="cf-edit-group mb-3">';
                $html .= '<legend class="cf-group-legend h6 text-muted border-bottom pb-1">' . $safeGroup . '</legend>';
                $html .= $groupHtml;
                $html .= '</fieldset>';
            }
        }

        return $html;
    }

    /**
     * Render a single form input for a field definition.
     */
    public function getFieldInput(object $def, $value): string
    {
        $key = htmlspecialchars($def->field_key, ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($def->field_label, ENT_QUOTES, 'UTF-8');
        $name = 'cf[' . $key . ']';
        $id = 'cf_' . $key;
        $required = $def->is_required ? ' required' : '';
        $helpText = !empty($def->help_text) ? htmlspecialchars($def->help_text, ENT_QUOTES, 'UTF-8') : '';
        $helpId = $id . '_help';

        $html = '<div class="mb-3 cf-field" data-field-key="' . $key . '">';
        $html .= '<label for="' . $id . '" class="form-label">' . $label;
        if ($def->is_required) {
            $html .= ' <span class="text-danger">*</span>';
        }
        $html .= '</label>';

        $ariaDesc = $helpText ? ' aria-describedby="' . $helpId . '"' : '';

        switch ($def->field_type) {
            case 'text':
                $safeVal = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '<input type="text" class="form-control" id="' . $id . '" name="' . $name . '"'
                    . ' value="' . $safeVal . '"' . $required . $ariaDesc . '>';
                break;

            case 'textarea':
                $safeVal = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '<textarea class="form-control" id="' . $id . '" name="' . $name . '"'
                    . ' rows="3"' . $required . $ariaDesc . '>' . $safeVal . '</textarea>';
                break;

            case 'date':
                $safeVal = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '<input type="date" class="form-control" id="' . $id . '" name="' . $name . '"'
                    . ' value="' . $safeVal . '"' . $required . $ariaDesc . '>';
                break;

            case 'number':
                $safeVal = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '<input type="number" class="form-control" id="' . $id . '" name="' . $name . '"'
                    . ' value="' . $safeVal . '" step="any"' . $required . $ariaDesc . '>';
                break;

            case 'boolean':
                $checked = $value ? ' checked' : '';
                $html .= '<div class="form-check">';
                $html .= '<input type="hidden" name="' . $name . '" value="0">';
                $html .= '<input type="checkbox" class="form-check-input" id="' . $id . '" name="' . $name . '"'
                    . ' value="1"' . $checked . $ariaDesc . '>';
                $html .= '<label class="form-check-label" for="' . $id . '">Yes</label>';
                $html .= '</div>';
                break;

            case 'dropdown':
                $html .= '<select class="form-select" id="' . $id . '" name="' . $name . '"' . $required . $ariaDesc . '>';
                $html .= '<option value="">— Select —</option>';
                if (!empty($def->dropdown_taxonomy)) {
                    $options = $this->service->getDropdownOptions($def->dropdown_taxonomy);
                    foreach ($options as $opt) {
                        $sel = ($value === $opt->code) ? ' selected' : '';
                        $optLabel = htmlspecialchars($opt->label, ENT_QUOTES, 'UTF-8');
                        $optCode = htmlspecialchars($opt->code, ENT_QUOTES, 'UTF-8');
                        $html .= '<option value="' . $optCode . '"' . $sel . '>' . $optLabel . '</option>';
                    }
                }
                $html .= '</select>';
                break;

            case 'url':
                $safeVal = htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
                $html .= '<div class="input-group">';
                $html .= '<input type="url" class="form-control" id="' . $id . '" name="' . $name . '"'
                    . ' value="' . $safeVal . '" placeholder="https://"' . $required . $ariaDesc . '>';
                $html .= '<a class="btn btn-outline-secondary cf-url-open" href="' . $safeVal . '"'
                    . ' target="_blank" rel="noopener" title="Open URL"><i class="bi bi-box-arrow-up-right"></i></a>';
                $html .= '</div>';
                break;
        }

        if ($helpText) {
            $html .= '<div id="' . $helpId . '" class="form-text">' . $helpText . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a repeatable field with add/remove buttons.
     */
    public function getRepeatableInput(object $def, array $values): string
    {
        $key = htmlspecialchars($def->field_key, ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($def->field_label, ENT_QUOTES, 'UTF-8');
        $helpText = !empty($def->help_text) ? htmlspecialchars($def->help_text, ENT_QUOTES, 'UTF-8') : '';

        if (empty($values)) {
            $values = [''];
        }

        $html = '<div class="mb-3 cf-field cf-repeatable" data-field-key="' . $key . '">';
        $html .= '<label class="form-label">' . $label;
        if ($def->is_required) {
            $html .= ' <span class="text-danger">*</span>';
        }
        $html .= ' <span class="badge bg-secondary">Repeatable</span></label>';

        $html .= '<div class="cf-repeatable-items">';
        foreach ($values as $i => $val) {
            $safeVal = htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
            $html .= '<div class="input-group mb-1 cf-repeatable-row">';
            $html .= '<input type="text" class="form-control" name="cf[' . $key . '][]" value="' . $safeVal . '">';
            $html .= '<button type="button" class="btn btn-outline-danger cf-remove-row" title="Remove">';
            $html .= '<i class="bi bi-dash-circle"></i></button>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '<button type="button" class="btn btn-sm btn-outline-primary cf-add-row" data-field-key="' . $key . '">';
        $html .= '<i class="bi bi-plus-circle"></i> Add another</button>';

        if ($helpText) {
            $html .= '<div class="form-text">' . $helpText . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Resolve a dropdown code to its display label.
     */
    protected function resolveDropdownLabel(string $taxonomy, string $code): string
    {
        try {
            $row = DB::table('ahg_dropdown')
                ->where('taxonomy', $taxonomy)
                ->where('code', $code)
                ->first();

            if ($row) {
                $label = htmlspecialchars($row->label, ENT_QUOTES, 'UTF-8');
                if (!empty($row->color)) {
                    $color = htmlspecialchars($row->color, ENT_QUOTES, 'UTF-8');

                    return '<span class="badge" style="background-color:' . $color . '">' . $label . '</span>';
                }

                return $label;
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        return htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    }
}
