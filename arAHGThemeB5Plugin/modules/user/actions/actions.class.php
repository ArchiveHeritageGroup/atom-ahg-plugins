<?php

require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/user/actions/indexAction.class.php';

class userActions extends sfActions
{
    public function executeIndex($request)
    {
        $action = new UserIndexAction($this->context, 'user', 'index');
        $action->execute($request);
        
        // Copy variables from action to this controller
        foreach (get_object_vars($action) as $key => $value) {
            $this->$key = $value;
        }
        
        return sfView::SUCCESS;
    }
}
