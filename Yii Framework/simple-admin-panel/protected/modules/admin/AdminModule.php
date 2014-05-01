<?php

class AdminModule extends CWebModule
{

	public $layout = 'application.modules.admin.views.layouts.main';
        
        private $_assetsUrl;
 
        public function getAssetsUrl()
        {
            if ($this->_assetsUrl === null)
                $this->_assetsUrl = Yii::app()->getAssetManager()->publish(
                    Yii::getPathOfAlias('admin.assets') );
            return $this->_assetsUrl;
        }

	public function init()
	{
		$this->setImport(array(
			'admin.models.*',
			'admin.components.*',
		));
	}

	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			return true;
		}
		else
			return false;
	}
}
