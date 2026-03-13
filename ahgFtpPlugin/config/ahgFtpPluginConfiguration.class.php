<?php

/**
 * ahgFtpPlugin configuration.
 *
 * Browser-based FTP/SFTP upload for CSV import digital objects.
 */
class ahgFtpPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'FTP/SFTP upload for CSV import digital objects';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ftpUpload';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $r = new \AtomFramework\Routing\RouteLoader('ftpUpload');

        $r->any('ftp_upload_index', '/ftp-upload', 'index');
        $r->any('ftp_upload_do', '/ftp-upload/upload', 'upload');
        $r->any('ftp_upload_chunk', '/ftp-upload/chunk', 'uploadChunk');
        $r->any('ftp_upload_list', '/ftp-upload/list', 'listFiles');
        $r->any('ftp_upload_delete', '/ftp-upload/delete', 'deleteFile');

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'FTP / SFTP Upload',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                'Browser-based drag-and-drop file upload',
                'FTP and SFTP protocol support',
                'Remote file listing and management',
                'CSV digitalObjectPath guidance',
            ],
        ];
    }
}
