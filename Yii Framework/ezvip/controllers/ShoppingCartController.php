<?php

class ShoppingCartController extends CController
{
    public function actionIndex()
    {

    }

    public function actionAddItem()
    {
        echo 1;
    }

    public function actionAddBottles()
    {
        if(isset($_GET['id']) && isset($_GET['qtty']) && isset($_GET['event']))
        {
            $order = Order::getOrderForUID($_SESSION['uid'], (int)$_GET['event']);
            try
            {
                $qtty = (int)$_GET['qtty'];
                if($qtty) $order->setBottle((int)$_GET['id'], $qtty);

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

    public function actionAddTickets()
    {
        
    	if(isset($_GET['qtty']) && isset($_GET['event']) && isset($_GET['type']))
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

    public function actionAddTable()
    {        
        if(isset($_GET['sectionId']) && isset($_GET['event']))
        {
            $order = Order::getOrderForUID($_SESSION['uid'], (int)$_GET['event']);            
            $order->setTable((int)$_GET['sectionId']);
            echo $this->_ajaxShoppingCartInfo($order);
        }
        else
        {
            echo 'Invalid parameters';
        }
    }

    public function actionGetOrderInfo()
    {
        $order = Order::getOrderForUID($_SESSION['uid']);
        echo json_encode($value);
    }

    public function actionDeleteItem()
    {
        if(isset($_GET['id']))
        {
            $order = Order::getOrderForUID($_SESSION['uid']);
            $oi = OrderItem::model()->findByPk((int)$_GET['id']);
            if($oi->orderId == $order->id)
            {
                $oi->delete();
                $order->updatePrice();
            }
        }
        echo $this->_ajaxShoppingCartInfo($order);
    }

	public function actionSetPartySize()
	{
		$request = new HttpDbRequest();
		$gender = trim($request->getDbParam("gender", ""));
		$num = trim($request->getDbParam("num", ""));

		$order = Order::getOrderForUID($_SESSION['uid']);
		$order->setPartySizeGender($gender, $num);
		
		echo $this->_ajaxShoppingCartInfo($order);
		
	}	    
    
    /**
     * Clear current shopping cart
     */
    public function actionClear()
    {
        $order = Order::getOrderForUID($_SESSION['uid']);
        $order->deleteItems();
        echo $this->_ajaxShoppingCartInfo($order);
    }

//    public function actionTest()
//    {
//        unset($_SESSION['uid']);
//    }

    protected function _ajaxShoppingCartInfo($order)
    {
        $items = array();
        return $this->renderPartial('small', array('order' => $order), true);
    }

    public function actionApproveOrder()
    {
        $order = Order::getOrderForUID($_SESSION['uid']);
        $approve = $order->approve();
		//error_log("approve = $approve");
        if($approve === true)
        {            
            echo 1;
        }
        else
        {
            echo $approve;            
        }
    }

    public function actionAddInsurance()
    {
        $order = Order::getOrderForUID($_SESSION['uid']);
        $order->setInsurance();

        echo 1;
    }
    
    public function actionClearInsurance()
    {
        $order = Order::getOrderForUID($_SESSION['uid']);
        $order->clearInsurance();

        echo 1;
    }

    public function actionDeleteOrder()
    {
        $order = Order::getOrderForUID($_SESSION['uid']);
        $order->delete();
    }

}