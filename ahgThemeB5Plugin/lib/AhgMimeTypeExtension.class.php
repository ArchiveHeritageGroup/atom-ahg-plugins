<?php

/**
 * Mime type extension loader.
 * 
 * Call AhgMimeTypeExtension::register() in your plugin configuration
 * to add additional mime types without modifying core files.
 */
class AhgMimeTypeExtension
{
    protected static $registered = false;
    
    /**
     * Additional mime types to register.
     */
    protected static $mimeTypes = [
        // Video
        'wtv' => 'video/x-ms-wtv',
        'hevc' => 'video/hevc',
        'm2ts' => 'video/mp2t',
        'mts' => 'video/mp2t',
        'ts' => 'video/mp2t',
        
        // Audio
        'ac3' => 'audio/ac3',
        '8svx' => 'audio/8svx',
        'amb' => 'audio/AMB',
        
        // RAW Images
        'cr2' => 'image/x-canon-cr2',
        'nef' => 'image/x-nikon-nef',
        'arw' => 'image/x-sony-arw',
        'dng' => 'image/x-adobe-dng',
    ];
    
    /**
     * Formats that need streaming proxy.
     */
    protected static $streamingFormats = [
        'video/x-ms-wtv',
        'video/hevc', 
        'video/mp2t',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-ms-wmv',
        'video/x-flv',
        'video/x-ms-asf',
        'application/mxf',
        'audio/x-aiff',
        'audio/basic',
        'audio/ac3',
        'audio/8svx',
    ];
    
    /**
     * Register additional mime types with QubitDigitalObject.
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }
        
        // Use reflection to add to the protected static array
        if (!class_exists('QubitDigitalObject')) { return; }
        $reflection = new ReflectionClass('QubitDigitalObject');
        $property = $reflection->getProperty('qubitMimeTypes');
        $property->setAccessible(true);
        
        $currentTypes = $property->getValue(null);
        $mergedTypes = array_merge($currentTypes, self::$mimeTypes);
        $property->setValue(null, $mergedTypes);
        
        self::$registered = true;
    }
    
    /**
     * Check if mime type needs streaming.
     */
    public static function needsStreaming($mimeType)
    {
        return in_array($mimeType, self::$streamingFormats);
    }
    
    /**
     * Get mime type for extension.
     */
    public static function getMimeType($extension)
    {
        $ext = strtolower($extension);
        return self::$mimeTypes[$ext] ?? null;
    }
    
    /**
     * Get all additional mime types.
     */
    public static function getAdditionalMimeTypes()
    {
        return self::$mimeTypes;
    }
}
