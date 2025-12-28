<?php

class userActions extends sfActions
{
    public function executeIndex(sfWebRequest $request)
    {
        // Get user from route
        $this->resource = $this->getRoute()->resource;
        
        if (!isset($this->resource)) {
            $this->forward404();
        }
        
        // Check if viewing own profile or has permission
        $currentUser = $this->context->user;
        $this->isOwnProfile = $currentUser->isAuthenticated() && 
            $currentUser->getAttribute('user_id') == $this->resource->id;
        
        // Set page title
        $this->response->setTitle($this->resource->username . ' - ' . $this->response->getTitle());
    }
}
