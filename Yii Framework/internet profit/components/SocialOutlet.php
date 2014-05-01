<?php

/**
 * Description of SocialOutlet
 * @author Sergio G.;
 *
 * @tutorial This abstract class describes the behavior of all extended classes +  has some common methods.
 * Actually we do not want to be able to create objects from this class, that's why abstract declaraton used.
 * To create new social media component you just need to call static "init" method with ID of social media. It will
 * return you the complete set of objects and data you need to work with social media.
 * @example  $obj = SocialOutlet::init($accountId); // where $accountId - PK from socialmedia table
 */
abstract class SocialOutlet extends CComponent{

	/**
	 * @var SocialConfig Stores the config object with data to access the application
	 */
	protected $configInstance = null;

	/**
	 * @var SocialMedia Stores the account object with data to auth the user
	 */
	protected $accountInstance = null;

	/**
	 * @var SocialType Stores the type object
	 */
	protected $typeInstance = null;

    protected $folder = null; // folder with oAuth files for component
    protected $currentSDKInstance = null; //current instance of SDK class for compnent
    public static $requestsBorder = 0; //safe custom border to prevent hitting API requests. ISIS check limits before process request to API. 

    //(TwitterOAuth,LinkedIn,Facebook classes from SDK). Must present in every component. @see setting process in getConfigInit();

	/**
	 * Getter for configInstance
	 * @return SocialConfig
	 */
	public function getConfigInstance() {
        return $this->configInstance;
    }

	/**
	 * Getter for accountInstance
	 * @return SocialMedia
	 */
	public function getAccountInstance() {
        return $this->accountInstance;
    }

	/**
	 * Getter for typeInstance
	 * @return SocialType
	 */
	public function getTypeInstance() {
        return $this->typeInstance;
    }

    public function getFolder() {
        return $this->folder;
    }

    public function setFolder($string) {
        $this->folder = $string;
        return true;
    }

    public function getCurrentSDK() {
        return $this->currentSDKInstance;
    }

    /**
     * Parent construct method for any social component creation process. For more details see the init static method.
     * @param Array $array the component configuration
     * * The configuration:
     * @var appConfig: the application configuration. (must be instance of SocialConfig)
     * @var userAccInfo: (optional) the acoount info. (must be instance of SocialMedia)
     * @var networkType: (optional) social network info. (must be instance of SocialType)
     */
    protected function __construct(array $array) {
        if (isset($array['appConfig']))
            $this->configInstance = $array['appConfig'];
        if (isset($array['userAccInfo']))
            $this->accountInstance = $array['userAccInfo'];
        if (isset($array['networkType']))
            $this->typeInstance = $array['networkType'];
        $this->connectLibrary();
        $this->setLimitersNames(); // note! twitter rediclared
        $this->getConfigInit(); //note! see also extended classes before looking to parent getConfigInit() method
        return true;
    }

    private function __clone() {
        //not allowed  
    }

    protected function getConfigInit($obj = null) {
        if (is_object($obj)) {
            $this->currentSDKInstance = $obj;
            return true;
        }
        else
            return false;
    }

    /**
     * Creates social media component of selected social network
     * @param int $id - id of selected account/network (Social Media ID )
     * @return object instance of social network (for ex. FacebookComponent, TwitterComponent)
     */
    public static function init($id) {
        if (is_numeric($id)) {
            $data = self::getAccountsList($id);

            //check if account is real.
            //if response from cron we ignore errors and return array;
            if (!isset($data) or is_null($data)) {
                if (Yii::app()->params['endName'] == 'console')
                    return array('error'); //response for cron. Maybe such social account was deleted but message still in table;
                else
                    throw new CHttpException(404, 'This account does not exists');
            }

            //check for users scopes. Disabled for crons requests;
            if (self::checkAccessPermission($data) === false) {
                if (Yii::app()->params['endName'] == 'console')
                    return array('error'); //response for cron. Maybe such social account was deleted but message still in table;
                else
                    throw new CHttpException(404, 'You do not have permissions to perform this action');
            } else {
                $obj = new $data->configinstance->socialtypename->component(//creation of new component (for ex. FacebookComponent)
                                array(
                                    'appConfig' => $data->configinstance, //SocailConfig for current ID
                                    'userAccInfo' => $data, //SocailMedia for current ID
                                    'networkType' => $data->configinstance->socialtypename, //SocailType for current ID
                        ));
                return $obj;
            }
        } // if (is_numeric($id))
        else
            throw new CHttpException(404, 'There must be an integer value passed to init method');
    }

    /**
     * Checks whether configInstance attribute is set to SocialConfig type
     * @return boolean
     */
    protected function makeValidation() {
        if (!$this->configInstance instanceof SocialConfig)
            throw new CHttpException(404, 'Configuration for component must be set before post messages or make any other API requests');
        return true;
    }

    /**
     * Perfoming creation of path string to component folder
     * @return string
     */
    protected function getComponentPath() {
        return Yii::app()->getBasePath() . "/" . MHA_SOCIAL_COMPONENT . "/";
    }

    /**
     * Connects oAuth library for each component
     * @return boolean
     */
    protected function connectLibrary() {
        Yii::import(MHA_SOCIAL_COMPONENT_PATH . '.' . $this->folder . '.' . $this->oAuthFile);
        return true;
    }

    /**
     * Getting SocialMedia AR model (accounts data) or array of models;
     * @param <id> $account - id of social network to list. If not specified - list all types of accounts
     * @return  SocialMedia instance collection or separate instance
     */
    public static function getAccountsList($account = null) {
        if (!is_null($account)) {
            if (is_numeric($account)) {
                if (Yii::app()->params['endName'] == 'console')
                    $accounts = SocialMedia::model()->resetScope()->findByPk($account);
                else
                    $accounts = SocialMedia::model()->findByPk($account); //if we need to get specific account info
            }
        } else {
            if (Yii::app()->params['endName'] == 'console')
                $accounts = SocialMedia::model()->resetScope()->findAll();
            else
                $accounts = SocialMedia::model()->findAll();            //we need to get full accounts list
        }
        return $accounts;
    }

    /**
     * This method performs access checking of current user and owner of the account/application, where is not possible to use Yii scope;
     * @param $obj - SocialMedia or  SocialConfig class instance
     * @return <boolean>
     */
    public static function checkAccessPermission($obj) {

        if (Yii::app()->params['endName'] == 'console')
            return true;
        else {
            if ($obj instanceof SocialMedia) {
                if ($obj->cmsuserid <> Yii::app()->getUser()->id)
                    return false;
                else
                    return true;
            }
            else
                return true;
        }
    }

    /**
     * Method performs message body creation for dynamic page creation view (/cms/manage/create)
     * @param string $title - page title
     * @param string $description - page descripton
     * @param string $uri - uri from page
     * @return string - message to send
     */
    public static function createMessage($title, $description, $uri) {
        if (!isset($description))
            $description = 'Page description not set';
        if (!isset($title))
            $title = 'Page title not set';

        if (!isset($uri))
            $uri = MHA_SITE_URL;
        else {
            if (stripos($uri, 'http') === false)
                $uri = MHA_SITE_URL . '/' . $uri;
        }

        //getting message from config
        $message = Yii::app()->config->get('postMessage');
        $message = str_ireplace('{title}', $title, $message);
        $message = str_ireplace('{description}', $description, $message);
        $message = str_ireplace('{page_uri}', $uri, $message);
        return $message;
    }

    /**
     * Method performs adding (insert) logic to selected social DB table for each social network we use.
     * @param int $className - class name
     * @param array $optArray - array with attribute values (name=>value) to be set.
     * @return mixed - inserted record ID on success or boolean false on error
     */
    protected function insertSocialData($className, $optArray) {
        if (!isset($className, $optArray))
            return false;
        $messageObj = new $className;
        //checkin if current message in the DB SocialMediaPrivateMessages table as favourite (saved by user). If yes - loop. Otherwise insert data
        if ($messageObj instanceof SocialMediaPrivateMessages) {
            $exists = $messageObj->exists('messageId=:messageId', array(':messageId' => $optArray['messageId']));
            if ($exists === true)
                return false;
        }
        $messageObj->setAttributes($optArray, false);
        if ($messageObj->save())
            return $messageObj->getPrimaryKey();
        else
            return false; //$messageObj->getErrors(); // for debugging
    }

    /**
     * Decode JSON object to array
     * @param string $json - JSON string with data
     * @return mixed false on error or string on success
     */
    protected function decodeJson($json, $aArray = true) {
        $dataArray = json_decode($json, $aArray);
        if (isset($dataArray))
            return $dataArray;
        else
            return false;
    }

    /**
     * Method performs set+checking actions on the limits data for current component. Also it watch after sync between DB, model and component limitersNames array fields.
     * These fields represent the values that needs to be checked for current component.
     * It uses requestCount attribute from SocialMedia model where serialized limits info is stored. 
     * It uses currentLimitsStatus attribute from SocialMedia model  where unserialized limits info is stored.
     * It uses limitersNames attribute from each component class where list of limits fields is stored.
     * It can add/delete missing fields.
     * @return boolean 
     */
    protected function setLimitersNames() {
        if (!is_array($this->accountInstance->currentLimitsStatus)) {
            foreach ($this->getTypeInstance()->limitsArray as $key => $value) {
                $dataArray[$key] = '0';
            } //create new empty assoc. array
            Yii::app()->db->createCommand()->update(
                    $this->accountInstance->tablename(), array('requestCount' => @serialize($dataArray)), "id = :id", array(
                ':id' => (int) $this->accountInstance->id,
                    )
            ); // update DB field
            $this->accountInstance->requestCount = @serialize($dataArray); // update current socialMedia model requestCount field
            $this->accountInstance->currentLimitsStatus = $dataArray; // update current socialMedia model currentLimitsStatus field
        } else {
            foreach ($this->getTypeInstance()->limitsArray as $key => $value) {
                if (array_key_exists($key, $this->accountInstance->currentLimitsStatus) === false) {// check if every limitersNames array member exist in the unserialized currentLimitsStatus array
                    $this->accountInstance->currentLimitsStatus[$key] = '0';
                }
            }

            foreach ($this->accountInstance->currentLimitsStatus as $key => $value) { //find these elements that do not belong to current component
                if (in_array($key, array_keys($this->getTypeInstance()->limitsArray)) === false) {
                    $keysToDelete[] = $key;
                }
            }
            foreach ($keysToDelete as $value) {
                unset($this->accountInstance->currentLimitsStatus[$value]);  //delete old junk values that do not in the $limitersNames component array anymore
            }
            $this->accountInstance->requestCount = @serialize($this->accountInstance->currentLimitsStatus);
            Yii::app()->db->createCommand()->update(
                    $this->accountInstance->tablename(), array('requestCount' => @serialize($this->accountInstance->currentLimitsStatus)), "id = :id", array(
                ':id' => (int) $this->accountInstance->id,
                    )
            );
        }
        return true;
    }

    /**
     * Updates limit for current component ans sync all the data between DB, AR model, etc.
     * Note that limit must present in remote "proxy app" Database
     * @param string $field - limit name to update
     * @param string $value  - value to update
     * @return boolean 
     */
    protected function updateLimiter($field, $value) {
        $this->accountInstance->currentLimitsStatus[$field] = $value;
        $this->accountInstance->requestCount = @serialize($this->accountInstance->currentLimitsStatus);
        Yii::app()->db->createCommand()->update(
                $this->accountInstance->tablename(), array('requestCount' => @serialize($this->accountInstance->currentLimitsStatus)), "id = :id", array(
            ':id' => (int) $this->accountInstance->id,
                )
        );
        return true;
    }

    /**
     * Wrapper to check the limits of max request per day
     * @param string $limiterName - name of limit
     * @param string $limit - name of limit border
     * @return <boolean>
     */
    protected function checkDayLimits($limiterName) {

        if ($this->accountInstance instanceof SocialMedia) {
            $currentHits = $this->accountInstance->currentLimitsStatus[$limiterName];
            if (($currentHits === null) or ($currentHits == '') or (!is_numeric($currentHits)))
                return true;
            $border = ((int) $this->getTypeInstance()->limitsArray[$limiterName]['limitAmount']);
            //if (($currentHits) >= ($border - $this->limitsArray['requestsBorder']))
            if (($currentHits) >= ($border - self::$requestsBorder))
                return false;
            else
                return true;
        }
        else
            return false;
    }

    /**
     * Create array with current limits and counters status. 
     * @return array - array('LimitName'=>(array('currentLimitHits'=>'7','currentLimit'=>'350')) 
     */
    public function getLimitersInfoArray() {

        $outArray = array();
        foreach ($this->getTypeInstance()->limitsArray as $key1 => $value1) {
            foreach ($this->accountInstance->currentLimitsStatus as $key2 => $value2) {
                if ($key2 == $key1) {
                    $outArray[$key1] = array('currentHits' => $value2, 'currentLimit' => $value1['limitAmount'], 'description' => $value1['limitDescription']);
                }
            }
        }
        unset($outArray[$this->accountInstance->networkName . '_comment_length']);
        return $outArray;
    }

    /**
     * Parent declaration of postMessage method
     * @return boolean
     */
    public function postMessage() {
        return true;
    }

    /**
     * Checks if account already been cached
     * @param array $array condition to search by Fields required:
     * - networkId;
     * - networkUserId;
     * - contentType
     * @return SocialProfiles model or null if model not found 
     */
    public static function checkProfileCache(array $array) {
        if (!isset($array['contentType']) or empty($array['contentType'])) {
            $array['contentType'] = Yii::app()->params['profilesCacheType']['extended'];
        }
        $model = SocialProfiles::model()->findByAttributes(array('networkId' => $array['networkId'], 'networkUserId' => $array['networkUserId'], 'contentType' => $array['contentType']));
        return $model;
    }

    /**
     * Writes account data into DB table
     * @param array $array - with data to write into cache
     * @return boolean 
     */
    public static function writeToProfileCache(array $array) {

        $model = new SocialProfiles;
        $model->data = @serialize($array['data']);
        $model->networkId = $array['networkId'];
        $model->networkUserId = $array['networkUserId'];
        if (!isset($array['contentType']) or empty($array['contentType'])) {
            $array['contentType'] = '0';
        }
        $model->contentType = $array['contentType'];
        $model->userName = $array['userName'];
        return $model->save();
    }

    /**
     * Gets info about URL from compete.com site
     * @param string $url - url to get info about
     * @return mixed - boolean FALSE on error / string on success
     */
    protected function getCompeteInfo($url) {
        Yii::import(MHA_SOCIAL_COMPONENT_PATH . '.' . 'statistics' . '.' . 'CompeteComponent');
        $obj = new CompeteComponent($url);
        return $obj->getInfo();
    }

    /**
     * Gets info about URL from alexa.com site
     * @param string $url - url to get info about
     * @return mixed - boolean FALSE on error / string on success
     */
    protected function getAlexaInfo($url) {
        Yii::import(MHA_SOCIAL_COMPONENT_PATH . '.' . 'statistics' . '.' . 'AlexaComponent');
        $obj = new AlexaComponent($url);
        return $obj->getInfo();
    }

    public function getDataForDashboard() {
        return true;
    }

    protected function cutField($fieldName, $maxLength) {
        if (strlen($fieldName) > $maxLength) {
            $fieldName = substr($fieldName, 0, (int) $maxLength - 1);
        }
        return $fieldName;
    }

}