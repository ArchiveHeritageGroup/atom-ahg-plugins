<?php

namespace ahgDataMigrationPlugin\Services;

class PathTransformer
{
    /**
     * Transform a path value based on transformation settings
     */
    public static function transform($value, $transformType, $options = [])
    {
        if (empty($value) || empty($transformType)) {
            return $value;
        }
        
        switch ($transformType) {
            case 'filename':
                // Extract just the filename
                return self::extractFilename($value);
                
            case 'replace_prefix':
            case 'replace':
                // Replace path prefix
                $find = $options['find'] ?? '';
                $replace = $options['replace'] ?? '';
                if ($find) {
                    $value = str_replace($find, $replace, $value);
                }
                return self::normalizeSlashes($value);
                
            case 'add_prefix':
            case 'prefix':
                // Add prefix to filename
                $prefix = $options['prefix'] ?? $options['replace'] ?? '';
                $filename = self::extractFilename($value);
                return $prefix . $filename;
                
            case 'lowercase':
                return strtolower($value);
                
            case 'uppercase':
                return strtoupper($value);
                
            case 'extension':
                // Change file extension
                $newExt = $options['extension'] ?? '';
                if ($newExt) {
                    return preg_replace('/\.[^.]+$/', $newExt, $value);
                }
                return $value;
                
            case 'normalize':
                // Just normalize slashes
                return self::normalizeSlashes($value);
                
            default:
                return $value;
        }
    }
    
    /**
     * Extract just the filename from a path
     */
    public static function extractFilename($path)
    {
        // Handle both Windows and Unix paths
        $path = str_replace('\\', '/', $path);
        $parts = explode('/', $path);
        return end($parts);
    }
    
    /**
     * Normalize path slashes to forward slashes
     */
    public static function normalizeSlashes($path)
    {
        return str_replace('\\', '/', $path);
    }
    
    /**
     * Build a relative path from absolute path
     */
    public static function makeRelative($absolutePath, $basePath)
    {
        $absolutePath = self::normalizeSlashes($absolutePath);
        $basePath = self::normalizeSlashes($basePath);
        
        if (strpos($absolutePath, $basePath) === 0) {
            return substr($absolutePath, strlen($basePath));
        }
        
        return $absolutePath;
    }
}
