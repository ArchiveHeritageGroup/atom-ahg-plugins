<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class RepositoryType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type === null) {
            self::$type = new ObjectType([
                'name' => 'Repository',
                'description' => 'An archival repository or institution',
                'fields' => function () {
                    return [
                        'id' => [
                            'type' => Type::nonNull(Type::id()),
                            'description' => 'Unique identifier',
                        ],
                        'slug' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'URL slug',
                        ],
                        'name' => [
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'Repository name',
                        ],
                        'identifier' => [
                            'type' => Type::string(),
                            'description' => 'Repository identifier',
                        ],
                        'holdings' => [
                            'type' => Type::nonNull(ConnectionTypes::connection('Item', ItemType::getType())),
                            'description' => 'Items held by this repository',
                            'args' => [
                                'first' => [
                                    'type' => Type::int(),
                                    'defaultValue' => 10,
                                    'description' => 'Number of items to return',
                                ],
                                'after' => [
                                    'type' => Type::string(),
                                    'description' => 'Cursor for pagination',
                                ],
                            ],
                            'resolve' => function ($repo, $args, $context) {
                                $first = min($args['first'] ?? 10, 100);
                                $offset = ScalarTypes::decodeCursor($args['after'] ?? null);

                                return $context['resolvers']->item->resolveByRepository(
                                    $repo['id'],
                                    $first,
                                    $offset
                                );
                            },
                        ],
                        'itemCount' => [
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'Total number of items in this repository',
                            'resolve' => function ($repo, $args, $context) {
                                return $context['resolvers']->item->countByRepository($repo['id']);
                            },
                        ],
                    ];
                },
            ]);
        }

        return self::$type;
    }
}
