<?php

/**
 * ExporterInterface - Common interface for all metadata exporters
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Contracts
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Contracts;

interface ExporterInterface
{
    /**
     * Export a single resource to the target format
     *
     * @param mixed $resource The resource to export (QubitInformationObject, etc.)
     * @param array $options  Export options (includeDigitalObjects, includeDrafts, etc.)
     *
     * @return string The exported content
     */
    public function export($resource, array $options = []): string;

    /**
     * Export multiple resources as a batch (generator for memory efficiency)
     *
     * @param array $resources Array of resources to export
     * @param array $options   Export options
     *
     * @return \Generator Yields exported content for each resource
     */
    public function exportBatch(array $resources, array $options = []): \Generator;

    /**
     * Export resource and save to file
     *
     * @param mixed  $resource The resource to export
     * @param string $path     Output file path
     * @param array  $options  Export options
     *
     * @return bool True on success
     */
    public function exportToFile($resource, string $path, array $options = []): bool;

    /**
     * Get the format code (e.g., 'ead3', 'rico', 'lido')
     *
     * @return string
     */
    public function getFormat(): string;

    /**
     * Get the format name for display (e.g., 'EAD3', 'RIC-O', 'LIDO')
     *
     * @return string
     */
    public function getFormatName(): string;

    /**
     * Get the MIME type for the exported content
     *
     * @return string
     */
    public function getMimeType(): string;

    /**
     * Get the file extension for the exported content
     *
     * @return string
     */
    public function getFileExtension(): string;

    /**
     * Check if this exporter supports the given resource type
     *
     * @param string $type Resource type (e.g., 'QubitInformationObject', 'QubitActor')
     *
     * @return bool
     */
    public function supportsResourceType(string $type): bool;

    /**
     * Get the list of supported resource types
     *
     * @return array
     */
    public function getSupportedResourceTypes(): array;

    /**
     * Get the sector this exporter is primarily for
     *
     * @return string (Archives, Museums, Libraries, Media, Preservation)
     */
    public function getSector(): string;

    /**
     * Get default export options
     *
     * @return array
     */
    public function getDefaultOptions(): array;

    /**
     * Validate export options
     *
     * @param array $options
     *
     * @return array Validated and normalized options
     *
     * @throws \InvalidArgumentException If options are invalid
     */
    public function validateOptions(array $options): array;
}
