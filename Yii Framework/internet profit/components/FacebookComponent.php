<?php

class FacebookResponseHandler {

    public $error = false; //error state FALSE by default
    public $message = null;
    public $response = null; //array (usually) with response from facebook

    public static function create() {
        return new self;
    }

    public function destroy() {
        unset($this); //@todo - check this
    }

}

/**
 * Description of FacebookComponent
 * @author Sergio G.
 */
final class FacebookComponent extends SocialOutlet implements SocialNetworkInterface {

    protected $oAuthFile = 'sdk_3_2_0.src.Facebook'; // oAuth file name to include
    protected $certificateFileName = 'sdk_3_2_0/src/fb_ca_chain_bundle.crt'; //certificate file name for facebook API sslCheck
    protected $certificateFilePath = ''; //full path to certificate file. Fills when we call __construct method
    private $messageBody; //message - comes from view via $_POST
    private $messageName = 'wall update'; //title
    private $messageDescription = 'my wall status updated'; //description
    protected $limitsArray = array(
        'fetchCount' => '50', // max amount of posts fetched from twitter wall
        'searchLimit' => '50', //max amount of results from search query.
        'socialDashboardStatusesLimit' => '50', //how many last posts to consider
    );
    private $videoExtensionsArray = array(
        '3g2',
        '3gp',
        '3gpp',
        'asf',
        'avi',
        'dat',
        'divx',
        'dv',
        'f4v',
        'flv',
        'm2ts',
        'm4v',
        'mkv',
        'mod',
        'mov',
        'mp4',
        'mpe',
        'mpeg',
        'mpeg4',
        'mpg',
        'mts',
        'nsv',
        'ogm',
        'ogv',
        'qt',
        'tod',
        'ts',
        'vob',
        'wmv',
    );

    /**
     * Constructor. Constructs object
     * @param array $array - assoc. arary of values to create the instance. For details see the parent declaration
     * @return boolean
     */
    public function __construct(array $array) {
        $this->folder = 'facebook';
        $this->certificateFilePath = $this->getComponentPath() . $this->folder . '/' . $this->certificateFileName;
        parent::__construct($array);
        return true;
    }

    /*     * *********************************GET*********************************** */

    /**
     * Method sets the configuration of new facebookLib instance to currentSDKInstance attribute
     * @return boolean
     */
    protected function getConfigInit() {
        $this->makeValidation();
        $config = array(
            'appId' => $this->configInstance->socialconfigappid,
            'secret' => IsisCurl::secure($this->configInstance->socialconfigappsecret, true),
        );

        $facebook = new Facebook($config);
        Facebook::$CURL_OPTS[CURLOPT_CAINFO] = $this->certificateFilePath;
        return parent::getConfigInit($facebook);
    }

    /**
     * Method gets all posts+comments from user news feed. Access to wall via access_token with offline_access param
     * @param integer $since - number that represent the date (unix timestamp format) since you want to fetch posts. <br />
     * For example, if you need to get all posts since 1st of december 2012 you need to pass 1354320000 number to this parametr
     * <br /><br /> <b>By default</b> method will fetch all posts
     * @param integer $since - amount of posts to get <br />
     * @return FacebookResponseHandler 
     */
    private function getNewsPosts($since = null, $limit = '10') {

        if ((is_null($limit)) or (!is_numeric($limit)))
            $limit = $this->limitsArray['socialDashboardStatusesLimit'];

        $paramsArray = array(
            'date_format' => 'U',
            'access_token' => $this->accountInstance->userkey,
            'limit' => $limit
        );

        if (is_numeric($since))
            $paramsArray['since'] = (int) $since;

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/me/home', 'GET', $paramsArray);
            if ((!isset($processor->response['data'])) || (empty($processor->response['data']))) {
                $processor->error = true;
                $processor->message = 'Response was empty or there was an error while trying to get messages from your wall';
            } else {
                //success
                $processor->error = false;
                $processor->message = null;
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $limitName = 'facebook_max_get_queries';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        return $processor;
    }

    /**
     * Method gets all posts+comments from user wall. Access to wall via access_token with offline_access param
     * @param integer $since - number that represent the date (unix timestamp format) since you want to fetch posts. <br />
     * For example, if you need to get all posts since 1st of december 2012 you need to pass 1354320000 number to this parametr
     * <br /><br /> <b>By default</b> method will fetch all posts 
     * @param integer $since - amount of posts to get <br />
     * @return array - processed resonse in assoc. array (@see processResponse() method )
     */
    private function getWallPosts($since = null, $limit = null) {

        if ((is_null($limit)) or (!is_numeric($limit)))
            $limit = $this->limitsArray['socialDashboardStatusesLimit'];

        $paramsArray = array(
            'date_format' => 'U',
            'access_token' => $this->accountInstance->userkey,
            'limit' => $limit
        );

        if (is_numeric($since))
            $paramsArray['since'] = (int) $since;

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/me/feed', 'GET', $paramsArray);
            if ((!isset($processor->response['data'])) || (empty($processor->response['data']))) {
                $processor->error = true;
                $processor->message = 'Response was empty or there was an error while trying to get messages from your wall';
            } else {
                //success
                $processor->error = false;
                $processor->message = null;
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $limitName = 'facebook_max_get_queries';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        return $processor;
    }

    /**
     * Method prepare data to be inserted into DB, then calling method insertSocialData to perform data insert into the DB
     * @return array
     */
    public function fetchWallPosts() {

        $limitName = 'facebook_max_get_queries';

        if (!isset($this->accountInstance->id))
            return SocialInformer::showError('account_id_error', 'Network type: ' . $this->accountInstance->networkName);
        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $since = time() - 1209600;
        $out = $this->getNewsPosts($since, null); //FacebookResponseHandler object

        if ($out->error === true)
            return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $out->message);

        $this->dataProcess($out->response);
        return SocialInformer::showSuccess('message_get');
    }

    /**
     * Creates a link for user/page avatar
     * @param string $id - Facebook user/avatar ID
     * @return string - URL with facebook user/page avatar
     */
    private function getPictureUrl($id) {
        return 'https://graph.facebook.com/' . $id . '/picture';
    }

    /**
     * Separate method to perform data process+insert to DB when getting response with data
     * @param array - array with data to process
     * @return boolean
     */
    private function dataProcess($out) {
        foreach ($out['data'] as $key => $message) {
            $messageObj = false;
            $avatar = $this->getPictureUrl($message['from']['id']);
            if (isset($message['message']))
                $messageBody = $message['message'];
            else if (isset($message['description']))
                $messageBody = $message['description'];
            else
                continue;

            $messageObj = $this->insertSocialData('socialMediaMessages', array(
                'networkId' => $this->accountInstance->id,
                'messageId' => $message['id'],
                'messageBody' => $messageBody,
                'messageDate' => $message['updated_time'],
                'messageFrom' => $message['from']['name'],
                'messageFromId' => $message['from']['id'],
                'parentId' => '0',
                'extraParams' => '',
                'avatar' => $avatar,
                    ));
            if ((isset($message['comments']['data'])) and (is_array($message['comments']['data'])) and ($messageObj <> false)) {
                foreach ($message['comments']['data'] as $key => $comment) {
                    $messageCommObj = false;
                    $avatar = $this->getPictureUrl($comment['from']['id']);
                    if (isset($comment['message'])) {
                        $messageBody = $comment['message'];
                    } else if (isset($comment['description'])) {
                        $messageBody = $comment['description'];
                    } else {
                        continue;
                    }
                    $messageCommObj = $this->insertSocialData('socialMediaMessages', array(
                        'networkId' => $this->accountInstance->id,
                        'messageId' => $comment['id'],
                        'messageBody' => $messageBody,
                        'messageDate' => $comment['created_time'],
                        'messageFrom' => $comment['from']['name'],
                        'messageFromId' => $comment['from']['id'],
                        'parentId' => $messageObj,
                        'extraParams' => '',
                        'avatar' => $avatar,
                            ));
                }
            }
        }
        return true;
    }

    /**
     * Method gets account info from facebook based on the access_token
     * @return array - FacebookResponseHandler object

     */
    private function getAccountInfoFromAccessKey() {

        if (!isset($this->accountInstance->userkey))
            return SocialInformer::showError('userkey_error', 'Network type: ' . $this->accountInstance->networkName);

        $processor = FacebookResponseHandler::create();

        try {
            $processor->response = $this->currentSDKInstance->api('/me', 'GET', array(
                'date_format' => 'U',
                'access_token' => $this->accountInstance->userkey
                    )
            );
            if (isset($processor->response['id'])) {
                $processor->error = false;
                $processor->message = null;
            } else {
                $processor->error = true;
                $processor->message = 'Sorry, There was an unknown error with your request'; //__toString is used
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $limitName = 'facebook_max_get_queries';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        return $processor;
    }

    /**
     * Explode string to pieces
     * @param string $idsString - string to explode
     * @return array array with elements devided by "_"
     */
    private function getObjectId($idsString) {
        $inputData = explode('_', $idsString);
        return $inputData;
    }

    /**
     * GFetching PM messages from Social Network
     * @return array - array with status information
     */
    public function getPmMessage() {
        $limitName = 'facebook_max_get_queries';

        //facebook implementing threads now - inbox will not work as it was designed for different API
        //return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $processor->message);


        if (!isset($this->accountInstance->userkey))
            return SocialInformer::showError('userkey_error', 'Network type: ' . $this->accountInstance->networkName);
        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $processor = FacebookResponseHandler::create();

        try {
            $processor->response = $this->currentSDKInstance->api('/me/outbox', 'GET', array(
                'access_token' => $this->accountInstance->userkey,
                'date_format' => 'U',
                'limit' => $this->limitsArray['fetchCount'],
                    )
            );
            if ((!isset($processor->response['data']))) {
                $processor->error = true;
                $processor->message = 'There was an error while trying to get messages from your inbox';
            } else {
                //success
                $processor->error = false;
                $processor->message = null;
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        if ($processor->error === true)
            return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $processor->message);
        else {
            foreach ($processor->response['data'] as $key => $instance) {

                if ((isset($instance['id'])) && (isset($instance['message'])) && (is_array($instance['from'])) && (is_array($instance['to']))) {

                    //checking needs to be done before insert. we must check (by messageId) if such message already in DB.
                    $avatar = $this->getPictureUrl($instance['from']['id']);
                    $messageObj = $this->insertSocialData('socialMediaPrivateMessages', array(
                        'networkID' => $this->accountInstance->id,
                        'messageId' => $instance['id'],
                        'messageDate' => $instance['updated_time'],
                        'messageFromId' => $instance['from']['id'],
                        'messageFrom' => $instance['from']['name'],
                        'messageSubject' => $instance['subject'],
                        'messageText' => $instance['message'],
                        'avatar' => $avatar,
                        'favouriteFlag' => '0',
                            ));
                    $avatar = '';
                }
            }
            return SocialInformer::showSuccess('message_get');
        }
    }

    /* SEARCH METHODS */

    /**
     * Search for people in FB
     * @param array $arrayData -array with data to search
     * @return mixed - JSON response or error array with message 
     */
    protected function search(array $arrayData) {
        $limitName = 'facebook_max_search_requests';

        if (!isset($arrayData['keyword']) or (!isset($arrayData['type'])))
            return SocialInformer::showError('input_error');
        if (!isset($this->accountInstance->userkey))
            return SocialInformer::showError('userkey_error');
        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached');

        $paramsArray = array(
            'q' => $arrayData['keyword'],
            'type' => $arrayData['type'],
            'access_token' => $this->accountInstance->userkey,
            'limit' => $this->limitsArray['searchLimit'],
        );

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/search', 'GET', $paramsArray);
            if ((!isset($processor->response['data']))) {
                $processor->error = true;
                $processor->message = 'There was an error while trying to get search results';
            } else {
                //success
                $processor->error = false;
                $processor->message = null;
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);
        return $processor;
    }

    /** Wrapper of search method for Facebook Social network
     * Perfoms search based on keyword.
     * @param string $keyword - keyword to sezrh for.
     * @return array with error or success message 
     */
    public function keywordSearch($keyword) {
        if (!isset($keyword))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);

        $out = $this->search(array('keyword' => $keyword, 'type' => 'user')); // note that we use USER type of search

        if ($out->error === true)
            return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $out->message);
        else {
            foreach ($out->response['data'] as $key => $instance) {
                $avatar = $this->getPictureUrl($instance['id']);
                $messageObj = $this->insertSocialData('socialSearch', array(
                    'networkID' => $this->accountInstance->id,
                    'sUserdID' => $instance['id'],
                    'sUserName' => $instance['name'],
                    'sUserExtraName' => $instance['id'], // will help us to build external link in the future;
                    'sUserAvatar' => $avatar,
                    'weFollow' => '0'
                        ));
                $avatar = '';
            }
            return SocialInformer::showSuccess('social_keyword_search');
        }
    }

    /**
     * Public wrapper for getting profile info
     * @param string $id - user Id in Facebook network
     * @return array - array with error or array with prepared values from social network  
     */
    public function getProfile($id) {
        $limitName = 'facebook_max_profiles_requests';
        if (!isset($id))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);

        if (!isset($this->accountInstance->userkey))
            return SocialInformer::showError('userkey_error', 'Network type: ' . $this->accountInstance->networkName);

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $processor = FacebookResponseHandler::create();

        try {
            $processor->response = $this->currentSDKInstance->api('/' . $id, 'GET', array(
                'access_token' => $this->accountInstance->userkey
                    )
            );
            if (isset($processor->response['id'])) {
                $processor->error = false;
                $processor->message = null;
            } else {
                $processor->error = true;
                $processor->message = 'Sorry, There was an unknown error with your request'; //__toString is used
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        if ($processor->error === true)
            return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $processor->message);

        $avatar = $this->getPictureUrl($id);

        $limitName = 'facebook_max_get_queries';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        return(array(
            'userID' => isset($processor->response['id']) ? $processor->response['id'] : 'noID',
            'userAvatar' => isset($avatar) ? $avatar : '',
            'userName' => isset($processor->response['name']) ? $processor->response['name'] : 'Name not available',
            'title' => 'Facebook User/Group. More info about user/group see on his/her profile page at Facebook. Link is below.',
            'extraInfo' => 'Gender: ' . $processor->response['gender'],
            'externalLink' => $processor->response['link'],
            'response' => '',
                ));
    }

    /**
     * Get information about Pages and Applications user own.
     * If such Pages and Application does exist => insert info about them to social_facebook_accounts table
     * @link http://developers.facebook.com/docs/reference/api/page/
     * @return array - array with status and message
     */
    public function getUserPagesData() {
        $limitName = 'facebook_max_get_queries';

        if (!isset($this->accountInstance->userkey))
            return SocialInformer::showError('userkey_error', 'Network type: ' . $this->accountInstance->networkName);

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $userInfo = $this->getAccountInfoFromAccessKey(); // FacebookResponseHandler object

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);
        if ($userInfo->error === true)
            return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $userInfo->message);

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/' . $userInfo->response['id'] . '/accounts', 'GET', array(
                'access_token' => $this->accountInstance->userkey
                    ));

            if ((!isset($processor->response['data'])) || (empty($processor->response['data']))) {
                $processor->error = true;
                $processor->message = 'Your access_token is old OR you do not have any pages in your Facebook account. If you do have pages and you sure about it, then please delete this facebook account in ISISCMS (in order to re-generate access_token), create new one using "Manage Accounts" page (../cp/socialMedia) and try to repeat this action again with your new ISISCMS Facebook account. ';
            } else {
                $processor->error = false;
                $processor->message = null;
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        if ($processor->error === true)
            return SocialInformer::showError('error_response', $this->accountInstance->networkName . ' says: ' . $processor->message);

        foreach ($processor->response['data'] as $value) {
            $this->insertSocialData('SocialFacebookAccounts', array(
                'name' => $value['name'],
                'category' => $value['category'],
                'accessToken' => $value['access_token'],
                'applicationId' => $value['id'],
                'userId' => Yii::app()->user->id,
                'socialMediaId' => $this->accountInstance->id
            ));
        }

        $userInfo->destroy();
        $processor->destroy();

        return SocialInformer::showSuccess('facebook_pages_get');
    }

    //*********************************POST*************************************

    public function postVideoToWall($postArray, $file) {
        parent::postMessage();

        if (isset($postArray['funPage'])) {
            //post to funpage
            $feedId = $postArray['funPage']['feedId'];
            $accessKey = $postArray['funPage']['accessToken'];
        } else {
            //post to userprofile page
            $feedId = 'me';
            $accessKey = $this->accountInstance->userkey;
        }

        $this->messageBody = htmlspecialchars(IsisStaticFuncCollection::doSanitize($postArray['message']));
        if ((!isset($postArray['title'])) || (empty($postArray['title'])))
            $title = 'My new video';
        else
            $title = IsisStaticFuncCollection::doSanitize($postArray['title']);

        $limitName = 'facebook_max_replies';
        if (strlen($this->messageBody) > (int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount'])
            $this->messageBody = substr($this->messageBody, 0, ((int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount']) - 10); // cutting down the long replies/posts (http://dev.twitter.com/pages/api_faq#140_count)

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        //check if video format allowed for upload (by extension)
        if (!in_array($file->getExtensionName(), $this->videoExtensionsArray))
            return SocialInformer::showError('photo_post', $this->accountInstance->networkName . ' says: ' . 'File format you are trying to upload is not supported');

        //move uploaded file to tmp directory -> then delete it
        $tmpFileName = Yii::app()->basePath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . rand(0, 100) . time() . $file->getName();
        if ($file->saveAs($tmpFileName, true) === false)
            return SocialInformer::showError('photo_post', $this->accountInstance->networkName . ' says: ' . 'There was internal error with attempt to proccess your file. Code1. Please contact support team.');
        $realPath = realpath($tmpFileName);
        if ($realPath === false)
            return SocialInformer::showError('photo_post', $this->accountInstance->networkName . ' says: ' . 'There was internal error with attempt to proccess your file. Code2. Please contact support team.');


        $this->currentSDKInstance->setFileUploadSupport(true);
        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/' . $feedId . '/videos', 'POST', array(
                'file' => '@' . $realPath,
                'description' => $this->messageBody,
                'title' => $title,
                'access_token' => $accessKey
                    )
            );
            if (isset($processor->response['id'])) {
                $processor->error = false;
                $processor->message = null;
            } else {
                $processor->error = true;
                $processor->message = 'Sorry, There was an unknown error with your request. ';
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc; //__toString is used
        }

        @unlink($realPath); //delete file from tmp directory. @todo: create a cron to clean old files from this directory

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        if ($processor->error === true)
            return SocialInformer::showError('photo_post', $this->accountInstance->networkName . ' says: ' . $processor->message);
        return SocialInformer::showSuccess('photo_post');
    }

    /**
     * Uploads photo to selected user wall fun page.
     * Current user wall will be selected if no ID specified.
     * @param array $postArray - array with post info to process. For now it should have such key(s) as:
     * <pre>"message"</pre>
     * <pre>"funPage" - optional. In case we post to fun page.</pre>
     * @param CUploadedFile $file - uploaded file via CUploadedFile
     * @return array
     */
    public function postPhotoToWall($postArray, $file) {

        parent::postMessage();

        if (isset($postArray['funPage'])) {
            //post to funpage
            $feedId = $postArray['funPage']['feedId'];
            $accessKey = $postArray['funPage']['accessToken'];
        } else {
            //post to userprofile page
            $feedId = 'me';
            $accessKey = $this->accountInstance->userkey;
        }

        $this->messageBody = htmlspecialchars($postArray['message']);

        $limitName = 'facebook_max_replies';
        if (strlen($this->messageBody) > (int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount'])
            $this->messageBody = substr($this->messageBody, 0, ((int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount']) - 10); // cutting down the long replies/posts (http://dev.twitter.com/pages/api_faq#140_count)

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $this->currentSDKInstance->setFileUploadSupport(true);

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/' . $feedId . '/photos', 'POST', array(
                'source' => '@' . realpath($file->getTempName()),
                'message' => $this->messageBody,
                'access_token' => $accessKey
                    )
            );
            if (isset($processor->response['id'])) {
                $processor->error = false;
                $processor->message = null;
            } else {
                $processor->error = true;
                $processor->message = 'Sorry, There was an unknown error with your request. ';
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc; //__toString is used
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        if ($processor->error === true)
            return SocialInformer::showError('photo_post', $this->accountInstance->networkName . ' says: ' . $processor->message);
        return SocialInformer::showSuccess('photo_post');
    }

    /**
     * Public wrapper for posting to wall
     * @param array $array - POST array from post message form
     * @return boolean if success post to wall - true, otherwise-false;
     */
    public function postMessage($array, $type = 'regular') {
        parent::postMessage();

        $this->messageBody = htmlspecialchars($array['message']);

        $limitName = 'facebook_max_replies';
        if (strlen($this->messageBody) > (int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount'])
            $this->messageBody = substr($this->messageBody, 0, ((int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount']) - 10); // cutting down the long replies/posts (http://dev.twitter.com/pages/api_faq#140_count)
        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        return $this->postToFacebook($array, $type);
    }

    /**
     * Method post message to wall using predifined facebookLib instance
     * @param facebookLib $facebook - facebookLib instance
     * @return array
     */
    private function postToFacebook($array, $type) {

        if ($type == 'regular') {
            //post to regular user wall
            $feedId = null;
            $accessToken = $this->accountInstance->userkey;
        } else {
            //post to fan page wall on behalf of page not user
            $feedId = $array['feedId'];
            $accessToken = $array['accessToken'];
        }

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/' . $feedId . '/feed', 'POST', array(
                'message' => $this->messageBody,
                'name' => $this->messageName,
                'description' => $this->messageDescription,
                'access_token' => $accessToken
                    )
            );
            if (isset($processor->response['id'])) {
                $processor->error = false;
                $processor->message = null;
            } else {
                $processor->error = true;
                $processor->message = 'Sorry, There was an unknown error with your request. ';
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc; //__toString is used
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);
        if ($processor->error === true)
            return SocialInformer::showError('message_post', $this->accountInstance->networkName . ' says: ' . $processor->message);
        return SocialInformer::showSuccess('message_post');
    }

    /**
     * Respond (comment) to the wall for specific fb post
     * @param <array> $params - array with pararms (requiered key is "message" and "commentId")
     * @return <mixed> - boolean of success, string with error on error
     */
    public function respondToWall($params) {
        $limitName = 'facebook_max_replies';

        if ((!isset($this->accountInstance->userkey)) or (!isset($params['message'])) or (!isset($params['commentId'])))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);
        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $id = $this->getObjectId($params['commentId']);

        if (strlen($params['message']) > (int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount'])
            $params['message'] = substr($params['message'], 0, ((int) $this->getTypeInstance()->limitsArray['facebook_comment_length']['limitAmount']) - 10); // cutting down the long replies/posts (http://dev.twitter.com/pages/api_faq#140_count)

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/' . $params['commentId'] . '/comments', 'POST', array(
                'message' => $params['message'],
                'access_token' => $this->accountInstance->userkey
                    )
            );
            if (isset($processor->response['id'])) {
                $processor->error = false;
                $processor->message = null;
            } else {
                $processor->error = true;
                $processor->message = 'Sorry, There was an unknown error with your request. ';
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc; //__toString is used
        }

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        if ($processor->error === true)
            return SocialInformer::showError('message_post', $this->accountInstance->networkName . ' says: ' . $processor->message);
        return SocialInformer::showSuccess('message_post');
    }

    public function sendPmMessage(array $arrayData) {
        //for now not available in Graph API
        return true;
    }

    public function invitePerson(array $arrayData) {
        return true;
        //not available in Graph API for now
    }

    public function getMoreUserDetails() {
        return true;
    }

    private function getFriendsList($userId = null) {
        if (is_null($userId))
            $userId = 'me';

        $processor = FacebookResponseHandler::create();
        try {
            $processor->response = $this->currentSDKInstance->api('/' . $userId . '/friends', 'GET', array(
                'access_token' => $this->accountInstance->userkey
                    ));

            if ((!isset($processor->response['data']))) {
                $processor->error = true;
                $processor->message = 'There was an error while trying to get friendsList for user ';
            } else {
                $processor->error = false;
                $processor->message = null;
            }
        } catch (FacebookApiException $exc) {
            $processor->error = true;
            $processor->message = $exc;
        }

        $limitName = 'facebook_max_get_queries';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        return $processor;
    }

    /**
     * Fetch information for user dashboard
     * If script will not be able to get main info for the user it will exit with FALSE response<br />
     * @return mixed -boolean FALSE on error, assoc. array with data on SUCCESS
     */
    public function getDataForDashboard() {

        $weeks = 4;
        $since = time() - 604800 * $weeks;
        $numberOfPosts = 50;

        parent::getDataForDashboard();

        $result = $this->checkLimitsForDashboard(array('limitName' => 'facebook_max_get_queries'));
        if ($result['code'] == 'error')
            return false; //unable to pass limits checking

        $responseObj = $this->getAccountInfoFromAccessKey();
        if ($responseObj->error === true)
            return false; //unable to get main info about user => no sense to continue =>exit  

        /* make data structure like for twitter and linkedin */

        $response['message'] = '';
        $response['message']['userInfo'] = $responseObj->response;
        $response['message']['userInfo']['picture_url'] = $this->getPictureUrl($response['message']['userInfo']['id']);

        $resentPosts = $this->getWallPosts($since, $numberOfPosts); //get last X posts from facebook not older then Y weeks

        if ($resentPosts->error === true)
            $response['message']['recentPosts'] = false; //maybe hit limit, etc
        else
            $response['message']['recentPosts'] = $resentPosts->response;

        $friendsResponse = $this->getFriendsList();

        if ($friendsResponse->error === true)
            $response['message']['friendsArray'] = false; //maybe hit limit, etc
        else
            $response['message']['friendsArray'] = $friendsResponse->response;

        /* service information */
        $response['message']['serviceInfo'] = array('weeks' => $weeks, 'numberOfPosts' => $numberOfPosts);

        return $response;
    }

    private function checkLimitsForDashboard(array $array) {
        $limitName = $array['limitName'];

        if (!isset($this->accountInstance->id))
            return SocialInformer::showError('account_id_error', 'Network type: ' . $this->accountInstance->networkName);
        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        return SocialInformer::showSuccess();
    }

}

?>
