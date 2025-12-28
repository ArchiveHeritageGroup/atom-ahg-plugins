<?php

require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/user/actions/indexAction.class.php';
require_once sfConfig::get('sf_root_dir') . '/apps/qubit/modules/user/actions/passwordEditAction.class.php';

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
    
    public function executePasswordEdit($request)
    {
        $action = new UserPasswordEditAction($this->context, 'user', 'passwordEdit');
        $result = $action->execute($request);
        
        // Copy variables from action to this controller
        foreach (get_object_vars($action) as $key => $value) {
            $this->$key = $value;
        }
        
        return $result ?? sfView::SUCCESS;
    }
}
