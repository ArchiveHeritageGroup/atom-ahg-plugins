<?php

class dataMigrationSaveMappingAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->context->user->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'POST required']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $name = trim($request->getParameter('mapping_name', ''));
        $category = trim($request->getParameter('category', 'Custom'));
        $overwrite = $request->getParameter('overwrite', '0') === '1';
        $existingId = $request->getParameter('existing_id', '');
        $setAsDefault = $request->getParameter('set_as_default', '0') === '1';
        $fields = $request->getParameter('fields', []);
        $targetType = $request->getParameter('target_type', 'archives');

        error_log("SaveMapping: name=$name, category=$category, overwrite=$overwrite, existingId=$existingId, setAsDefault=$setAsDefault, targetType=$targetType");

        if (empty($name) && empty($existingId)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Mapping name is required']));
        }

        // Convert Symfony array decorator to regular array
        if ($fields instanceof sfOutputEscaperArrayDecorator) {
            $fields = $fields->getRawValue();
        }

        // Build clean field array (including custom fields)
        $cleanFields = [];
        foreach ($fields as $field) {
            // Skip empty fields
            $sourceField = $field['source_field'] ?? '';
            $atomField = $field["atom_field"] ?? "";
            $ahgField = $field["ahg_field"] ?? "";
            $constantValue = $field['constant_value'] ?? '';
            
            // Include field if it has a mapping or constant value
            if (!empty($atomField) || !empty($constantValue)) {
                $cleanFields[] = [
                    'source_field' => $sourceField,
                    'atom_field' => $atomField,
                    'ahg_field' => $ahgField,
                    'constant_value' => $constantValue,
                    'include' => isset($field['include']) ? true : false,
                    'concatenate' => isset($field['concatenate']) ? true : false,
                    'concat_constant' => isset($field['concat_constant']) ? true : false,
                    'concat_symbol' => $field['concat_symbol'] ?? '|',
                    'transform' => $field['transform'] ?? '',
                ];
            }
        }

        error_log("SaveMapping: " . count($cleanFields) . " fields to save");

        $mappingData = json_encode(['fields' => $cleanFields]);

        try {
            $DB = \Illuminate\Database\Capsule\Manager::class;

            // If set as default, clear other defaults for this target type
            if ($setAsDefault) {
                $DB::table('atom_data_mapping')
                    ->where('target_type', $targetType)
                    ->update(['is_default' => 0]);
            }

            // If overwriting existing by ID
            if (!empty($existingId)) {
                $existing = $DB::table('atom_data_mapping')->where('id', $existingId)->first();

                if (!$existing) {
                    return $this->renderText(json_encode(['success' => false, 'error' => 'Mapping not found']));
                }

                $updateData = [
                    'field_mappings' => $mappingData,
                    'target_type' => $targetType,
                    'category' => $category ?: $existing->category,
                    'is_default' => $setAsDefault ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Update name if provided and different
                if (!empty($name) && $name !== $existing->name) {
                    $updateData['name'] = $name;
                }

                $DB::table('atom_data_mapping')
                    ->where('id', $existingId)
                    ->update($updateData);

                error_log("SaveMapping: Updated existing mapping ID $existingId");

                return $this->renderText(json_encode([
                    'success' => true,
                    'id' => $existingId,
                    'updated' => true,
                    'name' => $name ?: $existing->name,
                    'field_count' => count($cleanFields),
                    'is_default' => $setAsDefault
                ]));
            }

            // Check if mapping with this name exists
            $existing = $DB::table('atom_data_mapping')
                ->where('name', $name)
                ->first();

            if ($existing) {
                if (!$overwrite) {
                    return $this->renderText(json_encode([
                        'success' => false,
                        'error' => 'exists',
                        'existing_id' => $existing->id,
                        'is_default' => $existing->is_default,
                        'message' => 'A mapping with this name already exists.'
                    ]));
                }

                // Overwrite existing
                $DB::table('atom_data_mapping')
                    ->where('id', $existing->id)
                    ->update([
                        'field_mappings' => $mappingData,
                        'target_type' => $targetType,
                        'category' => $category,
                        'is_default' => $setAsDefault ? 1 : 0,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                error_log("SaveMapping: Overwritten mapping ID {$existing->id}");

                return $this->renderText(json_encode([
                    'success' => true,
                    'id' => $existing->id,
                    'updated' => true,
                    'field_count' => count($cleanFields),
                    'is_default' => $setAsDefault
                ]));
            }

            // Insert new
            $id = $DB::table('atom_data_mapping')->insertGetId([
                'name' => $name,
                'target_type' => $targetType,
                'category' => $category,
                'field_mappings' => $mappingData,
                'is_default' => $setAsDefault ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            error_log("SaveMapping: Created new mapping ID $id");

            return $this->renderText(json_encode([
                'success' => true,
                'id' => $id,
                'field_count' => count($cleanFields),
                'is_default' => $setAsDefault
            ]));

        } catch (\Exception $e) {
            error_log("SaveMapping error: " . $e->getMessage());
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
