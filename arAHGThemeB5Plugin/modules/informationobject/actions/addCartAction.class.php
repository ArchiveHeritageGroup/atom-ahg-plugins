<?php
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add to Cart action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class InformationObjectAddCartAction extends DefaultEditAction
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;
        $this->informationObject = QubitInformationObject::getById($this->resource->id);
        
        $userId = $this->context->user->getAttribute('user_id');
        
        // Check if already in cart
        $existing = DB::table('cart')
            ->where('user_id', $userId)
            ->where('archival_description_id', $this->resource->id)
            ->first();
        
        if ($existing) {
            // Already in cart, redirect with message
            $this->context->user->setFlash('notice', 'Item is already in your cart.');
            $this->redirect([$this->resource, 'module' => 'informationobject']);
            return;
        }
        
        // Create object entry for cart
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitCart',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Insert into cart table
        DB::table('cart')->insert([
            'id' => $objectId,
            'user_id' => $userId,
            'archival_description_id' => $this->resource->id,
            'archival_description' => $this->informationObject->getTitle(['cultureFallback' => true]),
            'slug' => $this->resource->slug
        ]);
        
        $this->context->user->setFlash('notice', 'Added to cart.');
        $this->redirect([$this->resource, 'module' => 'informationobject']);
    }
    
    protected function earlyExecute()
    {
        $this->resource = $this->getRoute()->resource;
        
        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }
    }
}
