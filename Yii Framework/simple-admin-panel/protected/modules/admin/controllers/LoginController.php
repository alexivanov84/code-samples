<?php

class LoginController extends CController
{
	public function actionIndex()
	{
            $this->layout = 'application.modules.admin.views.layouts.login';
            $model=new LoginForm;
            
            if(!Yii::app()->user->isGuest){
                $this->redirect($this->createUrl('default/index'));
            }
            else
            {
		// if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form-index-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['LoginForm']))
		{
			$model->attributes=$_POST['LoginForm'];
                        
			// validate user input and redirect to the previous page if valid
			if($model->validate() && $model->login())  {
                            $this->redirect($this->createUrl('default/index'));
                        }
		}

            }            
            // display the login form
            $this->render('index',array('model'=>$model));
	}

}