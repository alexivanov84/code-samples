<?php

class SocialMediaController extends BackController {

    public function accessRules() {
        return array(
            array('allow',
                'roles' => array('SocialAccountsManage')
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';
    public $defaultAction = 'admin';
    public $socialFlag = true;

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $model = $this->loadModel($id);
        $configData = $model->configinstance;
        $configData->socialconfigtype = $configData->socialtypename->typename; //getting the name of social network type using model AR relation
        $this->render('view', array(
            'model' => $model,
            'configData' => $configData,
        ));
    }

    /**
     * Output full profile page of selected user
     * @see getMoreUserDetails() details in each social component to see/add more information
     */
    public function actionUserDetails() {
        /* query with PK from cache table */
        if (isset($_GET['view'])) {
            $pk = (int) $_GET['view'];
            $datObj = SocialProfiles::model()->findByPk($pk);
            if (is_null($datObj)) {
                $this->redirect(array('socialMediaTools'));
            } else {
                $networkName = $datObj->mediainstance->networkName;
                $networkId = $datObj->networkId;
                $networkUserId = $datObj->networkUserId;
            }
        } else {
            /* regular query with POST request with profile data */
            $networkName = $_POST['networkName'];
            $networkId = (int) $_POST['networkId'];
            $networkUserId = $_POST['networkUserId'];
            $comebackLink = $_POST['comebackLink'];
        }
        if ((!isset($comebackLink)) or (empty($comebackLink)))
            $comebackLink = Yii::app()->request->urlReferrer;

        if ((!empty($networkUserId)) && (!empty($networkName)) && (is_numeric($networkId))) {
            Yii::app()->clientScript->registerScriptFile('https://www.google.com/jsapi', CClientScript::POS_HEAD);

            $response = SocialOutlet::checkProfileCache(array(
                        'networkId' => $networkId,
                        'networkUserId' => $networkUserId,
                        'contentType' => Yii::app()->params['profilesCacheType']['extended'],
                    ));

            if (is_null($response)) {
                $obj = SocialOutlet::init($networkId);
                $data = $obj->getMoreUserDetails(array('userId' => $networkUserId));

                if ($data['code'] === 'ok') {
                    SocialOutlet::writeToProfileCache(array(
                        'networkId' => $networkId,
                        'networkUserId' => $networkUserId,
                        'data' => $data['message'],
                        'contentType' => Yii::app()->params['profilesCacheType']['extended'],
                        'userName' => $data['message']['userInfo']['userName'],
                    ));
                }
                $dataArray = $data['message'];
            } else {

                try {
                    $dataArray = unserialize($response->data);
                } catch (Exception $exc) {
                    $dataArray = array('code' => 'error', 'message' => $exc->getMessage());
                }
            }

            $this->render('/socialMedia/userProfiles/full/general', array(
                'userId' => $networkUserId,
                'data' => $dataArray, // NOTE! can be array or string (on error)
                'networkName' => $networkName,
                'comebackLink' => $comebackLink,
                'dashboard' => false,
            ));
        }
    }

    public function actionGetKeys() {
        $callbackUrl = Yii::app()->createAbsoluteUrl();
        Yii::app()->request->redirect(MHA_MOTHERSHIP . '/site/throughlogin?accesskey=' . Yii::app()->config->get('proxyAccessKey') . '&callbackUrl=' . $callbackUrl, true);
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new SocialMedia;
        $oauthFlag = 'none';
        $types = SocialConfig::model()->findAll();
        if (isset($_POST['SocialMedia'])) {
            $model->attributes = $_POST['SocialMedia'];
            if ($model->save()) {
                Yii::app()->user->setFlash('notificationMessage', "New SocialMedia account created");

                /* if "demo server" and twitter account type we follow ISISCMS account */
                /* NOTE: configid=2 - is twitter ID from proxy server SocialType Model */
                if ((MHA_DEMO_SERVER == 'demo') && ($_POST['SocialMedia']['configid'] == '2')){
                    $social_object = SocialOutlet::init($model->getPrimaryKey());
                    $response = $social_object->followISIS(); //no matter what response will be
                    SocialMedia::updateLastAcessTime($model);
                }

                $this->redirect(array('view', 'id' => $model->id));
            }
        }
        //request came from proxy server via redirect
        if (isset($_GET['fromproxy']) == 'true') {
            $model['accid'] = $_GET['accid'];
            $model['userkey'] = $_GET['userkey'];
            $model['usersecret'] = $_GET['usersecret'];
            $model['configid'] = $_GET['configid'];
        }

        $this->render('create', array(
            'model' => $model,
            'types' => $types,
        ));
    }

    public function actionOauthCreate() {
        $this->redirect(array('create'));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = $this->loadModel($id);
        if (isset($_POST['SocialMedia'])) {
            $model->attributes = $_POST['SocialMedia'];
            if ($model->save()) {
                Yii::app()->user->setFlash('notificationMessage', "SocialMedia account updated");
                $this->redirect(array('view', 'id' => $model->id));
            }
        }
        $types = SocialConfig::model()->findAll();
        $this->render('update', array(
            'model' => $model,
            'types' => $types,
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
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
        }
        else
            throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        $model = new SocialMedia('search');
        $model->unsetAttributes();  // clear any default values
        Yii::app()->getClientScript()->registerScriptFile(Yii::app()->baseUrl . '/js/actions/socialMedia.js');
        if (isset($_GET['SocialMedia']))
            $model->attributes = $_GET['SocialMedia'];
        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id) {
        $model = SocialMedia::model()->findByPk((int) $id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param CModel the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'social-media-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     * Render limits admin page
     */
    public function actionCountersStatus() {
        if ((SocialMedia::model()->checkDefaultAccsExistance()) == false)
            $this->redirect(array('SocialMediaMessages/setAccounts', 'showError' => 1));

        Yii::app()->getClientScript()->registerScriptFile(Yii::app()->baseUrl . '/js/actions/socialFunctions.js');
        Yii::app()->getClientScript()->registerScriptFile(Yii::app()->baseUrl . '/js/actions/socialMediaLimits.js');
        $this->render('counters', array());
    }

    /**
     * Renders subview via AJAX request
     */
    public function actionCountersStatusAjaxAddon() {
        $models = SocialMedia::model()->findAll();
        $this->renderPartial('counters_ajax', array(
            'models' => $models, //again should find all models // TO-DO: try to find out if it is possible to go without it
        ));
    }

    /**
     * Resets all limits counters for current users
     * @return boolean
     */
    public function actionResetCounters() {
//checking access role for admin
        if (Yii::app()->user->checkAccess('Administrator') === true) {
            Yii::app()->db->createCommand()->update(
                    SocialMedia::model()->tableName(), array('requestCount' => ''), "cmsuserid = :cmsuserid", array(
                ':cmsuserid' => Yii::app()->getUser()->id,
                    )
            );
        }
        return true;
    }

    public function actionResetStatus($accountId) {
        $accountId = (int) $accountId;
        $media = SocialMedia::model()->findByPk($accountId);
        if (!is_null($media)) {
            /* AR will return NULL in case of not valid GET param OR if user will try to affect other users profile (scope will not allow this) */
            $media->validAccount = 1;
            $media->save();
        }

        return true;
    }

    /**
     * Method allows get info about user profile from social network for any instance (for ex. for: SocialSearch, SocialPMMessages, etc OR by social user ID)
     */
    public function actionGetUserProfile() {
        //pre checkings start;
        if ((!isset($_POST['ID1'], $_POST['ID2'])) or (!is_numeric($_POST['ID1']))) {
            $this->renderPartial('/socialMedia/userProfiles/popup/user_profile_error', array(
                'result' => 'Wrong input data. Code1',
                'networkName' => '',
            ));
            Yii::app()->end();
        }
        $modelName = base64_decode($_POST['ID2']); //we do not show AR models names in JS

        $flag = false;

        if ($modelName == 'simpleProfile') {
            /* if we need to get data based on social user ID passed in POST request
             * for now ONLY TWITTER. TO-DO: make possible get profile info for other networks too
             */
            $flag = true;
            $modelName = 'SocialSearch'; //fooling around with script
        }

        if (class_exists($modelName) === false) {
            $this->renderPartial('/socialMedia/userProfiles/popup/user_profile_error', array(
                'result' => 'Wrong input data. Code2',
                'networkName' => '',
            ));
            Yii::app()->end();
        }
        //pre checkings end;
        $fromCache = false;

        if ($flag === true) {
            /* if we getting data based on social user Id not model instance */
            $obj = new SocialSearch;
            $defaultNetwork = SocialMedia::model()->defaults()->findByAttributes(array('networkName' => 'twitter'));

            if (is_null($defaultNetwork)) {
                $this->renderPartial('/socialMedia/userProfiles/popup/user_profile_error', array(
                    'result' => 'Wrong input data. Code2',
                    'networkName' => '',
                ));
                Yii::app()->end();
            }
            $obj->networkID = $defaultNetwork->id;
            $obj->sUserdID = $_POST['ID1'];
            $obj->userID = Yii::app()->getUser()->id;
            $obj->networkName = 'twitter';
        } else {
            $model = new $modelName;
            $obj = $model->findByPk($_POST['ID1']); // Object of current record (can be SocialSearch, SocialMediaPrivateMessages, etc);
        }


        $response = SocialOutlet::checkProfileCache(array(
                    'networkId' => $obj->getNetworkTypeFieldValue(),
                    'networkUserId' => $obj->getSocialNetworkUserID(),
                    'contentType' => Yii::app()->params['profilesCacheType']['popup'], //popup cache type
                ));

        if (is_null($response)) {
            $socialInstance = SocialOutlet::init($obj->getNetworkTypeFieldValue()); //social Instance
            $result = $socialInstance->getProfile($obj->getSocialNetworkUserID());
        } else {
            $result = unserialize($response->data);
            $fromCache = true;
        }

        if ((isset($result['code'])) and ($result['code'] == 'error')) {
            //error response from social network
            $this->renderPartial('/socialMedia/userProfiles/popup/user_profile_error', array(
                'result' => $result['message'],
                'networkName' => $socialInstance->getAccountInstance()->networkName,
                'networkId' => $obj->getNetworkTypeFieldValue(),
            ));
        } else {
            // success response from social network or cache
            //check where we get data. If form cache -> do not write to cache
            //if from social network -> write to DB cache;
            if ($fromCache === false) {
                SocialOutlet::writeToProfileCache(array(
                    'networkId' => $obj->getNetworkTypeFieldValue(),
                    'networkUserId' => $obj->getSocialNetworkUserID(),
                    'data' => $result,
                    'contentType' => Yii::app()->params['profilesCacheType']['popup'], //popup cache type
                    'userName' => $result['userName'],
                ));
            }
            $this->renderPartial('/socialMedia/userProfiles/popup/user_profile', array(
                'result' => $result,
                'networkName' => $obj->networkName,
                'model' => $obj,
                'networkID' => $obj->getNetworkTypeFieldValue(),
            ));
        }
    }

    /**
     * Return confirmForm view for new popup window
     */
    public function actionPrepareConfirmForm() {
        $this->renderPartial('confirmForm');
    }

    /**
     * Checks if sting is not empty or not null
     * @param mixed $field - field to check
     * @return boolean
     */
    protected function checkProfileField($field) {
        if ((!empty($field) > 0) and (!is_null($field)))
            return true;
        return false;
    }

    public function actionUpdateSocialMenu() {
        return $this->renderPartial('/lastProfiles');
        Yii::app()->end();
    }

}
