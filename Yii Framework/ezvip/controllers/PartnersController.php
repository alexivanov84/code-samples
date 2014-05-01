<?php

class PartnersController extends CController
{

    public $layout = 'partners';
    protected $_venue = null;
    public $id = null;

    const ITEMS_PER_PAGE = 10;

    public function actionIndex()
    {
        $data = array();
        $c = new CDbCriteria();
        
        if(isset($_GET['partner']) && is_numeric($_GET['partner'])) 
        {
            $partner =Partner::model()->findByPk($_GET['partner']);
            if(count($partner)) {
                $this->_venue = $partner->user->venueId;
                //if($this->_venue)
                //{
                    $c = new CDbCriteria();
					if($this->_venue) {
						$c->addCondition("venueId = {$this->_venue}");
					}
                    $c->addCondition("deleted = 0");
                    $c->addInCondition('active',array(Event::ACTIVE, Event::PARTNER_ONLY));
                    $c->addCondition("date >= DATE(NOW())");
					$c->addColumnCondition(array('location'=>'miami'));
					$c->group='date';
					$c->order='date ASC';

                    $allEventsCount = Event::model()->count($c);
                    $pagination = $this->_getPaginationInfo($allEventsCount, isset($_GET['page']) ? $_GET['page'] : 1);

                    $c->limit = self::ITEMS_PER_PAGE;
                    $c->offset = $pagination->begin;

                    $data['partner_id'] = $_GET['partner'];
                    $data['events'] = Event::model()->findAll($c);
                    $data['pagination'] = $pagination;
                    $data['partner'] = $partner;
                    $this->render('index', $data);
                //}
            }
        }
    }
    
    protected function _getPaginationInfo($allItemsCount, $page, $itemsPerPage = self::ITEMS_PER_PAGE)
    {
        $o = new stdClass();
        $o->currentPage = $page;
        $o->begin = ($o->currentPage - 1) * $itemsPerPage;

        $o->fullPages = (int) ($allItemsCount / $itemsPerPage);
        $halfPages = $allItemsCount % $itemsPerPage;
        if ($halfPages > 0)
        {
            $o->fullPages++;
        }

        if ($o->fullPages < $o->currentPage)
        {
            $o->currentPage = 1;
            $o->begin = 0;
        }
        return $o;
    }
    
    public function actionTerms(){
    	$req = Yii::app()->getRequest();
    	
    	$partnerId = $req->getQuery('partner');
    	$partner = Partner::model()->findByPk($partnerId);

    	$data = array('terms'=>$partner->terms);
    	$this->render('terms', $data);
    	
    }

}
