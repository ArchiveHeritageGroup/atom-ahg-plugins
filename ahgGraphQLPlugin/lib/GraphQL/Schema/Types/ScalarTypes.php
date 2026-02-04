<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ScalarTypes
{
    private static ?ObjectType $pageInfoType = null;

    public static function pageInfo(): ObjectType
    {
        if (self::$pageInfoType === null) {
            self::$pageInfoType = new ObjectType([
                'name' => 'PageInfo',
                'description' => 'Information about pagination in a connection',
                'fields' => [
                    'hasNextPage' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'When paginating forwards, are there more items?',
                    ],
                    'hasPreviousPage' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'When paginating backwards, are there more items?',
                    ],
                    'startCursor' => [
                        'type' => Type::string(),
                        'description' => 'When paginating backwards, the cursor to continue',
                    ],
                    'endCursor' => [
                        'type' => Type::string(),
                        'description' => 'When paginating forwards, the cursor to continue',
                    ],
                ],
            ]);
        }

        return self::$pageInfoType;
    }

    public static function encodeCursor(int $offset): string
    {
        return base64_encode('cursor:' . $offset);
    }

    public static function decodeCursor(?string $cursor): int
    {
        if ($cursor === null) {
            return 0;
        }

        $decoded = base64_decode($cursor);
        if (strpos($decoded, 'cursor:') !== 0) {
            return 0;
        }

        return (int) substr($decoded, 7);
    }
}
