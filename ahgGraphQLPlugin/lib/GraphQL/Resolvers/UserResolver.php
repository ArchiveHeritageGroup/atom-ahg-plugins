<?php

namespace AhgGraphQLPlugin\GraphQL\Resolvers;

use Illuminate\Database\Capsule\Manager as DB;

class UserResolver extends BaseResolver
{
    public function resolveById(int $id): ?array
    {
        $row = DB::table('user')
            ->where('id', $id)
            ->select(['id', 'username', 'email'])
            ->first();

        return $row ? (array) $row : null;
    }
}
