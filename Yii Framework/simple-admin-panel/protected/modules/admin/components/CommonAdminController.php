<?php

class CommonAdminController extends CController
{
    public $layout = 'application.modules.admin.views.layouts.column2';
    
    /**
    * @var array the breadcrumbs of the current page. The value of this property will
    * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
    * for more details on how to specify this property.
    */
    public $breadcrumbs=array();
    
    /**
    * @var array context menu items. This property will be assigned to {@link CMenu::items}.
    */
    public $menu=array();
    
    public function beforeAction($action)
    {
    	if(Yii::app()->user->isGuest){
    		$this->redirect('login');
    	}
    	
        return true;
    }

}