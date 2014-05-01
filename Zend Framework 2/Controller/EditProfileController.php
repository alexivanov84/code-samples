<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\XmlRpc\Value\String;

use Zend\Mvc\Application;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Form\Form;
use Zend\Form\Element;
use Application\Form\EditUsernameForm;
use Application\Form\EditEmailForm;
use Application\Form\EditPasswordForm;
use Application\Form\EditProfileForm;
use Application\Form\EditSocialForm;
use Application\Form\EditAvatarForm;
use Application\Form\EditCoverForm;
use Application\Form\EditPrivacyForm;
use Application\Form\EditNotificationsForm;
use Zend\Session\Container;
use Auth\Model\User;
use Application\Model\Page;
use Application\Model\Message;


class EditProfileController extends AbstractActionController
{
	
    public function indexAction()
    {
    	$user = new User();
    	$page = new Page();
    	$messages = new Message();
   	
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$messages = $this->switchPost($request);
    	}
    	
    	$plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
    	
	   	$user_session = new Container('user');

    	if ($plugin->isLoggedIn()) {
		   	if(!$user_session->user->active) {
		   		$this->redirect()->toRoute('editprofile/default', array('action'=>'activate-account'));
		   	}    		

    		$user = $user_session->user;
   	
    		$page->states = $plugin->getStates();

    		return array(
    					'user' => $user,
    					'page' => $page,
    					'messages' => $messages
    				);
    	} else {
    		$this->redirect()->toRoute('login');
    	}
    }
    
    /*
    private function switchPost($request) {
    	$form_data = $request->getPost();
    	switch ($form_data->action) {
    		case 'edit_username' : $messages['edit_username'] =  $this->editUsername($form_data); break;
    		case 'edit_email' : $messages['edit_email'] =  $this->editEmail($form_data); break;
    		case 'edit_password' : $messages['edit_password'] =  $this->editPassword($form_data); break;
    		case 'edit_profile' : $messages['edit_profile'] =  $this->editProfile($form_data); break;
    		case 'edit_privacy' : $messages['edit_privacy'] =  $this->editPrivacy($form_data); break;
    		case 'edit_notifications' : $messages['edit_notifications'] =  $this->editNotifications($form_data); break;
    	}	
    	return $messages;
    }
    */
    
    public function editUsernameAction() {
    	$status=null;
    	$values=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditUsernameForm();
    	$request = $this->getRequest();
    	if ($request->isPost()) {
	    	$form_data = $request->getPost();
	    	$user = $this->getServiceLocator()->get('Auth/Model/User');
	    	$user->id = $user_session->user->id;
	    	$form->setInputFilter($user->getInputFilter());
	    	$form->setData($form_data);
	    	 
	    	$fields = array('username');
	    	$form->setValidationGroup($fields);
	    	 
	    	if ($form->isValid()) {
	    		$user->exchangeArray($form->getData());
	    		$plugin->getUserTable()->editUser($user->id, $form->getData());
	    		$user_session->user->username = $user->username;
	    		$messages->success[]="Username updated!";
	    		//$actions=array('update-object-text'=>array('#user-url'=>$plugin->getUserProfileUrl($user->username)));
	    		$values = array('user-url'=>$plugin->getUserProfileUrl($user->username));
	    		$status="success";
	    	} else {
	    		//$this->user->edit_login->messages[]="Form error!";
	    		$status="error";
	    		$actions="";
	    	}
	    	
	    	$form_messages = $form->getMessages();
	    	foreach ($form_messages as $message) {
	    		foreach ($message as $key=>$msg) {
	    			$messages->warning[]=$msg;
	    		}
	    	}	    	
			
	    	$result = new JsonModel(array(
	    			'messages'=>$messages,
	    			'values'=>$values,
	    			'status'=>$status
	    	));

			return $result;
    	}
    }
        
    public function editEmailAction() {
    	$status=null;
    	$actions=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditEmailForm();
    	$request = $this->getRequest();
    	
    	if ($request->isPost()) {
    		$form_data = $request->getPost();
	    	$user = $this->getServiceLocator()->get('Auth/Model/User');
	    	$user->id = $user_session->user->id;
	    	$form->setInputFilter($user->getInputFilter());
	    	$form->setData($form_data);
	    	
	    	$fields = array('email', 'email2');
	    	$form->setValidationGroup($fields);
	    	
	    	if ($form->isValid()) {
	    		$user_updates = $user->updateArray($form->getData());
	    		
	    		$plugin->getUserTable()->editUser($user_session->user->id, $user_updates);
	    		$user_session->user->email = $user_updates['email'];
	    		$messages->success[]="Email updated!";
	    	} else {
	    		//$this->user->edit_login->messages[]="Form error!";
	    	}
    	}	
    	    		 
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	
    	$result = new JsonModel(array(
    			'messages'=>$messages,
    			'actions'=>$actions,
    			'status'=>$status
    	));
    	
    	return $result;

    }
    
    public function editPasswordAction() {
    	$status=null;
    	$actions=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditPasswordForm();
    	$request = $this->getRequest();
    	
    	if ($request->isPost()) {
    		$form_data = $request->getPost();
	    	$user = $this->getServiceLocator()->get('Auth/Model/User');
	    	$user->id = $user_session->id;
	    	$form->setInputFilter($user->getInputFilter());
	    	$form->setData($form_data);
	    	 
	    	$fields = array('password', 'password2');
	    	$form->setValidationGroup($fields);
	    	 
	    	if ($form->isValid()) {
	    		$salt = $plugin->getUserTable()->dig_salt();
	    		$user_updates['password'] = $plugin->getUserTable()->saltify($form_data['password'], $salt);
	    		$user_updates['salt'] = $salt;
	    		$plugin->getUserTable()->editUser($user->id, $user_updates);
	    		$user_session->user->password=$user_updates['password'];
	    		$user_session->user->salt=$user_updates['salt'];
	    		$messages->success[]="Password updated!";
	    	} else {
	    		//$this->user->edit_password->messages[]="Form error!";
	    	}
    	}
    	
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	
    	$result = new JsonModel(array(
    			'messages'=>$messages,
    			'actions'=>$actions,
    			'status'=>$status
    	));
    	
    	return $result;
    }

    public function editPersonalAction() {
    	$status=null;
    	$actions=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditProfileForm();
    	$states = $plugin->getStates();
    	$form->get('state_id')->setOptions(array('value_options'=>$states))->setEmptyOption(null);
    	$user = $this->getServiceLocator()->get('Auth/Model/User');
	    $user->id = $user_session->id;    	
    	$form->setInputFilter($user->getInputFilter());
    	$request = $this->getRequest();
    	if ($request->isPost()) {

    		$form_data = $request->getPost();
    		$form->setData($form_data);
    		if ($user_session->user_type=='user') {
    			$fields = array('first_name', 'last_name', 'about');
    		} else {
	    		$fields = array('first_name', 'last_name', 'about', 'city', 'zip', 'state_id');
    		}
	    	$form->setValidationGroup($fields);
	    	if ($form->isValid()) {
	    		$user->exchangeArray($form->getData());
	    		$user_updates = $user->updateArray($form->getData());
	    		if (empty($user_updates['state_id'])) {
	    			$user_updates['state_id'] = null;
	    		}	    		
	    		$plugin->getUserTable()->editUser($user->id, $user_updates);
	    		$user_session->user->first_name = $user->first_name;
	    		$user_session->user->last_name = $user->last_name;
	    		$user_session->user->about = $user->about;
	    		$user_session->user->city = $user->city;
	    		$user_session->user->zip = $user->zip;
	    		$user_session->user->state_id = $user->state_id;

	    		$messages->success[]="Personal information updated!";
	    	} else {
	    		//$this->user->edit_login->messages[]="Form error!";
	    	}
    	}
    	
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	
    	$result = new JsonModel(array(
    			'messages'=>$messages,
    			'actions'=>$actions,
    			'status'=>$status
    	));
    	
    	return $result;
    }
    
    public function editSocialAction() {
    	$status=null;
    	$actions=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditSocialForm();
    	//$form->get('website_url')->setMessages(array(\Zend\Validator\Uri::NOT_URI=>'test'));
    	$user = $this->getServiceLocator()->get('Auth/Model/User');
    	$user->id = $user_session->id;
    	$form->setInputFilter($user->getInputFilter());
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    
    		$form_data = $request->getPost();
    		$form->setData($form_data);
    		$fields = array('website_url', 'facebook_url', 'twitter_url', 'google_url', 'blog_url', 'other_url_01', 'other_url_02', 'instagram_url');
    		$form->setValidationGroup($fields);
    		if ($form->isValid()) {
    			$user->exchangeArray($form->getData());
    			$user_updates = $user->updateArray($form->getData());
    			$plugin->getUserTable()->editUser($user->id, $user_updates);
    			$user_session->user->website_url = $user->website_url;
    			$user_session->user->facebook_url = $user->facebook_url;
    			$user_session->user->twitter_url = $user->twitter_url;
    			$user_session->user->google_url = $user->google_url;
    			$user_session->user->blog_url = $user->blog_url;
    			$user_session->user->other_url_01 = $user->other_url_01;
    			$user_session->user->other_url_02 = $user->other_url_02;
    			$user_session->user->instagram_url = $user->instagram_url;
    			$messages->success[]="Social information updated!";
    		} else {
    			//$this->user->edit_login->messages[]="Form error!";
    		}
    	}
    	 
    	 
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	 
    	$result = new JsonModel(array(
    			'messages'=>$messages,
    			'actions'=>$actions,
    			'status'=>$status
    	));
    	 
    	return $result;
    }
    
    public function editAvatarAction() {
    	$status=null;
    	$values=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditAvatarForm();
    	$user = $this->getServiceLocator()->get('Auth/Model/User');
    	$user->id = $user_session->id;
    	$form->setInputFilter($user->getInputFilter());
    	$file    = $this->params()->fromFiles('avatar');
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$form_data = $request->getFiles()->toArray();
    		$form->setData($form_data);
    		$fields = array('avatar');
	    	$form->setValidationGroup($fields);
	    	 
	    	if ($form->isValid()) {
	    		$user->exchangeArray($form->getData());

	    		if (!empty($user->avatar['name'])) {
	    			$plugin->saveAvatar($user->id, $user->avatar);
	    		}
	    		unset($user->avatar);
	    		$messages->success[]="Profile picture updated!";
	    		$status="success";
	    		$user_session->user->avatar = $plugin->getUserAvatar($user->id, 'm').'?'.time();
	    		$values=array('avatar'=>$user_session->user->avatar);
	    	} else {
	    		//$this->user->edit_login->messages[]="Form error!";
	    		$status="error";
	    	}
    	}
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	
    	$result = new JsonModel(array(
    			'messages'=>$messages,
    			'values'=>$values,
    			'status'=>$status
    	));
    	
    	return $result;
    }
    
    public function editCoverAction() {
    	$status=null;
    	$values=null;
    	$messages=null;
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditCoverForm();
    	$user = $this->getServiceLocator()->get('Auth/Model/User');
    	$user->id = $user_session->id;
    	$form->setInputFilter($user->getInputFilter());
    	$file    = $this->params()->fromFiles('cover');
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$form_data = $request->getFiles()->toArray();
    		$form->setData($form_data);
    		$fields = array('cover');
    		$form->setValidationGroup($fields);
    		 
    		if ($form->isValid()) {
    			$user->exchangeArray($form->getData());
    
    			if (!empty($user->cover['name'])) {
    				$plugin->saveCover($user->id, $user->cover);
    			}
    			unset($user->cover);
    			$messages->success[]="Header picture updated!";
    			$status="success";
    			$user_session->user->cover = $plugin->getUserCover($user->id).'?'.time();
    			$values=array('cover'=>$user_session->user->cover);
    		} else {
    			//$this->user->edit_login->messages[]="Form error!";
    			$status="error";
    		}
    	}
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	 
    	$result = new JsonModel(array(
    			'messages'=>$messages,
    			'values'=>$values,
    			'status'=>$status
    	));
    	 
    	return $result;
    }
    
    public function editPrivacy($form_data) {
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditPrivacyForm();
    	 
    	$user = $this->getServiceLocator()->get('Auth/Model/User');
    	$form->setInputFilter($user->getInputFilter());
    	$form->setData($form_data);
    	 
    	$fields = array('allow_public_view', 'allow_public_share');
    	$form->setValidationGroup($fields);
    	 
    	if ($form->isValid()) {
    		$user->exchangeArray($form->getData());
    		$user->id = $user_session->id;
    		//var_dump($user);
    		$plugin->getUserTable()->editUserPrivacy($user->id, $user);
    		$user_session->allow_public_view = $user->allow_public_view;
    		$user_session->allow_public_share = $user->allow_public_share;
    		$messages->success[]="Privacy settings updated!";
    	} else {
    		//$this->user->edit_login->messages[]="Form error!";
    	}
    	 
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	return $messages;
    }
    
    public function editNotifications($form_data) {
    	$plugin = $this->HelperPlugin();
    	$messages = new Message();
    	$user_session = new Container('user');
    	$form = new EditNotificationsForm();
    
    	$user = $this->getServiceLocator()->get('Auth/Model/User');
    	$form->setInputFilter($user->getInputFilter());
    	$form->setData($form_data);
    
    	$fields = array('ntf_follows', 'ntf_comments', 'ntf_likes');
    	$form->setValidationGroup($fields);
    
    	if ($form->isValid()) {
    		$user->exchangeArray($form->getData());
    		$user->id = $user_session->id;
    		//var_dump($user);
    		$plugin->getUserTable()->editUserNotifications($user->id, $user);
    		$user_session->ntf_follows = $user->ntf_follows;
    		$user_session->ntf_comments = $user->ntf_comments;
    		$user_session->ntf_likes = $user->ntf_likes;
    		$messages->success[]="Notification settings updated!";
    	} else {
    		//$this->user->edit_login->messages[]="Form error!";
    	}
    
    	$form_messages = $form->getMessages();
    	foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
    			$messages->warning[]=$msg;
    		}
    	}
    	return $messages;
    }
    
    
    public function deactivateAccountAction() {
    	$plugin = $this->HelperPlugin();
    	$user_session = new Container('user');
    	$messages = new Message();
    	$actions = Array();
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$form_data = $request->getPost();
    		$filter = new \Zend\Filter\StripTags();
    		$deactivation_message = $filter->filter($form_data['deactivation_message']);
    		
    		if ($plugin->isLoggedIn()) {
    			$user=$user_session->user;
    			$user_updates = array('active'=>null);
    			if($plugin->getUserTable()->editUser($user->id, $user_updates)) {
    				$status='success';
    				$messages->success[]='Your account was deactivated';	
    				$user_session->user->active = null;
    				$actions['redirect']=$user_session->last_url;
    				
    				$config = $this->getServiceLocator()->get('Config');
					$mail = new \Zend\Mail\Message();
					$mail->setBody(
						'User '.$user->username.' ('.$user->email.') deactivated his/her account.'."\n".
						'Message from the user:'."\n".$deactivation_message
					);
					$mail->setFrom($config['email']['noreply']);
					$mail->addTo($config['email']['admin']);
					$mail->setSubject('User '.$user->username.' deactivated his/her account.');
					$transport = new \Zend\Mail\Transport\Sendmail();
					$transport->send($mail);
					$plugin->sendDeactivationEmail($user_session->user);
    			} else {
    				$status='error';
    				$messages->error[]='Your account was not deactivated! Please contact the system admin.';
    			}
    		}
    		$result = new JsonModel(array(
    				'messages'=>$messages,
    				'status'=>$status,
    				'actions'=>$actions,
    		));
    		 
    		return $result;
				   		
    	} else {
	    	if($plugin->isAjaxRequest()) {
	    	
	    		$ViewModel = new ViewModel();
	    		$ViewModel->setTerminal(true)
	    		->setTemplate('application/edit-profile/deactivate-account');
	    		$htmlOutput = $this->getServiceLocator()
	    		->get('viewrenderer')
	    		->render($ViewModel);
	    		$params['html'] = $htmlOutput;
	    		$result = new JsonModel($params);
	    		
	    		return $result;
	    	}
    	}
    }
    
    public function activateAccountAction() {
    	$plugin = $this->HelperPlugin();
    	$user_session = new Container('user');
    	$page = new Page();
    	$messages= new Message();
    	$actions = Array();
    	
    	//var_dump($user_session->last_url);
    	
    	$status = '';
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		 
    		$form_data = $request->getPost();
    		if ($plugin->isLoggedIn()) {
    			$user=$user_session->user;
    			$user_updates = array('active'=>1);
    			if($plugin->getUserTable()->editUser($user->id, $user_updates)) {
    				$status='success';
    				$messages->success[]='Your account was activated';
    				$user_session->user->active = 1;
    				$actions['redirect'] = $user_session->last_url;
    				$plugin->removeFromDeactivatedList($user_session->user);
    				if(!$plugin->isAjaxRequest()) {
    					return $this->redirect()->toUrl($user_session->last_url);
    				}
    				
    				$config = $this->getServiceLocator()->get('Config');
    				$mail = new \Zend\Mail\Message();
    				$mail->setBody(
    						'User '.$user->username.' ('.$user->email.') reactivated his/her account.'."\n"
    				);
    				$mail->setFrom($config['email']['noreply']);
    				$mail->addTo($config['email']['admin']);
    				$mail->setSubject('User '.$user->username.' reactivated his/her account.');
    				$transport = new \Zend\Mail\Transport\Sendmail();
    				$transport->send($mail);
    				
    			} else {
    				$status='error';
    				$messages->error[]='Your account can\'t be activated! Please contact the system admin.';
    			}
    		}
    	}     
    			
  		$result = array(
    			'user'=>$user_session->user,
  				'page'=>$page,
    			'messages'=>$messages,
    			'status'=>$status
    	);  
    	  		
    	if($plugin->isAjaxRequest()) {
    		
    		$ViewModel = new ViewModel();
    		$ViewModel->setTerminal(true)
    		->setTemplate('application/edit-profile/activate-account');
    		$htmlOutput = $this->getServiceLocator()
    		->get('viewrenderer')
    		->render($ViewModel);
    		$result=array(
    			'html'=>$htmlOutput,
    			'messages'=>$messages,
    			'actions'=>$actions,
    			'status'=>$status,
    		);
    		
    		$result = new JsonModel($result);
    		
    	} 
    	
    	return $result;
    }
    
    public function makeStylistAction() {
    	$plugin = $this->HelperPlugin();
    	$user_session = new Container('user');
    	$page = new Page();
    	$messages= new Message();
    	$actions = Array();
    	$status = null;
    	$request = $this->getRequest();
    	if ($request->isPost()) {
    		$form_data = $request->getPost();
    		if ($plugin->isLoggedIn()) {
    			$user=$user_session->user;
    			$user_updates = array('user_type_id'=>3);
    			if($plugin->getUserTable()->editUser($user->id, $user_updates)) {
    				$status='success';
    				$messages->success[]='Your account was converted to STYLIST';
    				$user_session->user->user_type_id = 3;
    				$user_session->user->user_type = "stylist";
    				$actions['redirect'] = $user_session->last_url;
    				if(!$plugin->isAjaxRequest()) {
    					return $this->redirect()->toUrl($user_session->last_url);
    				}
    			} else {
    				$status='error';
    				$messages->error[]='Your account can\'t be turned into SYLIST.';
    			}
    		}
    	}
    	 
    	$result = array(
    			'user'=>$user_session->user,
    			'page'=>$page,
    			'messages'=>$messages,
    			'status'=>$status
    	);
    		
    	if($plugin->isAjaxRequest()) {
    
    		$ViewModel = new ViewModel();
    		$ViewModel->setTerminal(true)
    		->setTemplate('application/edit-profile/make-stylist');
    		$htmlOutput = $this->getServiceLocator()
    		->get('viewrenderer')
    		->render($ViewModel);
    		$result=array(
    				'html'=>$htmlOutput,
    				'messages'=>$messages,
    				'actions'=>$actions,
    				'status'=>$status,
    		);
    
    		$result = new JsonModel($result);
    
    	}
    	 
    	return $result;
    }
    
    
}