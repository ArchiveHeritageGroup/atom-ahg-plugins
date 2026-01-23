<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Preservation Settings Action
 * Configure backup replication targets and preservation settings
 */
class ahgSettingsPreservationAction extends sfAction
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin access
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ($action === 'add_target') {
                $this->addTarget($request);
            } elseif ($action === 'update_target') {
                $this->updateTarget($request);
            } elseif ($action === 'delete_target') {
                $this->deleteTarget($request);
            } elseif ($action === 'toggle_target') {
                $this->toggleTarget($request);
            }

            $this->redirect(['module' => 'ahgSettings', 'action' => 'preservation']);
        }

        // Get replication targets
        $this->targets = DB::table('preservation_replication_target')
            ->orderBy('name')
            ->get()
            ->toArray();

        // Get recent replication logs
        $this->recentLogs = DB::table('preservation_replication_log as prl')
            ->join('preservation_replication_target as prt', 'prl.target_id', '=', 'prt.id')
            ->select('prl.*', 'prt.name as target_name')
            ->orderBy('prl.started_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Get statistics
        $this->stats = [
            'total_targets' => DB::table('preservation_replication_target')->count(),
            'active_targets' => DB::table('preservation_replication_target')->where('is_active', 1)->count(),
            'total_syncs' => DB::table('preservation_replication_log')->count(),
            'successful_syncs' => DB::table('preservation_replication_log')->where('status', 'completed')->count(),
            'failed_syncs' => DB::table('preservation_replication_log')->where('status', 'failed')->count(),
        ];
    }

    protected function addTarget($request)
    {
        $name = trim($request->getParameter('name'));
        $type = $request->getParameter('target_type');
        $description = trim($request->getParameter('description'));

        // Build connection config based on type
        $config = [];
        if ($type === 'local') {
            $config['path'] = trim($request->getParameter('path'));
        } elseif ($type === 'sftp' || $type === 'rsync') {
            $config['host'] = trim($request->getParameter('host'));
            $config['port'] = (int) $request->getParameter('port', 22);
            $config['path'] = trim($request->getParameter('path'));
            $config['user'] = trim($request->getParameter('user'));
        } elseif ($type === 's3') {
            $config['bucket'] = trim($request->getParameter('bucket'));
            $config['region'] = trim($request->getParameter('region'));
        }

        if (empty($name) || empty($config)) {
            return;
        }

        DB::table('preservation_replication_target')->insert([
            'name' => $name,
            'target_type' => $type,
            'connection_config' => json_encode($config),
            'description' => $description,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function updateTarget($request)
    {
        $id = (int) $request->getParameter('target_id');
        $name = trim($request->getParameter('name'));
        $description = trim($request->getParameter('description'));
        $type = $request->getParameter('target_type');

        // Build connection config based on type
        $config = [];
        if ($type === 'local') {
            $config['path'] = trim($request->getParameter('path'));
        } elseif ($type === 'sftp' || $type === 'rsync') {
            $config['host'] = trim($request->getParameter('host'));
            $config['port'] = (int) $request->getParameter('port', 22);
            $config['path'] = trim($request->getParameter('path'));
            $config['user'] = trim($request->getParameter('user'));
        } elseif ($type === 's3') {
            $config['bucket'] = trim($request->getParameter('bucket'));
            $config['region'] = trim($request->getParameter('region'));
        }

        DB::table('preservation_replication_target')
            ->where('id', $id)
            ->update([
                'name' => $name,
                'target_type' => $type,
                'connection_config' => json_encode($config),
                'description' => $description,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    protected function deleteTarget($request)
    {
        $id = (int) $request->getParameter('target_id');

        // Delete logs first
        DB::table('preservation_replication_log')->where('target_id', $id)->delete();
        DB::table('preservation_replication_target')->where('id', $id)->delete();
    }

    protected function toggleTarget($request)
    {
        $id = (int) $request->getParameter('target_id');

        $current = DB::table('preservation_replication_target')
            ->where('id', $id)
            ->value('is_active');

        DB::table('preservation_replication_target')
            ->where('id', $id)
            ->update(['is_active' => $current ? 0 : 1]);
    }
}
