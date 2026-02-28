<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/audit/:id — Single audit entry with old/new values (requires audit:admin scope)
 */
class apiv2AuditReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('audit:admin') && !$this->isAdmin()) {
            return $this->error(403, 'Forbidden', 'audit:admin scope required for detailed audit records');
        }

        $tableExists = DB::select("SHOW TABLES LIKE 'ahg_audit_log'");
        if (empty($tableExists)) {
            return $this->error(404, 'Not Found', 'Audit trail not installed');
        }

        $id = (int) $request->getParameter('id');

        $entry = DB::table('ahg_audit_log')
            ->where('id', $id)
            ->first();

        if (!$entry) {
            return $this->notFound('Audit entry not found');
        }

        // Decode JSON fields
        if (!empty($entry->changed_fields)) {
            $entry->changed_fields = json_decode($entry->changed_fields, true);
        }
        if (!empty($entry->old_values)) {
            $entry->old_values = json_decode($entry->old_values, true);
        }
        if (!empty($entry->new_values)) {
            $entry->new_values = json_decode($entry->new_values, true);
        }

        // Get user info
        $entry->user_name = null;
        if ($entry->user_id) {
            $entry->user_name = DB::table('user')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('user.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                })
                ->where('user.id', $entry->user_id)
                ->selectRaw('COALESCE(ai.authorized_form_of_name, user.username) as name')
                ->value('name');
        }

        return $this->success($entry);
    }
}
