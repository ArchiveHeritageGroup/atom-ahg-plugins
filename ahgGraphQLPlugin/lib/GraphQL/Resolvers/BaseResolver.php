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

    protected function buildConnection(array $items, int $total, int $offset, int $first): array
    {
        return ConnectionTypes::buildConnection($items, $total, $offset, $first);
    }
}
