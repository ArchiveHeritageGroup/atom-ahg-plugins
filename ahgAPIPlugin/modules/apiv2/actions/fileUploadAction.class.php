<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2FileUploadAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $type = $request->getParameter('type', 'general');
        $uploadDir = $this->config('sf_upload_dir') . '/' . $type . '/' . date('Y/m');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $results = [];

        // Handle multiple files
        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'])) {
                // Multiple files with same field name
                for ($i = 0; $i < count($file['name']); $i++) {
                    if ($file['error'][$i] === UPLOAD_ERR_OK) {
                        $result = $this->saveFile([
                            'name' => $file['name'][$i],
                            'tmp_name' => $file['tmp_name'][$i],
                            'type' => $file['type'][$i],
                            'size' => $file['size'][$i]
                        ], $uploadDir);
                        $results[] = $result;
                    }
                }
            } else {
                // Single file
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $result = $this->saveFile($file, $uploadDir);
                    $results[] = $result;
                }
            }
        }

        // Handle base64 uploads
        $data = $this->getJsonInput();
        if (!empty($data['files'])) {
            foreach ($data['files'] as $fileData) {
                if (!empty($fileData['base64'])) {
                    $result = $this->saveBase64($fileData, $uploadDir);
                    $results[] = $result;
                }
            }
        }

        if (empty($results)) {
            return $this->error(400, 'Bad Request', 'No files uploaded');
        }

        return $this->success([
            'uploaded' => count($results),
            'files' => $results
        ], 201);
    }

    protected function saveFile($file, $uploadDir)
    {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        move_uploaded_file($file['tmp_name'], $filepath);

        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'mime_type' => $file['type'],
            'size' => $file['size'],
            'path' => str_replace($this->config('sf_upload_dir'), '/uploads', $filepath)
        ];
    }

    protected function saveBase64($data, $uploadDir)
    {
        $base64 = $data['base64'];
        $ext = $data['extension'] ?? 'bin';
        $originalName = $data['name'] ?? 'file.' . $ext;

        if (preg_match('/^data:([^;]+);base64,/', $base64, $matches)) {
            $mimeType = $matches[1];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            $mimeType = 'application/octet-stream';
        }

        $content = base64_decode($base64);
        $filename = uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        file_put_contents($filepath, $content);

        return [
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => strlen($content),
            'path' => str_replace($this->config('sf_upload_dir'), '/uploads', $filepath)
        ];
    }
}
