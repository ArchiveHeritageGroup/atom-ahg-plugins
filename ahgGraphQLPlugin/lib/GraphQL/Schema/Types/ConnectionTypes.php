<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ConnectionTypes
{
    private static array $connectionTypes = [];
    private static array $edgeTypes = [];

    public static function connection(string $name, ObjectType $nodeType): ObjectType
    {
        $key = $name . 'Connection';

        if (!isset(self::$connectionTypes[$key])) {
            self::$connectionTypes[$key] = new ObjectType([
                'name' => $key,
                'description' => "A connection to a list of {$name} items",
                'fields' => function () use ($name, $nodeType) {
                    return [
                        'edges' => [
                            'type' => Type::nonNull(Type::listOf(Type::nonNull(self::edge($name, $nodeType)))),
                            'description' => 'A list of edges',
                        ],
                        'pageInfo' => [
                            'type' => Type::nonNull(ScalarTypes::pageInfo()),
                            'description' => 'Information to aid in pagination',
                        ],
                        'totalCount' => [
                            'type' => Type::nonNull(Type::int()),
                            'description' => 'Total number of items in the connection',
                        ],
                    ];
                },
            ]);
        }

        return self::$connectionTypes[$key];
    }

    public static function edge(string $name, ObjectType $nodeType): ObjectType
    {
        $key = $name . 'Edge';

        if (!isset(self::$edgeTypes[$key])) {
            self::$edgeTypes[$key] = new ObjectType([
                'name' => $key,
                'description' => "An edge in the {$name} connection",
                'fields' => [
                    'node' => [
                        'type' => Type::nonNull($nodeType),
                        'description' => 'The item at the end of the edge',
                    ],
                    'cursor' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'A cursor for use in pagination',
                    ],
                ],
            ]);
        }

        return self::$edgeTypes[$key];
    }

    public static function buildConnection(array $items, int $total, int $offset, int $first): array
    {
        $edges = [];
        foreach ($items as $index => $item) {
            $edges[] = [
                'node' => $item,
                'cursor' => ScalarTypes::encodeCursor($offset + $index),
            ];
        }

        $hasNextPage = $offset + count($items) < $total;
        $hasPreviousPage = $offset > 0;

        return [
            'edges' => $edges,
            'pageInfo' => [
                'hasNextPage' => $hasNextPage,
                'hasPreviousPage' => $hasPreviousPage,
                'startCursor' => !empty($edges) ? $edges[0]['cursor'] : null,
                'endCursor' => !empty($edges) ? $edges[count($edges) - 1]['cursor'] : null,
            ],
            'totalCount' => $total,
        ];
    }
}
