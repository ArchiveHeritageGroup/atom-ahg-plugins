<?php

namespace AhgCore\Contracts;

/**
 * MetadataExtractorInterface
 *
 * Interface for metadata extraction services.
 * Used by ahgMetadataExtractionPlugin and other plugins that
 * extract metadata from files.
 */
interface MetadataExtractorInterface
{
    /**
     * Extract metadata from a file
     *
     * @param string $filePath Path to the file
     * @param array $options Extraction options
     * @return array Extracted metadata
     */
    public function extract(string $filePath, array $options = []): array;

    /**
     * Get supported MIME types
     *
     * @return array List of supported MIME types
     */
    public function getSupportedTypes(): array;

    /**
     * Check if extractor supports a file type
     *
     * @param string $mimeType MIME type to check
     * @return bool
     */
    public function supports(string $mimeType): bool;

    /**
     * Get extractor identifier
     *
     * @return string
     */
    public function getId(): string;
}
