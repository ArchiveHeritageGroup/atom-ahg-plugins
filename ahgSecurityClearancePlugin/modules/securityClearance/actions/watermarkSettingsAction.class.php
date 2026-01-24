<?php
use Illuminate\Database\Capsule\Manager as DB;

class securityClearanceWatermarkSettingsAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/WatermarkSettingsService.php';
        
        if ($request->isMethod('post')) {
            // Handle custom watermark upload
            $files = $request->getFiles();
            if (isset($files['custom_watermark_file']) && $files['custom_watermark_file']['error'] === UPLOAD_ERR_OK) {
                $this->handleUpload($request, $files['custom_watermark_file']);
            }
            
            // Handle delete custom watermark
            if ($request->getParameter('delete_custom_watermark')) {
                $this->deleteCustomWatermark($request->getParameter('delete_custom_watermark'));
                $this->redirect(['module' => 'securityClearance', 'action' => 'watermarkSettings']);
            }
            
            // Save settings
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'default_watermark_enabled',
                $request->getParameter('default_watermark_enabled', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'default_watermark_type',
                $request->getParameter('default_watermark_type', 'COPYRIGHT')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'default_custom_watermark_id',
                $request->getParameter('default_custom_watermark_id', '')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'apply_watermark_on_view',
                $request->getParameter('apply_watermark_on_view', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'apply_watermark_on_download',
                $request->getParameter('apply_watermark_on_download', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'security_watermark_override',
                $request->getParameter('security_watermark_override', '0')
            );
            \AtomExtensions\Services\WatermarkSettingsService::setSetting(
                'watermark_min_size',
                $request->getParameter('watermark_min_size', '200')
            );
            
            // Update Cantaloupe cache
            \AtomExtensions\Services\WatermarkSettingsService::updateCantaloupeCache();
            
            $this->getUser()->setFlash('notice', 'Watermark settings saved successfully.');
            $this->redirect(['module' => 'securityClearance', 'action' => 'watermarkSettings']);
        }
        
        // Load current settings
        $this->defaultEnabled = \AtomExtensions\Services\WatermarkSettingsService::getSetting('default_watermark_enabled', '1');
        $this->defaultType = \AtomExtensions\Services\WatermarkSettingsService::getSetting('default_watermark_type', 'COPYRIGHT');
        $this->defaultCustomWatermarkId = \AtomExtensions\Services\WatermarkSettingsService::getSetting('default_custom_watermark_id', '');
        $this->applyOnView = \AtomExtensions\Services\WatermarkSettingsService::getSetting('apply_watermark_on_view', '1');
        $this->applyOnDownload = \AtomExtensions\Services\WatermarkSettingsService::getSetting('apply_watermark_on_download', '1');
        $this->securityOverride = \AtomExtensions\Services\WatermarkSettingsService::getSetting('security_watermark_override', '1');
        $this->minSize = \AtomExtensions\Services\WatermarkSettingsService::getSetting('watermark_min_size', '200');
        $this->watermarkTypes = \AtomExtensions\Services\WatermarkSettingsService::getWatermarkTypes();
        
        // Load custom watermarks (global ones - object_id is NULL)
        $this->customWatermarks = DB::table('custom_watermark')
            ->whereNull('object_id')
            ->where('active', 1)
            ->orderBy('name')
            ->get();
    }
    
    protected function handleUpload($request, $file)
    {
        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $this->getUser()->setFlash('error', 'Invalid file type. Only PNG, JPEG, GIF allowed.');
            return;
        }
        
        // Create uploads directory if needed
        $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/watermarks';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'watermark_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Insert into database
            $name = $request->getParameter('custom_watermark_name', 'Custom Watermark');
            $position = $request->getParameter('custom_watermark_position', 'center');
            $opacity = $request->getParameter('custom_watermark_opacity', '0.40');
            
            DB::table('custom_watermark')->insert([
                'object_id' => null,
                'name' => $name,
                'filename' => $filename,
                'file_path' => $filepath,
                'position' => $position,
                'opacity' => (float)$opacity,
                'created_by' => $this->context->user->getUserId(),
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->getUser()->setFlash('notice', 'Custom watermark uploaded successfully.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to save uploaded file.');
        }
    }
    
    protected function deleteCustomWatermark($id)
    {
        $watermark = DB::table('custom_watermark')->where('id', $id)->first();
        
        if ($watermark) {
            if (file_exists($watermark->file_path)) {
                @unlink($watermark->file_path);
            }
            DB::table('custom_watermark')->where('id', $id)->delete();
            $this->getUser()->setFlash('notice', 'Custom watermark deleted.');
        }
    }
}