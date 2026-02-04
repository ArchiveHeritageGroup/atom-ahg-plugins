<?php

namespace AhgGraphQLPlugin\GraphQL\Schema;

use AhgGraphQLPlugin\GraphQL\Schema\Types\ActorType;
use AhgGraphQLPlugin\GraphQL\Schema\Types\ConnectionTypes;
use AhgGraphQLPlugin\GraphQL\Schema\Types\ItemType;
use AhgGraphQLPlugin\GraphQL\Schema\Types\RepositoryType;
use AhgGraphQLPlugin\GraphQL\Schema\Types\ScalarTypes;
use AhgGraphQLPlugin\GraphQL\Schema\Types\TermType;
use AhgGraphQLPlugin\GraphQL\Schema\Types\UserType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;

class SchemaBuilder
{
    private bool $introspectionEnabled;

    public function __construct(bool $introspectionEnabled = true)
    {
        $this->introspectionEnabled = $introspectionEnabled;
    }

    public function build(): Schema
    {
        $queryType = $this->buildQueryType();
        $mutationType = $this->buildMutationType();

        $config = SchemaConfig::create()
            ->setQuery($queryType)
            ->setMutation($mutationType);

        return new Schema($config);
    }

    private function buildQueryType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => [
                // Single item lookups
                'item' => [
                    'type' => ItemType::getType(),
                    'description' => 'Get a single archival description',
                    'args' => [
                        'slug' => [
                            'type' => Type::string(),
                            'description' => 'Item slug',
                        ],
                        'id' => [
                            'type' => Type::id(),
                            'description' => 'Item ID',
                        ],
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!empty($args['slug'])) {
                            return $context['resolvers']->item->resolveBySlug($args['slug']);
                        }
                        if (!empty($args['id'])) {
                            return $context['resolvers']->item->resolveById($args['id']);
                        }

                        return null;
                    },
                ],

                // Item list with pagination
                'items' => [
                    'type' => Type::nonNull(ConnectionTypes::connection('Item', ItemType::getType())),
                    'description' => 'Browse archival descriptions',
                    'args' => [
                        'first' => [
                            'type' => Type::int(),
                            'defaultValue' => 10,
                            'description' => 'Number of items to return (max 100)',
                        ],
                        'after' => [
                            'type' => Type::string(),
                            'description' => 'Cursor for pagination',
                        ],
                        'repository' => [
                            'type' => Type::string(),
                            'description' => 'Filter by repository slug',
                        ],
                        'level' => [
                            'type' => Type::string(),
                            'description' => 'Filter by level of description',
                        ],
                        'sector' => [
                            'type' => Type::string(),
                            'description' => 'Filter by sector (archive, library, museum)',
                        ],
                    ],
                    'complexity' => fn($childrenComplexity, $args) => 10 + $childrenComplexity * min($args['first'] ?? 10, 100),
                    'resolve' => function ($root, $args, $context) {
                        $first = min($args['first'] ?? 10, 100);
                        $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                        return $context['resolvers']->item->resolveList(
                            $first,
                            $offset,
                            $args['repository'] ?? null,
                            $args['level'] ?? null,
                            $args['sector'] ?? null
                        );
                    },
                ],

                // Actor queries
                'actor' => [
                    'type' => ActorType::getType(),
                    'description' => 'Get a single authority record',
                    'args' => [
                        'slug' => [
                            'type' => Type::string(),
                            'description' => 'Actor slug',
                        ],
                        'id' => [
                            'type' => Type::id(),
                            'description' => 'Actor ID',
                        ],
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!empty($args['slug'])) {
                            return $context['resolvers']->actor->resolveBySlug($args['slug']);
                        }
                        if (!empty($args['id'])) {
                            return $context['resolvers']->actor->resolveById($args['id']);
                        }

                        return null;
                    },
                ],

                'actors' => [
                    'type' => Type::nonNull(ConnectionTypes::connection('Actor', ActorType::getType())),
                    'description' => 'Browse authority records',
                    'args' => [
                        'first' => [
                            'type' => Type::int(),
                            'defaultValue' => 10,
                            'description' => 'Number of items to return (max 100)',
                        ],
                        'after' => [
                            'type' => Type::string(),
                            'description' => 'Cursor for pagination',
                        ],
                        'entityType' => [
                            'type' => Type::string(),
                            'description' => 'Filter by entity type',
                        ],
                    ],
                    'complexity' => fn($childrenComplexity, $args) => 10 + $childrenComplexity * min($args['first'] ?? 10, 100),
                    'resolve' => function ($root, $args, $context) {
                        $first = min($args['first'] ?? 10, 100);
                        $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                        return $context['resolvers']->actor->resolveList(
                            $first,
                            $offset,
                            $args['entityType'] ?? null
                        );
                    },
                ],

                // Repository queries
                'repositories' => [
                    'type' => Type::nonNull(ConnectionTypes::connection('Repository', RepositoryType::getType())),
                    'description' => 'Browse repositories',
                    'args' => [
                        'first' => [
                            'type' => Type::int(),
                            'defaultValue' => 10,
                            'description' => 'Number of items to return (max 100)',
                        ],
                        'after' => [
                            'type' => Type::string(),
                            'description' => 'Cursor for pagination',
                        ],
                    ],
                    'complexity' => fn($childrenComplexity, $args) => 10 + $childrenComplexity * min($args['first'] ?? 10, 100),
                    'resolve' => function ($root, $args, $context) {
                        $first = min($args['first'] ?? 10, 100);
                        $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                        return $context['resolvers']->item->resolveRepositories($first, $offset);
                    },
                ],

                'repository' => [
                    'type' => RepositoryType::getType(),
                    'description' => 'Get a single repository',
                    'args' => [
                        'slug' => [
                            'type' => Type::string(),
                            'description' => 'Repository slug',
                        ],
                        'id' => [
                            'type' => Type::id(),
                            'description' => 'Repository ID',
                        ],
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!empty($args['slug'])) {
                            return $context['resolvers']->item->resolveRepositoryBySlug($args['slug']);
                        }
                        if (!empty($args['id'])) {
                            return $context['resolvers']->item->resolveRepository($args['id']);
                        }

                        return null;
                    },
                ],

                // Taxonomy queries
                'taxonomies' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(TermType::getTaxonomyType()))),
                    'description' => 'List all taxonomies',
                    'resolve' => function ($root, $args, $context) {
                        return $context['resolvers']->taxonomy->resolveAll();
                    },
                ],

                'taxonomy' => [
                    'type' => TermType::getTaxonomyType(),
                    'description' => 'Get a single taxonomy',
                    'args' => [
                        'id' => [
                            'type' => Type::nonNull(Type::id()),
                            'description' => 'Taxonomy ID',
                        ],
                    ],
                    'resolve' => function ($root, $args, $context) {
                        return $context['resolvers']->taxonomy->resolveById($args['id']);
                    },
                ],

                // Search query
                'search' => [
                    'type' => Type::nonNull(ConnectionTypes::connection('Item', ItemType::getType())),
                    'description' => 'Search items',
                    'args' => [
                        'query' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'Search query string',
                        ],
                        'first' => [
                            'type' => Type::int(),
                            'defaultValue' => 10,
                            'description' => 'Number of items to return (max 100)',
                        ],
                        'after' => [
                            'type' => Type::string(),
                            'description' => 'Cursor for pagination',
                        ],
                    ],
                    'complexity' => fn($childrenComplexity, $args) => 20 + $childrenComplexity * min($args['first'] ?? 10, 100),
                    'resolve' => function ($root, $args, $context) {
                        $first = min($args['first'] ?? 10, 100);
                        $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                        return $context['resolvers']->item->resolveSearch(
                            $args['query'],
                            $first,
                            $offset
                        );
                    },
                ],

                // Current user
                'me' => [
                    'type' => UserType::getType(),
                    'description' => 'Get current authenticated user',
                    'resolve' => function ($root, $args, $context) {
                        if (!empty($context['userId'])) {
                            return $context['resolvers']->user->resolveById($context['userId']);
                        }

                        return null;
                    },
                ],
            ],
        ]);
    }

    private function buildMutationType(): ?ObjectType
    {
        return new ObjectType([
            'name' => 'Mutation',
            'fields' => [
                // Placeholder for future mutations
                '_empty' => [
                    'type' => Type::string(),
                    'description' => 'Placeholder - mutations coming soon',
                    'resolve' => fn() => null,
                ],
            ],
        ]);
    }
}
