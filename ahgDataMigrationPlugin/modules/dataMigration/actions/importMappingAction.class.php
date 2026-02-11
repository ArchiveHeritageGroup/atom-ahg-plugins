<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Import a mapping profile from JSON file.
 */
class importMappingAction extends AhgController
{
    public function execute($request)
    {
        // Check user authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST method required']));
        }

        try {
            // Check for uploaded file
            $file = $request->getFiles('mapping_file');
            $jsonContent = $request->getParameter('mapping_json');

            if ($file && UPLOAD_ERR_OK === $file['error']) {
                $content = file_get_contents($file['tmp_name']);
            } elseif ($jsonContent) {
                $content = $jsonContent;
            } else {
                return $this->renderText(json_encode(['error' => 'No file or JSON content provided']));
            }

            // Parse JSON
            $data = json_decode($content, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                return $this->renderText(json_encode(['error' => 'Invalid JSON: '.json_last_error_msg()]));
            }

            // Validate required fields
            if (empty($data['name']) || empty($data['field_mappings'])) {
                return $this->renderText(json_encode(['error' => 'Missing required fields: name, field_mappings']));
            }

            // Check for existing mapping with same name
            $existingName = $data['name'];
            $newName = $this->getUniqueName($existingName, $data['target_type'] ?? 'information_object');

            // Insert into database
            $mappingId = \Illuminate\Database\Capsule\Manager::table('atom_data_mapping')->insertGetId([
                'name' => $newName,
                'description' => $data['description'] ?? null,
                'target_type' => $data['target_type'] ?? 'information_object',
                'source_template' => $data['source_template'] ?? null,
                'sector_code' => $data['sector_code'] ?? null,
                'field_mappings' => json_encode($data['field_mappings']),
                'is_default' => 0,
                'created_by' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->renderText(json_encode([
                'success' => true,
                'mapping_id' => $mappingId,
                'name' => $newName,
                'message' => $newName !== $existingName
                    ? "Mapping imported as '{$newName}' (original name already existed)"
                    : "Mapping '{$newName}' imported successfully",
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    /**
     * Get a unique name for the mapping.
     *
     * @param string $baseName
     * @param string $targetType
     *
     * @return string
     */
    protected function getUniqueName(string $baseName, string $targetType): string
    {
        $name = $baseName;
        $suffix = 1;

        while ($this->nameExists($name, $targetType)) {
            $name = $baseName.' ('.$suffix.')';
            ++$suffix;

            if ($suffix > 100) {
                // Failsafe
                $name = $baseName.' ('.uniqid().')';

                break;
            }
        }

        return $name;
    }

    /**
     * Check if a mapping name already exists.
     *
     * @param string $name
     * @param string $targetType
     *
     * @return bool
     */
    protected function nameExists(string $name, string $targetType): bool
    {
        return \Illuminate\Database\Capsule\Manager::table('atom_data_mapping')
            ->where('name', $name)
            ->where('target_type', $targetType)
            ->exists()
        ;
    }
}
