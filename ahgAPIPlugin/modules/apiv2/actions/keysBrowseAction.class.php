<?php

use Illuminate\Database\Capsule\Manager as DB;

class apiv2KeysBrowseAction extends AhgApiAction
{
    public function GET($request)
    {
        // Only show user's own keys
        $keys = DB::table('ahg_api_key')
            ->where('user_id', $this->apiKeyInfo['user_id'])
            ->select(['id', 'name', 'api_key_prefix', 'scopes', 'rate_limit', 'expires_at', 'last_used_at', 'is_active', 'created_at'])
            ->get();

        return $this->success([
            'keys' => $keys->map(function ($key) {
                $key->scopes = json_decode($key->scopes);
                return $key;
            })->toArray()
        ]);
    }
}
