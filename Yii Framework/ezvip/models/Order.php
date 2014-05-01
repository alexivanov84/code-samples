<?php

class Order extends CActiveRecord
{
    protected $_clearPrice = null;
    protected $_priceWithoutDiscont = 0;

    protected $_bottlesPrice = null;
    public $discount = 0.00;
    
    public $approveTable = null;

    /**
     *
     * @param string $className
     * @return Order
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return array(
                array('email', 'email'),
        );

    }

    public function relations()
    {
        return array(
                'items' => array(self::HAS_MANY, 'OrderItem', 'orderId'),
                'venue' => array(self::BELONGS_TO, 'Venue', 'venueId'),
                'event' => array(self::BELONGS_TO, 'Event', 'eventId'),
        		'promoter'=>array(self::BELONGS_TO, 'Promoter', 'promoterId'),
        		'special'=>array(self::BELONGS_TO, 'Specials', 'bottleSpecial'),
                'partner'=>array(self::BELONGS_TO, 'Partner', 'partnerId'),
        );
    }
    
    public function updatePrice()
    {
        //$this->calcComplimentaryTickets();
        $conn = $this->getDbConnection();
        
        $command = $conn->createCommand("UPDATE `Order`
                                         SET price = (SELECT SUM( qtty * price )
                                                      FROM `OrderItem`
                                                      WHERE orderId = {$this->id} AND type!='".ORDER_ITEM_INSURANCE."')
                                         WHERE id = {$this->id}");

        $command->execute();
        $this->refresh();//update all fields in the object

        $this->_clearPrice = $this->price;
        $this->_bottlesPrice = null;
        $this->getBottlesPrice();

            //error_log("bottlesPrice = $this->_bottlesPrice itemCount = ".count($this->items));
        $this->setTaxes();
        $this->setGratuities();
        $this->setSurcharges();
        $this->setDiscount();
        
        $this->price += $this->getTaxes() + $this->getGratuities() + $this->getSurcharges() + $this->getInsuranceSum();
        $this->_priceWithoutDiscont = $this->price;
        $this->price -= $this->getDiscount();

        if($this->price < 0){
            $this->price = 0;
        }

        $this->update();
    }
    
    public function getClearPrice(){
        if($this->_clearPrice===null){
	    	$this->_clearPrice = 0;
	        foreach($this->items as $item)
	        {
                    if($item->type != ORDER_ITEM_INSURANCE){
                        $this->_clearPrice += $item->price * $item->qtty;
                    }
	        }
        }
        return $this->_clearPrice;      	
    	/*if($this->_clearPrice==null){
    		$query = "SELECT SUM( qtty * price ) AS `sum` FROM `OrderItem`
                          WHERE orderId = {$this->id}";
    		$command = $conn->createCommand($query);
    		$result = $command->queryScalar();
    		if(!result) $result = 0;
    		$this->_clearPrice = $result;
    	}
    	return $this->_clearPrice;*/
    }
    
    public function checkEmpty() {
        if(count($this->items)) {
            foreach($this->items as $item) {
                $item->delete();
            }
        }
        
        return;
    }

    public function getBottlesCount()
    {
        $counter = 0;
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_BOTTLE)
            {
                $counter += $item->qtty;
            }
        }

        return $counter;
    }
    
    public function getBottles()
    {
        $result = array();
        $i = 0;
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_BOTTLE)
            {
                $result[$i]['qtty'] = $item->qtty;
                $result[$i]['id'] = $item->reference;
                $bottle = BottleVE::model()->findByPk($item->reference);
                $result[$i]['brand'] = $bottle->baseBottle->brand;
                $i++;
            }
        }
        
        return $result;
    }
    
    public function getEvents()
    {
        $result = array();
        $i = 0;
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_TICKET)
            {
                $result[$item->reference] = $item->qtty;
            }
        }
        
        return $result;
    }
    
    public function getBottlesQtty()
    {
        $result = array();
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_BOTTLE)
            {
                $result[$item->reference] = $item->qtty;
            }
        }

        return $result;
    }
    

    public function getNumComplTickets(){
    	//error_log(isset($this->venue). " AND ".isset($this->event)." bottlePrice = {$this->_bottlesPrice} ");
    	if(isset($this->venue) && isset($this->event)){
    		if($this->event->complTicketsFromVenue){
    			$complTicketsForBtlSum = $this->venue->complTicketsForBtlSum;
    			$btlSum = $this->venue->btlSum;
    		} else {
    			$complTicketsForBtlSum = $this->event->complTicketsForBtlSum;
    			$btlSum = $this->event->btlSum;    			
    		}
			
    		if($btlSum!=0){
    			return floor($this->getBottlesPrice()/$btlSum) * $complTicketsForBtlSum;
    		}
    		return 0;
    		
    	}
    	return 0;
    }
    
    public function getBottlesPrice(){
        if($this->_bottlesPrice===null){
	    	$this->_bottlesPrice = 0;
	        foreach($this->items as $item)
	        {
	            if($item->type == ORDER_ITEM_BOTTLE)
	            {
	                $this->_bottlesPrice += $item->price * $item->qtty;
	            }
	        }
        }
        return $this->_bottlesPrice;    	
    }
    
    public function getBottlesDescription()
    {
        $descriptions = array();
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_BOTTLE)
            {
                $bottle = BottleVE::model()->findByPk($item->reference);
                $descriptions[] = $item->qtty . " " . $bottle->baseBottle->brand;
            }
        }

        $description = implode(', ', $descriptions);

        return $description;
    }

    /**
     * Test contains this order bottle or no
     * @return boolean
     */
    public function hasBottle()
    {
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_BOTTLE)
            {
                return true;
            }
        }

        return false;
    }


    public function getAllBottlesPrice()
    {
        $price = 0;

        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_BOTTLE)
            {
                $price += $item->price * $item->qtty;
            }
        }

        return $price;
    }
    
    public function getAllTicketsPrice()
    {
        $price = 0;

        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_TICKET)
            {
                $price += $item->price * $item->qtty;
            }
        }

        return $price;
    }

    public function getInsuranceSum(){
        $price = 0;

        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_INSURANCE)
            {
                return $item->price * $item->qtty;
            }
        }

        return 0;    	
    }
    

    public function hasComplimentaryTickets()
    {
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_TICKET && $item->reference == TICKET_COMPLIMENTARY)
            {
                return true;
            }
        }
        return false;
    }


    public function hasInsurance()
    {
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_INSURANCE)
            {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @return Section
     */
    public function getTable()
    {
        foreach($this->items as $item)
        {
        	if($item->type == ORDER_ITEM_SECTION)
            {
                if($item->reference != BEST_AVAILABLE)
                {
                    return EventSection::model()->getSectionBy($this->eventId, $item->reference);
                	//return Section::model()->findByPk($item->reference);
                }
                else
                {
                    return Section::getBestAvailable($item->eventId, $item->venueId);
                }
            }
        }

        return null;
    }

    /**
     * Test order
     * 1. minBottlePrice for tables
     * 2. Заказан ли столик вообще?
     *
     * @return boolean
     */
    public function approve()
    {
        if($this->bottleSpecial){
        	return $this->approveSpecialOrder();
            return true;
        } else {
			return $this->approveEventOrder();
        }
    }
    
    
    // MOD - calculate minimal cost of table
    public function getCalculatedTableMinimalCost() {
        $tableMinimum = 0;
        
        if( $this->approveTable==null ){
            $table = $this->getTable();
        } else {
            $table = $this->approveTable;
        }
        
        if(!is_null($table) && isset($table->minPricesBottles))
        {
            $tableMinimum = max($table->minPricesBottles, $this->partySizeGuys*$table->guyPrice + $this->partySizeGirls*$table->girlPrice); 
            
            return $tableMinimum;
        }  
        
        return $tableMinimum;        
    }
    // MOD end
    
    
    
    public function approveEventOrder(){
        if($this->approveTable==null){
    		$table = $this->getTable();
        } else {
        	$table = $this->approveTable;
        }
		
        /*if(!is_null($this->venue)){
	        $maxComplTickets = $this->getNumComplTickets();//$this->venue->complTicketsForBottle * $this->getBottlesCount();
            $partySize = $this->partySizeGuys + $this->partySizeGirls;        
	        if($partySize> $maxComplTickets)
	        {
	        	return "The party size (guys + girls = {$partySize}) is greater than amount of complimentary tickets($maxComplTickets)";
	        }
        }*/
        
        if(!is_null($table) && isset($table->minPricesBottles))
        {
            $tableMinimum = max($table->minPricesBottles, $this->partySizeGuys*$table->guyPrice + $this->partySizeGirls*$table->girlPrice); 
        	if($tableMinimum > $this->getAllBottlesPrice())
            {
                return "The minimum order requirement for this table is $" . $tableMinimum;
            }
        }  

    	if($this->event->isExpired())
	    {
	    	return "It's too late to buy tickets for this event";
	    }  
	    
	foreach($this->items as $item)
        {
            
            /*$ticketPartFields = $this->event->ticketPartFields;
            $ticketTitles = $this->event->ticketTitles;
        	if($item->type == ORDER_ITEM_TICKET){
        	$ticketType = $item->reference;
            	$partField = $ticketPartFields[$ticketType];
            	$fieldAmount = "{$partField}Amount";
            	$fieldSold = "{$partField}Sold";
            	$ticketsLeft = $this->event->$fieldAmount - $this->event->$fieldSold;
            	if($ticketsLeft<=0){
            		return "There are no {$ticketTitles[$ticketType]} tickets";
            	}
        	    if($ticketsLeft<$item->qtty){
            		return "Only {$ticketsLeft} {$ticketTitles[$ticketType]} left";
            	}
            }*/
//            $eventTicketType = EventTicketType::model()->findByPk($item->reference);
            if($item->type == ORDER_ITEM_TICKET) {
                $eventTicketType = $item->eventTicketType;
                $amount = $eventTicketType->amount;
                $sold = $eventTicketType->sold;
                $ticketsLeft = $amount - $sold;
                if($ticketsLeft<=0){
                        return "There are no {$eventTicketType->ticketType->title} tickets";
                }

                if($ticketsLeft<$item->qtty){
                        return "Only {$ticketsLeft} {$eventTicketType->ticketType->title} left";
                }
            }
            
        }    

	    return true;
        
    }
    
    public function approveSpecialOrder(){
        $special = $this->special;
        
        if($special->isExpired()){
                return "It's too late to buy tickets for this special";
        }
        
        foreach($this->items as $item)
        {
            if($item->type==ORDER_ITEM_TICKET){
	        	$specialtickettype = $item->specialtickettype;
	            $amount = $specialtickettype->amount;
	            $sold = $specialtickettype->sold;
	            $ticketsLeft = $amount - $sold;
	            if($ticketsLeft<=0){
	                    return "There are no {$specialtickettype->ticketType->title} tickets";
	            }
	            
	            if($ticketsLeft<$item->qtty){
	                    return "Only {$ticketsLeft} {$specialtickettype->ticketType->title} left";
	            }
			}    
        }    
//        if($special->isExpired()){
//        	return "It's too late to buy tickets for this special";
//        } else if(($special->num_tickets - $special->num_sold_tickets)<=0){
//        	return "Sold out";
//        }
        
        return true;
        
    }

    public function decrementSoldTickets(){
    	if($this->bottleSpecial){
    		$this->addSoldSpecialTickets(-1);
    	} else {
    		$this->addSoldEventTickets(-1); 
    	}
    }
    
    public function incrementSoldTickets(){
        if($this->bottleSpecial){
                $this->addSoldSpecialTickets(1);
    	} else {
                $this->addSoldEventTickets(1);
    	}   	
    }
    
    public function addSoldSpecialTickets($koef){
        foreach($this->items as $item) {
            if($item->type == ORDER_ITEM_TICKET)
            {
                $num = $koef*$item->qtty; 
                $query = "UPDATE SpecialTicketType SET sold = sold + ($num) WHERE id={$item->reference}";
                $this->getDbConnection()->createCommand($query)->execute();         
            }
        }

    }
    
    public function blockTicketsTable(){
    	if($this->bottleSpecial){
    		$query = "LOCK TABLES Specials AS special WRITE, Specials AS t WRITE, Specials WRITE, `SpecialTicketType` WRITE, `Order` WRITE;";
    		$this->getDbConnection()->createCommand($query)->execute();
//                $query = "LOCK TABLES SpecialTicketType AS specialtickettype WRITE, Specials AS t WRITE, Specials WRITE, `Order` WRITE;";
//    		$this->getDbConnection()->createCommand($query)->execute();
    	} else {
    		$query = "LOCK TABLES Event AS e WRITE, Event AS event WRITE, Event AS t WRITE, `EventTicketType` WRITE, `Order` WRITE;";
    		$this->getDbConnection()->createCommand($query)->execute();    		
    	}	
    }
    
    public function unblockTicketsTable(){
    	//if($this->bottleSpecial){ error_log("unblock");
    	$query = "UNLOCK TABLES;";
    	$this->getDbConnection()->createCommand($query)->execute();
    	//}	    	
    }
    
    //there -1 is koef for adding or substracting some tickets
    public function addSoldEventTickets($koef){
    	$koef = Utils::sign($koef);
        
        foreach($this->items as $item) {
            if($item->type == ORDER_ITEM_TICKET)
            {
                $num = $koef*$item->qtty; 
                $query = "UPDATE EventTicketType SET sold = sold + ($num) WHERE id={$item->reference}";
                $this->getDbConnection()->createCommand($query)->execute();         
            }
        }
  	
    }
    
    /**
     *
     * @param PaymentForm $pf
     */
    public function setPaymentData(PaymentForm $pf)
    {
        $this->ccType = $pf->ccType;
        $this->ccNumber = $pf->ccNumber;
        $this->ccExpDate = $pf->ccExpDate;
        $this->ccCVV = $pf->ccCVV;
        $this->firstName = $pf->firstName;
        $this->lastName = $pf->lastName;
        $this->email = $pf->email;
        $this->phone = $pf->phone;
        $this->address1 = $pf->address1;
        $this->address2 = $pf->address2;
        $this->city = $pf->city;
        $this->state = $pf->state;
        $this->country = $pf->country;
        $this->zip = $pf->zip;

        $this->update();
    }


    /**
     *
     * @param ReservationForm $rf
     */
    public function setReservationData(ReservationForm $rf)
    {
        $this->firstName = $rf->firstName;
        $this->lastName = $rf->lastName;
        $this->email = $rf->email;
        $this->girls = $rf->girls;
        $this->guys = $rf->guys; 

        $this->update();

    }

    public function delete()
    {
        if($this->billed) return;

        foreach($this->items as $oi)
        {
            $oi->delete();
        }

        parent::delete();
    }

    public function execute($billedState)
    {
        /*foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_TICKET)
            {
                $item->holdTickets();
            }
        }*/

        if(isset($this->email) && strlen($this->email) > 0)
        {
            $headers = 'From: ezvip@ezvip.com' . "\r\n";
            mail($this->email, 'EzVIP Reservation confirmation', 'You reserved table', $headers);
        }

        $this->billed = $billedState;
        $this->update();
    }


    public function getTotal()
    {
//        return '0.3';
        return sprintf("%01.2f", $this->price);
    }
    
    /**
     * Add or update special tickets for this order
     */
    public function setSpecialTickets()
    {
        $specialtickettype = SpecialTicketType::model()->findByPk($_POST['id']);
        $orderItem = OrderItem::get($this->id, ORDER_ITEM_TICKET, $_POST['id']);
        
        if($_POST['qty'] == 0) {
            $orderItem->delete();
        } else {
            $orderItem->qtty = $_POST['qty'];
            $orderItem->price = $specialtickettype->price;
            $orderItem->eventId = 0;
            $orderItem->venueId = 0;
            $orderItem->save();   
        
        }
        
        $orderItemsCount = Yii::app()->db->createCommand()
                                    ->select('SUM(qtty*price) AS ct')
                                    ->from('OrderItem')
                                    ->where('orderId='.$this->id)
                                    ->queryRow();
        
        if($orderItemsCount['ct']) {
            return $orderItemsCount['ct'];
        }
                                              
        return false;
    }
    

    /**
     * Add or update tickets for this order
     */
    public function setTickets($qtty, $type)
    {
        /*$ticksts = array(
                TICKET_EXPRESS => 'expressPrice',
                TICKET_GENERAL => 'generalPrice',
                TICKET_VIP => 'vipPrice',
                TICKET_COMPLIMENTARY => 'complimentaryPrice');*/

        /*if(!key_exists($type, $ticksts))
        {
            throw new Exception('Invalid type of ticket: ' . $type);
        }*/
        $eventTicketType = EventTicketType::model()->findByPk($type);
        $event = Event::model()->findByPk((int)$this->eventId);
        $orderItem = OrderItem::get($this->id, ORDER_ITEM_TICKET, $type);


        if($qtty == 0 )
        {
            if(!$orderItem->getIsNewRecord())
            {
                $orderItem->delete();
            }
        }
        else
        {
            $orderItem->qtty = $qtty;
//            $orderItem->price = $event->{$ticksts[$type]};
            $orderItem->price = $eventTicketType->price;
            $orderItem->eventId = $this->eventId;
            $orderItem->venueId = $this->venueId;
            $orderItem->save();
        }

        //$this->update();
//        if($type !== TICKET_COMPLIMENTARY)
//        {
            $this->updatePrice();
//        }

        return true;
    }
    
    /**
     * Add or update partySize for this order
     */
    public function setPartySize($qtty)
    {
        $orderItem = OrderItem::get($this->id, ORDER_ITEM_PARTYSIZE);

        if($qtty == 0 )
        {
            if(!$orderItem->getIsNewRecord())
            {
                $orderItem->delete();
            }
        }
        else
        {
            $orderItem->qtty = $qtty;
            $orderItem->price = 0;
           	$orderItem->save();
        }
        
        return true;
    }    

    /**
     * Set talbe for order
     *
     * if sectionId == 0 then we add best available table
     *
     * @param int $eventId
     * @param int $sectionId
     */
    public function setTable($sectionId = 0)
    {
        $orderItem = OrderItem::get($this->id, ORDER_ITEM_SECTION);

        $orderItem->qtty = 1;
        $orderItem->price = 0;
        $orderItem->reference = $sectionId;
        $orderItem->eventId = $this->eventId;
        $orderItem->venueId = $this->venueId;

        $orderItem->save();

        return true;
    }

    /**
     * Set bottle type and quantity in the order
     *
     * @param int $bottleId
     * @param int $qtty
     */
    public function setBottle($bottleId, $qtty)
    {
        $bottle = BottleVE::model()->findByPk($bottleId);
        if(is_null($bottle))
        {
            throw new Exception("Can't find bottle " . $bottleId);
        }
        $orderItem = OrderItem::get($this->id, ORDER_ITEM_BOTTLE, $bottleId);
        
        if($qtty === 0)
        {
            $orderItem->delete();
        }
        else
        {
            $orderItem->qtty = $qtty;
            $orderItem->price = $bottle->price;
            $orderItem->eventId = $this->eventId;
            $orderItem->venueId = $this->venueId;
            $orderItem->save();
        }

        $this->updatePrice();

        return true;
    }
    
    public function setPartySizeGender($gender, $num)
    {
		if($gender=="guy")
		{
			$this->partySizeGuys = intval($num);
		}
		else if($gender=="girl")
		{
			$this->partySizeGirls = intval($num);
		} 
		$this->save();
	   	//$this->calcComplimentaryTickets();
	   	$this->refresh();
	   	
    }

    public function setInsurance()
    {
    	$item = OrderItem::get($this->id, ORDER_ITEM_INSURANCE);
    	$item->price = 10;
        $item->qtty = 1;
        $item->eventId = $this->eventId;
        $item->venueId = $this->venueId;
        $item->save();
        $this->updatePrice();
    }

    public function clearInsurance()
    {
    	$item = OrderItem::get($this->id, ORDER_ITEM_INSURANCE);
        $item->delete();
        $this->updatePrice();
    }

    /**
     * Return order for user id
     * @param string $uid
     * @return Order
     */
    public static function getOrderForUID($uid, $eventId = null, $bottleSpecial = 0, $partnerId = 0)
    {
        //if ($eventId == null) { order точно не создается, а просто берется из базы }


        $order = self::model()->with('event', 'items', 'event', 'promoter', 'venue')->findByAttributes(array('userId' => $uid, 'billed' => OrderState::DEF));

        if(!is_null($order) && (!is_null($eventId) ||  $bottleSpecial!=0))
        {
            //if we found nobilled order with
            //other eventId we should delete it and create new
            if($order->eventId != $eventId || $order->bottleSpecial != $bottleSpecial)
            {
                //error_log("deleted order {$order->eventId} {$eventId} {$order->bottleSpecial} || {$bottleSpecial}");
            	$order->delete();
                $order = null;
            }
        }

        if(is_null($order))
        {
            //each order should be assigned to event & venue NEW we can sell special bottle without event
            if(is_null($eventId) && $bottleSpecial==0)
            {
                throw new Exception("Can't create order");
            }
            $order = new Order();
            $order->date = date("Y-m-d H:i:s");
            $order->userId = $uid;
//            $order->bottleSpecial = $bottleSpecial;
            if($eventId) {
                $order->_fillVenueByEvent($eventId);
            }
            else $order->bottleSpecial = $bottleSpecial;
            
            if($partnerId) {
            	$order->partnerId = $partnerId;
            }           
            
            $order->save();
        }

        return $order;
    }
    
    public static function getLastBilledOrderForUID($uid){
    	$condition = new CDbCriteria(array('order'=>'`t`.id DESC'));
    	$order = self::model()->with('event', 'items', 'event', 'promoter', 'venue')->findByAttributes(array('userId' => $uid, 'billed' => OrderState::BILLED), $condition);
    	return $order;
    }

    /**
     *
     * @param string $uid
     * @param int $bottleSpecial
     * @return Order
     */
    public static function getSpecialOrderForUID($uid, $bottleSpecial = null)
    {
        return self::getOrderForUID($uid, null, $bottleSpecial);
    }
    
    /**
     *
     * @param string $uid
     * @param int $partnerId
     * @return Order
     */
    public static function getPartnerOrderForUID($uid, $eventId, $partnerId = null)
    {
        return self::getOrderForUID($uid, $eventId, 0, $partnerId);
    }
    
    /**
     *
     * @param string $uid
     * @param int $partnerId
     * @return Order
     */
    public static function getPartner2OrderForUID($uid, $eventId = null, $bottleSpecial = null, $partnerId = null)
    {
        return self::getOrderForUID($uid, $eventId, $bottleSpecial, $partnerId);
    }

    public static function getLastBilledOrder($uid)
    {
        $order = self::model()->findByAttributes(array('userId' => $uid, 'billed' => OrderState::BILLED),
                array('order' => 'date DESC'));

        return $order;
    }

    public static function getLastReservedOrder($uid)
    {
        $order = self::model()->findByAttributes(array('userId' => $uid, 'billed' => OrderState::RESERVED),
                array('order' => 'date DESC'));

        return $order;
    }

    public static function getOrderByHash($hash)
    {
        $order = self::model()->findByAttributes(array('hash' => $hash), array('limit' => 1));
        return $order;
    }


    protected function _fillVenueByEvent($eventId)
    {
        $this->eventId = $eventId;
        if(isset($this->event->venue)) 
        	$this->venueId = $this->event->venue->id;
    }

    public function setGratuities()
    {
        $this->gratuities = $this->calcFeeByName('gratuity');
    }
    
    public function getGratuities($format = false)
    {
        $g = $this->gratuities;
    	return $format ? sprintf("%01.2f", $g) : $g;
    }    

    public function setSurcharges()
    {
        $this->surcharges = $this->calcFeeByName('serviceCharge');
    }
    
    public function getSurcharges($format = false)
    {
		$s = $this->surcharges;
        return $format ? sprintf("%01.2f", $s) : $s;
    }    

    public function setTaxes()
    {
		$this->taxes = $this->calcFeeByName('salesTax');
    }
    
    public function getTaxes($format = false)
    {
		$t = $this->taxes;
        return $format ? sprintf("%01.2f", $t) : $t;
    }    

    public function setDiscount()
    { //there we can't use calcFeeByName, because there are no discount for specials
        if($this->venueId)
        	$g = $this->calcFee($this->venue->discount, $this->venue->discountType);
        else
        	$g = 0; 
    	
        $this->discount = $g;
    }
    
    public function getDiscount($format = false)
    {
        $g = $this->discount;
    	return $format ? sprintf("%01.2f", $g) : $g;
    }    
    
    public function getTaxAndTips($setTrue = false)
    {
        if($setTrue) {
            return $this->getTaxes(true) + $this->getGratuities(true) + $this->getSurcharges(true) + $this->getInsuranceSum();
        } else {
            return $this->getTaxes() + $this->getGratuities() + $this->getSurcharges() - $this->getDiscount() + $this->getInsuranceSum();
        }
    }    

    public function calcFee($value, $type){
    	$sum = $this->getClearPrice();
    	$tax = $value * (($type == '%') ? ($sum * 0.01) : Utils::gt0($sum));
    	return $tax;
    }
    
    public function calcFeeByName($name){
        $type = "{$name}Type";
    	if($this->venueId)
        {
	    	$g = $this->calcFee($this->venue->$name, $this->venue->$type);
        }
        else if($this->bottleSpecial)
        {
        	$g = $this->calcFee($this->special->$name, $this->special->$type);
        }
        else
        {
        	$g = 0;
        }  
        return $g;     	
    }    
    
    public function getTicketsNumber($ticketType)
    {
        foreach($this->items as $item)
        {
            if($item->type == ORDER_ITEM_TICKET && $item->reference == $ticketType)
            {
                return $item->qtty;
            }
        }
    }

    /**
     * Delete all items from order
     */
    public function deleteItems()
    {
        foreach($this->items as $item)
        {
            $item->delete();
        }

        $this->price = 0;
        $this->save();
        $this->refresh();
    }

    protected function _createHash()
    {
        if(empty($this->hash))
        {
            $this->hash = $better_token = md5(uniqid(rand(), true));
        }
    }

    public function beforeSave()
    {
        $this->_createHash();
        return parent::beforeSave();
    }

    public function isBottleSpecial()
    {
        return $this->bottleSpecial > 0;
    }
    
    public function note($text)
    {
        $this->note = $text;
        $this->save();
    }    
    
}