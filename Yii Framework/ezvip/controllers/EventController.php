<?php


class EventController  extends SimpleCacheController//extends CController
{
    
	protected $cacheDuration = 120;
	protected $onlyCacheActions = "eventCalendar";
	
    public function actionIndex()
    {
        $this->redirect('/searchevent');
    }
    
    public function actionGet()
    {	
        $request = Yii::app()->getRequest();
        $partner_id = $request->getParam('partner_id');
        $partner_ae_id = Partners::checkPartner2();
        $data = array();
        $data['showPrices'] = true;
        
        if(!isset($_GET['id']) || !is_numeric($_GET['id']))
        {
                $this->redirect('/');
        }
        else
        {
                $eventId = (int) $_GET['id'];
        }

        $event = Event::model()->findByPk($eventId);
        
        if($event==null || $event->isExpired() || $event->deleted)
        {
            $this->redirect('/');
        }
        
        if (!is_null($event))
        {
                if(isset($event->venue)) $data['venue'] = $event->venue;
                $data['event'] = $event;
        }
        else
        {
                $this->redirect('/');
        }
        
        /*$csVenues = array(6, 46, 47, 48, 49, 50, 54, 62, 63); //SET(6), Louis(46),Mansion(47), Cameo(48), DREAM(49), PLAY(50), Mokai(54), LIV(62), Arkadia(63) //Amnesia(61)
        if(in_array($event->venueId, $csVenues) || $event->id==9503){
        	$this->render('buynowCrowdsurge', $data);
        	return;
        }*/
        
        if(isset($partner_id) && is_numeric($partner_id)) 
        {
            $order = Order::getPartnerOrderForUID($_SESSION['uid'], $eventId, $partner_id);
        }
        elseif($partner_ae_id){
            $order = Order::getPartner2OrderForUID($_SESSION['uid'], $eventId, null, $partner_ae_id);
        } 
        else { 
            $order = Order::getOrderForUID($_SESSION['uid'], $eventId);
        }
        
        //$order->checkEmpty();
        $order->updatePrice();
        $this->_leftOnlyTickets($order);
        $items = $order->getEvents();
        
        
        $eventTicketTypes = EventTicketType::model()->findAllByAttributes(array('eventId'=>$eventId));
        
        $data['eventTicketTypes'] = $eventTicketTypes;
        $data['order'] = $order;
        $data['items'] = $items;
        //$data['smallShoppingCart'] = $this->renderPartial('smallOrderInfo', array('order' => $order), true);

        if(isset($partner_id) && is_numeric($partner_id)) {
            $this->layout = 'partners';
            $data['partner_id'] = $partner_id;
            $data['partner'] =Partner::model()->findByPk($partner_id);
            $this->render('partnerEvent', $data);
            
        } elseif($partner_ae_id) {
            $this->layout = 'partners';
            $data['partner_ae_id'] = $partner_ae_id;
            $this->render('partnerEvent'.$partner_ae_id, $data);
        }
        else {
            $this->render('buynow2', $data);
        }
        
    }
    
    public function actionEventCalendar(){
    	$request = Yii::app()->getRequest();

    	$venueId = $request->getQuery('id', '');
    	$period = $this->parsePeriod($request->getQuery('period', date('Y-m')));
    	
    	if($venueId==''){
    		$this->redirect('/searchevent');
    		return;
    	}
    	if(empty($period)){
	   		$this->redirect("/eventCalendar/$id");
    		return;
    	}
    	
    	//$minDate = date('Y-m-d'); AND date>='$minDate'
    	$c = new CDbCriteria(array("limit"=>31, "order"=>"`t`.date"));
    	$c->addCondition("`t`.date>=:beginDate AND `t`.date<=:endDate  AND `t`.venueId=:venueId AND `t`.deleted='0'");    	
    	$params = array(":beginDate"=>$period['beginDate'], ":endDate"=>$period['endDate'], ":venueId"=>$venueId);
    	$c->params = array_merge($c->params, $params);

    	$records0 = Event::model()->with('base')->findAll($c);
		$records = array();
    	foreach($records0 as $record){
     		$records[Utils::convertDateToMysql($record->date)] = $record;
    	}
		//error_log(print_r($records, 1));
    	$calendarData = Utils::getMonthCalendarData($period['month'], $period['year']);
    	
    	$days = array();
    	foreach($calendarData as $day){
    		if(isset($day['date']) && isset($records[$day['date']])){
    			$day['event'] = $records[$day['date']];
    		}
    		$days[] = $day;
    	}

    	$data['venueId'] = $venueId;
    	$data['venue'] = Venue::model()->findByPk($venueId);
    	$data['days'] = $days;
    	$data['period'] = $period;
    	
    	$this->render('eventCalendar', $data);
    	
    }
    
    public function actionEventCalendar2(){
    	$request = Yii::app()->getRequest();
    	
    	$venueId = $request->getQuery('venueId', '');
    	$year = $request->getQuery('year', '');
    	$month = $request->getQuery('month', '');
    	
        if($venueId=="" || $year=="" || $month==""){
    		$this->redirect('/searchevent');
    		return;
    	}
    	$url = "/eventCalendar/$venueId/{$year}-{$month}";
    	$this->redirect($url);

    }
    
    public function parsePeriod($period){
    	$arr = explode("-", $period);
    	
    	if(count($arr)!=2){
    		return array();
    	}
    	
    	$year = $arr[0];
    	$month = $arr[1];
    	$firstDay = "01";
    	//$firstDay = date('d'); //error_log("$year $month");
    	$endDay = cal_days_in_month (CAL_GREGORIAN, $month, $year);

    	return array('beginDate'=>"$year-$month-$firstDay", 'endDate'=>"$year-$month-$endDay", "year"=>intval($year), "month"=>intval($month));
    	
    }
    
    public function actionAddTickets()
    {
        
    	if(isset($_POST['qtty']) && isset($_POST['event']) && isset($_POST['type']))
        {
            $order = Order::getOrderForUID($_SESSION['uid'], (int)$_GET['event']);
            try
            {
                $order->setTickets((int)$_GET['qtty'], (int)$_GET['type']);
            }
            catch(Exception $e)
            {
                echo $e->getMessage();
                return;
            }
            echo $this->_ajaxShoppingCartInfo($order);
        }
        else
        {
            echo 'Invalid parameters';
        }
    }
    
    public function actionEventTicket(){
        $eventId = $_POST['eventId'];
        $type = $_POST['id'];
        $qtty = $_POST['qtty'];
                
        if(is_numeric($eventId)) {
            $order = Order::getOrderForUID($_SESSION['uid'], $eventId);
            $this->_leftOnlyTickets($order);
            $order->setTickets($qtty, $type);
            echo $order->getTotal();
        }

        die();

    }
    
	protected function _leftOnlyTickets($order){
			error_log("_leftOnlyTickets");
	        $findFlag = 0;
	        foreach($order->items as $item){
	        	if($item->type!=ORDER_ITEM_TICKET){
	        		$findFlag = 1;
	        		$item->delete();
	        	}
	        }
	        if($findFlag) $order->updatePrice();    			
	}    
    
    
}