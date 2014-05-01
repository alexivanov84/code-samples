<?php 
class WebUser extends CWebUser {
    private $_model = null;
    public $loginUrl=array('admin/login');
 
	/**
     * Overrides a Yii method that is used for roles in controllers (accessRules).
     *
     * @param string $operation Name of the operation required (here, a role).
     * @param mixed $params (opt) Parameters for this operation, usually the object to access.
     * @return bool Permission granted?
     */
    public function checkAccess($operation, $params=array())
    {
        if (empty($this->id)) {
            // Not identified => no rights
            return false;
        }
        $role = $this->getState("role");
        
        // allow access if the operation request is the current user's role
        return ($operation === $role);
    }
    
    public function isAdmin()
    {
        if (empty($this->id)) {
            // Not identified => no rights
            return false;
        }
        $role = $this->getState("role");
        
        // allow access if the operation request is the current user's role
        return ('admin' === $role);
    }
    
    public function isEditor()
    {
        if (empty($this->id)) {
            // Not identified => no rights
            return false;
        }
        $role = $this->getState("role");
        
        // allow access if the operation request is the current user's role
        return ('editor' === $role);
    }
	
    public function getRole() {
        if($user = $this->getModel()){
            return $user->role;
        }
    }
 
    private function getModel(){
        if (!$this->isGuest && $this->_model === null){
            $this->_model = User::model()->findByPk($this->id, array('select' => 'role'));
        }
        return $this->_model;
    }

}