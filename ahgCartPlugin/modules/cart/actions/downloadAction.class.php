<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Repositories/EcommerceRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Repositories\EcommerceRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Download Action
 * Serves digital objects based on product type selection
 *
 * Product Types:
 * 1 = Low Resolution (72dpi, max 1200px)
 * 2 = High Resolution (300dpi, full resolution)
 * 3 = TIFF Master (archival quality)
 * 9 = Research Use (watermarked)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartDownloadAction extends sfAction
{
    public function execute($request)
    {
        $token = $request->getParameter('token');

        if (empty($token)) {
            $this->forward404('Invalid download link.');
        }

        $ecommerceRepo = new EcommerceRepository();

        // Validate token
        $tokenRecord = $ecommerceRepo->validateDownloadToken($token);

        if (!$tokenRecord) {
            $this->getResponse()->setStatusCode(403);
            return $this->renderText('Download link has expired or reached maximum downloads.');
        }

        // Get order item
        $orderItem = DB::table('ahg_order_item')
            ->where('id', $tokenRecord->order_item_id)
            ->first();

        if (!$orderItem) {
            $this->forward404('Order item not found.');
        }

        // Verify order is paid/completed
        $order = DB::table('ahg_order')
            ->where('id', $orderItem->order_id)
            ->first();

        if (!$order || !in_array($order->status, ['paid', 'completed'])) {
            $this->getResponse()->setStatusCode(403);
            return $this->renderText('Order has not been paid.');
        }

        // Get the digital object for this archival description
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $orderItem->archival_description_id)
            ->first();

        if (!$digitalObject) {
            $this->forward404('No digital object found for this item.');
        }

        // Get product type to determine which file to serve
        $productType = DB::table('ahg_product_type')
            ->where('id', $orderItem->product_type_id)
            ->first();

        if (!$productType || !$productType->is_digital) {
            $this->forward404('This product type is not available for download.');
        }

        // Determine which file to serve based on product type
        $filePath = $this->getFilePathForProductType(
            $digitalObject,
            $orderItem->product_type_id,
            $orderItem->archival_description_id
        );

        if (!$filePath || !file_exists($filePath)) {
            $this->forward404('File not found. Please contact support.');
        }

        // Increment download count
        $ecommerceRepo->incrementDownloadCount($token, $_SERVER['REMOTE_ADDR'] ?? null);

        // Serve the file
        $this->serveFile($filePath, $orderItem, $productType);

        return sfView::NONE;
    }

    /**
     * Get the appropriate file path based on product type
     */
    protected function getFilePathForProductType($digitalObject, $productTypeId, $objectId): ?string
    {
        $uploadsPath = sfConfig::get('sf_upload_dir', sfConfig::get('sf_root_dir') . '/uploads');

        // Get the base path for the digital object
        $basePath = $digitalObject->path ?? null;
        if (!$basePath) {
            return null;
        }

        // Full path to original file
        $originalPath = $uploadsPath . '/' . $basePath;

        // Check for derivatives based on product type
        switch ($productTypeId) {
            case 1: // Low Resolution (72dpi, max 1200px)
                // Look for reference image or thumbnail
                $derivatives = $this->getDerivatives($objectId);
                if (!empty($derivatives['reference'])) {
                    $refPath = $uploadsPath . '/' . $derivatives['reference'];
                    if (file_exists($refPath)) {
                        return $refPath;
                    }
                }
                // Generate or serve scaled version
                return $this->getOrCreateLowResVersion($originalPath, $objectId);

            case 2: // High Resolution (300dpi, full resolution)
                // Serve the master/original file
                if (!empty($derivatives['master'] ?? null)) {
                    $masterPath = $uploadsPath . '/' . $derivatives['master'];
                    if (file_exists($masterPath)) {
                        return $masterPath;
                    }
                }
                return $originalPath;

            case 3: // TIFF Master
                // Look for TIFF version first
                $tiffPath = $this->findTiffVersion($originalPath, $objectId);
                if ($tiffPath && file_exists($tiffPath)) {
                    return $tiffPath;
                }
                // Fall back to original
                return $originalPath;

            case 9: // Research Use (watermarked)
                // Generate or serve watermarked version
                return $this->getOrCreateWatermarkedVersion($originalPath, $objectId);

            default:
                // For other digital product types, serve the reference or original
                $derivatives = $this->getDerivatives($objectId);
                if (!empty($derivatives['reference'])) {
                    $refPath = $uploadsPath . '/' . $derivatives['reference'];
                    if (file_exists($refPath)) {
                        return $refPath;
                    }
                }
                return $originalPath;
        }
    }

    /**
     * Get derivatives for a digital object
     */
    protected function getDerivatives($objectId): array
    {
        $derivatives = [];

        $rows = DB::table('digital_object')
            ->where('parent_id', function($query) use ($objectId) {
                $query->select('id')
                    ->from('digital_object')
                    ->where('object_id', $objectId)
                    ->limit(1);
            })
            ->get();

        foreach ($rows as $row) {
            $usageId = $row->usage_id ?? null;
            // AtoM usage IDs: 175 = master, 173 = reference, 174 = thumbnail
            if ($usageId == 175) {
                $derivatives['master'] = $row->path;
            } elseif ($usageId == 173) {
                $derivatives['reference'] = $row->path;
            } elseif ($usageId == 174) {
                $derivatives['thumbnail'] = $row->path;
            }
        }

        return $derivatives;
    }

    /**
     * Get or create low resolution version
     */
    protected function getOrCreateLowResVersion($originalPath, $objectId): ?string
    {
        if (!file_exists($originalPath)) {
            return null;
        }

        $cacheDir = sfConfig::get('sf_root_dir') . '/downloads/lowres';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
        $cachedPath = $cacheDir . '/' . $objectId . '_lowres.' . $ext;

        // Return cached version if exists
        if (file_exists($cachedPath)) {
            return $cachedPath;
        }

        // Create low-res version using ImageMagick or GD
        if (extension_loaded('imagick')) {
            try {
                $image = new \Imagick($originalPath);
                $image->setImageResolution(72, 72);
                $image->resampleImage(72, 72, \Imagick::FILTER_LANCZOS, 1);

                // Scale to max 1200px
                $width = $image->getImageWidth();
                $height = $image->getImageHeight();
                if ($width > 1200 || $height > 1200) {
                    $image->scaleImage(1200, 1200, true);
                }

                $image->setImageCompressionQuality(85);
                $image->writeImage($cachedPath);
                $image->destroy();

                return $cachedPath;
            } catch (\Exception $e) {
                error_log('Failed to create low-res version: ' . $e->getMessage());
            }
        }

        // Fall back to original if we can't create low-res
        return $originalPath;
    }

    /**
     * Find TIFF version of file
     */
    protected function findTiffVersion($originalPath, $objectId): ?string
    {
        $uploadsPath = sfConfig::get('sf_upload_dir', sfConfig::get('sf_root_dir') . '/uploads');

        // Check if original is already TIFF
        $ext = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['tif', 'tiff'])) {
            return $originalPath;
        }

        // Look for TIFF in same directory
        $dir = dirname($originalPath);
        $basename = pathinfo($originalPath, PATHINFO_FILENAME);

        foreach (['tif', 'tiff'] as $tiffExt) {
            $tiffPath = $dir . '/' . $basename . '.' . $tiffExt;
            if (file_exists($tiffPath)) {
                return $tiffPath;
            }
        }

        // Check for master derivative that might be TIFF
        $derivatives = $this->getDerivatives($objectId);
        if (!empty($derivatives['master'])) {
            $masterPath = $uploadsPath . '/' . $derivatives['master'];
            $masterExt = strtolower(pathinfo($masterPath, PATHINFO_EXTENSION));
            if (in_array($masterExt, ['tif', 'tiff']) && file_exists($masterPath)) {
                return $masterPath;
            }
        }

        return null;
    }

    /**
     * Get or create watermarked version for research use
     */
    protected function getOrCreateWatermarkedVersion($originalPath, $objectId): ?string
    {
        if (!file_exists($originalPath)) {
            return null;
        }

        $cacheDir = sfConfig::get('sf_root_dir') . '/downloads/watermarked';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
        $cachedPath = $cacheDir . '/' . $objectId . '_watermarked.' . $ext;

        // Return cached version if exists
        if (file_exists($cachedPath)) {
            return $cachedPath;
        }

        // Create watermarked version using ImageMagick
        if (extension_loaded('imagick')) {
            try {
                $image = new \Imagick($originalPath);

                // Scale to reasonable size first (max 1500px)
                $width = $image->getImageWidth();
                $height = $image->getImageHeight();
                if ($width > 1500 || $height > 1500) {
                    $image->scaleImage(1500, 1500, true);
                    $width = $image->getImageWidth();
                    $height = $image->getImageHeight();
                }

                // Create watermark text
                $watermarkText = 'RESEARCH USE ONLY';
                $draw = new \ImagickDraw();
                $draw->setFont('Helvetica-Bold');
                $draw->setFontSize(min($width, $height) / 15);
                $draw->setFillColor(new \ImagickPixel('rgba(128, 128, 128, 0.5)'));
                $draw->setGravity(\Imagick::GRAVITY_CENTER);

                // Add diagonal watermark
                $image->annotateImage($draw, 0, 0, -30, $watermarkText);

                // Add multiple smaller watermarks
                $draw->setFontSize(min($width, $height) / 25);
                $draw->setFillColor(new \ImagickPixel('rgba(128, 128, 128, 0.3)'));

                for ($y = 100; $y < $height; $y += 200) {
                    for ($x = 50; $x < $width; $x += 300) {
                        $draw->setGravity(\Imagick::GRAVITY_NORTHWEST);
                        $image->annotateImage($draw, $x, $y, -30, 'RESEARCH USE');
                    }
                }

                $image->setImageCompressionQuality(85);
                $image->writeImage($cachedPath);
                $image->destroy();

                return $cachedPath;
            } catch (\Exception $e) {
                error_log('Failed to create watermarked version: ' . $e->getMessage());
            }
        }

        // Fall back to original if we can't create watermarked version
        return $originalPath;
    }

    /**
     * Serve the file to the browser
     */
    protected function serveFile($filePath, $orderItem, $productType): void
    {
        $filename = pathinfo($filePath, PATHINFO_BASENAME);
        $mimeType = $this->getMimeType($filePath);
        $fileSize = filesize($filePath);

        // Create a descriptive filename
        $title = $orderItem->archival_description ?? 'download';
        $title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $title = substr($title, 0, 50);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $downloadFilename = $title . '_' . strtolower(str_replace(' ', '_', $productType->name)) . '.' . $ext;

        // Set headers
        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Content-Type', $mimeType);
        $this->getResponse()->setHttpHeader('Content-Length', $fileSize);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $downloadFilename . '"');
        $this->getResponse()->setHttpHeader('Cache-Control', 'private, max-age=0, must-revalidate');
        $this->getResponse()->setHttpHeader('Pragma', 'public');

        // Send headers
        $this->getResponse()->sendHttpHeaders();

        // Stream file
        readfile($filePath);
        exit;
    }

    /**
     * Get MIME type for file
     */
    protected function getMimeType($filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'pdf' => 'application/pdf',
            'webp' => 'image/webp',
        ];

        if (isset($mimeTypes[$ext])) {
            return $mimeTypes[$ext];
        }

        // Use finfo if available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType ?: 'application/octet-stream';
        }

        return 'application/octet-stream';
    }
}
