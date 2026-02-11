<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ConditionPhotoUploadAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $conditionId = (int) $request->getParameter('id');
        
        // Verify condition exists
        $condition = $this->repository->getConditionById($conditionId);
        if (!$condition) {
            return $this->error(404, 'Not Found', 'Condition check not found');
        }

        // Handle file upload
        if (!empty($_FILES['photo'])) {
            $result = $this->handleFileUpload($_FILES['photo'], $conditionId, $request);
            return $result;
        }

        // Handle base64 upload (mobile)
        $data = $this->getJsonInput();
        if (!empty($data['photo_base64'])) {
            $result = $this->handleBase64Upload($data, $conditionId);
            return $result;
        }

        return $this->error(400, 'Bad Request', 'No photo provided (use photo file or photo_base64)');
    }

    protected function handleFileUpload($file, $conditionId, $request)
    {
        $uploadDir = $this->config('sf_upload_dir') . '/conditions/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('cond_') . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return $this->error(500, 'Upload Failed', 'Could not save file');
        }

        // Get image dimensions
        $size = @getimagesize($filepath);

        $photoId = $this->repository->createConditionPhoto($conditionId, [
            'photo_type' => $request->getParameter('photo_type', 'detail'),
            'caption' => $request->getParameter('caption'),
            'description' => $request->getParameter('description'),
            'location_on_object' => $request->getParameter('location_on_object'),
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_path' => $filepath,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'width' => $size ? $size[0] : null,
            'height' => $size ? $size[1] : null,
            'photographer' => $request->getParameter('photographer'),
            'photo_date' => $request->getParameter('photo_date'),
            'camera_info' => $request->getParameter('camera_info'),
            'is_primary' => $request->getParameter('is_primary', 0)
        ]);

        return $this->success([
            'id' => $photoId,
            'filename' => $filename,
            'message' => 'Photo uploaded'
        ], 201);
    }

    protected function handleBase64Upload($data, $conditionId)
    {
        $base64 = $data['photo_base64'];
        
        // Remove data URI prefix if present
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            $ext = $matches[1];
            $base64 = substr($base64, strpos($base64, ',') + 1);
        } else {
            $ext = $data['extension'] ?? 'jpg';
        }

        $imageData = base64_decode($base64);
        if (!$imageData) {
            return $this->error(400, 'Bad Request', 'Invalid base64 data');
        }

        $uploadDir = $this->config('sf_upload_dir') . '/conditions/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('cond_') . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;

        if (file_put_contents($filepath, $imageData) === false) {
            return $this->error(500, 'Upload Failed', 'Could not save file');
        }

        $size = @getimagesize($filepath);

        $photoId = $this->repository->createConditionPhoto($conditionId, [
            'photo_type' => $data['photo_type'] ?? 'detail',
            'caption' => $data['caption'] ?? null,
            'description' => $data['description'] ?? null,
            'location_on_object' => $data['location_on_object'] ?? null,
            'filename' => $filename,
            'original_filename' => $data['original_filename'] ?? $filename,
            'file_path' => $filepath,
            'file_size' => strlen($imageData),
            'mime_type' => 'image/' . $ext,
            'width' => $size ? $size[0] : null,
            'height' => $size ? $size[1] : null,
            'photographer' => $data['photographer'] ?? null,
            'photo_date' => $data['photo_date'] ?? date('Y-m-d'),
            'camera_info' => $data['camera_info'] ?? null,
            'is_primary' => $data['is_primary'] ?? 0
        ]);

        return $this->success([
            'id' => $photoId,
            'filename' => $filename,
            'message' => 'Photo uploaded'
        ], 201);
    }
}
