<?php

use Illuminate\Database\Capsule\Manager as DB;

class apiv2DescriptionsUpdateAction extends AhgApiAction
{
    public function PUT($request, $data = null)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $slug = $request->getParameter('slug');
        $slugRecord = DB::table('slug')->where('slug', $slug)->first();

        if (!$slugRecord) {
            return $this->error(404, 'Not Found', "Description '$slug' not found");
        }

        $objectId = $slugRecord->object_id;

        try {
            DB::beginTransaction();

            // Update information_object
            $ioUpdate = [];
            if (isset($data['identifier'])) $ioUpdate['identifier'] = $data['identifier'];
            if (isset($data['level_of_description_id'])) $ioUpdate['level_of_description_id'] = $data['level_of_description_id'];
            if (isset($data['repository_id'])) $ioUpdate['repository_id'] = $data['repository_id'];
            
            if (!empty($ioUpdate)) {
                $ioUpdate['updated_at'] = date('Y-m-d H:i:s');
                DB::table('information_object')->where('id', $objectId)->update($ioUpdate);
            }

            // Update i18n
            $i18nUpdate = [];
            $i18nFields = ['title', 'alternate_title', 'scope_and_content', 'extent_and_medium',
                           'archival_history', 'acquisition', 'arrangement', 'access_conditions',
                           'reproduction_conditions', 'appraisal', 'accruals', 'finding_aids'];
            
            foreach ($i18nFields as $field) {
                if (isset($data[$field])) {
                    $i18nUpdate[$field] = $data[$field];
                }
            }

            if (!empty($i18nUpdate)) {
                DB::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', 'en')
                    ->update($i18nUpdate);
            }

            // Update publication status if provided
            if (isset($data['publication_status'])) {
                $statusId = $data['publication_status'] === 'published' ? 160 : 159;
                DB::table('status')
                    ->where('object_id', $objectId)
                    ->where('type_id', 158)
                    ->update(['status_id' => $statusId]);
            }

            // Update object timestamp
            DB::table('object')->where('id', $objectId)->update(['updated_at' => date('Y-m-d H:i:s')]);

            DB::commit();

            return $this->success([
                'id' => $objectId,
                'slug' => $slug,
                'message' => 'Description updated successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Server Error', $e->getMessage());
        }
    }
}
