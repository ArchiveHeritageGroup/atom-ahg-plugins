<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

class apiv2KeysDeleteAction extends AhgApiController
{
    public function DELETE($request)
    {
        $keyId = (int) $request->getParameter('id');

        // Only delete own keys
        if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
            $deleted = DB::table('ahg_api_key')
                ->where('id', $keyId)
                ->where('user_id', $this->apiKeyInfo['user_id'])
                ->delete();
        } else {
            $conn = \Propel::getConnection();
            $stmt = $conn->prepare('DELETE FROM ahg_api_key WHERE id = ? AND user_id = ?');
            $stmt->execute([$keyId, $this->apiKeyInfo['user_id']]);
            $deleted = $stmt->rowCount();
        }

        if (!$deleted) {
            return $this->error(404, 'Not Found', 'API key not found');
        }

        return $this->success(['message' => 'API key deleted']);
    }
}
