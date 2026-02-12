<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Library Slug Preview Action
 * Mirrors core InformationObjectSlugPreviewAction for Library rename functionality
 */
class librarySlugPreviewAction extends AhgController
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        
        if ($slug) {
            $this->resource = QubitInformationObject::getBySlug($slug);
        }

        // Return 401 if unauthorized
        if (!sfContext::getInstance()->user->isAuthenticated()) {
            $this->response->setStatusCode(401);
            return sfView::NONE;
        }

        if ($this->resource && !\AtomExtensions\Services\AclService::check($this->resource, 'read')) {
            $this->response->setStatusCode(401);
            return sfView::NONE;
        }

        $text = $request->getParameter('text');
        $resourceId = $this->resource ? $this->resource->id : null;
        
        // Return JSON containing first available slug
        $availableSlug = self::determineAvailableSlug($text, $resourceId);

        $response = [
            'slug' => $availableSlug,
            'padded' => $availableSlug != QubitSlug::slugify($text),
        ];

        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($response));
    }

    public static function determineAvailableSlug($text, $resourceId = null)
    {
        $originalText = $text;
        $counter = 0;

        do {
            $slugText = QubitSlug::slugify($text);

            $criteria = new Criteria();
            $criteria->add(QubitSlug::SLUG, $slugText);

            $slug = QubitSlug::getOne($criteria);

            // Pad text if slugified text slug is used by another resource
            ++$counter;
            $text = $originalText . '-' . $counter;
        } while ((null != $slug) && ($resourceId !== null) && ($slug->objectId != $resourceId));

        return $slugText;
    }
}
