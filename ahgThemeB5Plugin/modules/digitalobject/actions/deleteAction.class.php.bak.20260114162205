<?php

/**
 * Digital Object Delete Action - Pure Laravel Implementation
 */

use Illuminate\Database\Capsule\Manager as DB;

class digitalobjectDeleteAction extends sfAction
{
    protected $uploadDir;

    public function execute($request)
    {
        $this->uploadDir = sfConfig::get('sf_upload_dir');

        // Get resource from route (AtoM standard way)
        $resource = $this->getRoute()->resource;
        
        $id = null;
        if ($resource && isset($resource->id)) {
            $id = (int) $resource->id;
        }
        
        // Fallback to id parameter
        if (!$id) {
            $id = (int) $request->getParameter('id');
        }
        
        // Fallback to slug parameter
        if (!$id) {
            $slug = $request->getParameter('slug');
            if ($slug) {
                $id = (int) DB::table('slug')
                    ->where('slug', $slug)
                    ->value('object_id');
            }
        }

        if (!$id) {
            $this->forward404();
        }

        // Fetch digital object
        $this->resource = DB::table('digital_object')
            ->where('id', $id)
            ->first();

        if (!$this->resource) {
            $this->forward404();
        }

        // Check auth
        $user = $this->getUser();
        if (!$user || !$user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        // Get parent digital object (if this is a derivative)
        $this->parent = null;
        if ($this->resource->parent_id) {
            $this->parent = DB::table('digital_object')
                ->where('id', $this->resource->parent_id)
                ->first();
        }

        // Get the information object/actor for redirect
        $objectId = $this->resource->object_id;
        if (!$objectId && $this->parent) {
            $objectId = $this->parent->object_id;
        }

        $this->redirectTarget = null;
        if ($objectId) {
            $objectClass = DB::table('object')
                ->where('id', $objectId)
                ->value('class_name');

            if ($objectClass === 'QubitInformationObject') {
                $this->redirectTarget = DB::table('information_object as io')
                    ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                    ->where('io.id', $objectId)
                    ->select('s.slug', DB::raw("'informationobject' as module"))
                    ->first();
            } elseif ($objectClass === 'QubitActor') {
                $this->redirectTarget = DB::table('actor as a')
                    ->leftJoin('slug as s', 'a.id', '=', 's.object_id')
                    ->where('a.id', $objectId)
                    ->select('s.slug', DB::raw("'actor' as module"))
                    ->first();
            } elseif ($objectClass === 'QubitRepository') {
                $this->redirectTarget = DB::table('actor as a')
                    ->leftJoin('slug as s', 'a.id', '=', 's.object_id')
                    ->where('a.id', $objectId)
                    ->select('s.slug', DB::raw("'repository' as module"))
                    ->first();
            }
        }

        // Handle form submission
        if ($request->isMethod('delete') || 
            ($request->isMethod('post') && $request->getParameter('delete'))) {
            
            $this->deleteDigitalObject($id);

            // Redirect to parent digital object edit page or information object
            if ($this->parent) {
                $this->redirect(['module' => 'digitalobject', 'action' => 'edit', 'id' => $this->parent->id]);
            } elseif ($this->redirectTarget) {
                $this->redirect(['module' => $this->redirectTarget->module, 'slug' => $this->redirectTarget->slug]);
            } else {
                $this->redirect('@homepage');
            }
        }
    }

    protected function deleteDigitalObject(int $id): void
    {
        $do = DB::table('digital_object')->where('id', $id)->first();
        if (!$do) {
            return;
        }

        // Delete children (derivatives) first
        $children = DB::table('digital_object')
            ->where('parent_id', $id)
            ->pluck('id');

        foreach ($children as $childId) {
            $this->deleteDigitalObjectRecord($childId);
        }

        // Delete main digital object
        $this->deleteDigitalObjectRecord($id);
    }

    protected function deleteDigitalObjectRecord(int $id): void
    {
        $do = DB::table('digital_object')->where('id', $id)->first();

        // Delete physical file
        if ($do && $do->path && $do->name) {
            $path = $do->path;
            // Handle path that already includes /uploads/
            if (strpos($path, '/uploads/') === 0) {
                $path = substr($path, 9);
            }
            $fullPath = $this->uploadDir . '/' . ltrim($path, '/') . $do->name;
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Delete properties
        $propIds = DB::table('property')->where('object_id', $id)->pluck('id');
        if ($propIds->isNotEmpty()) {
            DB::table('property_i18n')->whereIn('id', $propIds)->delete();
            DB::table('property')->whereIn('id', $propIds)->delete();
        }

        // Delete slug
        DB::table('slug')->where('object_id', $id)->delete();

        // Delete digital object
        DB::table('digital_object')->where('id', $id)->delete();

        // Delete base object
        DB::table('object')->where('id', $id)->delete();
    }
}