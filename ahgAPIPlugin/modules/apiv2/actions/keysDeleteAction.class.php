<?php

use Illuminate\Database\Capsule\Manager as DB;

class apiv2KeysDeleteAction extends AhgApiAction
{
    public function DELETE($request)
    {
        $keyId = (int) $request->getParameter('id');

        // Only delete own keys
        $deleted = DB::table('ahg_api_key')
            ->where('id', $keyId)
            ->where('user_id', $this->apiKeyInfo['user_id'])
            ->delete();

        if (!$deleted) {
            return $this->error(404, 'Not Found', 'API key not found');
        }

        return $this->success(['message' => 'API key deleted']);
    }
}
