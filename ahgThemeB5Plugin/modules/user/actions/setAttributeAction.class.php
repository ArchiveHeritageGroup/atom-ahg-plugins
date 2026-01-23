<?php

/**
 * Set user attribute/preference action
 *
 * Allows setting user session attributes like preferred_iiif_viewer
 */
class UserSetAttributeAction extends sfAction
{
    public function execute($request)
    {
        // Only allow for authenticated users
        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);
            return sfView::NONE;
        }

        $name = $request->getParameter('name');
        $value = $request->getParameter('value');

        // Whitelist of allowed attributes
        $allowedAttributes = [
            'preferred_iiif_viewer',
            'preferred_media_player',
            'sidebar_collapsed',
            'theme_preference',
        ];

        if (!in_array($name, $allowedAttributes)) {
            $this->getResponse()->setStatusCode(400);
            return sfView::NONE;
        }

        // Set the attribute in the user's session
        $this->context->user->setAttribute($name, $value);

        // Return success
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode(['success' => true]));

        return sfView::NONE;
    }
}
