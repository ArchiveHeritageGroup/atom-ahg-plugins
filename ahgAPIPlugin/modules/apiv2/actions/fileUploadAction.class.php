<?php

use AtomFramework\Http\Controllers\AhgApiController;
use AtomExtensions\Services\FileValidationService;

class apiv2FileUploadAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        // Sanitize type parameter to prevent path traversal
        $type = basename($request->getParameter('type', 'general'));
        // Extra safety: strip any remaining dangerous characters
        $type = preg_replace('/[^a-zA-Z0-9_-]/', '', $type);
        if (empty($type)) {
            $type = 'general';
        }

        $uploadDir = $this->config('sf_upload_dir') . '/' . $type . '/' . date('Y/m');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $results = [];
        $errors = [];

        // Handle multiple files
        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'])) {
                // Multiple files with same field name
                for ($i = 0; $i < count($file['name']); $i++) {
                    if ($file['error'][$i] === UPLOAD_ERR_OK) {
                        $fileEntry = [
                            'name' => $file['name'][$i],
                            'tmp_name' => $file['tmp_name'][$i],
                            'type' => $file['type'][$i],
                            'size' => $file['size'][$i],
                        ];
                        $result = $this->saveFile($fileEntry, $uploadDir);
                        if (isset($result['error'])) {
                            $errors[] = $result;
                        } else {
                            $results[] = $result;
                        }
                    }
                }
            } else {
                // Single file
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $result = $this->saveFile($file, $uploadDir);
                    if (isset($result['error'])) {
                        $errors[] = $result;
                    } else {
                        $results[] = $result;
                    }
                }
            }
        }

        // Handle base64 uploads
        $data = $this->getJsonInput();
        if (!empty($data['files'])) {
            foreach ($data['files'] as $fileData) {
                if (!empty($fileData['base64'])) {
                    $result = $this->saveBase64($fileData, $uploadDir);
                    if (isset($result['error'])) {
                        $errors[] = $result;
                    } else {
                        $results[] = $result;
                    }
                }
            }
        }

        if (empty($results) && empty($errors)) {
            return $this->error(400, 'Bad Request', 'No files uploaded');
        }

        if (empty($results) && !empty($errors)) {
            return $this->error(400, 'Validation Failed', $errors);
        }

        $response = [
            'uploaded' => count($results),
            'files' => $results,
        ];

        if (!empty($errors)) {
            $response['rejected'] = count($errors);
            $response['errors'] = $errors;
        }

        return $this->success($response, 201);
    }

    protected function saveFile($file, $uploadDir)
    {
        // Validate the upload via FileValidationService
        $validation = FileValidationService::validateUpload($file);
        if (!$validation['valid']) {
            return [
                'error' => true,
                'original_name' => $file['name'] ?? 'unknown',
                'reasons' => $validation['errors'],
            ];
        }

        $sanitizedName = FileValidationService::sanitizeFilename($file['name']);
        $ext = strtolower(pathinfo($sanitizedName, PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        move_uploaded_file($file['tmp_name'], $filepath);

        // Post-move MIME check (magic bytes on the actual saved file)
        $mimeCheck = FileValidationService::validateMime($filepath);
        if (!$mimeCheck['valid']) {
            @unlink($filepath);

            return [
                'error' => true,
                'original_name' => $file['name'],
                'reasons' => $mimeCheck['errors'],
            ];
        }

        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'mime_type' => $mimeCheck['detected_mime'],
            'size' => $file['size'],
            'path' => str_replace($this->config('sf_upload_dir'), '/uploads', $filepath),
        ];
    }

    protected function saveBase64($data, $uploadDir)
    {
        $base64 = $data['base64'];
        $ext = strtolower($data['extension'] ?? 'bin');
        $originalName = $data['name'] ?? 'file.' . $ext;

        if (preg_match('/^data:([^;]+);base64,/', $base64, $matches)) {
            $claimedMime = $matches[1];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            $claimedMime = 'application/octet-stream';
        }

        // Validate base64 size before decoding
        $sizeCheck = FileValidationService::validateBase64Size($base64);
        if (!$sizeCheck['valid']) {
            return [
                'error' => true,
                'original_name' => $originalName,
                'reasons' => $sizeCheck['errors'],
            ];
        }

        // Validate extension
        $allowedExtensions = FileValidationService::getAllowedExtensions();
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        if (!in_array($ext, $allowedExtensions, true)) {
            return [
                'error' => true,
                'original_name' => $originalName,
                'reasons' => ["File extension '{$ext}' is not allowed."],
            ];
        }

        $content = base64_decode($base64, true);
        if ($content === false) {
            return [
                'error' => true,
                'original_name' => $originalName,
                'reasons' => ['Invalid base64 encoding.'],
            ];
        }

        // Check decoded size
        $maxSize = FileValidationService::getMaxSize();
        if (strlen($content) > $maxSize) {
            return [
                'error' => true,
                'original_name' => $originalName,
                'reasons' => ['File exceeds maximum allowed size.'],
            ];
        }

        $sanitizedName = FileValidationService::sanitizeFilename($originalName);
        $filename = uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        file_put_contents($filepath, $content);

        // Validate MIME type of the written file
        $mimeCheck = FileValidationService::validateMime($filepath, $claimedMime);
        if (!$mimeCheck['valid']) {
            @unlink($filepath);

            return [
                'error' => true,
                'original_name' => $originalName,
                'reasons' => $mimeCheck['errors'],
            ];
        }

        return [
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $mimeCheck['detected_mime'],
            'size' => strlen($content),
            'path' => str_replace($this->config('sf_upload_dir'), '/uploads', $filepath),
        ];
    }
}
