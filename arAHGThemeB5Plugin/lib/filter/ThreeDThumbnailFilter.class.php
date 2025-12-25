<?php

/**
 * Filter to trigger 3D thumbnail generation after digital object upload
 */
class ThreeDThumbnailFilter extends sfFilter
{
    protected static $supported3DExtensions = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae'];

    public function execute($filterChain)
    {
        // Execute the action first
        $filterChain->execute();

        // Only process after digital object edit/create actions
        $moduleName = $this->context->getModuleName();
        $actionName = $this->context->getActionName();

        if ($moduleName === 'digitalobject' && in_array($actionName, ['edit', 'create'])) {
            $this->processIfNeeded();
        }

        // Also check for information object with digital object upload
        if ($moduleName === 'informationobject' && in_array($actionName, ['edit', 'create', 'update'])) {
            $this->processIfNeeded();
        }
    }

    protected function processIfNeeded()
    {
        $request = $this->context->getRequest();
        $response = $this->context->getResponse();

        // Only process on successful POST requests (redirects indicate success)
        if ($request->getMethod() !== sfRequest::POST) {
            return;
        }

        // Check if we have a digital object ID in the request or session
        $digitalObjectId = $request->getParameter('id');
        
        if (!$digitalObjectId) {
            // Try to get from the resource
            $resource = $this->context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
            if ($resource && method_exists($resource, 'getDigitalObject')) {
                $do = $resource->getDigitalObject();
                if ($do) {
                    $digitalObjectId = $do->id;
                }
            }
        }

        if (!$digitalObjectId) {
            return;
        }

        // Check if it's a 3D file
        $do = QubitDigitalObject::getById($digitalObjectId);
        if (!$do || !$this->is3DFile($do->name)) {
            return;
        }

        // Queue background processing
        $this->queueThumbnailGeneration($digitalObjectId, $do->name);
    }

    protected function is3DFile($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, self::$supported3DExtensions);
    }

    protected function queueThumbnailGeneration($digitalObjectId, $filename)
    {
        $script = '/usr/share/nginx/archive/atom-framework/bin/process-3d-upload.sh';
        $logFile = '/usr/share/nginx/archive/log/3d-thumbnail.log';

        if (!file_exists($script)) {
            error_log("3D thumbnail script not found: {$script}");
            return;
        }

        // Log the queue action
        $logEntry = sprintf(
            "[%s] [INFO] Queued thumbnail generation for DO %d: %s\n",
            date('Y-m-d H:i:s'),
            $digitalObjectId,
            $filename
        );
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Execute in background
        $cmd = sprintf(
            'nohup %s %d > /dev/null 2>&1 &',
            escapeshellcmd($script),
            (int) $digitalObjectId
        );
        exec($cmd);
    }
}
