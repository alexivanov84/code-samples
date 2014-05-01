<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use Auth\Model\User;
use Application\Model\Page;


class UserController extends AbstractActionController
{
	
    public function indexAction()
    {
    	$plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
    	
    	$user = new User();
    	
    	$user_session = new Container('user');
    	
    	$page = new Page();
    	
    	if ($plugin->isLoggedIn()) {
	    	$user = $user_session->user;
    	}
		
    	$user_subject = new User();
    	
    	$user_param = $this->getEvent()->getRouteMatch()->getParam('userid');
    	
    	try {
    		$user_subject = $plugin->getUserTable()->getUser($user_param);
    	} catch (\Exception $e) {
    		//throw new \Exception($e); 
    	}
    	
    	
    	if (!isset($user_subject->id)) {
    		try {
    			$user_subject = $plugin->getUserTable()->getUserByUsername($user_param);
    		} catch (\Exception $e) {
    			
    		}
    	}
    	
    	if ($plugin->isUserViewable($user_subject, $user_session->user)) {
    		$user_subject->cover = $plugin->getUserCover($user_subject->id);
    		$user_subject->avatar = $plugin->getUserAvatar($user_subject->id, 'm');
    		
    		if ($plugin->isLoggedIn()) {
            	$user->followed_ids = $plugin->getUserFollowTable()->getFollowedIdsByUser($user_session->user->id);
    		}
                
    		$seasons = $plugin->getStylebookTable()->getUserStylebookSeasons($user_subject->id)->toArray();
    		
    		$stylebooks = $plugin->getUserStylebooksBySeason($user_subject->id, $seasons);
    		
   		 	
	   		return array(
    					'user' => $user,
    					'user_subject' => $user_subject,
    					'stylebooks' => $stylebooks,
	   					'seasons'	=>	$seasons,
	   					'page'	=> $page,
    				);
    		
    	} else {
    		$this->getResponse()->setStatusCode(404);
    		$viewModel = new ViewModel(array(
    				'user' => $user,
    				'page'	=> $page,
    		));
    	   	return $viewModel->setTemplate('error/404.phtml');
    	}
    	
    }
    
    
    public function MyStylesAction()
    {
    	$plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
    	 
    	$user = new User();
    	
    	$user_session = new Container('user');
    	
    	if(!$user_session->user->active) {
    		$this->redirect()->toRoute('editprofile/default', array('action'=>'activate-account'));
    	}
    	
    
    	if ($plugin->isLoggedIn()) {
    		
    		$user = $user_session->user;
    		
    		$seasons = $plugin->getStylebookTable()->getUserStylebookSeasons($user->id)->toArray();
    		
    		$stylebooks = $plugin->getUserStylebooksBySeason($user->id, $seasons);
    		
    
    		return array('user' => $user,
    				'stylebooks' => $stylebooks,
    				'seasons'	=>	$seasons,
    				);
    
    	} else {
    		$this->redirect()->toRoute('login');
    	}
    	 
    }
    
    public function followAction()
    {
        $userid = (int) $this->params()->fromRoute('userid', 0);
        $type = $this->params()->fromRoute('type', 'follow');
        
    	$plugin = $this->HelperPlugin();

    	$user_session = new Container('user');
    	
        
        if (!$plugin->isLoggedIn()) {
    		$result = new JsonModel(array(
                        'actions'=>array('redirect' => $this->url()->fromRoute('login')),
	    	));
                return $result;
    	}
    	
    	try {
            $dbAdapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
            $dbAdapter->getDriver()->getConnection()->beginTransaction();

            $data = array('status' => true, 'messages'=>array(), 'actions'=>array('mode' => 'follow'), 'id' => $userid);

            switch($type){
                case "unfollow":
                    if($id = $plugin->getUserFollowTable()->checkUserFollowed($userid, $user_session->user->id)) {
                        // Delete row from  user_follow table matched by user and follower.
                        $plugin->getUserFollowTable()->deleteUserFollow($id);
                        if(($key = array_search($userid, $user_session->user->followed_ids)) !== false) {
                        	unset($user_session->user->followed_ids[$key]);
                        }
                        $data['values']['ajax-src'] = $this->url()->fromRoute('user/follow', array('userid'=>$userid));
                        $data['values']['html'] = 'Follow';
                    } else {
                        throw new \Exception("You must follow this user in order to do this action.");
                    }
                    break;
                case "follow":
                default:
                    if(!$plugin->getUserFollowTable()->checkUserFollowed($userid, $user_session->user->id)) {
                        // Add new row in user_follow table.
                        $userFollow = new \Auth\Model\UserFollow();
                        $userFollow->followed_id = $userid;
                        $userFollow->follower_id = $user_session->user->id;
                        $plugin->getUserFollowTable()->addUserFollow($userFollow);
                        $user_session->user->followed_ids[]=$userid;
                        $data['values']['ajax-src'] = $this->url()->fromRoute('user/follow', array('userid'=>$userid, 'type'=>'unfollow'));
                        $data['values']['html'] = 'Following';
                    } else {
                        throw new \Exception("You are following this user already.");
                    }
                    break;
            }

            $dbAdapter->getDriver()->getConnection()->commit();

        } catch(Exception $e){
            $dbAdapter->getDriver()->getConnection()->rollback();

            $message = $e->getMessage();
            $data['messages']['warning'][] = $message;
        }
        
        $result = new JsonModel($data);
                
        return $result;
    	 
    }
    
}
