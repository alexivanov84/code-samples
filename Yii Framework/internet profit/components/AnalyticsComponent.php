<?php
// Lets import Google Client component here for class declarations
Yii::import('ext.google.client.src.Google_Client', true);
Yii::import('ext.google.client.src.contrib.Google_AnalyticsService', true);

/**
 * Date: 29.10.12 17:10
 * Google Analytics
 *
 * @property Google_Client $currentSDKInstance
 * @property Google_AnalyticsService $service
 *
 * Use probably want to use @see Analytics to create this component.
 */
class AnalyticsComponent extends SocialOutlet
{
	/**
	 * Scopes to request user permissions for
	 * @var array
	 */
	private $scopes = array(
		'https://www.googleapis.com/auth/analytics.readonly'
	);

	/**
	 * @var Google_AnalyticsService
	 */
	private $_service;

	/**
	 * Analytics Account ID.
	 * This property is initialized when AnalyticsComponent is created by @see Analytics
	 * @var string
	 */
	public $accountID;

	/**
	 * Analytics WebProperty ID
	 * This property is initialized when AnalyticsComponent is created by @see Analytics
	 * @var string
	 */
	public $propertyID;

	/**
	 * Analytics Profile ID
	 * This property is initialized when AnalyticsComponent is created by @see Analytics
	 * @var string
	 */
	public $profileID;

	/**
	 * Getter for Analytics Service
	 * @return Google_AnalyticsService
	 */
	public function getService(){
		return $this->_service;
	}

	/**
	 * Overwrite to do nothing
	 * @return bool
	 */
	protected function connectLibrary()
	{
		return true;
	}

	/**
	 * Main class configuration
	 * @param array $data Not used
	 * @return boolean
	 */
	protected function getConfigInit($data)
	{
		$this->makeValidation();

		// Set client
		$client = new Google_Client();

		// We use arrays internally, despite the fact the we return objects.
		// This is done to be able to extends basic google API classes.
		// PHP has no object casting you know.
		$client->setUseObjects(false);
		$client->setAccessToken($this->accountInstance->userkey);
		parent::getConfigInit($client);

		$this->_service = new Google_AnalyticsService($client);

		if($this->currentSDKInstance->isAccessTokenExpired()){
			return $this->refreshToken();
		}

		return true;
	}

	/**
	 * Last error message
	 * @var string
	 */
	private $_lastError = null;

	/**
	 * Getter for last error
	 * @return string
	 */
	public function getLastError(){
		return $this->_lastError;
	}

	/**
	 * @return bool Tells whether there are request errors
	 */
	public function hasErrors()
	{
		return ($this->_lastError === null);
	}

	/**
	 * Resfresh oAuth Key using refresh token
	 * @link https://developers.google.com/accounts/docs/OAuth2WebServer
	 * @return boolean
	 */
	protected function refreshToken()
	{
		$refreshToken = $this->accountInstance->usersecret;

		try{
			$this->currentSDKInstance->setApplicationName($this->configInstance->socialconfigappid);

			$this->currentSDKInstance->setClientId($this->configInstance->socialconfigappkey);
			$this->currentSDKInstance->setClientSecret($this->configInstance->socialconfigappsecret);
			$this->currentSDKInstance->setScopes($this->scopes);

			$this->currentSDKInstance->refreshToken($refreshToken);
			$token = $this->currentSDKInstance->getAccessToken();

			$this->accountInstance->userkey = $token;

			$token = CJSON::decode($token);
			$this->accountInstance->usersecret = $token['refresh_token'];
			$this->accountInstance->validAccount = 1;
			$this->accountInstance->update();

			return true;
		}
		catch(Exception $e){
			$this->_lastError = $e->getMessage();

			$this->accountInstance->validAccount = 0;
			$this->accountInstance->update();

			return false;
		}
	}

	/**
	 * Performs a request to Google Analytics Core Reporting engine.
	 * This is wrapper to native function, but it is more convinient
	 * and uses arrays instead of weird Google API ga: strings.
	 *
	 * @param string $start_date Start date for fetching Analytics data. YYYY-MM-DD
	 * @param string $end_date End date for fetching Analytics data. YYYY-MM-DD
	 * @param array $metrics An array of metrics
	 * @param array $optParams Optional parameters.
	 *
	 * @opt_param int max-results The maximum number of entries to include in this feed.
	 * @opt_param array sort List of dimensions or metrics that determine the sort order for Analytics data.
	 * @opt_param array dimensions A list of Analytics dimensions.
	 * @opt_param int start-index An index of the first entity to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
	 * @opt_param string segment An Analytics advanced segment to be applied to data.
	 * @opt_param string filters A comma-separated list of dimension or metric filters to be applied to Analytics data.
	 *
	 * @return AnalyticsData
	 */
	public function get($start_date, $end_date, $metrics, $optParams = array()) {
		$ids = 'ga:' . $this->profileID;
		$metric_string = '';

		if(!empty($metrics)){
			$metric_string = array();

			foreach ($metrics as $metric) {
				$metric_string[] = 'ga:'.$metric;
			}

			$metric_string = implode(', ', $metric_string);
		}

		if(!empty($optParams['sort'])){
			$sort = $optParams['sort'];
			$optParams['sort'] = array();

			foreach ($sort as $s) {
				if(strpos($s, '-')===0)
					$optParams['sort'][] = '-ga:' . substr($s, 1);
				else
					$optParams['sort'][] = 'ga:' . $s;
			}

			$optParams['sort'] = implode(', ', $optParams['sort']);
		}

		if(!empty($optParams['dimensions'])){
			$dimensions = $optParams['dimensions'];
			$optParams['dimensions'] = array();

			foreach ($dimensions as $d) {
				$optParams['dimensions'][] = 'ga:'.$d;
			}

			$optParams['dimensions'] = implode(', ', $optParams['dimensions']);
		}

		if(empty($optParams['filters'])){
			unset($optParams['filters']);
		}

		$data = $this->_service->data_ga->get($ids, $start_date, $end_date, $metric_string, $optParams);

		return new AnalyticsData($data);
	}

	/**
	 * Run this method to check whether you can make requests
	 * @return boolean Whether request can continue
	 */
	private function canMakeRequests()
	{
		if($this->accountInstance->validAccount == false)
			return false;

		return true;
	}

	/**
	 * Returns an array of all site profiles available for the Google Account.
	 * @return array
	 */
	public function listProfiles()
	{
		if(!$this->canMakeRequests()){
			return null;
		}

		$profiles = $this->service->management_profiles->listManagementProfiles('~all', '~all');

		if(empty($profiles) || $profiles['totalResults'] == 0){
			return array();
		}
		else{
			$profiles = new Google_Profiles($profiles);
			return $profiles->items;
		}
	}

	/**
	 * Returns a list of goals available for the active profile
	 * @return array
	 */
	public function listGoals()
	{
		$goals = $this->service->management_goals->listManagementGoals($this->accountID, $this->propertyID, $this->profileID);

		if(empty($goals) || $goals['totalResults'] == 0){
			return array();
		}
		else{
			$goals = new Google_Goals($goals);
			return $goals->items;
		}
	}
}

/**
 * This class is the extension of default result class for analytics requests.
 * It has some additional features. It implements Iterator and ArrayAccess interfaces for easier access.
 * Results are also presented as an assoc. array, where keys are requested metrics or dimensions.
 * Compare this to original implementation, that returns lists.
 * Use $totals to access aggregated results in a better format.
 */
class AnalyticsData extends Google_GaData implements Iterator, ArrayAccess{

	/**
	 * @var int Current position for iterator inteface
	 */
	protected $position = 0;

	/**
	 * Aggegated query results
	 * @var array
	 */
	public $totals;

	/**
	 * Creates instance from data
	 * @param $data
	 */
	public function __construct($data)
	{
		parent::__construct($data);

		foreach ($this->getTotalsForAllResults() as $column=>$value) {
			$column = substr($column, 3);
			$this->totals[$column] = $value;
		}
	}

	/**
	 * Formats 0 indexed row to a assoc. array., where indexes are column names
	 * @param array $row Raw row
	 * @return array
	 */
	protected function formatRow($row){
		$formatted = array();

		for($i=0; $i < count($this->columnHeaders); $i++){
			$column = $this->columnHeaders[$i];
			$column = $column['name'];
			$column = substr($column, 3);
			$formatted[$column] = $row[$i];
		}

		return $formatted;
	}

	/**
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current()
	{
		return $this->formatRow($this->rows[$this->position]);
	}

	/**
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next()
	{
		$this->position++;
	}

	/**
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid()
	{
		return isset($this->rows[$this->position]);
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind()
	{
		$this->position = 0;
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset An offset to check for.
	 * @return boolean true on success or false on failure.
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset)
	{
		return isset($this->rows[$offset]);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset)
	{
		return isset($this->rows[$offset]) ? $this->formatRow($this->rows[$offset]) : null;
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->rows[] = $value;
		} else {
			$this->rows[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset The offset to unset.
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->rows[$offset]);
	}
}
