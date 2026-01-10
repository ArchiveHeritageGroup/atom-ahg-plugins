<?php

use Illuminate\Database\Capsule\Manager as DB;

class apiv2DescriptionsDeleteAction extends AhgApiAction
{
    public function DELETE($request)
    {
        if (!$this->hasScope('delete')) {
            return $this->error(403, 'Forbidden', 'Delete scope required');
        }

        $slug = $request->getParameter('slug');
        $slugRecord = DB::table('slug')->where('slug', $slug)->first();

        if (!$slugRecord) {
            return $this->error(404, 'Not Found', "Description '$slug' not found");
        }

        $objectId = $slugRecord->object_id;

        // Check for children
        $io = DB::table('information_object')->where('id', $objectId)->first();
        $hasChildren = DB::table('information_object')
            ->where('parent_id', $objectId)
            ->exists();

        if ($hasChildren) {
            return $this->error(400, 'Bad Request', 'Cannot delete description with children. Delete children first or use cascade=true');
        }

        try {
            DB::beginTransaction();

            // Delete related records
            DB::table('status')->where('object_id', $objectId)->delete();
            DB::table('slug')->where('object_id', $objectId)->delete();
            DB::table('information_object_i18n')->where('id', $objectId)->delete();
            DB::table('event')->where('object_id', $objectId)->delete();
            DB::table('relation')->where('object_id', $objectId)->orWhere('subject_id', $objectId)->delete();
            DB::table('note')->where('object_id', $objectId)->delete();
            DB::table('property')->where('object_id', $objectId)->delete();

            // Update nested set
            $width = $io->rgt - $io->lft + 1;
            DB::table('information_object')
                ->where('lft', '>', $io->rgt)
                ->decrement('lft', $width);
            DB::table('information_object')
                ->where('rgt', '>', $io->rgt)
                ->decrement('rgt', $width);

            // Delete information_object
            DB::table('information_object')->where('id', $objectId)->delete();
            DB::table('object')->where('id', $objectId)->delete();

            DB::commit();

            return $this->success([
                'id' => $objectId,
                'slug' => $slug,
                'message' => 'Description deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Server Error', $e->getMessage());
        }
    }
}
