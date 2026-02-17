<?php

namespace AhgFederation;

/**
 * Custom OAI Set for Heritage Platform Federation
 *
 * Provides an additional OAI set that includes all records
 * suitable for heritage federation harvesting.
 */
class OaiHeritageSet implements \QubitOaiSet
{
    public const SET_SPEC = 'heritage:federation';
    public const SET_NAME = 'Heritage Federation Records';

    /**
     * Check if a record belongs to this set
     */
    public function contains($record): bool
    {
        // All published records are included in the heritage federation set
        $status = $record->getPublicationStatus();
        return $status && $status->statusId === \QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID;
    }

    /**
     * Get the set specification
     */
    public function setSpec(): string
    {
        return self::SET_SPEC;
    }

    /**
     * Get the set name
     */
    public function getName(): string
    {
        return self::SET_NAME;
    }

    /**
     * Apply set criteria to a query
     */
    public function apply($criteria): void
    {
        // Only include published records
        $criteria->add(\QubitInformationObject::PARENT_ID, null, \Criteria::ISNOTNULL);

        // Join with status table to filter by publication status
        $criteria->addJoin(
            \QubitInformationObject::ID,
            \QubitStatus::OBJECT_ID,
            \Criteria::LEFT_JOIN
        );

        $criteria->add(\QubitStatus::TYPE_ID, \QubitTerm::STATUS_TYPE_PUBLICATION_ID);
        $criteria->add(\QubitStatus::STATUS_ID, \QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID);
    }
}

/**
 * OAI Set for records from a specific repository
 */
class OaiRepositorySet implements \QubitOaiSet
{
    private $repository;

    public function __construct(\QubitRepository $repository)
    {
        $this->repository = $repository;
    }

    public function contains($record): bool
    {
        return $record->repositoryId === $this->repository->id;
    }

    public function setSpec(): string
    {
        return 'repository:' . $this->repository->slug;
    }

    public function getName(): string
    {
        return $this->repository->getAuthorizedFormOfName(['cultureFallback' => true]);
    }

    public function apply($criteria): void
    {
        $criteria->add(\QubitInformationObject::PARENT_ID, null, \Criteria::ISNOTNULL);
        $criteria->add(\QubitInformationObject::REPOSITORY_ID, $this->repository->id);
    }
}

/**
 * OAI Set for records at a specific level of description
 */
class OaiLevelSet implements \QubitOaiSet
{
    private $level;

    public function __construct(\QubitTerm $level)
    {
        $this->level = $level;
    }

    public function contains($record): bool
    {
        return $record->levelOfDescriptionId === $this->level->id;
    }

    public function setSpec(): string
    {
        return 'level:' . strtolower(str_replace(' ', '_', $this->level->getName(['culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()])));
    }

    public function getName(): string
    {
        return 'Level: ' . $this->level->getName(['cultureFallback' => true]);
    }

    public function apply($criteria): void
    {
        $criteria->add(\QubitInformationObject::PARENT_ID, null, \Criteria::ISNOTNULL);
        $criteria->add(\QubitInformationObject::LEVEL_OF_DESCRIPTION_ID, $this->level->id);
    }
}
