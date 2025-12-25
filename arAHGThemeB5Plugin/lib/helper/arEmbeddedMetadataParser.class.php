<?php

/**
 * arEmbeddedMetadataParser - Wrapper for backward compatibility
 * Delegates to AtomFramework\Helpers\EmbeddedMetadataParser
 */

require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Helpers/EmbeddedMetadataParser.php';

use AtomFramework\Helpers\EmbeddedMetadataParser as FrameworkParser;

class arEmbeddedMetadataParser
{
    public static function extract($filePath)
    {
        return FrameworkParser::extract($filePath);
    }

    public static function extractExif($filePath)
    {
        return FrameworkParser::extractExif($filePath);
    }

    public static function extractIptc($filePath)
    {
        return FrameworkParser::extractIptc($filePath);
    }

    public static function extractXmp($filePath)
    {
        return FrameworkParser::extractXmp($filePath);
    }
}
