<?php

/**
 * Observer class to trigger 3D thumbnail generation after digital object save
 * 
 * To use, add to QubitDigitalObject::save() or call manually after upload
 */
class ThreeDThumbnailObserver
{
    protected static $supported3DExtensions = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae'];

    /**
     * Check if a digital object needs 3D thumbnail generation and queue it
     */
    public static function afterSave(QubitDigitalObject $digitalObject)
    {
        // Only process master objects (not derivatives)
        if ($digitalObject->parentId !== null) {
            return;
        }

        // Check if it's a 3D file
        if (!self::is3DFile($digitalObject->name)) {
            return;
        }

        // Check if derivatives already exist
        if (class_exists('Criteria')) {
            $criteria = new Criteria();
            $criteria->add(QubitDigitalObject::PARENT_ID, $digitalObject->id);
            $hasDerivatives = (null !== QubitDigitalObject::getOne($criteria));
        } else {
            $hasDerivatives = \Illuminate\Database\Capsule\Manager::table('digital_object')
                ->where('parent_id', $digitalObject->id)
                ->exists();
        }
        if ($hasDerivatives) {
            return; // Already has derivatives
        }

        // Queue thumbnail generation
        self::queueGeneration($digitalObject->id, $digitalObject->name);
    }

    protected static function is3DFile($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, self::$supported3DExtensions);
    }

    public static function queueGeneration($digitalObjectId, $filename = '')
    {
        $script = sfConfig::get('sf_root_dir') . '/atom-framework/bin/process-3d-upload.sh';
        $logFile = sfConfig::get('sf_root_dir') . '/log/3d-thumbnail.log';

        $logEntry = sprintf(
            "[%s] [INFO] Queued thumbnail generation for DO %d: %s\n",
            date('Y-m-d H:i:s'),
            $digitalObjectId,
            $filename
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        $cmd = sprintf(
            'nohup %s %d > /dev/null 2>&1 &',
            escapeshellcmd($script),
            (int) $digitalObjectId
        );
        exec($cmd);
    }
}
