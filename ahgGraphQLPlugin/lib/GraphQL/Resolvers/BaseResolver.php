<?php

namespace AhgGraphQLPlugin\GraphQL\Resolvers;

use AhgAPIPlugin\Repository\ApiRepository;
use AhgGraphQLPlugin\GraphQL\Schema\Types\ConnectionTypes;

abstract class BaseResolver
{
    protected ApiRepository $repository;
    protected string $culture;

    public function __construct(ApiRepository $repository, string $culture = 'en')
    {
        $this->repository = $repository;
        $this->culture = $culture;
    }

    /**
     * Restrict a query aliased "io" to the descriptions the caller may see:
     * drafts only for staff, never a record hidden by classification, donor
     * restriction, embargo, ICIP or ODRL. The same rule as the REST API
     * (ApiVisibility), which GraphQL bypassed until 8 Oct 2026. Fails closed.
     */
    protected function visible($query)
    {
        require_once \sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';
        \ApiVisibility::applyToQuery($query, \sfContext::getInstance()->getUser());

        return $query;
    }

    /** Whether the caller is staff (drafts and non-public custom fields). */
    protected function isStaff(): bool
    {
        require_once \sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';

        return \ApiVisibility::isStaff(\sfContext::getInstance()->getUser());
    }

    protected function buildConnection(array $items, int $total, int $offset, int $first): array
    {
        return ConnectionTypes::buildConnection($items, $total, $offset, $first);
    }
}
