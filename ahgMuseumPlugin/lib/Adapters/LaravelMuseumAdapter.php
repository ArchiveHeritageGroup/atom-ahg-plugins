<?php

namespace ahgMuseumPlugin\Adapters;

use AtomFramework\Museum\Contracts\CcoValidatorInterface;
use AtomFramework\Museum\Contracts\MaterialTaxonomyInterface;
use AtomFramework\Museum\Contracts\MuseumMetadataInterface;
use AtomFramework\Museum\Contracts\ObjectCatalogerInterface;
use AtomFramework\Museum\Models\MuseumObject;
use AtomFramework\Museum\Services\CcoValidator;
use AtomFramework\Museum\Services\MaterialTaxonomyService;
use AtomFramework\Museum\Services\MeasurementService;
use AtomFramework\Museum\Services\ObjectCataloger;
use AtomFramework\Museum\Services\ObjectTypeManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel-based adapter for museum metadata functionality.
 *
 * This adapter bridges AtoM's Symfony/Propel layer with the framework-agnostic
 * museum metadata services.
 */
class LaravelMuseumAdapter implements MuseumMetadataInterface
{
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private CcoValidatorInterface $validator;
    private ObjectCatalogerInterface $cataloger;
    private ObjectTypeManager $typeManager;
    private MaterialTaxonomyInterface $materialTaxonomy;
    private MeasurementService $measurementService;
    private array $validationErrors = [];

    public function __construct()
    {
        // Bootstrap the framework - use global namespace for sfConfig
        $this->container = require \sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        // Get services from container
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->validator = $this->container->get(CcoValidator::class);
        $this->cataloger = $this->container->get(ObjectCataloger::class);
        $this->typeManager = $this->container->get(ObjectTypeManager::class);
        $this->materialTaxonomy = $this->container->get(MaterialTaxonomyService::class);
        $this->measurementService = $this->container->get(MeasurementService::class);

        $this->logger->info('LaravelMuseumAdapter initialized');
    }

    public function getCcoFields(string $workType): array
    {
        $this->logger->debug('Getting CCO fields', ['work_type' => $workType]);

        return $this->typeManager->getFields($workType);
    }

    public function validateCcoRequirements(array $data, string $workType): bool
    {
        $this->logger->debug('Validating CCO requirements', [
            'work_type' => $workType,
            'data_keys' => array_keys($data),
        ]);

        $result = $this->validator->validate($data, $workType);
        $this->validationErrors = $result['errors'];

        return $result['valid'];
    }

    public function enrichWithCcoMetadata(int $objectId, array $museumProperties): void
    {
        $this->logger->info('Enriching information object with CCO metadata', [
            'object_id' => $objectId,
        ]);

        try {
            // Check if museum object already exists
            $existingObject = $this->cataloger->findByInformationObjectId($objectId);

            if ($existingObject) {
                // Update existing
                $this->cataloger->update($existingObject->getId(), $museumProperties);
                $this->logger->info('Updated existing museum object', [
                    'museum_object_id' => $existingObject->getId(),
                ]);
            } else {
                // Create new
                $museumObject = $this->cataloger->create($objectId, $museumProperties);
                $this->logger->info('Created new museum object', [
                    'museum_object_id' => $museumObject->getId(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to enrich with CCO metadata', [
                'error' => $e->getMessage(),
                'object_id' => $objectId,
            ]);

            throw $e;
        }
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get museum metadata for an information object.
     *
     * @param int $informationObjectId
     *
     * @return null|MuseumObject
     */
    public function getMuseumMetadata(int $informationObjectId): ?MuseumObject
    {
        return $this->cataloger->findByInformationObjectId($informationObjectId);
    }

    /**
     * Check if an information object has museum metadata.
     *
     * @param int $informationObjectId
     *
     * @return bool
     */
    public function hasMuseumMetadata(int $informationObjectId): bool
    {
        return $this->cataloger->exists($informationObjectId);
    }

    /**
     * Delete museum metadata for an information object.
     *
     * @param int $informationObjectId
     *
     * @return bool
     */
    public function deleteMuseumMetadata(int $informationObjectId): bool
    {
        $museumObject = $this->cataloger->findByInformationObjectId($informationObjectId);

        if (!$museumObject) {
            return false;
        }

        return $this->cataloger->delete($museumObject->getId());
    }

    /**
     * Get available work types.
     *
     * @return array
     */
    public function getWorkTypes(): array
    {
        return $this->typeManager->getWorkTypes();
    }

    /**
     * Get work type configuration.
     *
     * @param string $workType
     *
     * @return null|array
     */
    public function getWorkTypeConfig(string $workType): ?array
    {
        return $this->typeManager->getWorkTypeConfig($workType);
    }

    /**
     * Get all materials.
     *
     * @return array
     */
    public function getMaterials(): array
    {
        return $this->materialTaxonomy->getAllMaterials();
    }

    /**
     * Get materials by category.
     *
     * @param string $category
     *
     * @return array
     */
    public function getMaterialsByCategory(string $category): array
    {
        return $this->materialTaxonomy->getMaterialsByCategory($category);
    }

    /**
     * Get all techniques.
     *
     * @return array
     */
    public function getTechniques(): array
    {
        return $this->materialTaxonomy->getAllTechniques();
    }

    /**
     * Search materials.
     *
     * @param string $search
     * @param int    $limit
     *
     * @return array
     */
    public function searchMaterials(string $search, int $limit = 10): array
    {
        return $this->materialTaxonomy->searchMaterials($search, $limit);
    }

    /**
     * Search techniques.
     *
     * @param string $search
     * @param int    $limit
     *
     * @return array
     */
    public function searchTechniques(string $search, int $limit = 10): array
    {
        return $this->materialTaxonomy->searchTechniques($search, $limit);
    }

    /**
     * Format measurements for display.
     *
     * @param array $measurements
     *
     * @return string
     */
    public function formatMeasurements(array $measurements): string
    {
        return $this->measurementService->formatMeasurements($measurements);
    }

    /**
     * Get extent statement from measurements.
     *
     * @param array $measurements
     *
     * @return string
     */
    public function getExtentStatement(array $measurements): string
    {
        return $this->measurementService->getExtentStatement($measurements);
    }

    /**
     * Parse measurements from various formats.
     *
     * @param mixed $input
     *
     * @return array
     */
    public function parseMeasurements($input): array
    {
        return $this->measurementService->parseMeasurements($input);
    }

    /**
     * Get the logger instance.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the cataloger instance.
     *
     * @return ObjectCatalogerInterface
     */
    public function getCataloger(): ObjectCatalogerInterface
    {
        return $this->cataloger;
    }
}
