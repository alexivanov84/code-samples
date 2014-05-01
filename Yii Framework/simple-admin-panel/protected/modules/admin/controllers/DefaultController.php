<?php
class DefaultController extends CommonAdminController {
    
	public function actionIndex() 
        {
                $this->render('index');
	}
        
        /**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

 }
