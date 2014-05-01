<?php

/**
 * Description of TwitterComponent
 * @author Sergio G.
 */
final class LinkedinComponent extends SocialOutlet implements SocialNetworkInterface {

    private $messageBody; //message
    private $messageTitle = 'Check this out';
    private $messageDescription = 'Status Update';
    protected $oAuthFile = 'LinkedIn'; //include LinkedIn library that include oAuth and other staff for LinkedIn
    private $inviteSubject = 'Connection request';
    private $inviteText = 'Hello! I would like to add you to my professional network';
    private $inviteDefaultType = 'id';
    //custom limits which we set in class. Other we set in proxy server app and control them remotely
    protected $limitsArray = array(
        'fetchCount' => '50', // max amount of posts fetched from linkedin wall
        'searchLimit' => '25', //max amount of results from search query. (https://developer.linkedin.com/documents/people-search-api)
    );
    //for more limits see isiscms proxy application limits module + http://developer.linkedin.com/documents/throttle-limits

    /**
     * Constructor. Constructs object properties
     * @param <array> $array - assoc. arary of values to create the instance. For details see the parent declaration
     * @return <boolean>
     */
    public function __construct(array $array) {
        $this->folder = 'linkedin';
        parent::__construct($array);
        return true;
    }

//**********************************GET*****************************************
    /**
     * Method sets the configuration of new LinkedIn instance to currentSDKInstance attribute
     * @return <boolean>
     */
    protected function getConfigInit() {
        $this->makeValidation();
        $appConfig = array(
            'appKey' => IsisCurl::secure($this->configInstance->socialconfigappkey, true),
            'appSecret' => IsisCurl::secure($this->configInstance->socialconfigappsecret, true),
            'callbackUrl' => '' //$this->generateCallbackUrl(),
        );
        $LinkedIn = new LinkedIn($appConfig);
        $LinkedIn->setTokenAccess(array(
            'oauth_token' => $this->accountInstance->userkey,
            'oauth_token_secret' => $this->accountInstance->usersecret)
        );
        return parent::getConfigInit($LinkedIn);
    }

    /** Method gets all posts+comments from user's wall. Access to wall via access_token with offline_access param
     * @return <json> JSON object of data from account wall or <boolean> false
     */
    private function getWallPosts($scope=NULL) {
        $LinkedIn = $this->currentSDKInstance;
        /* '' - only first degree updates
          ?type=SHAR - only first degree updates
          ?type=SHAR&scope=self only mine */
        if (isset($scope))
            $response = $LinkedIn->updates('?scope=self&type=SHAR&count=' . $this->limitsArray['fetchCount']); //mine shares
        else
            $response = $LinkedIn->updates('?type=SHAR&count=' . $this->limitsArray['fetchCount']); //connections shares

        $limitName = 'linkedin_max_get_queries';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        return $this->processResponse($response);
    }

    /**
     * @param array $response - standart response from LinkedIn class
     * @return array - assoc. array with error OR success message
     * <pre>
     * array('success','response')
     * </pre>
     * OR
     * <pre>
     * array('error','error description')
     * </pre>*
     */
    private function processResponse($response) {
        if ($response['success'] === true) {
            $output = simplexml_load_string($response['linkedin']);
            if ($output !== false)
                return array('code' => 'success', 'message' => $output);
            else {
                if ($response['info']['http_code'] == 201)
                    return array('code' => 'success', 'message' => 'Your request performed.');
                else
                    return array('code' => 'error', 'message' => 'Warning! It seems like your requests was performed, but no success message was recieved from LinkedIn.');
            }
        } else {
            $output = simplexml_load_string($response['linkedin']);
            if ($output === false) {
                if (isset($response['error']))
                    return array('code' => 'error', 'message' => $response['error']);
                else
                    return array('code' => 'error', 'message' => 'unknown error');
            }
            else {
                if (isset($output->message))
                    return array('code' => 'error', 'message' => $output->message);
                else
                    return array('code' => 'error', 'message' => 'unknown error');
            }
        }
    }

    /**
     * Wrapper for getWallPosts() method.
     * This method process data and sends it for insert into insertSocialData method
     * MAX 300 GET requests per day
     * @see - http://developer.linkedin.com/docs/DOC-1112 (Calls for the user's network data section -> Get Network Updates)
     * @return <boolean>
     */
    public function fetchWallPosts() {
        $limitName = 'linkedin_max_get_queries';

        if (!isset($this->accountInstance->id))
            return SocialInformer::showError('account_id_error', 'Network type: ' . $this->accountInstance->networkName);

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);
        $out = $this->getWallPosts(true); //getting response from linkedIn in assoc. array format (all mine connections shares)


        /* IF first query not succeed try second. If second returns FASLE too => error, else continue only with second succeed query
          ELSE if first query ok try second one. Anyway success message will be output in this case, cause 1st was successful. */

        if (($out['code'] == 'error')) {
            $out = null;
            $out = $this->getWallPosts(); //getting response from linkedIn in assoc. array format (all mine shares)
            if (($out['code'] == 'error'))
                return SocialInformer::showError('empty_response', $out['message'] . ' Network type: ' . $this->accountInstance->networkName);
            $this->dataProcess($out['message']);
        }
        else {
            $this->dataProcess($out['message']);
            $out = null;
            $out = $this->getWallPosts(); //getting response from linkedIn in assoc. array format (all mine shares)
            if (($out['code'] == 'success'))
                $this->dataProcess($out['message']);
        }
        return SocialInformer::showSuccess('message_get');
    }

    /**
     * Separate method to perform data process+insert to DB when getting response with data from LinkedIn
     * @param <array> $out - array with 
     * @return boolean 
     */
    public function dataProcess($out) {
        foreach ($out as $key => $value) {
            $messageObj = false;
            if (isset($value->{'update-content'}->person->{'current-share'}) and (strlen($value->{'update-content'}->person->{'current-share'}->comment) > 1)) {
                $avatar = '';
                if (isset($value->{'update-content'}->person->{'picture-url'}))
                    $avatar = $value->{'update-content'}->person->{'picture-url'};
                $messageObj = $this->insertSocialData('SocialMediaMessages', array(
                    'networkId' => $this->accountInstance->id,
                    'messageId' => $value->{'update-key'},
                    'messageBody' => $value->{'update-content'}->person->{'current-share'}->comment,
                    'messageDate' => date(U, (substr($value->timestamp, 0, -3))),
                    'messageFrom' => $value->{'update-content'}->person->{'first-name'} . " " . $value->{'update-content'}->person->{'last-name'},
                    'messageFromId' => $value->{'update-content'}->person->{'id'},
                    'parentId' => '0',
                    'extraParams' => '',
                    'avatar' => $avatar,
                        ));
            }
            //checking for comments to current post and process them if we have ones
            if (($value->{'update-comments'}->{'update-comment'}) and ($messageObj <> false)) {
                $messageComObj = false;
                foreach ($value->{'update-comments'}->{'update-comment'} as $key2 => $comment) {
                    if (strlen($comment->comment) > 1) {
                        $subavatar = '';
                        if (isset($comment->person->{'picture-url'}))
                            $subavatar = $comment->person->{'picture-url'};
                        $messageComObj = $this->insertSocialData('SocialMediaMessages', array(
                            'networkId' => $this->accountInstance->id,
                            'messageId' => $comment->id,
                            'messageBody' => $comment->comment,
                            'messageDate' => date(U, (substr($comment->timestamp, 0, -3))),
                            'messageFrom' => $comment->person->{'first-name'} . " " . $comment->person->{'last-name'},
                            'messageFromId' => $comment->person->{'id'},
                            'parentId' => $messageObj,
                            'extraParams' => '',
                            'avatar' => $subavatar,
                                ));
                    }
                }
            }
        }
        return true;
    }

    public function getPmMessage() {
        //not available at all. LinkedIn API doesn't provide such thing
        return true;
    }

    /**
     * Get list of connections for current active user (based on access_token)
     * @param boolean $encoded - encode/decode result with json_encode function or not
     * @return array - assoc. array with status and message/data 
     */
    public function getConnectionsList($encoded=true) {
        //no limit for user on it. only application limit it seems... documentation as always great in LinkedIn 
        if (!$this->currentSDKInstance instanceof LinkedIn) {
            return false;
        }
        $response = $this->currentSDKInstance->connections();
        $out = $this->processResponse($response); //array

        if ($out['code'] == 'success') {
            if ($encoded == true)
                $out['message'] = @json_decode(@json_encode($out['message']), 1);
        }
        return $out;
    }

    /**
     * Search for people in LinkedIn
     * @param array $arrayData -array with data to search
     * @return mixed - SimpleXMLElement object with data on success or error array with message on error
     */
    protected function search(array $arrayData) {
        $limitName = 'linkedin_max_search_requests';

        $LinkedIn = $this->currentSDKInstance;
        if (!isset($arrayData['start']))
            $start = '0';
        else
            $start = $arrayData['start'];

        if (!isset($arrayData['sort']))
            $sort = 'connections';
        else
            $sort = $arrayData['sort'];

        $response = $LinkedIn->search(':(people:(id,first-name,last-name,picture-url,headline,api-standard-profile-request,site-standard-profile-request,public-profile-url))?keywords=' . urlencode($arrayData["keyword"]) . '&facets=network&facet=network,F,S,A,O' . '&start=' . $start . '&count=' . $this->limitsArray["searchLimit"] . '&sort=' . $sort);

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);
        return $this->processResponse($response);
    }

    /**
     * Wrapper of search method for LinkedIn Social network
     * Perfoms search based on keyword.
     * LinkedIn method makes 2(two) calls to search method (to get around 50 results) because of pagination in API requests results from LinkedIn (limit 25 per page)
     * @param string $keyword - keyword to serch for
     * @return array assoc array with error or success code and message 
     */
    public function keywordSearch($keyword) {
        if (!isset($keyword))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);

        //we randomly select sorting type of return call to make results a little different each time
        $rand_sort_val = 0;
        $sortItem = array('0' => 'connections', '1' => 'recommenders', '2' => 'distance', '3' => 'relevance');
        $rand_sort_val = rand(0, 3);

        $searchResults = $this->search(array('keyword' => $keyword, 'sort' => $sortItem[$rand_sort_val])); // first call
        if ($searchResults['code'] == 'error')
            return SocialInformer::showError('error_response', ' Description: ' . $searchResults['message'] . ' Network type: ' . $this->accountInstance->networkName);

        $currentTotal = @json_decode(@json_encode($searchResults['message']), 1);

        $resultsArray = array('0' => $searchResults['message']); //virtual array with results
        //making extra calls (Linkedin have stupid pagination for 25 search results per call)
        $i = 0;
        while ($i <= 2) {
            $i++;
            if ($currentTotal['people']['@attributes']['total'] > $this->limitsArray['searchLimit'] * $i) {
                $searchResults_next = $this->search(array('keyword' => $keyword, 'start' => $this->limitsArray['searchLimit'] * $i, 'sort' => $sortItem[$rand_sort_val])); //extra call for 25 more results 
                if ($searchResults_next['code'] == 'error') {
                    // break; //error   
                } else {
                    $resultsArray[$i] = $searchResults_next['message'];
                }
            }
        }

        //getting array of connections for current user
        $connections_array = $this->getConnectionsList(); //+1 request      

        foreach ($resultsArray as $key => $separateResult) {
            foreach ($separateResult->people->person as $key => $instance) {

                if (isset($instance->{'picture-url'}))
                    $avatar = $instance->{'picture-url'};
                if (strtolower($instance->{'last-name'}) == 'private') {
                    $username = 'Not available';
                    $usernameStatus = 'Private user. Account not available';
                } else {
                    $username = $instance->{'first-name'} . ' ' . $instance->{'last-name'};
                    $usernameStatus = 'Available user account';
                }

                $extraInfoArray = array(); //array with serialized extra data. Store some system info for linkedIn future requests

                if (isset($instance->{'api-standard-profile-request'}))
                    $extraInfoArray['api-standard-profile-request'] = (string) $instance->{'api-standard-profile-request'}->url; //will be used to get profile details through API
                if (isset($instance->{'site-standard-profile-request'}))
                    $extraInfoArray['site-standard-profile-request'] = (string) $instance->{'site-standard-profile-request'}->url; //will be used to get profile details via Web (link)
                if (isset($instance->{'public-profile-url'}))
                    $extraInfoArray['public-profile-url'] = (string) $instance->{'public-profile-url'};
                else
                    $extraInfoArray['public-profile-url'] = (string) $instance->{'site-standard-profile-request'}->url; //will be used to get profile details via Web (link)

                $extraInfoArray['last-name'] = (string) $instance->{'last-name'};
                $extraInfoArray['first-name'] = (string) $instance->{'first-name'};
                $weFollow = '0';

                /* check if have user in our connections START */
                if ($connections_array['code'] == 'success') {
                    /* stupid LinkedIN returns different array structure if there's 1 record or more >then 1 instead of one structure for easier processing */
                    if (is_array($connections_array['message']['person'][0])) {
                        /* if there's more then 1 record in results row */
                        foreach ($connections_array['message']['person'] as $connections_key => $connections_value) {
                            $id1 = trim($instance->id);
                            $id2 = trim($connections_value['id']);
                            if ((string) $id1 === (string) $id2) {
                                $weFollow = '1';
                            }
                        }
                    } else {
                        /* if there's one result record (1 connection only) */
                        $id1 = trim($instance->id);
                        $id2 = trim($connections_array['message']['person']['id']);
                        if ((string) $id1 === (string) $id2) {
                            $weFollow = '1';
                        }
                    }
                }
                /* check if have user in our connections END */

                $messageObj = $this->insertSocialData('SocialSearch', array(
                    'networkID' => $this->accountInstance->id,
                    'sUserdID' => (string) $instance->id,
                    'sUserName' => (string) $username,
                    //  'sUserExtraName' => (string) $usernameStatus,
                    'sUserExtraName' => '',
                    'sUserAvatar' => (string) $avatar,
                    'sInfo' => (string) $instance->headline,
                    'weFollow' => $weFollow,
                    'sExtraInfo' => @serialize($extraInfoArray),
                        ));
                $avatar = '';
            }
        }
   return SocialInformer::showSuccess('social_keyword_search', 'Network type: ' . $this->accountInstance->networkName);
    }

    /**
     * GET user profile based on provided ID
     * @param string $id - user Id in LinkedIn network or "~" symbol to return info for current user
     * @param boolean $encoded - flag whether we need encode result by @json_encode php function
     * @return array - assoc array with sttatus and message/data
     */
    private function getProfileInfo($id, $encoded=false) {

        $limitName = 'linkedin_max_profiles_requests';

        if (!isset($id))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $LinkedIn = $this->currentSDKInstance;

        if ($id == '~')
            $response = $LinkedIn->profile('~:(id,first-name,last-name,industry,location:(name),current-share,num-connections,num-recommenders,summary,specialties,associations,interests,member-url-resources,picture-url,headline,site-standard-profile-request)');
        else
            $response = $LinkedIn->profile('id=' . urlencode($id) . ':(id,first-name,last-name,industry,location:(name),current-share,num-connections,num-recommenders,summary,specialties,associations,interests,member-url-resources,picture-url,headline,site-standard-profile-request)');

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        $out = $this->processResponse($response);
        if ($out['code'] == 'success') {
            if ($encoded == true) {
                $out['message'] = @json_decode(@json_encode($out['message']), 1);
                return $out;
            }
            else
                return $out;
        }
        else {
            /* try request with less amount of fields */
            $response = $LinkedIn->profile('id=' . urlencode($id) . ':(id,first-name,last-name,industry,location:(name),current-share,num-connections,num-recommenders,summary,picture-url,headline,site-standard-profile-request)');

            $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
            $this->updateLimiter($limitName, $currentAmount + 1);

            $out = $this->processResponse($response);
            if ($out['code'] == 'success') {
                if ($encoded == true) {
                    $out['message'] = @json_decode(@json_encode($out['message']), 1);
                    return $out;
                }
                else
                    return $out;
            }
            else {
                /* error */
                return SocialInformer::showError('get_profile', 'Message: ' . $out['message'] . ' Network type: ' . $this->accountInstance->networkName);
            }
        }
    }

    /**
     * Public wrapper for getting profile info
     * @param string $id - user Id in LinkedIn network
     * @return array - assoc. array with code and message/data
     */
    public function getProfile($id) {
        if (!isset($id))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);

        $response = $this->getProfileInfo($id, true);
        if ($response['code'] == 'error') {
            return SocialInformer::showError('', $response['message'], true);
        }

        return(array(
            'userID' => isset($response['message']['id']) ? $response['message']['id'] : 'noID',
            'userAvatar' => isset($response['message']['picture-url']) ? $response['message']['picture-url'] : '',
            'userName' => isset($response['message']['first-name']) ? $response['message']['first-name'] . $response['message']['last-name'] : 'Name not available',
            'title' => isset($response['message']['headline']) ? $response['message']['headline'] : '',
            // 'extraInfo' => isset($response['industry']) ? 'Industry: ' . $response['industry'] : 'Industry: not specified',
            'externalLink' => isset($response['message']['site-standard-profile-request']['url']) ? $response['message']['site-standard-profile-request']['url'] : '',
            'response' => $response['message'],
                ));
    }

    //* MAX 250 POST requests per day
    //* @see - http://developer.linkedin.com/docs/DOC-1112 ("Calls for the user's own data" section -> Update Status/Share API)

    /**
     * Sends response to the wall as a comment
     * @param array $params - array of needed params for responce:
     * @return array - assoc. array with code and message/data
     */
    public function respondToWall($params) {
        $limitName = 'linkedin_max_replies';

        if ((!isset($this->accountInstance->userkey)) or (!isset($params['message'])) or (!isset($params['commentId'])))
            return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName);

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        if (strlen($params['message']) > ((int) $this->getTypeInstance()->limitsArray['linkedin_comment_length']['limitAmount']))
            $params['message'] = substr($params['message'], 0, ((int) $this->getTypeInstance()->limitsArray['linkedin_comment_length']['limitAmount']) - 10); // cutting down the long replies/posts (http://dev.twitter.com/pages/api_faq#140_count)

        $response = $this->currentSDKInstance->comment($params['commentId'], $params['message']);

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        $out = $this->processResponse($response);
        if ($out['code'] == 'error') {
            return SocialInformer::showError('message_post', $out['message'] . ' Network type: ' . $this->accountInstance->networkName);
        }
        else
            return SocialInformer::showSuccess('message_post');
    }

    /**
     * Post new message to the wall as update-status
     * @param array $params - array with needed params for responce:
     * Required: 
     * <pre>$array['message']</pre> - message body;
     * @return array - assoc. array with code and message/data
     */
    public function postMessage($array) {
        parent::postMessage();

        $limitName = 'linkedin_max_replies';

        if ($this->checkDayLimits($limitName) === false)
            return SocialInformer::showError('d_limit_reached', 'Network type: ' . $this->accountInstance->networkName);

        $this->messageBody = htmlspecialchars($array['message']);
        $content = array();
        $content['comment'] = $this->messageBody; //linkedin control string length so we do not need to worry about it.
        $private = false;

        /* seems to be 250 per user per day http://developer.linkedin.com/docs/DOC-1112 */
        $response = $this->currentSDKInstance->share('new', $content, $private);

        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        $out = $this->processResponse($response);
        if ($out['code'] == 'error') {
            return SocialInformer::showError('message_post', $out['message'] . ' Network type: ' . $this->accountInstance->networkName);
        }
        else
            return SocialInformer::showSuccess('message_post');
    }

    public function sendPmMessage(array $arrayData) {
        //only availabe if we have id or name of connection
        //not available in ISISCMS for now
        return true;
    }

    /**
     * Invites a person by ID or EMAIL from search by keyword module
     * @param array $arrayData - array with input data
     * Required array elements: 
     * user - id/email of the user to invite;
     * SocialSearch - SocialSearch instance with current user from keyword search module 
     * 
     * If email type is used then mention that inviter must know user first-name and last-name along with e-mail address
     * @return  array with success or error message
     */
    public function invitePerson(array $arrayData) {

        if ((!isset($arrayData['socialSearchObject'])) or (($arrayData['socialSearchObject'] instanceof SocialSearch) === false))
            return SocialInformer::showError('input_error', 'Erorr details: No social object was found. Network type: ' . $this->accountInstance->networkName);

        //get extra data from current search record for this user
        $extraInfo = @unserialize($arrayData['socialSearchObject']->sExtraInfo);

        //if user profile is private he/she cannot be invited
        if ((isset($extraInfo['last-name'])) and (strtolower($extraInfo['last-name']) == 'private'))
            return SocialInformer::showError('social_invite_linkedin', 'Network type: ' . $this->accountInstance->networkName);

        //check type of message and other staff based on type
        if ((!isset($arrayData['type'])) or (strlen($arrayData['type']) == 0) or (($arrayData['type'] == 'id'))) {
            $arrayData['type'] = $this->inviteDefaultType; // by ID
            $arrayData['user'] = $arrayData['socialSearchObject']->sUserdID;
        } else {
            if (!isset($arrayData['user']))
                return SocialInformer::showError('input_error', 'Network type: ' . $this->accountInstance->networkName); //email not set - error
            $arrayData['type'] = 'email';
            $validator = new CEmailValidator;
            if ($validator->validateValue($arrayData['user']) === false)
                return SocialInformer::showError('input_error', 'Error details: Value must be an e-Mail string. Network type: ' . $this->accountInstance->networkName);


            if ((!isset($extraInfo['first-name'])) or (!isset($extraInfo['last-name'])))
                return SocialInformer::showError('email_invite', 'Network type: ' . $this->accountInstance->networkName);
        }

        //set default values if they not set
        if ((!isset($arrayData['subject'])) or (strlen($arrayData['subject']) == 0))
            $arrayData['subject'] = $this->inviteSubject;

        if ((!isset($arrayData['text'])) or (strlen($arrayData['text']) == 0))
            $arrayData['text'] = $this->inviteText;

        if ($arrayData['type'] == 'id')
            $response = $this->currentSDKInstance->invite($arrayData['type'], $arrayData['user'], $arrayData['subject'], $arrayData['text']);
        else
            $response = $this->currentSDKInstance->invite($arrayData['type'], array('email' => $arrayData['user'], 'first-name' => $extraInfo['first-name'], 'last-name' => $extraInfo['last-name']), $arrayData['subject'], $arrayData['text']);

        $limitName = 'linkedin_max_search_requests';
        $currentAmount = (int) $this->accountInstance->currentLimitsStatus[$limitName];
        $this->updateLimiter($limitName, $currentAmount + 1);

        $out = $this->processResponse($response);
        if ($out['code'] == 'error') {
            //update current model with error description
            $inv_error = 'LinkedIn says: ' . $out['message'];
            $arrayData['socialSearchObject']->weFollow = 2;
            $arrayData['socialSearchObject']->errorDescr = $inv_error;
            $arrayData['socialSearchObject']->save();
            return SocialInformer::showError('social_invite', $out['message'] . ' Network type: ' . $this->accountInstance->networkName);
        }
        else
            return SocialInformer::showSuccess('social_invite');
    }

    //TO-DO: review all below!!!!!!

    /**
     *
     * @param array $dataArray
     * @return type 
     */
    public function getMoreUserDetails(array $dataArray) {
        if (isset($dataArray['userId'])) {
            /* get user profile info from twitter API; if there's url for user we add more analitycs tools */
            $alexa = null;
            $siteAnalytics = null;

            $userInfo = $this->getProfile($dataArray['userId']);
            if ($userInfo['code'] == 'error') {
                return $userInfo;
            } else {
                /* stupid linkedIn returns different array strucuture for 1 result or more */
                if (isset($userInfo['response']['member-url-resources']['member-url'][0])) {
                    $url = $userInfo['response']['member-url-resources']['member-url'][0]['url'];
                } else if (isset($userInfo['response']['member-url-resources']['member-url']['url'])) {
                    $url = $userInfo['response']['member-url-resources']['member-url']['url'];
                }

                if (isset($url)) {
                    $sa = $this->getCompeteInfo($url);
                    if (is_string($sa))
                        $siteAnalytics = $sa;

                    $sa = $this->getAlexaInfo($url);
                    if (is_string($sa))
                        $alexa = $sa;
                }
            }

            return array(
                'code' => 'ok',
                'message' => array(
                    'userInfo' => $userInfo,
                    'alexa' => $alexa,
                    'siteAnalytics' => $siteAnalytics,
                ),
            );
        }
        else
            return SocialInformer::showError('', 'no userID provided', true);
    }

    /**
     * Get information for user dashboard
     * @return array - empty array on FALSE, array with data on SUCCESS 
     */
    public function getDataForDashboard() {
        parent::getDataForDashboard();
        /* get extended usr information */
        $result = $this->getMoreUserDetails(array('userId' => '~'));
        if ($result['code'] == 'error')
            return false; //unable to get main info about user => no sense to continut =>exit
        /* get user info like in thinkup */
        return $result;
    }

}

?>
