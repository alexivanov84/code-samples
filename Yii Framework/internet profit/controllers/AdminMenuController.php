<?php

class AdminMenuController extends BackController {

    public function accessRules() {
        return array(
            array('allow',
                'roles' => array('AdminMenuEdit')
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public $defaultAction = 'admin';

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $this->render('view', array(
            'model' => $this->loadModel($id),

        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new AdminMenu;

        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($model);
        if (isset($_POST['AdminMenu'])) {
            $model->attributes = $_POST['AdminMenu'];
            if ($model->save()) {
                Yii::app()->user->setFlash('notificationMessage', "New Admin menu item created");
                $this->redirect(array('view', 'id' => $model->id));
            }
        }

        $parents = AdminMenu::model()->findAll(array(
            'select' => 'id, title'
                ));
        $parents = CHtml::listData($parents, 'id', 'title');

        $this->render('create', array(
            'model' => $model,
            'parents' => $parents
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        // Uncomment the following line if AJAX validation is needed
        $this->performAjaxValidation($model);
        if (isset($_POST['AdminMenu'])) {
            $model->attributes = $_POST['AdminMenu'];
            if ($model->save()) {
                Yii::app()->user->setFlash('notificationMessage', 'Admin menu item updated');
                $this->redirect(array('view', 'id' => $model->id));
            }
        }

        $parents = AdminMenu::model()->findAll(array(
            'select' => 'id, title',
            'condition' => 'id != :self',
            'params' => array(':self' => $id)
                ));
        $parents = CHtml::listData($parents, 'id', 'title');

        $this->render('update', array(
            'model' => $model,
            'parents' => $parents
        ));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        if (Yii::app()->request->isPostRequest) {
            // we only allow deletion via POST request
            $this->loadModel($id)->delete();

            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if (!isset($_GET['ajax']))
            { 
                Yii::app()->user->setFlash('notificationMessage', 'Admin menu item deleted');
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));}
        }
        else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        // Uncomment the following line if AJAX validation is needed

        $model = new AdminMenu('search');
        $model->unsetAttributes();  // clear any default values

        if (isset($_GET['AdminMenu']))
            $model->attributes = $_GET['AdminMenu'];

        $parents = AdminMenu::model()->findAll(array(
            'select' => 'id, title'
                ));

        $parents = CHtml::listData($parents, 'id', 'title');

        $this->render('admin', array(
            'model' => $model,
            'parents' => $parents,

        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id) {
        $model = AdminMenu::model()->findByPk((int) $id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param CModel the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'admin-menu-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

}
