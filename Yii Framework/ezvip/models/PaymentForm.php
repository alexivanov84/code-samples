<?php

require_once dirname(__FILE__) . '/../func/class.phpmailer.php';
require dirname(__FILE__) . '/../../Pdf/config/lang/eng.php';
require dirname(__FILE__) . '/../../Pdf/tcpdf.php';

class PaymentForm extends CFormModel
{
    //public $ccType = 0;
    public $ccNumber = '';
    public $ccExpDate = '';

    public $ccExpDateMonth = '';
    public $ccExpDateYear = '';
    
    public $dateOfBirthYear;
    public $dateOfBirthMonth = '';
    public $dateOfBirthDay = '';

    public $ccCVV = '';

    public $firstName = '';
    public $lastName = '';
    public $dateOfBirth = '';
    public $email = '';
    public $phone = '';
    public $address1 = '';
    public $address2 = '';
    public $city = '';
    public $state = '';
    public $country = '';
    public $zip = '';
    public $termsConfirm = 0;
    public $istest = 0;
    
    public $age21Confirm = 0;
    public $age21ConfirmCheck = false;
    protected $_controller = null;
    protected $pdf = null;

    public function setController(CController $controller){
    	$this->_controller = $controller;
    }
    
    
    public function rules()
    {
        $requiredFields = array(
            'ccNumber',
            'ccCVV',
            'firstName', 'lastName',
            'email', 'phone',
            'address1', 'city',
            'state', 'country',
            'zip'
        ); //'ccType', 

        return array(
            array(implode(',', $requiredFields), 'required'),
            array('email', 'email'),
            array('ccNumber, ccExpDateMonth, ccExpDateYear,
                      ccCVV, firstName, lastName, 
                      dateOfBirthYear, dateOfBirthMonth, dateOfBirthDay, 
                      email, phone, address1,
                      city, state, country, zip, termsConfirm, age21Confirm, istest', 'safe'), //ccType, address2,
         );
    }

    public function u($p)
    {
		return isset($this->$p) ? urlencode($this->$p) : '';
    }

    public function get($p)
    {
		return isset($this->$p) ? $this->$p : '';
    }

	public function setAttributesFromOrder(Order $order){
         //$payment->ccType = $order->ccType;
         $payment->ccNumber = $order->ccNumber;
         $payment->ccCVV = $order->ccCVV;
         $payment->firstName = $order->firstName;
         $payment->lastName = $order->lastName;
         $payment->email = $order->email;
         $payment->phone = $order->phone;
         $payment->address1 = $order->address1;
         //$payment->address2 = $order->address2;
         $payment->city = $order->city;
         $payment->country = $order->country;
         $payment->state = $order->state;
         $payment->zip = $order->zip;

         $payment->ccExpDateYear = date('Y', strtotime($order->ccExpDate));
         $payment->ccExpDateMonth = date('m', strtotime($order->ccExpDate));		
	}    
	
	public function setAttributesToOrder(Order $order){
            $order->firstName = $this->get('firstName');
            $order->lastName = $this->get('lastName');
            $order->dateOfBirth = $this->get('dateOfBirthYear') . '-' . sprintf("%02d", $this->get('dateOfBirthMonth'))  . '-' . sprintf("%02d", $this->get('dateOfBirthDay'));
            //error_log("date of birth $o->dateOfBirth ");
            $order->address1 = $this->get('address1');
            $order->address2 = $this->get('address2');
            $order->city = $this->get('city');
            $order->state = $this->get('state');
            $order->country = $this->get('country');
            $order->zip = $this->get('zip');
            $order->email = $this->get('email');
            $order->phone = $this->get('phone');

            $order->ccNumber = $this->get('ccNumber');
            $order->ccCVV = $this->get('ccCVV');
            //$o->ccType = $this->get('ccType');
            $order->ccExpDate = $this->get('ccExpDateYear') . '-' . $this->get('ccExpDateMonth') . '-01';
            //$o->paypalRequest = $dp->getRequestString();	
            $order->save();
	}
    
    public function doDirectPaymentReceipt(Order $order){
            $errorString = '';
            $this->setAttributesToOrder($order);
            $order->approveTable = $order->getTable();
            $order->blockTicketsTable();
            if($order->eventId){
                    $order->event->refresh();
            } else if($order->bottleSpecial){
                    $order->special->refresh();
            }
			//sleep(4);
            $approved = $order->approve();
            
            if($approved!==true){
            	$errorString = $approved;
            	$order->unblockTicketsTable();
            } else{
                $order->unblockTicketsTable();
            	$order->incrementSoldTickets();

                if($this->age21ConfirmCheck && !$this->age21Confirm == 1){
                        unset($_SESSION['age21Confirm']);
                	$errorString = "You can't order, because you is not over 21";
                } else {
                    $_SESSION['age21Confirm'] = $this->age21Confirm;
                }          	
            	//var_dump($errorString); exit;
                
                if($order->bottleSpecial) {
                    if($order->price == 0) {
                        $errorString = "Please type a valid amount";
                    }
                }
                
                if ($this->termsConfirm == 1 && $errorString=="")
                {
                        $_SESSION['termsConfirm'] = $this->termsConfirm;
                	if($this->validate() || $this->get('istest') == 1) {
                        $errorString = $this->_doAIManetPayment($order);
                    	//$this->_doDirectPaymentPayPal($payment, $order);
                    	if($errorString==""){
                			unset($_SESSION['errorString']);
                			return true;
                    	}
                    	
                    } else {
                        $_SESSION['payment'] = array($this->getAttributes());
                        foreach($this->getErrors() as $erar){
                            $errorString .= isset($erar[0]) ? ($erar[0] .'<br />') : '';
                        }
                    }
                }
                elseif($this->termsConfirm != 1)
                {
                    unset($_SESSION['termsConfirm']);
                    $errorString = "Please, read Terms & conditions and check checkbox";
                }
                
            	if($errorString!="") $order->decrementSoldTickets(); //if fail we must return reserved tickets
            }
            
            $_SESSION['errorString'] = $errorString;
            $order->note('Validation error: ' . $errorString); 
            return false;   	
    }
    
	protected function _doAIManetPayment(Order $o)
	{
            require_once PROT . 'func/anet_config.php';

            $errorString = "";
            
		    $transaction = new AuthorizeNetAIM;
		    $transaction->setSandbox(AUTHORIZENET_SANDBOX);
		    $transaction->setFields(
		        array(
		        'amount' => $o->getTotal(),
		        'card_num' => $this->get('ccNumber'),
		        'card_code' => $this->get('ccCVV'), 
		        'exp_date' => $this->get('ccExpDateMonth').'/'.$this->get('ccExpDateYear'),
		        'first_name' => $this->get('firstName'),
		        'last_name' => $this->get('lastName'),
		        'address' => $this->get('address1'),
		        'city' => $this->get('city'),
		        'state' => $this->get('state'),
		        'country' => $this->get('country'),
		        'zip' => $this->get('zip'),
		        'email' => $this->get('email'),
		        'phone' => $this->get('phone')		        
		        )
		    );
		                
            if($this->get('istest') == 0)
            {
                $o->save();
                $response = $transaction->authorizeAndCapture();                  
            }
            else
            {
                //create fake response
                $response = new stdClass;
                $response->approved = true;
                $response->transaction_id = 'testid';
            }
            
            if($response->approved)
            {
                
                $o->anetTransactionId = $response->transaction_id;
                $o->note = 'Success.';
                $o->billed = OrderState::BILLED;
                $o->save();
				//throw new Exception('test exception');
                /*if(isset($this->email) && strlen($this->email) > 0){
            		$headers = 'From: ezvip@ezvip.com' . "\r\n";
            		mail($this->email, 'EzVIP Reservation confirmation', 'You reserved table', $headers);
        		}*/                    
                
                $userEmail = urldecode($this->u('email'));
                $ezvipEmail = 'orders@ezvip.com';
                $venueEmail = isset($o->venue)?$o->venue->email: '';

                // subject
                //$subject = 'EzVip Order Confirmation';
                
                if($o->partnerId) {
                    $partner = $o->partner;
                }
                else {
                    $partner = '';
                }
                
                // message
                $message = $this->_getEmailBody($o, array(
                                                            'firstName' => urldecode($this->u('firstName')),
                                                            'lastName' => urldecode($this->u('lastName')),
                                                            'country' => urldecode($this->u('country')),
                                                            'city' => urldecode($this->u('city')),
                                                            'phone' => urldecode($this->u('phone')),
                                                            'number' => $this->u('ccNumber'),
                                                            'address' => urldecode($this->u('address1')),
                                                            'partner' => $partner
                 ));
                
                if($partner != '') {
                    $emailFromTitle = $partner->emailFromTitle;
                    $emailFrom = $partner->emailFrom;
                    $subject = ucfirst($partner->user->name).' Order Confirmation';
                    $terms = $partner->terms;
                    $to = $partner->emailTo;
                    $emails = explode(",", $to);
                    $emails[] = $userEmail;
                }
                else {
                    $emailFromTitle = 'EZVIP Service';
                    $emailFrom = 'ezvip@ezvip.com';
                    $subject = 'EzVip Order Confirmation';
                    $terms = Content::text('terms');
                    $emails = array('orders@ezvip.com', 'al@ezvip.com', 'alex@aws3.com', $venueEmail, $userEmail);
                }
                
                $terms = $this->_getTermsAndCondition($o, $terms);
                if(!empty($emails)) {
                    foreach($emails as $email){
                        if($email != '') {
                            $this->_sendEmail($o, $email, $subject, $message['customer'], $terms, $emailFromTitle, $emailFrom);
                        }
                    }
                }
                
            }
            else
            {	
                $o->note("response_reason_code: {$response->response_reason_code}<br/>\n
                response_code: {$response->response_code}<br/><br/>\n
                {$response->response_reason_text}");
            	
                $errorString = $response->response_reason_text;
            }
            return $errorString;
	}  

	/**
	 *
	 * $ccParams = array(
	 *	'firstName' => '',
	 *	'lastName' => '',
	 *	'number' => '',
	 *	'address' => ''
	 * );
	 *
	 * @param Order $order
	 * @param array $ccParams
	 * @return string
	 */
	protected function _getEmailBody(Order $order, array $ccParams)
	{
            $controller = $this->_controller;
            if(is_null($controller)){
            	error_log("_getEmailBody() controller can't be null");
            	return "";
            }
			if(!$order->bottleSpecial)
            {
                $body['customer'] = $controller->renderPartial('/venues/orderemail', array('order' => $order, 'cc' => $ccParams), true);       
                //$body['venue'] = $controller->renderPartial('/venues/orderemail', array('order' => $order, 'cc' => $ccParams), true);
                //$body['bottle'] = $controller->renderPartial('/venues/orderemail', array('order' => $order, 'cc' => $ccParams), true);
            }
            else
            {
                $body['customer'] = $controller->renderPartial('/venues/orderemailBS', array('order' => $order, 'cc' => $ccParams), true);
            }
            return $body;
	}
        
        protected function _getTermsAndCondition(Order $order, $terms = '')
	{
            $controller = $this->_controller;
            if(is_null($controller)){
            	error_log("_getEmailBody() controller can't be null");
            	return "";
            }
            
            $body = '';
            
            if(!$order->bottleSpecial)
            {
                $body = $controller->renderPartial('/venues/terms', array('order' => $order, 'terms' => $terms), true);     
            }
            
            return $body;
	}
        
        protected function _sendEmail($o, $email, $subject, $message, $terms = '', $emailFromTitle = 'EZVIP Service', $emailFrom = 'ezvip@ezvip.com')
        {
            $mail = new PHPMailer();

            $mail->SetFrom($emailFrom, $emailFromTitle);

            $mail->AddAddress($email);
            	            
            $mail->Subject = $subject;

            if($this->pdf == null) {
                $pdf = $this->convertHtmlToPdf($o, $message, $terms);
                $this->pdf = $pdf;
            }
            else {
                $pdf = $this->pdf;
            }

            $mail->AddAttachment($_SERVER['DOCUMENT_ROOT']. '/' . $pdf);
            $mail->MsgHTML($message);
            $mail->CharSet = 'UTF-8';

            $mail->Send();
        }
    
        protected function convertHtmlToPdf(Order $order, $body = '', $terms = '')
        {
            global $l;
        	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
            //$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            //$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            //$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(0, 11, 0);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(FALSE, 0);
            $pdf->setImageScale(1);
            $pdf->setLanguageArray($l);
            $pdf->setFontSubsetting(false);
            $pdf->SetFont('dejavusans', '', 8, '', true);
            $pdf->AddPage();

            
           // Set some content to print
$html = <<<EOD
   $body
EOD;

    
$html1 = <<<EOD
   $terms
EOD;
   


        $output_file = "images/order/order_".$order->id.".pdf";
        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='L', $autopadding=false);
        
        if($terms != '') {
            $pdf->AddPage();
            $pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html1, $border=0, $ln=1, $fill=0, $reseth=true, $align='L', $autopadding=false);
        }
        $pdf->Output($output_file, "F"); //F for saving output to file
        
        return $output_file;
            
        }
        
}