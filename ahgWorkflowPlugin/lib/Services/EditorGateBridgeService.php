<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Editor Gate Bridge Service
 *
 * Bridges FormService templates with PublishGateService rules
 * to provide inline gate validation in editor forms.
 *
 * @version 1.0.0
 */
class EditorGateBridgeService
{
    /**
     * Get editor validation state for all fields in a template against gate rules.
     *
     * @param int $objectId   Object being edited
     * @param int $templateId Form template ID
     * @return array [{field_name, required_by_gate, gate_severity, current_value, passes}]
     */
    public function getEditorValidation(int $objectId, int $templateId): array
    {
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        // Get template fields with gate linkage
        $fields = DB::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->whereNotNull('publish_gate_rule_type')
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        if (empty($fields)) {
            return [];
        }

        // Load current object values
        $i18n = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->first();

        $main = DB::table('information_object')
            ->where('id', $objectId)
            ->first();

        $results = [];

        foreach ($fields as $field) {
            // Get current value
            $value = null;
            $fieldName = $field->field_name;
            if ($i18n && property_exists($i18n, $fieldName)) {
                $value = $i18n->$fieldName;
            } elseif ($main && property_exists($main, $fieldName)) {
                $value = $main->$fieldName;
            }

            $validation = $this->validateFieldAgainstGate(
                $fieldName,
                $value,
                $objectId,
                $field->publish_gate_rule_type,
                $field->gate_severity
            );

            $results[] = [
                'field_name' => $fieldName,
                'label' => $field->label,
                'required_by_gate' => true,
                'gate_rule_type' => $field->publish_gate_rule_type,
                'gate_severity' => $field->gate_severity ?? 'warning',
                'current_value' => $value,
                'passes' => $validation['passes'],
                'messages' => $validation['messages'],
            ];
        }

        return $results;
    }

    /**
     * Check a single field against relevant gate rules.
     *
     * @param string      $fieldName Field name
     * @param mixed       $value     Current or proposed value
     * @param int         $objectId  Object ID
     * @param string|null $ruleType  Specific rule type to check
     * @param string|null $severity  Override severity
     * @return array {passes: bool, messages: [string]}
     */
    public function validateFieldAgainstGate(
        string $fieldName,
        $value,
        int $objectId,
        ?string $ruleType = null,
        ?string $severity = null
    ): array {
        $messages = [];
        $passes = true;

        // Find matching gate rules
        $query = DB::table('ahg_publish_gate_rule')
            ->where('is_active', 1);

        if ($ruleType) {
            $query->where('rule_type', $ruleType);
        }

        // Match by field name if it's a field-based rule
        if (in_array($ruleType, ['field_required', 'field_not_empty'])) {
            $query->where('field_name', $fieldName);
        }

        $rules = $query->get()->toArray();

        foreach ($rules as $rule) {
            $checkPassed = match ($rule->rule_type) {
                'field_required' => $value !== null && trim((string) $value) !== '',
                'field_not_empty' => $value !== null && strlen(trim(strip_tags((string) $value))) > 0,
                default => true, // Non-field rules pass by default at field level
            };

            if (!$checkPassed) {
                $passes = false;
                $effectiveSeverity = $severity ?? $rule->severity;
                $messages[] = [
                    'message' => $rule->error_message,
                    'severity' => $effectiveSeverity,
                ];
            }
        }

        return ['passes' => $passes, 'messages' => $messages];
    }

    /**
     * Get template fields enriched with gate pass/fail status.
     *
     * @param int $templateId Form template ID
     * @param int $objectId   Object ID
     * @return array Template fields with gate overlay
     */
    public function getTemplateWithGateOverlay(int $templateId, int $objectId): array
    {
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        $fields = DB::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        // Load current values
        $i18n = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->first();

        $main = DB::table('information_object')
            ->where('id', $objectId)
            ->first();

        foreach ($fields as &$field) {
            $field->gate_status = null;
            $field->gate_messages = [];

            if (!empty($field->publish_gate_rule_type)) {
                $value = null;
                $fn = $field->field_name;
                if ($i18n && property_exists($i18n, $fn)) {
                    $value = $i18n->$fn;
                } elseif ($main && property_exists($main, $fn)) {
                    $value = $main->$fn;
                }

                $validation = $this->validateFieldAgainstGate(
                    $fn, $value, $objectId,
                    $field->publish_gate_rule_type,
                    $field->gate_severity
                );

                $field->gate_status = $validation['passes'] ? 'passed' : 'failed';
                $field->gate_messages = $validation['messages'];
            }
        }

        return $fields;
    }
}
