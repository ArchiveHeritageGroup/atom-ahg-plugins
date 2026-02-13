<?php

/**
 * Digital Object Edit Action - Pure Laravel Implementation
 */

use Illuminate\Database\Capsule\Manager as DB;
use AtomFramework\Http\Controllers\AhgController;

class digitalobjectEditAction extends AhgController
{
    const TAXONOMY_MEDIA_TYPE = 46;
    const USAGE_REFERENCE = 141;
    const USAGE_THUMBNAIL = 142;
    const USAGE_CHAPTERS = 195;
    const USAGE_SUBTITLES = 196;
    const MEDIA_VIDEO = 138;
    const MEDIA_AUDIO = 135;
    const MEDIA_IMAGE = 136;

    protected $uploadDir;

    public function execute($request)
    {
        ProjectConfiguration::getActive()->loadHelpers(['Qubit', 'Asset']);

        // Get upload directory from config
        $this->uploadDir = sfConfig::get('sf_upload_dir');

        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // Get resource from route (AtoM standard way)
        $resource = $this->getRoute()->resource;
        
        $id = null;
        if ($resource && isset($resource->id)) {
            $id = (int) $resource->id;
        }
        
        // Fallback to id parameter
        if (!$id) {
            $id = (int) $request->getParameter('id');
        }
        
        // Fallback to slug parameter
        if (!$id) {
            $slug = $request->getParameter('slug');
            if ($slug) {
                $id = (int) DB::table('slug')
                    ->where('slug', $slug)
                    ->value('object_id');
            }
        }

        if (!$id) {
            $this->forward404();
        }

        // Fetch digital object directly
        $this->resourceData = DB::table('digital_object')
            ->where('id', $id)
            ->first();

        if (!$this->resourceData) {
            $this->forward404();
        }

        // Get reference image (child with usage_id 141)
        $this->referenceImage = DB::table('digital_object')
            ->where('parent_id', $id)
            ->where('usage_id', self::USAGE_REFERENCE)
            ->first();

        // Get thumbnail image (child with usage_id 142)
        $this->thumbnailImage = DB::table('digital_object')
            ->where('parent_id', $id)
            ->where('usage_id', self::USAGE_THUMBNAIL)
            ->first();

        // Get video tracks
        $this->chaptersTrack = DB::table('digital_object')
            ->where('parent_id', $id)
            ->where('usage_id', self::USAGE_CHAPTERS)
            ->first();

        $this->subtitlesTrack = DB::table('digital_object')
            ->where('parent_id', $id)
            ->where('usage_id', self::USAGE_SUBTITLES)
            ->first();

        // Get parent - check object_id
        $objectId = $this->resourceData->object_id;
        $this->parent = null;

        if ($objectId) {
            $objectClass = DB::table('object')
                ->where('id', $objectId)
                ->value('class_name');

            if ($objectClass === 'QubitInformationObject') {
                $this->parent = DB::table('information_object as io')
                    ->leftJoin('information_object_i18n as i18n', function ($join) {
                        $join->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                    ->where('io.id', $objectId)
                    ->select('io.id', 'i18n.title', 's.slug', DB::raw("'informationobject' as module"))
                    ->first();
            } elseif ($objectClass === 'QubitActor') {
                $this->parent = DB::table('actor as a')
                    ->leftJoin('actor_i18n as i18n', function ($join) {
                        $join->on('a.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->leftJoin('slug as s', 'a.id', '=', 's.object_id')
                    ->where('a.id', $objectId)
                    ->select('a.id', 'i18n.authorized_form_of_name as title', 's.slug', DB::raw("'actor' as module"))
                    ->first();
            } elseif ($objectClass === 'QubitRepository') {
                $this->parent = DB::table('actor as a')
                    ->leftJoin('actor_i18n as i18n', function ($join) {
                        $join->on('a.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->leftJoin('slug as s', 'a.id', '=', 's.object_id')
                    ->where('a.id', $objectId)
                    ->select('a.id', 'i18n.authorized_form_of_name as title', 's.slug', DB::raw("'repository' as module"))
                    ->first();
            }
        }

        if (!$this->parent) {
            $this->parent = (object) [
                'id' => $objectId,
                'title' => 'Unknown',
                'slug' => '',
                'module' => 'informationobject',
            ];
        }

        // Check auth
        $user = $this->getUser();
        if (!$user || !$user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        // Max upload size
        $this->maxUploadSize = min(
            $this->parseSize(ini_get('upload_max_filesize')),
            $this->parseSize(ini_get('post_max_size'))
        );

        // Check if can thumbnail
        $ext = strtolower(pathinfo($this->resourceData->name ?? '', PATHINFO_EXTENSION));
        $this->canThumbnail = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif']);

        // Check for compound object toggle
        $this->showCompoundObjectToggle = $this->hasChildrenWithDigitalObjects();

        $this->addFormFields();

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters(), $request->getFiles());
            if ($this->form->isValid()) {
                $this->processForm();
                $this->redirect(['module' => $this->parent->module, 'slug' => $this->parent->slug]);
            }
        }
    }

    protected function parseSize(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;
        switch ($unit) {
            case 'G': $value *= 1024;
            case 'M': $value *= 1024;
            case 'K': $value *= 1024;
        }
        return $value;
    }

    protected function hasChildrenWithDigitalObjects(): bool
    {
        if (!$this->resourceData->object_id) {
            return false;
        }

        $children = DB::table('information_object')
            ->where('parent_id', $this->resourceData->object_id)
            ->pluck('id');

        if ($children->isEmpty()) {
            return false;
        }

        return DB::table('digital_object')
            ->whereIn('object_id', $children)
            ->exists();
    }

    protected function addFormFields()
    {
        // Master file replacement
        $this->form->setValidator('file', new sfValidatorFile(['required' => false]));
        $this->form->setWidget('file', new sfWidgetFormInputFile());

        // Media type
        $mediaTypes = [];
        $terms = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', self::TAXONOMY_MEDIA_TYPE)
            ->where('term_i18n.culture', 'en')
            ->orderBy('term_i18n.name')
            ->get();

        foreach ($terms as $term) {
            $mediaTypes[$term->id] = $term->name;
        }

        $this->form->setValidator('mediaType', new sfValidatorChoice(['choices' => array_keys($mediaTypes)]));
        $this->form->setWidget('mediaType', new sfWidgetFormSelect(['choices' => $mediaTypes]));
        $this->form->setDefault('mediaType', $this->resourceData->media_type_id);

        // Alt text
        $this->form->setValidator('digitalObjectAltText', new sfValidatorString(['required' => false]));
        $this->form->setWidget('digitalObjectAltText', new sfWidgetFormTextarea());
        $altText = $this->getPropertyValue($this->resourceData->id, 'digitalObjectAltText');
        if ($altText) {
            $this->form->setDefault('digitalObjectAltText', $altText);
        }

        // Compound object toggle
        if ($this->showCompoundObjectToggle) {
            $this->form->setValidator('displayAsCompound', new sfValidatorBoolean(['required' => false]));
            $this->form->setWidget('displayAsCompound', new sfWidgetFormSelectRadio([
                'choices' => ['1' => $this->context->i18n->__('Yes'), '0' => $this->context->i18n->__('No')],
            ]));
            $compoundValue = $this->getPropertyValue($this->resourceData->id, 'displayAsCompound');
            if ($compoundValue !== null) {
                $this->form->setDefault('displayAsCompound', $compoundValue);
            }
        }

        // Latitude
        $this->form->setValidator('latitude', new sfValidatorNumber(['required' => false]));
        $this->form->setWidget('latitude', new sfWidgetFormInput());
        $lat = $this->getPropertyValue($this->resourceData->id, 'latitude');
        if ($lat) {
            $this->form->setDefault('latitude', $lat);
        }

        // Longitude
        $this->form->setValidator('longitude', new sfValidatorNumber(['required' => false]));
        $this->form->setWidget('longitude', new sfWidgetFormInput());
        $lon = $this->getPropertyValue($this->resourceData->id, 'longitude');
        if ($lon) {
            $this->form->setDefault('longitude', $lon);
        }

        // Reference upload fields (only if no reference exists)
        if (!$this->referenceImage) {
            $this->form->setValidator('repFile_reference', new sfValidatorFile(['required' => false]));
            $this->form->setWidget('repFile_reference', new sfWidgetFormInputFile());

            $this->form->setValidator('generateDerivative_reference', new sfValidatorBoolean(['required' => false]));
            $this->form->setWidget('generateDerivative_reference', new sfWidgetFormInputCheckbox([], ['value' => 1]));
        }

        // Thumbnail upload fields (only if no thumbnail exists)
        if (!$this->thumbnailImage) {
            $this->form->setValidator('repFile_thumbnail', new sfValidatorFile(['required' => false]));
            $this->form->setWidget('repFile_thumbnail', new sfWidgetFormInputFile());

            $this->form->setValidator('generateDerivative_thumbnail', new sfValidatorBoolean(['required' => false]));
            $this->form->setWidget('generateDerivative_thumbnail', new sfWidgetFormInputCheckbox([], ['value' => 1]));
        }

        // Video track fields (only for video/audio)
        if (in_array($this->resourceData->media_type_id, [self::MEDIA_VIDEO, self::MEDIA_AUDIO])) {
            if (!$this->chaptersTrack) {
                $this->form->setValidator('trackFile_chapters', new sfValidatorFile(['required' => false]));
                $this->form->setWidget('trackFile_chapters', new sfWidgetFormInputFile());
            }

            $this->form->setValidator('trackFile_subtitles', new sfValidatorFile(['required' => false]));
            $this->form->setWidget('trackFile_subtitles', new sfWidgetFormInputFile());

            $this->form->setValidator('lang_subtitles', new sfValidatorI18nChoiceLanguage(['required' => false]));
            $this->form->setWidget('lang_subtitles', new sfWidgetFormI18nChoiceLanguage());
        }
    }

    protected function getPropertyValue(int $objectId, string $name): ?string
    {
        return DB::table('property as p')
            ->leftJoin('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id')->where('pi.culture', '=', 'en');
            })
            ->where('p.object_id', $objectId)
            ->where('p.name', $name)
            ->value('pi.value');
    }

    protected function saveProperty(int $objectId, string $name, ?string $value): void
    {
        // Treat empty string as null (delete) to preserve original behavior
        $value = ($value !== null && $value !== '') ? $value : null;

        // Delegate to WriteService — handles object→property→property_i18n lifecycle
        \AtomFramework\Services\Write\WriteServiceFactory::digitalObject()
            ->saveProperty($objectId, $name, $value, 'en');
    }

    protected function processForm()
    {
        // Handle master file replacement
        $masterFile = $this->form->getValue('file');
        if ($masterFile && $masterFile->getTempName()) {
            $this->replaceMasterFile($masterFile);
        }

        // Update media type
        $mediaType = $this->form->getValue('mediaType');
        if ($mediaType) {
            \AtomFramework\Services\Write\WriteServiceFactory::digitalObject()
                ->updateMetadata($this->resourceData->id, ['media_type_id' => $mediaType]);
        }

        // Save properties
        $this->saveProperty($this->resourceData->id, 'digitalObjectAltText', $this->form->getValue('digitalObjectAltText'));
        $this->saveProperty($this->resourceData->id, 'latitude', $this->form->getValue('latitude'));
        $this->saveProperty($this->resourceData->id, 'longitude', $this->form->getValue('longitude'));

        if ($this->showCompoundObjectToggle) {
            $this->saveProperty($this->resourceData->id, 'displayAsCompound', $this->form->getValue('displayAsCompound'));
        }

        // Handle reference upload
        $refFile = $this->form->getValue('repFile_reference');
        if ($refFile && $refFile->getTempName()) {
            $this->createDerivative(self::USAGE_REFERENCE, $refFile, 480, null);
        }

        // Handle reference auto-generate
        if ($this->form->getValue('generateDerivative_reference')) {
            $this->generateDerivativeFromMaster(self::USAGE_REFERENCE, 480, null);
        }

        // Handle thumbnail upload
        $thumbFile = $this->form->getValue('repFile_thumbnail');
        if ($thumbFile && $thumbFile->getTempName()) {
            $this->createDerivative(self::USAGE_THUMBNAIL, $thumbFile, 100, 100);
        }

        // Handle thumbnail auto-generate
        if ($this->form->getValue('generateDerivative_thumbnail')) {
            $this->generateDerivativeFromMaster(self::USAGE_THUMBNAIL, 100, 100);
        }

        // Handle video track uploads
        $chaptersFile = $this->form->getValue('trackFile_chapters');
        if ($chaptersFile && $chaptersFile->getTempName()) {
            $this->createTrack(self::USAGE_CHAPTERS, $chaptersFile);
        }

        $subtitlesFile = $this->form->getValue('trackFile_subtitles');
        if ($subtitlesFile && $subtitlesFile->getTempName()) {
            $lang = $this->form->getValue('lang_subtitles');
            $this->createTrack(self::USAGE_SUBTITLES, $subtitlesFile, $lang);
        }

        // Update timestamp
        DB::table('object')
            ->where('id', $this->resourceData->id)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    protected function createDerivative(int $usageId, $uploadFile, int $maxWidth, ?int $maxHeight): void
    {
        $tempPath = $uploadFile->getTempName();
        $originalName = $uploadFile->getOriginalName();

        // Read and resize image
        $content = $this->resizeImage($tempPath, $maxWidth, $maxHeight);

        // Create the derivative record
        $this->saveDerivativeRecord($usageId, $originalName, $content);
    }

    protected function generateDerivativeFromMaster(int $usageId, int $maxWidth, ?int $maxHeight): void
    {
        // Get master file path
        $masterPath = $this->getMasterFilePath();
        if (!$masterPath || !file_exists($masterPath)) {
            return;
        }

        // Generate filename based on usage
        $ext = 'jpg';
        $baseName = pathinfo($this->resourceData->name, PATHINFO_FILENAME);
        $suffix = $usageId == self::USAGE_REFERENCE ? '_141' : '_142';
        $filename = $baseName . $suffix . '.' . $ext;

        // Resize image
        $content = $this->resizeImage($masterPath, $maxWidth, $maxHeight);

        // Create the derivative record
        $this->saveDerivativeRecord($usageId, $filename, $content);
    }

    protected function getMasterFilePath(): ?string
    {
        if (!$this->resourceData->path || !$this->resourceData->name) {
            return null;
        }

        $path = $this->resourceData->path;

        // Handle path that already includes /uploads/
        if (strpos($path, '/uploads/') === 0) {
            $path = substr($path, 9); // Remove /uploads/
        }

        return $this->uploadDir . '/' . ltrim($path, '/') . $this->resourceData->name;
    }

    protected function resizeImage(string $sourcePath, int $maxWidth, ?int $maxHeight): string
    {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return file_get_contents($sourcePath);
        }

        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];

        // Calculate new dimensions
        $ratio = 1;
        if ($maxWidth && $srcWidth > $maxWidth) {
            $ratio = min($ratio, $maxWidth / $srcWidth);
        }
        if ($maxHeight && $srcHeight > $maxHeight) {
            $ratio = min($ratio, $maxHeight / $srcHeight);
        }

        $newWidth = max(1, (int) ($srcWidth * $ratio));
        $newHeight = max(1, (int) ($srcHeight * $ratio));

        // Create source image
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $srcImage = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $srcImage = @imagecreatefromwebp($sourcePath);
                break;
            case 'image/tiff':
                // TIFF needs ImageMagick
                return $this->resizeWithImageMagick($sourcePath, $maxWidth, $maxHeight);
            default:
                return file_get_contents($sourcePath);
        }

        if (!$srcImage) {
            return file_get_contents($sourcePath);
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF
        if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        ob_start();
        imagejpeg($dstImage, null, 85);
        $content = ob_get_clean();

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $content;
    }

    protected function resizeWithImageMagick(string $sourcePath, int $maxWidth, ?int $maxHeight): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'img');
        $dimensions = $maxHeight ? "{$maxWidth}x{$maxHeight}" : "{$maxWidth}x";

        exec("convert " . escapeshellarg($sourcePath) . " -resize " . escapeshellarg($dimensions) . " -quality 85 jpeg:" . escapeshellarg($tmpFile));

        $content = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $content;
    }

    protected function saveDerivativeRecord(int $usageId, string $filename, string $content): void
    {
        // Use same path as master
        $path = $this->resourceData->path;
        if (strpos($path, '/uploads/') === 0) {
            $path = substr($path, 9);
        }
        $path = rtrim($path, '/');

        // Save file to disk
        $fullDir = $this->uploadDir . '/' . ltrim($path, '/');

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        // Generate unique filename
        $suffix = $usageId == self::USAGE_REFERENCE ? '_141' : '_142';
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $ext = 'jpg';
        $newFilename = $baseName . $suffix . '.' . $ext;

        file_put_contents($fullDir . '/' . $newFilename, $content);

        // Create derivative record via WriteService (handles object + digital_object rows)
        \AtomFramework\Services\Write\WriteServiceFactory::digitalObject()
            ->createDerivative($this->resourceData->id, [
                'usage_id' => $usageId,
                'mime_type' => 'image/jpeg',
                'media_type_id' => self::MEDIA_IMAGE,
                'name' => $newFilename,
                'path' => '/uploads/' . ltrim($path, '/') . '/',
                'byte_size' => strlen($content),
                'checksum' => md5($content),
                'checksum_type' => 'md5',
            ]);
    }

    protected function createTrack(int $usageId, $uploadFile, ?string $language = null): void
    {
        $tempPath = $uploadFile->getTempName();
        $originalName = $uploadFile->getOriginalName();
        $content = file_get_contents($tempPath);

        // Use same path as master
        $path = $this->resourceData->path;
        if (strpos($path, '/uploads/') === 0) {
            $path = substr($path, 9);
        }
        $path = rtrim($path, '/');

        // Save file to disk
        $fullDir = $this->uploadDir . '/' . ltrim($path, '/');

        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        file_put_contents($fullDir . '/' . $originalName, $content);

        // Determine mime type
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = $ext === 'vtt' ? 'text/vtt' : 'application/x-subrip';

        // Create derivative record via WriteService (handles object + digital_object rows)
        $doWriteService = \AtomFramework\Services\Write\WriteServiceFactory::digitalObject();
        $trackId = $doWriteService->createDerivative($this->resourceData->id, [
            'usage_id' => $usageId,
            'mime_type' => $mimeType,
            'media_type_id' => 137, // Text
            'name' => $originalName,
            'path' => '/uploads/' . ltrim($path, '/') . '/',
            'byte_size' => strlen($content),
            'checksum' => md5($content),
            'checksum_type' => 'md5',
        ]);

        // Save language property if provided
        if ($language) {
            $doWriteService->saveProperty($trackId, 'language', $language, 'en');
        }
    }

    protected function replaceMasterFile($uploadFile): void
    {
        $tempPath = $uploadFile->getTempName();
        $originalName = $uploadFile->getOriginalName();
        $content = file_get_contents($tempPath);
        $mimeType = $uploadFile->getType();

        // Determine media type from mime
        $mediaTypeId = self::MEDIA_IMAGE;
        if (strpos($mimeType, "video") !== false) {
            $mediaTypeId = self::MEDIA_VIDEO;
        } elseif (strpos($mimeType, "audio") !== false) {
            $mediaTypeId = self::MEDIA_AUDIO;
        } elseif (strpos($mimeType, "text") !== false || strpos($mimeType, "pdf") !== false) {
            $mediaTypeId = 137; // Text
        }

        // Get or create path
        $path = $this->resourceData->path;
        if (!$path) {
            $hash = hash("sha256", $this->resourceData->object_id . time());
            $path = "/uploads/r/" . substr($hash, 0, 2) . "/" . substr($hash, 2, 2) . "/" . substr($hash, 4, 2) . "/" . $hash . "/";
        }
        if (strpos($path, "/uploads/") === 0) {
            $pathForDir = substr($path, 9);
        } else {
            $pathForDir = $path;
        }

        $fullDir = $this->uploadDir . "/" . ltrim($pathForDir, "/");
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        // Delete old file if exists
        if ($this->resourceData->name) {
            $oldPath = $fullDir . $this->resourceData->name;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Save new file
        file_put_contents($fullDir . $originalName, $content);

        // Update digital object record via WriteService
        \AtomFramework\Services\Write\WriteServiceFactory::digitalObject()
            ->updateMetadata($this->resourceData->id, [
                'name' => $originalName,
                'path' => $path,
                'mime_type' => $mimeType,
                'media_type_id' => $mediaTypeId,
                'byte_size' => strlen($content),
                'checksum' => md5($content),
                'checksum_type' => 'md5',
            ]);
    }

}