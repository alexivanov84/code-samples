<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Form\Annotation\Object;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use Auth\Model\User;
use Application\Model\Page;


class ExploreController extends AbstractActionController
{

    public function indexAction()
    {
    	$user = new User();
    	$page = new Page();
    	
    	$user_session = new Container('user');   	
    		
    	$plugin = $this->HelperPlugin();
    	
    	$plugin->setLastUrl();
    	
    	$page->filter_menu = $plugin->prepareFilterMenu();
    	
    	$media_likes_ids=array();
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$user=$user_session->user;
    		$user->media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
    	
    	$filterparam = $this->getEvent()->getRouteMatch()->getParam('filterparam');
		$page->filterparam = $plugin->prepareFilterParameters($filterparam);
   	
		$page->items_count = 24;
		$page->page_number=1;
		$medias = $plugin->browseMediaByParameters($page->filterparam['array'], $page->items_count, $page->page_number);
		$current_page=$medias->getCurrentPageNumber();
		$page_count=$medias->count();
		if($current_page<$page_count) {
			$next_page = $current_page+1;
			$page->next_page_url='/application/explore/ajaxBrowsePager/?filterparam='.$page->filterparam['string'].'&page='.$next_page;
    			//$medias = $plugin->searchMediaByParameters($explore_parameters);
		}

	   	return array(
    				'user' => $user,
    				'page' => $page,
    				'plugin' => $plugin,
    				'medias' => $medias,
    			);
    }
    
    public function ajaxBrowsePagerAction() 
    {
    	$user = new User();
    	$page = new Page();
    	 
    	$user_session = new Container('user');
    	
    	$plugin = $this->HelperPlugin();
    	
    	$media_likes_ids=array();
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$user=$user_session->user;
    		$media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
    	
    	//$route_parameters = $this->getEvent()->getRouteMatch()->getParams();
    	$getparams = $this->getRequest()->getQuery();
    	$page->page_number = $getparams['page'];
    	 
		$page->filterparam = $plugin->prepareFilterParameters($getparams['filterparam']);
    	 
    	
    	$page->items_count = 24;
    	$medias = $plugin->browseMediaByParameters($page->filterparam['array'] , $page->items_count, $page->page_number);
    	$current_page=$medias->getCurrentPageNumber();
    	$page_count=$medias->count();
    	
    	if($current_page<$page_count) {
    		$next_page = $current_page+1;
    		$next_page_url='/application/explore/ajaxBrowsePager/?filterparam='.$page->filterparam['string'].'&page='.$next_page;
    		//$medias = $plugin->searchMediaByParameters($explore_parameters);
    	} else {
    		$next_page_url=null;
    	}
    	
    	$ViewModel = new ViewModel(array(
    				'user' => $user,
    				'page' => $page,
    				'plugin' => $plugin,
    				'medias' => $medias,
    				'media_likes_ids' => $media_likes_ids
    	));
    	$ViewModel->setTerminal(true)
    	->setTemplate('application/explore/ajax-explore-pager');
    	
    	
    	$htmlOutput = $this->getServiceLocator()
    	->get('viewrenderer')
    	->render($ViewModel);
    	
    	$result = new JsonModel(array(
    			'html' => $htmlOutput,
    			'next_page_url'=> $next_page_url,
    	));
    	return $result;
    }
    
    public function preSearchRedirectAction() {
    	$request = $this->getRequest();
    	if ($request->isPost()){
    		$post = $request->getPost();
    		if(isset($post->searchparam)){
    			$this->redirect()->toRoute('search', array('searchparam'=>urlencode($post->searchparam)));
    		}
    	}
    }
    
    public function searchAction() {
    	//$route_parameters = $this->getEvent()->getRouteMatch()->getParams();
    	//var_dump($route_parameters);
    	
    	$user = new User();
    	$page = new Page();
    	 
    	$user_session = new Container('user');
    	
    	$plugin = $this->HelperPlugin();
    	 
    	$plugin->setLastUrl();
    	 
    	$page->filter_menu = $plugin->prepareFilterMenu();
    	 
    	$media_likes_ids=array();
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$user=$user_session->user;
    		$media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
    
    	$searchparam = $this->getEvent()->getRouteMatch()->getParam('searchparam');
    	$page->searchparam = $plugin->prepareSearchParameters($searchparam);
    	$page->search_input=urldecode($searchparam);
    	 
    	$filterparam = $this->getEvent()->getRouteMatch()->getParam('filterparam');
        $page->filterparam = $plugin->prepareFilterParameters($filterparam);
    	
    	$page->items_count = 24;
    	$page->page_number=1;
    	
    	$medias = $plugin->searchMediaByParameters($page->searchparam['array'], $page->filterparam['array'], $page->items_count, $page->page_number);
    	
    	$current_page=$medias->getCurrentPageNumber();
    	$page_count=$medias->count();
    	
    	if($current_page<$page_count) {
    		$next_page = $current_page+1;
    		$page->next_page_url='/application/explore/ajaxSearchPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.$next_page;
    	}
    	
    	$ViewModel = new ViewModel(array(
    			'user' => $user,
    			'page' => $page,
    			'plugin' => $plugin,
    			'medias' => $medias,
    			'media_likes_ids' => $media_likes_ids,
    	));
    	$ViewModel->setTemplate('application/explore/index');
    	return $ViewModel;
    }
    
    public function ajaxSearchPagerAction() 
    {
    	$user = new User();
    	$page = new Page();
    	 
    	$user_session = new Container('user');
    	
    	$plugin = $this->HelperPlugin();
    	
    	$media_likes_ids=array();
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$user=$user_session->user;
    		$media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
    	
    	//$route_parameters = $this->getEvent()->getRouteMatch()->getParams();
    	$getparams = $this->getRequest()->getQuery();
    	$page->page_number = $getparams['page'];

    	$page->searchparam = $plugin->prepareSearchParameters($getparams['searchparam']);
		$page->filterparam = $plugin->prepareFilterParameters($getparams['filterparam']);
    	 
    	
    	$page->items_count = 24;
    	$medias = $plugin->searchMediaByParameters($page->searchparam['array'], $page->filterparam['array'], $page->items_count, $page->page_number);
    	$current_page=$medias->getCurrentPageNumber();
    	$page_count=$medias->count();
    	
    	if($current_page<$page_count) {
    		$next_page = $current_page+1;
    		$next_page_url='/application/explore/ajaxSearchPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.$next_page;
    		//$medias = $plugin->searchMediaByParameters($explore_parameters);
    	} else {
    		$next_page_url=null;
    	}
    	
    	$ViewModel = new ViewModel(array(
    				'user' => $user,
    				'page' => $page,
    				'plugin' => $plugin,
    				'medias' => $medias,
    				'media_likes_ids' => $media_likes_ids
    	));
    	$ViewModel->setTerminal(true)
    	->setTemplate('application/explore/ajax-explore-pager');
    	
    	
    	$htmlOutput = $this->getServiceLocator()
    	->get('viewrenderer')
    	->render($ViewModel);
    	
    	$result = new JsonModel(array(
    			'html' => $htmlOutput,
    			'next_page_url'=> $next_page_url,
    	));
    	return $result;
    }
    
    public function previewAction() {
        $this->layout('layout/fullscreen');
        $exploreby = 'search';
        $media_likes_ids=array();
        $next_page_url = '';
        $data = array();
        $user = new User();
    	$page = new Page();
        
    	$user_session = new Container('user');   	
    	$data['plugin'] = $plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
        
        
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$data['user'] = $user = $user_session->user;
    		$media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
        
        $media_id = $this->getEvent()->getRouteMatch()->getParam('mediaid', null);

        $page->items_count = 24;
        $page->page_number = $this->getEvent()->getRouteMatch()->getParam('pagenumber');;

		$searchparam = $this->getEvent()->getRouteMatch()->getParam('searchparam');
		$page->searchparam = $plugin->prepareSearchParameters($searchparam);
		$page->search_input=urldecode($searchparam);
		
		$filterparam = $this->getEvent()->getRouteMatch()->getParam('filterparam');
		$page->filterparam = $plugin->prepareFilterParameters($filterparam);
		
		$mediasObject = $plugin->searchMediaByParameters($page->searchparam['array'], $page->filterparam['array'], $page->items_count, $page->page_number);
		
		$current_page=$mediasObject->getCurrentPageNumber();
		$page_count=$mediasObject->count();
		//var_dump($mediasObject);
		
		if ($page_count>1) {
			$data['next_page_url'] = '/application/explore/previewAjaxPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.((($page->page_number+$page_count)%$page_count)+1);
			$data['prev_page_url'] = '/application/explore/previewAjaxPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.((($page->page_number-2+$page_count)%$page_count)+1);
		}
		
		$medias = array();
		if($mediasObject->getTotalItemCount() > 0) {
			$mediasObject = $mediasObject->getCurrentItems();
			$i = 0;
			foreach($mediasObject as $media) {
				$medias[$i]['id'] = $media['id'];
				$medias[$i]['added_on'] = $media['added_on'];
				$i++;
			}
		}
		$media_id = $this->getEvent()->getRouteMatch()->getParam('mediaid', null);
		
		$data['medias'] = $medias;
		$data['searchparam'] = $searchparam;
		$data['filterparam'] = $filterparam;
		
        if (!isset($media_id)) {
        	$media_id = $medias[0]['id'];
        }
        
        // Get current Media 
        $current_media = $plugin->getMediaTable()->getMedia($media_id);
        $ViewModel = new ViewModel(array('media_likes_ids'=>$media_likes_ids, 'media'=>$current_media));
        $ViewModel->setTerminal(true)
        ->setTemplate('application/media/activity');
        
        $activity = $this->getServiceLocator()
        ->get('viewrenderer')
        ->render($ViewModel);
        $current_media->activity = $activity;
        $data['current_media'] = $current_media;
        
       
        if (isset($current_media)) {
        	foreach($data['medias'] as $media_key => $media) {
        		if ($media['id']==$current_media->id) {
        			$data['starting_slide'] = $media_key;
        		}
        	}	
        }

        return $data;
    }
    
    public function previewAjaxPagerAction() {
        $exploreby = 'search';
        $next_page_url = '';
        $data = array();
        $user = new User();
    	$page = new Page();
        
    	$user_session = new Container('user');   	
    	$data['plugin'] = $plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
        
        
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$data['user'] = $user = $user_session->user;
    	}
        
        $getparams = $this->getRequest()->getQuery();
        
        $page->items_count = 24;
        $page->page_number = $getparams['page'];

        $page->searchparam = $plugin->prepareSearchParameters($getparams['searchparam']);
        $page->filterparam = $plugin->prepareFilterParameters($getparams['filterparam']);
        
        $medias = $plugin->searchMediaByParameters($page->searchparam['array'], $page->filterparam['array'], $page->items_count, $page->page_number);
        
        $current_page=$medias->getCurrentPageNumber();
        $page_count=$medias->count();
        //if($current_page < $page_count) {
        //	$next_page_url = '/application/explore/previewAjaxPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.($page->page_number+1);
        //}
        if ($page_count>1) {
        	$next_page_url = '/application/explore/previewAjaxPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.((($page->page_number+$page_count)%$page_count)+1);
        	$prev_page_url = '/application/explore/previewAjaxPager/?searchparam='.$page->searchparam['string'].'&filterparam='.$page->filterparam['string'].'&page='.((($page->page_number-2+$page_count)%$page_count)+1);
        }


        $ViewModel = new ViewModel(array(
                            'medias' => $medias,
                            'plugin' => $plugin,
                            //'start'  => $page->items_count * ($page->page_number-1),
    	));
    	$ViewModel->setTerminal(true)
    	->setTemplate('application/explore/ajax-preview-pager');
    	
    	
    	$htmlOutput = $this->getServiceLocator()
    	->get('viewrenderer')
    	->render($ViewModel);
    	
    	$result = new JsonModel(array(
    			'html' => $htmlOutput,
    			'next_page_url' => $next_page_url,
    			'prev_page_url' => $prev_page_url,
    	));
        
        return $result;
    }
    
    
    public function topStylesAction() {
    	$plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
    	 
    	$user = new User();
    	 
    	$user_session = new Container('user');
    	 
    	$page = new Page();
    	 
    	if ($plugin->isLoggedIn()) {
    		$user = $user_session->user;
    	}
    	
    	$limit = 12;
    	
    	$stylebooks = $plugin->getTopStylebooks($user, $limit);
    	
    	$ViewModel = new ViewModel(array(
    			'user' => $user,
    			'stylebooks' => $stylebooks,
    			'plugin' => $plugin,
    	));
		return $ViewModel;
    }
    
    public function topBrandsAction() {
    	$plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
    
    	$user = new User();
    
    	$user_session = new Container('user');
    
    	$page = new Page();
    
    	if ($plugin->isLoggedIn()) {
    		$user = $user_session->user;
    		$user->followed_ids = $plugin->getUserFollowTable()->getFollowedIdsByUser($user->id);
    	}
    	 
    	$limit = 12;
    	 
    	$brands = $plugin->getTopBrands($user, $limit);
    	 
    	$ViewModel = new ViewModel(array(
    			'user' => $user,
    			'brands' => $brands,
    			'plugin' => $plugin,
    	));
    	return $ViewModel;
    }
    
    public function hotMediaPreviewAction() {
        $this->layout('layout/fullscreen');
        $media_likes_ids=array();
        $data = array();
        $user = new User();
    	$page = new Page();
        
    	$user_session = new Container('user');   	
    	$data['plugin'] = $plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();

    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$data['user'] = $user = $user_session->user;
    		$media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
        
        $media_id = $this->getEvent()->getRouteMatch()->getParam('mediaid', null);
		
		$mediasObject = $plugin->getMostLikedMedia(12);
		$medias = array();
		if(count($mediasObject )> 0) {
			$i = 0;
			foreach($mediasObject as $media) {
				$medias[$i]['id'] = $media['id'];
				$medias[$i]['added_on'] = $media['added_on'];
				$i++;
			}
		}
		
		if (!$media_id) {
			$media_id = $medias[0]['id'];
		}
		
		$data['medias'] = $medias;
		
        
        if($media_id != null) {
                // Get current Media 
                $current_media = $plugin->getMediaTable()->getMedia($media_id);
                $ViewModel = new ViewModel(array('media_likes_ids'=>$media_likes_ids, 'media'=>$current_media));
                $ViewModel->setTerminal(true)
                        ->setTemplate('application/media/activity');

                $activity = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($ViewModel);
                $current_media->activity = $activity;
                $data['current_media'] = $current_media;
        }
        
        $ViewModel2 = new ViewModel($data);
        $ViewModel2->setTemplate('application/explore/preview');
        return $ViewModel2;
    }
    
    public function latestMediaPreviewAction() {
    	$this->layout('layout/fullscreen');
    	$media_likes_ids=array();
    	$data = array();
    	$user = new User();
    	$page = new Page();
    
    	$user_session = new Container('user');
    	$data['plugin'] = $plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
    
    	if ($plugin->isLoggedIn() && isset($user_session->user)) {
    		$data['user'] = $user = $user_session->user;
    		$media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
    	}
    
    	$media_id = $this->getEvent()->getRouteMatch()->getParam('mediaid', null);
    
    	$mediasObject = $plugin->getLatestMedia(12);
    	$medias = array();
    	if(count($mediasObject )> 0) {
    		$i = 0;
    		foreach($mediasObject as $media) {
    			$medias[$i]['id'] = $media['id'];
    			$medias[$i]['added_on'] = $media['added_on'];
    			$i++;
    		}
    	}
    
    	if (!$media_id) {
    		$media_id = $medias[0]['id'];
    	}
    
    	$data['medias'] = $medias;
    
    
    	if($media_id != null) {
    		// Get current Media
    		$current_media = $plugin->getMediaTable()->getMedia($media_id);
    		$ViewModel = new ViewModel(array('media_likes_ids'=>$media_likes_ids, 'media'=>$current_media));
    		$ViewModel->setTerminal(true)
    		->setTemplate('application/media/activity');
    
    		$activity = $this->getServiceLocator()
    		->get('viewrenderer')
    		->render($ViewModel);
    		$current_media->activity = $activity;
    		$data['current_media'] = $current_media;
    	}
    
    	$ViewModel2 = new ViewModel($data);
    	$ViewModel2->setTemplate('application/explore/preview');
    	return $ViewModel2;
    }
    
}
