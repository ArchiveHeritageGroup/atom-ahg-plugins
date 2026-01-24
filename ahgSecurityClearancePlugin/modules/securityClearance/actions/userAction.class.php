<?php

use Illuminate\Database\Capsule\Manager as DB;

class securityClearanceUserAction extends sfAction
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        $culture = $this->getUser()->getCulture();

        // Get user by slug using Laravel Query Builder
        $this->user = DB::table('user as u')
            ->join('actor as a', 'a.id', '=', 'u.id')
            ->join('slug', 'slug.object_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function($join) use ($culture) {
                $join->on('ai.id', '=', 'a.id')
                     ->where('ai.culture', '=', $culture);
            })
            ->where('slug.slug', $slug)
            ->select([
                'u.*',
                'a.entity_type_id',
                'ai.authorized_form_of_name',
                'slug.slug'
            ])
            ->first();

        if (!$this->user) {
            $this->forward404();
        }

        // Get security clearance with classification details
        $clearanceData = DB::table('user_security_clearance as usc')
            ->join('security_classification as sc', 'sc.id', '=', 'usc.classification_id')
            ->leftJoin('user as granted_user', 'granted_user.id', '=', 'usc.granted_by')
            ->where('usc.user_id', $this->user->id)
            ->select([
                'usc.*',
                'sc.name as classification_name',
                'sc.code as classification_code',
                'sc.level as classification_level',
                'sc.color as classification_color',
                'granted_user.username as granted_by_username'
            ])
            ->first();

        // Convert to object with camelCase properties for template compatibility
        if ($clearanceData) {
            $this->clearance = (object) [
                'id' => $clearanceData->id,
                'userId' => $clearanceData->user_id,
                'classificationId' => $clearanceData->classification_id,
                'classificationName' => $clearanceData->classification_name,
                'classificationCode' => $clearanceData->classification_code,
                'classificationLevel' => $clearanceData->classification_level,
                'classificationColor' => $clearanceData->classification_color,
                'grantedBy' => $clearanceData->granted_by,
                'grantedByUsername' => $clearanceData->granted_by_username,
                'grantedAt' => $clearanceData->granted_at,
                'expiresAt' => $clearanceData->expires_at,
                'notes' => $clearanceData->notes,
            ];
        } else {
            $this->clearance = null;
        }

        // Get all classification levels for dropdown
        $this->classifications = DB::table('security_classification')
            ->where('active', 1)
            ->orderBy('level')
            ->get();

        // Get clearance history
        $this->history = DB::table('user_security_clearance_log as log')
            ->leftJoin('security_classification as sc', 'sc.id', '=', 'log.classification_id')
            ->leftJoin('security_classification as prev_sc', 'prev_sc.id', '=', 'log.previous_classification_id')
            ->where('log.user_id', $this->user->id)
            ->orderBy('log.created_at', 'desc')
            ->select([
                'log.*',
                'sc.name as new_name',
                'prev_sc.name as previous_name'
            ])
            ->get()
            ->map(function($row) {
                return (array) $row;
            })
            ->toArray();

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    protected function processForm($request)
    {
        $actionType = $request->getParameter('action_type');
        $currentUserId = $this->getUser()->getUserId();

        if ('update' === $actionType) {
            $classificationId = $request->getParameter('classification_id');
            $expiresAt = $request->getParameter('expires_at') ?: null;
            $notes = $request->getParameter('notes');

            if ($classificationId) {
                // Insert or update clearance
                $existing = DB::table('user_security_clearance')
                    ->where('user_id', $this->user->id)
                    ->first();

                $data = [
                    'user_id' => $this->user->id,
                    'classification_id' => $classificationId,
                    'granted_by' => $currentUserId,
                    'granted_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt,
                    'notes' => $notes,
                ];

                if ($existing) {
                    // Log the change
                    DB::table('user_security_clearance_log')->insert([
                        'user_id' => $this->user->id,
                        'action' => 'updated',
                        'previous_classification_id' => $existing->classification_id,
                        'classification_id' => $classificationId,
                        'changed_by' => $currentUserId,
                        'notes' => $notes,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    DB::table('user_security_clearance')
                        ->where('user_id', $this->user->id)
                        ->update($data);
                } else {
                    // Log the grant
                    DB::table('user_security_clearance_log')->insert([
                        'user_id' => $this->user->id,
                        'action' => 'granted',
                        'classification_id' => $classificationId,
                        'changed_by' => $currentUserId,
                        'notes' => $notes,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    DB::table('user_security_clearance')->insert($data);
                }

                $this->redirect('@security_clearance_user?slug=' . $this->user->slug . '&success=updated');
            }
        } elseif ('revoke' === $actionType) {
            $reason = $request->getParameter('revoke_reason');
            $existing = DB::table('user_security_clearance')
                ->where('user_id', $this->user->id)
                ->first();

            if ($existing) {
                // Log the revocation
                DB::table('user_security_clearance_log')->insert([
                    'user_id' => $this->user->id,
                    'action' => 'revoked',
                    'previous_classification_id' => $existing->classification_id,
                    'changed_by' => $currentUserId,
                    'notes' => $reason,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('user_security_clearance')
                    ->where('user_id', $this->user->id)
                    ->delete();
            }

            $this->redirect('@security_clearance_user?slug=' . $this->user->slug . '&success=revoked');
        }
    }
}
