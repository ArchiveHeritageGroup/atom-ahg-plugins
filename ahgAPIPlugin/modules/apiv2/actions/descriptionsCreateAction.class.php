<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

class apiv2DescriptionsCreateAction extends AhgApiController
{
    public function POST($request, $data = null)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        if (empty($data['title'])) {
            return $this->error(400, 'Bad Request', 'Title is required');
        }

        try {
            DB::beginTransaction();

            // Get parent ID
            $parentId = 1; // Root by default
            if (!empty($data['parent_slug'])) {
                $parent = DB::table('slug')->where('slug', $data['parent_slug'])->first();
                if ($parent) {
                    $parentId = $parent->object_id;
                }
            }

            // Get parent for nested set
            $parentObj = DB::table('information_object')->where('id', $parentId)->first();
            
            // Create information object
            $now = date('Y-m-d H:i:s');
            $lft = $parentObj->rgt;
            $rgt = $parentObj->rgt + 1;

            // Make room in nested set
            DB::table('information_object')
                ->where('rgt', '>=', $parentObj->rgt)
                ->increment('rgt', 2);
            DB::table('information_object')
                ->where('lft', '>', $parentObj->rgt)
                ->increment('lft', 2);

            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => $now,
                'updated_at' => $now
            ]);

            DB::table('information_object')->insert([
                'id' => $objectId,
                'identifier' => $data['identifier'] ?? null,
                'level_of_description_id' => $data['level_of_description_id'] ?? null,
                'repository_id' => $data['repository_id'] ?? null,
                'parent_id' => $parentId,
                'lft' => $lft,
                'rgt' => $rgt,
                'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            // Create i18n record
            DB::table('information_object_i18n')->insert([
                'id' => $objectId,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'title' => $data['title'],
                'alternate_title' => $data['alternate_title'] ?? null,
                'scope_and_content' => $data['scope_and_content'] ?? null,
                'extent_and_medium' => $data['extent_and_medium'] ?? null,
                'archival_history' => $data['archival_history'] ?? null,
                'acquisition' => $data['acquisition'] ?? null,
                'arrangement' => $data['arrangement'] ?? null,
                'access_conditions' => $data['access_conditions'] ?? null,
                'reproduction_conditions' => $data['reproduction_conditions'] ?? null
            ]);

            // Generate slug
            $slug = $this->generateSlug($data['title']);
            DB::table('slug')->insert([
                'object_id' => $objectId,
                'slug' => $slug
            ]);

            // Set publication status (draft by default)
            $statusId = ($data['publication_status'] ?? 'draft') === 'published' ? 160 : 159;
            DB::table('status')->insert([
                'object_id' => $objectId,
                'type_id' => 158, // Publication status type
                'status_id' => $statusId
            ]);

            DB::commit();

            // Trigger webhook for item.created
            try {
                \AhgAPI\Services\WebhookService::trigger(
                    \AhgAPI\Services\WebhookService::EVENT_CREATED,
                    \AhgAPI\Services\WebhookService::ENTITY_DESCRIPTION,
                    $objectId,
                    [
                        'slug' => $slug,
                        'title' => $data['title'],
                        'identifier' => $data['identifier'] ?? null,
                        'repository_id' => $data['repository_id'] ?? null,
                    ]
                );
            } catch (\Exception $webhookError) {
                error_log('Webhook trigger error: ' . $webhookError->getMessage());
            }

            return $this->success([
                'id' => $objectId,
                'slug' => $slug,
                'title' => $data['title'],
                'message' => 'Description created successfully'
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Server Error', $e->getMessage());
        }
    }

    protected function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
