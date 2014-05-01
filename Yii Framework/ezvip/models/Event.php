<?php

define('RECURRING_NOT', 0);
define('RECURRING_WEEKLY', 1);
define('RECURRING_BIWEEKLY', 2);
define('RECURRING_MONTHLY', 3);

class Event extends CActiveRecord implements Searchable
{
    protected $_fileSaver = null;
    public $unixDate = null;
    public $lastDateUnix = null;
    public $afterSaveFlag = 0;
    
    const INACTIVE = 0;
    const ACTIVE = 1;
    const SITE_ONLY = 2;
	const PARTNER_ONLY = 3;
    
    private $_cachedVirtualTourUrl = null;
    private $_cachedPartnerVirtualTourUrl = null;
    
    public $ticketPartFields = array(
            TICKET_EXPRESS => 'express',
            TICKET_GENERAL => 'general',
            TICKET_VIP => 'vip',
            TICKET_COMPLIMENTARY => 'complimentary'
        );
        
    public $ticketTitles = array(
            TICKET_EXPRESS => 'express admission ',
            TICKET_GENERAL => 'general admission ',
            TICKET_VIP => 'vip admission ',
            TICKET_COMPLIMENTARY => 'complimentary admission '
        );        

    public $eventArtistsNew = array();
    public $eventTicketTypesNew = array();
    /**
     *
     * @param string $className
     * @return Event
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

	public function init()
	{
		parent::init();
		ZendModelSearch::getInstance()->makeIndexable($this);
	}       
    
//    public function init()
//    {
//        $this->_fileSaver = new AdminFileSaver($this);
//    }

    public function save()
    {
        if($this->getIsNewRecord()){
        	$this->version = 0;
        } else {
    		$this->version++;
        }
        $this->changeDate = date("Y:m:d H:i:s");

        if($this->tablesLeft<=0) $this->recountTablesLeft();
    	
		$images = $this->getImageOptions();
        
        foreach ($images as $i)
        {
            if(!file_exists($i[1]))
            {
                mkdir($i[1], 0777, true);
            }
        }        
        
    	$this->_fileSaver = new AdminFileSaver($this);
    	
        if(parent::save())
        {
            $this->_fileSaver->save($images);
            $this->_processRecurring();
            return true;
        }

        return false;
    }
    
    public function saveWithoutImages()
    {
        if($this->getIsNewRecord()){
        	$this->version = 0;
        } else {
    		$this->version++;
        }
        
        if($this->tablesLeft<=0) $this->recountTablesLeft();

        return parent::save();
                        
    }    

    public function rules()
    {
        $numricalFields = "venueId, complimentaryPrice, complimentaryAmount, ";
        $numricalFields .= "expressPrice, expressAmount, generalPrice, generalAmount, ";
        $numricalFields .= "vipPrice, vipAmount, genre";

        return array(
            array($numricalFields, 'numerical'),
            array('title', 'required'), // venueId
            //array('active', 'in', 'range' => array(0, 3)),
            array('image, flyer, bigFlyer, sectionImage, logo', 'file', 'types'=>'jpg, png', 'allowEmpty' => true),
            //array('date, startSale', 'type', 'type' => 'date', 'dateFormat' => 'MM/dd/yyyy'),
            array('title,date,recurring,categoryId,venueId,description,time,startSale,complTicketsForBtlSum, btlSum, complTicketsFromVenue,' .
            	'location, complimentaryPrice,complimentaryAmount,complimentarySold,expressPrice,expressAmount,expressSold,'.
            	'generalPrice,generalAmount, generalSold,vipPrice,vipAmount,vipSold,active, trending, lastDate, eventNotes,'.
            	'partnerEventNotes, showGLForm, featured, genre, titleFontSize, venueTitleFontSize, trendingTitleFontSize, guyPrice, girlPrice, virtualTourUrl, partnerVirtualTourUrl', 'safe'), //tablesLeft
            array('image, flyer, bigFlyer, sectionImage, logo', 'unsafe')	
        );
    }

    public function relations()
    {
        return array(
        	'artists' =>  array(self::HAS_MANY, 'EventArtist', 'instanceId', 'order'=>'artists.id ASC'),
        	'bottles' =>  array(self::HAS_MANY, 'BottleVE', 'eventId'),
            'venue' =>  array(self::BELONGS_TO, 'Venue', 'venueId'),
        	'base' => array(self::BELONGS_TO, 'Event', 'baseEvent'),
        	'category' => array(self::BELONGS_TO, 'Category', 'categoryId'),
            'eventTicketTypes' => array(self::HAS_MANY, 'EventTicketType', 'eventId'),
        );
    }
    
    public function beforeSave()
    {
        if(isset($this->venue->location)){
    		$this->location = $this->venue->location;
        }
        
        $this->_toDbDate(array('date', 'startSale', 'lastDate'));

        $this->unixDate = CDateTimeParser::parse($this->date, 'yyyy-MM-dd');
        $this->lastDateUnix = CDateTimeParser::parse($this->lastDate, 'yyyy-MM-dd');
        
        return parent::beforeSave();
    }
    
    public function afterSave(){
    	//if(!parent::afterSave()) return false;
    	if(!$this->afterSaveFlag) {
            $eventId = $this->getAttribute('id');
            $else_data = array('instanceId'=>$eventId);
            $model = EventArtist::model();
            $records = $this->getAttribute('eventArtistsNew');

            $errors = Utils::saveList($model, $records, $else_data);
            if(count($errors)){
                    $this->addErrors($errors);
                    return;
            }

            $else_data = array('eventId'=>$eventId);
            $model = EventTicketType::model();
            $records = $this->getAttribute('eventTicketTypesNew');
            $errors = Utils::saveList($model, $records, $else_data);
            if(count($errors)){
                    $this->addErrors($errors);
                    return;
            }
            $this->afterSaveFlag = 1;
        }
        
    	parent::afterSave();
    	return;
    	
    }
    
    public function afterDelete()
    {
    	//if(!parent::afterDelete) return false;
    	//delete event artists
     	/*$id = $this->getAttribute('id');
        $searchCriteria = new CDbCriteria();
        $searchCriteria->addSearchCondition('eventId', $id);       	
     	$records = EventArtist::model()->findAll($searchCriteria);
     	foreach($records as $record)
     	{
     		$record->delete();
     	}*/ //because we didn't delete event we only set it as deleted
     	
     	parent::afterDelete();
     	   	
    }    

    public function afterFind()
    {
        $date = CDateTimeParser::parse($this->date, 'yyyy-MM-dd');
        if($date !== false)
        {
            $this->date = date("m/d/Y", $date);
            $this->unixDate = $date;
        }

        $startSale = CDateTimeParser::parse($this->startSale, 'yyyy-MM-dd');
        if($startSale !== false)
        {
            $this->startSale = date("m/d/Y", $startSale);
        }
        
        $lastDate = CDateTimeParser::parse($this->lastDate, 'yyyy-MM-dd');
        if($lastDate !== false)
        {
            $this->lastDate = date("m/d/Y", $lastDate);
            $this->lastDateUnix = $lastDate;
        }
        
        return parent::afterFind();
    }

    public function delete()
    {
        if($this->baseEvent == 0)
        {
            $childEvents = Event::model()->findAllByAttributes(array('baseEvent' => $this->id, 'deleted' => 0));

            foreach($childEvents as $event)
            {
                $event->delete();
            }
        }

        $this->deleted = 1;
        $this->saveWithoutImages();

        //return parent::delete();
    }

    protected function _isBaseEvent()
    {
        return $this->baseEvent == 0;
    }    
    
    public function getImage($returnFileName = true)
    {
        return $this->getCommonImage("events/images", "image", $returnFileName);	    	
    }

    public function getFlyer($returnFileName = true)
    {        
        return $this->getCommonImage("events/flyers", "flyer", $returnFileName);	
    }
    
    public function getBigFlyer($returnFileName = true)
    {        
    	return $this->getCommonImage("events/bigFlyers", "bigFlyer", $returnFileName);	
    } 
    
    public function getSectionImage($returnFileName = true)
    {        
    	return $this->getCommonImage("events/sectionImages", "sectionImage", $returnFileName);	
    }     
    
    public function getLogo($returnFileName = true)
    {        
    	return $this->getCommonImage("events/logo", "logo", $returnFileName);	
    }       

    public function getCommonImage($folder, $imageField, $returnFileName = true){
    	if($this->id && $this->$imageField) {	
            return $returnFileName ? Utils::getImageUrl("$folder/", $this->$imageField) : true;
        } else {
            if($this->_isBaseEvent())
            {
            	return $returnFileName ? Utils::getNoImage() : false;
            }
            else
            {
                //it is recurring event
                //test on existing base event;
                return $this->base->getCommonImage($folder, $imageField, $returnFileName);
            }
        }    	
    }
    
    public function recountTablesLeft(){
    	if(!$this->venueId)
    		return;
    	$c = new CDbCriteria();
    	$c->addColumnCondition(array("eventId"=>$this->id, "billed"=>OrderState::BILLED));
    	$ordered =  Order::model()->count($c);
    	
    	$c2 = new CDbCriteria();
    	$c2->addColumnCondition(array("venueId"=>$this->venueId));    	
    	$all = Section::model()->count($c2);
    	$this->tablesLeft = $all - $ordered;
    	
    }
    
    public function recurre()
    {
        if($this->recurring == RECURRING_NOT){return;}

        $offsets = array(
            RECURRING_WEEKLY => "+1 week",
            RECURRING_BIWEEKLY => "+2 week",
            RECURRING_MONTHLY => "+1 month",
        );

        $this->date = date("m/d/Y", strtotime($offsets[$this->recurring], $this->unixDate));
        $this->save();
    }

    protected function _processRecurring()
    {
	   	if($this->baseEvent == 0)
        {            
            //this is base event
            //we can create our clone events

            if($this->recurring == RECURRING_NOT)
            {
                //delete all events with this event as parent;
                $events = Event::model()->deleteAllByAttributes(array('baseEvent' => $this->id));
            }
            else
            {
                $recurreEventIds = array();
                $eventTime = $this->unixDate;

                $offsets = array(
                    RECURRING_WEEKLY => "+1 week",
                    RECURRING_BIWEEKLY => "+2 week",
                    RECURRING_MONTHLY => "+1 month",
                );

                //update all recurre events
                while(true)
                {
                    //get next event time
                    $eventTime = strtotime($offsets[$this->recurring], $eventTime);
                    if($eventTime > $this->lastDateUnix)
                    {
                        break;
                    }
                                        
                    //Yii::log($eventTime);
                    
                    //create event in this day                    
                    $event = $this->_copyEventToDay($eventTime);
                    
                    if($event->saveWithoutImages())
                    {
                        $recurreEventIds[] = $event->id;
                    }
                    else
                    {                        
                        throw new CException("Can't save event" . print_r($event->getErrors(),1));
                    }
                }

                //delete all not require events
                $childEvents = Event::model()->findAllByAttributes(array('baseEvent' => $this->id));

                foreach($childEvents as $event)
                {
                    if(!in_array($event->id, $recurreEventIds))
                    {
                        //an old event.
                        $event->delete();
                    }
                }

                
            }           
        }
    }

    /**
     * Copy and return base event for gived day
     *
     * @param string $date
     * @return Event
     */
    protected function _copyEventToDay($date)
    {
        //search child event for given date
        $event = Event::model()->with('artists', 'eventTicketTypes')->findByAttributes(array('date' => date('Y-m-d', $date), 'baseEvent' => $this->id));

        if(is_null($event))
        {
            $event = new Event();
            $event->date = date('Y-m-d', $date);
            $event->baseEvent = $this->id;
        }

        //copy properties from base event;
        foreach($this->getAttributes() as $k => $v)
        {
            if(in_array($k, array('id', 'baseEvent', 'date', 'recurring', 'lastDate', 'venue', 'complimentarySold', 'expressSold', 'generalSold', 'vipSold')))
            {
                continue;
            } 
            else if(in_array($k, array('image', 'flyer', 'bigFlyer', 'sectionImage', 'logo'))) 
            {
            	$im = $this->getImageOptions2($k);
            	if($im!==false){
            		$oldFilename = $im[1] . "{$event->$im[0]}";
            		//error_log("old filename $oldFilename");
            		if(is_file($oldFilename))
            		{
            			unlink($oldFilename);
            			Utils::deleteS3($im[2], $oldFilename);
            		}
            	}
            	$v = "";
            }
            
            	
            $event->{$k} = $v;
        }

        $artists = $this->eventArtistsNew;
        foreach($artists as $key=>$value)
        	$artists[$key]['id'] = "";
        //error_log("artists = ".print_r($this->artists,1));
        
        
        foreach($event->artists as $key=>$value){
        	$artist['id'] = $value->id;
        	$artist['deleted'] = 1;
        	$artists[] = $artist;
        }
        $event->eventArtistsNew = $artists;
        
        $eventTicketTypes = $this->eventTicketTypesNew;
        
        foreach($eventTicketTypes as $key=>$value)
        	$eventTicketTypes[$key]['id'] = "";
        //error_log("artists = ".print_r($this->artists,1));
        foreach($event->eventTicketTypes as $key=>$value){
        	$eventTicketType['id'] = $value->id;
        	$eventTicketType['deleted'] = 1;
        	$eventTicketTypes[] = $eventTicketType;
        }
        $event->eventTicketTypesNew = $eventTicketTypes;
        	
        return $event;
    }

    /*public function holdTickets($ticketType, $qtty)
    {
        if($qtty < 0) throw new Exception('Invalid parameter');        
        $this->_changeTicketsCount($ticketType, -$qtty);
    }*/

    public function addTickets($ticketType, $qtty)
    {
        if($qtty < 0) throw new Exception('Invalid parameter');
        $this->_changeSodlTicketsCount($ticketType, -$qtty);
    }

    public function isExpired()
    {
        //return date("Y-m-d",$this->unixDate);
        return  ((date("Y-m-d",$this->unixDate) == date("Y-m-d")) && (date("H") >= 21)) || ($this->unixDate <= strtotime('-1 day'));

    }

    protected function _changeSoldTicketsCount($ticketType, $delta)
    {
		$ticketPartFields = $this->ticketPartFields;
        if(!isset($ticketPartFields[$ticketType]))
        {
            throw new Exception('Invalid field name');
        }
        else
        {
            $field = $ticketPartFields[$ticketType]."Sold";
            $this->{$field} += $delta;            
            $this->save();
        }
    }


    protected function _toAmericanDate(array $fields)
    {
        foreach($fields as $field)
        {
            $date = CDateTimeParser::parse($this->{$field}, 'yyyy-MM-dd');
            if($date !== false)
            {
                $this->{$field} = date("m/d/Y", $date);
            }
        }
    }

    protected function _toDbDate(array $fields)
    {
        foreach($fields as $field)
        {
            $date = CDateTimeParser::parse($this->{$field}, 'MM/dd/yyyy');
            if($date !== false)
            {
                $this->{$field} = date("Y-m-d", $date);
            }
        }
    }

    public function getEventArrayList($user, $beginDate, $endDate)
    {
    	if($beginDate=="" || $endDate=="") return array();

    	$events1 = array();
	    
	    $c1 = new CDbCriteria();
	    $c1->join = " LEFT JOIN Venue AS v ON t.venueId=v.id ";
        $c1->order = "v.name ASC, t.title ASC"; // t1 is table Venue, but in other cases it can be another
	    //$c1->select = "Event.*, v.name AS venue";
	    $c1->addCondition("t.date>='$beginDate'");
	    $c1->addCondition("t.date<='$endDate'");
	    $c1->addCondition("v.deleted='0'");
	    
	    if($user->isVenueAdmin()){
	        $c1->join = $c1->join." LEFT JOIN User AS u ON t.venueId=u.venueId ";
	        $c1->addCondition("t.venueId='{$user->venueId}'");	
       	} 
       	else if($user->isPromoter())
       	{
	        $c1->join = $c1->join." LEFT JOIN PromoterVenue AS pv ON v.id=pv.venueId ";
	        $c1->addCondition("pv.promoterId='{$user->id}'");	       		
       	}
	    $events = Event::model()->with('venue')->findAll($c1);
	    $baseEvents = array();
	    foreach($events as $e){
	    	if($e->baseEvent)
	    	{
	    		if(in_array($e->baseEvent, $baseEvents)) continue;
	    		$baseEvents[] = $e->baseEvent;
	    	} 
	    	else if($e->recurring)
	    	{
	    		$baseEvents[] = $e->id;
	    	}
	    	
	    	$event = array();
    		$event['id'] = $e->id; //echo "id = {$e->id} $e->title<br/>";
	    	$event['name'] = $e->title."({$e->venue->name})";	    	
	      	$events1[] = $event;
	    }

	    return $events1;    	
    }  

   
    public static function getUniqueArtists($type = "")
    {
    	$typeStr = "";
    	$typeStr2 = "";
        if($type!=""){
    		$type = intval($type);
    		$typeStr = " AND ea.type='$type' ";
    		$typeStr2 = " AND sa.type='$type' ";
        }
        
        $query = "SELECT DISTINCT ea.name AS name 
        FROM EventArtist AS ea LEFT JOIN Event AS e ON e.id=ea.instanceId
        WHERE e.date>=CURDATE() AND e.deleted = '0' AND e.location='".$_SESSION['region']."' $typeStr
        UNION
		SELECT DISTINCT sa.name AS name
        FROM SpecialArtist AS sa LEFT JOIN Specials AS s ON s.id=sa.instanceId
        WHERE s.date>=CURDATE() AND (s.location='".$_SESSION['region']."' OR s.location='') $typeStr2         
        ORDER BY name ASC";

    	$pdo = self::model()->getDbConnection()->getPdoInstance();
        $pdos = $pdo->query($query);
        
        $artists = array();
        
        
        while(($artist = $pdos->fetchColumn()) !== false)
        {
            if(!is_null($artist) && trim($artist)!="")
            {
                $artists[] = $artist;
            }
        }
        
        return $artists;
    }
    
    public static function findEvents($options)
    {
        $options = (object)array_merge(array(
            'date' => '',
            'venue' => '',
            'genre' => '',
            'artist' => '',
            'limit' => 10,
            'page' => 1
        ), (array)$options);
        
        
        
        $pdo = self::model()->getDbConnection()->getPdoInstance();
        
        $where = array(1);
        
        if(strlen($options->genre) > 0){$where[] = 'e.genre = ' . (int)$options->genre;}
        if(strlen($options->artist) > 0){$where[] = 'ea.name = ' . $pdo->quote($options->artist);}
        if(strlen($options->venue) > 0){$where[] = 'v.id = ' . (int)$options->venue;}
        
        if(strlen($options->date) > 0){
            $where[] = 'e.date = ' . $pdo->quote(Utils::convertDateToMysql($options->date));
        }else{
            $where[] = 'e.date >= DATE(NOW())';
        }
        
        $sql = "SELECT DISTINCT
                    e.id eid, e.title, e.genre, 
                    v.id vid
                FROM Event e
                LEFT JOIN Venue v ON v.id = e.venueId
				LEFT JOIN EventArtist ea ON e.id=ea.eventId
                WHERE e.deleted = 0 AND " . implode(' AND ', $where) ." 
                ORDER BY e.date ASC, e.id
                LIMIT " . (int)($options->limit + 1) . "
                OFFSET " . $options->limit * ($options->page - 1);

        $pdos = $pdo->query($sql);

        $rows = array();
        
        while(($row = $pdos->fetchObject()) !== false)
        {
            $rows[] = $row;
        }
        
        return array('rows' => array_slice($rows, 0, $options->limit),
                     'hasNextPage' => (count($row) > $options->limit));
    }
    
    public static function countSearchResults($options)
    {
        $options = (object)array_merge(array(
            'date' => '',
            'venue' => '',
            'genre' => '',
            'artist' => '',
        ), (array)$options);
        
        $pdo = self::model()->getDbConnection()->getPdoInstance();
        
        $where = array(1);
        
        if(strlen($options->genre) > 0){$where[] = 'e.genre = ' . (int)$options->genre;}
        if(strlen($options->artist) > 0){$where[] = 'ea.name = ' . $pdo->quote($options->artist);}
        if(strlen($options->venue) > 0){$where[] = 'v.id = ' . (int)$options->venue;}
        
        if(strlen($options->date) > 0){
            $where[] = 'e.date = ' . $pdo->quote(Utils::convertDateToMysql($options->date));
        }else{
            $where[] = 'e.date >= DATE(NOW())';
        }
        
        $sql = "SELECT COUNT(DISTINCT e.id) AS count
                FROM Event e
                LEFT JOIN Venue v ON v.id = e.venueId
                LEFT JOIN EventArtist ea ON e.id=ea.eventId
                WHERE v.deleted = 0 AND e.deleted = 0 AND " . implode(' AND ', $where);

        $statetment = $pdo->query($sql);
        $rows = $statetment->fetchAll();
        $count = "";
        if(isset($rows[0]['count'])){
        	$count = $rows[0]['count'];
        }         
        if($count=="") $count = 0;
        return $count;

    } 

    public function getImageOptions(){
    	return array(
               array('image', UPLOADS . 'events/images/', 'events/images'),
               array('flyer', UPLOADS . 'events/flyers/', 'events/flyers'),
               array('bigFlyer', UPLOADS . 'events/bigFlyers/', 'events/bigFlyers'),
               array('sectionImage', UPLOADS . 'events/sectionImages/', 'events/sectionImages'),
               array('logo', UPLOADS . 'events/logo/', 'events/logo'),
        );    	
    }
    
    public function getImageOptions2($field){
    	$images = $this->getImageOptions();
    	foreach($images as $im){
    		if($im[0]==$field) return $im;
    	}
    	return false;
    }

    public function getVirtualTourUrl($partnerId = 0){
    	if($this->_cachedVirtualTourUrl==null){
			$link = !is_null($this->venue) ? $this->venue->virtualTourUrl : $this->virtualTourUrl;
			if(trim($link)){
				$link .= "&parameters=eventId={$this->id}";
                                if($partnerId){
                                    $link .= ",partnerId={$partnerId}";
                                }
			}
                        
			$this->_cachedVirtualTourUrl = $link;
    	}
    	return 	$this->_cachedVirtualTourUrl;
    }
    
    public function getPartnerVirtualTourUrl($partnerId = 0){
    	if($this->_cachedPartnerVirtualTourUrl==null){
    			$link = !is_null($this->venue) ? $this->venue->partnerVirtualTourUrl : $this->partnerVirtualTourUrl;
    			if(trim($link)){
    				$link .= "&parameters=eventId={$this->id}";
                                if($partnerId){
                                    $link .= ",partnerId={$partnerId}";
                                }
    			}
    			$this->_cachedPartnerVirtualTourUrl = $link;
    	}
    	return 	$this->_cachedPartnerVirtualTourUrl;
    }    
    
    //implement interface Searchable
	public function getUrl(){
		return "/event/{$this->id}";
	}
	
	public function getTitle(){
		return $this->title;
	}
	
	public function getTeaser(){
		return $this->date;
	}
	
	public function getContent(){
		$content = ZendModelSearch::getContentFromFields($this, "title, date, description, complimentaryPrice, expressPrice, generalPrice, vipPrice");
		if(!is_null($this->venue)) $content = $content." ".$this->venue->name;
		if(count($this->artists)){
			foreach($this->artists as $artist){
				$content = $content." ".$artist->name;
			}
		}
		$content .= " ".Utils::getLocationById($this->location);	
		$content .= " ".Utils::getLocationById($this->genre);
		//error_log("title = {$this->title} date {$this->date}");
		//error_log($content);
		return $content;
	}
	
	public function getExpiresDate(){
		return $this->date;
	}
	
	public function getType(){
		return 'Event';
	}	
	
	public function getSearchId(){
		return $this->id;
	}	
        
    public function getAllTicketsAmount(){
		$ticketTypes = $this->eventTicketTypes;
		$all = 0;
		foreach($ticketTypes as $t){
			$all += $t->amount;
		}
		return $all;		
	}
	
	public function getTicketsLeft(){
		$ticketTypes = $this->eventTicketTypes;
		$left = 0;
		foreach($ticketTypes as $t){
			$left += $t->amount - $t->sold;
		}
		return $left;
	}
    
	public function getWeeklyEventList(){
		$dateFormat = 'Y-m-d';
		$recurring = RECURRING_WEEKLY;
		$beginDate = date($dateFormat);
		$endDate = date($dateFormat, strtotime("+6 days"));		

		$data = array(
			'select' => "DATE_FORMAT(`t`.`date`, '%W') AS weekDay, t.*, v.name AS venueName, v.shortName AS venueShortName ",
			'join' =>		'LEFT JOIN Event AS be ON t.baseEvent = be.id 
							LEFT JOIN Venue AS v ON t.venueId = v.id',
			'order' => 'date ASC, title ASC, id ASC',
			'params' =>array('recurring'=>$recurring, 'beginDate'=>$beginDate, 'endDate'=>$endDate, 'location'=>$_SESSION['region']),
		);		
		
		$c = new CDbCriteria($data);
		$c->addCondition("t.location=:location AND t.date>=:beginDate AND t.date<=:endDate AND
			(be.recurring = :recurring OR t.recurring = :recurring) 
			AND t.deleted = 0 AND v.deleted = 0 AND v.isPartner <> 1 AND t.active = 1
			");
		
		$db = Yii::app()->db;
		$records0 = $db->getSchema()->getCommandBuilder()->createFindCommand("Event", $c)->queryAll();
		
		$records = array();
		foreach($records0 as $record){
			$record = (object) $record;
			$records[$record->weekDay][] = $record;
		}
                
		return $records;		
	}
        
    public function getTrendingEventList(){
                $c = new CDbCriteria; 

                $c = new CDbCriteria(array("order"=>"date ASC"));
                $c->addColumnCondition(array('trending'=>1, 'location'=>$_SESSION['region'], 'deleted'=>0));
                $c->addInCondition('active',array(Event::ACTIVE, Event::SITE_ONLY));
                $c->addCondition("date >= DATE(NOW())");
                
                $records = Event::model()->findAll($c);
                
		return $records;		
	}
	
	public function getBottles($cond = array(), $searchCond = ''){
		$c = new CDbCriteria();
		if(count($this->bottles)){
			$cond['t.eventId'] = $this->id;
		} else {
			$cond['t.venueId'] = $this->venueId;
		}
		$c = new CDbCriteria(array("order"=>"baseBottle.brand ASC"));
		$c->addColumnCondition($cond);
                if($searchCond != '') {
                    $c->addSearchCondition('baseBottle.brand', $searchCond);
                }
		$bottles = BottleVE::model()->with('baseBottle')->findAll($c);
		return $bottles;
	}
	
	public static function activityList(){
		return array(self::INACTIVE=>'Not active', self::ACTIVE=>'Active', self::SITE_ONLY=>'Site only', self::PARTNER_ONLY=>'Partner only');
	}
	
}