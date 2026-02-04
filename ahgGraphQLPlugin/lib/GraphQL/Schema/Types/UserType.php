<?php

namespace AhgGraphQLPlugin\GraphQL\Schema\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class UserType
{
    private static ?ObjectType $type = null;

    public static function getType(): ObjectType
    {
        if (self::$type === null) {
            self::$type = new ObjectType([
                'name' => 'User',
                'description' => 'A user account',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'Unique identifier',
                    ],
                    'username' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'Username',
                    ],
                    'email' => [
                        'type' => Type::string(),
                        'description' => 'Email address (admin only)',
                        'resolve' => function ($user, $args, $context) {
                            // Only show email to admins or the user themselves
                            if (!empty($context['isAdmin']) || (isset($context['userId']) && $context['userId'] == $user['id'])) {
                                return $user['email'] ?? null;
                            }

                            return null;
                        },
                    ],
                ],
            ]);
        }

        return self::$type;
    }
}
