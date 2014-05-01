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
use Zend\Debug\Debug;
use Zend\Session\Container;
use Application\Model\Media;
use Application\Model\MediaTag;
use Application\Model\MediaStylebook;
use Application\Form\MediaForm;
use Application\Form\MediaTagsForm;
use Auth\Model\User;
use Application\Model\Message;
use Application\Model\Page;

class MediaController extends AbstractActionController
{
    
    protected $sessionContainer;
    public $messages = array();
    
    public function __construct()
    {
        $this->sessionContainer = new Container('media_upload');
        $this->messages = new Message();
    }
	
    public function indexAction()
    {
    	// Todo
    }
    
    public function detailsAction()
    {
        $mediaid = $this->params()->fromRoute('mediaid', null);
        $user_session = new Container('user');
        $user = new User();
        $plugin = $this->HelperPlugin();
        $data = array('media_likes_ids'=>array());
        
        if($plugin->isLoggedIn()) {
            $user_session = new Container('user');
            $user = $user_session->user;
            $media_likes_ids = $plugin->getMediaLikeTable()->getMediaIdsByUser($user_session->user->id);
            $user->followed_ids = $plugin->getUserFollowTable()->getFollowedIdsByUser($user_session->user->id);
            $data['media_likes_ids'] = $media_likes_ids;
            $data['user'] = $user;
            
        }
        
        $media = $plugin->getMediaTable()->getMedia($mediaid);
        
        $user_subject = $plugin->getUserTable()->getUser($media->user_id);
        
        $data['media'] = $media;
        
        $ViewModelActivity = new ViewModel($data);
        $ViewModelActivity->setTerminal(true)
                ->setTemplate('application/media/activity');

        $htmlOutputActivity = $this->getServiceLocator()
                ->get('viewrenderer')
                ->render($ViewModelActivity);
        
        $ViewModelFollow = new ViewModel(array('user'=>$user, 'user_subject'=>$user_subject));
        $ViewModelFollow->setTerminal(true)
        ->setTemplate('application/user/user-follow');
        
        $htmlOutputFollow = $this->getServiceLocator()
        ->get('viewrenderer')
        ->render($ViewModelFollow);
        
        $media_tags = $plugin->getMediaTags($mediaid);
        
        $ViewModelTags = new ViewModel(array('user'=>$user, 'media_tags'=>$media_tags, 'plugin'=>$plugin));
        $ViewModelTags->setTerminal(true)
        ->setTemplate('application/media/gettags');
        
        $htmlOutputTags = $this->getServiceLocator()
        ->get('viewrenderer')
        ->render($ViewModelTags);
        
        $media_stylebook = $plugin->getMediaStylebookTable()->getMediaStylebook(0, $mediaid);
        
        $params = array(
                    'activity' => $htmlOutputActivity,
                    'follow' => $htmlOutputFollow,
                    'tags' => $htmlOutputTags,
                    'title' => $media->title,
                    'comment' => $media->comment,
                    'username' => $media->username,
                    'avatar' => $plugin->getUserAvatar($media->uid, 'm'),
                    'stylebook' => array('title'=>$media_stylebook['stylebook_title'], 'url'=>'/stylebook/'.$media_stylebook['sid']),
        );
        
        if($media->fname != null || $media->lname != null) {
            $params['fullname'] = $media->fname . "\n" . $media->lname;
        } else {
            $params['fullname'] = $media->username;
        }
        
        $result = new JsonModel($params);
        
        return $result;
    }
    
    public function popupcommentAction() {
        $media_id = (int) $this->params()->fromRoute('mediaid', 0);
        
        $data = array();
        $plugin = $this->HelperPlugin();
        $request = $this->getRequest();
        $user_session = new Container('user');
        
        
        if (!$plugin->isLoggedIn()) {
    		$result = new JsonModel(array(
                            'actions'=>array('redirect' => $this->url()->fromRoute('login')),
                            'status'=>'error'
	    	));
                return $result;
    	}
        
        $user = $user_session->user;
        $form  = new \Application\Form\MediaCommentsForm();
        $form->get('user_id')->setValue($user_session->id);
        $form->get('media_id')->setValue($media_id);
                
        if ($request->isPost()){
                $post = $request->getPost();
                $mediaComment = new \Application\Model\MediaComment();
               
                try {
                    $dbAdapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
                    $dbAdapter->getDriver()->getConnection()->beginTransaction();
                    
                    $data = array('status' => 'success', 'messages'=>array());

                    $form->setInputFilter($mediaComment->getInputFilter());
                    $form->setData($post);
                    
                    if ($form->isValid()) {
                        $mediaComment->exchangeArray($form->getData());
                        $mediaComment_id = $plugin->getMediaCommentTable()->addMediaComment($mediaComment);
                        $data['comment'] = $mediaComment->comment;
                        
                    } else {
                        $data['status'] = 'warning';
                        $form_messages = $form->getMessages();
                        foreach ($form_messages as $message) {
                            foreach ($message as $key=>$msg) {
                                $data['messages']['warning'][] = $msg;
                            }
                        }
                    }
                    
                    $dbAdapter->getDriver()->getConnection()->commit();
                    
                } catch(\Exception $e){
                    $dbAdapter->getDriver()->getConnection()->rollback();
                    
                    $message = $e->getMessage();
                    $data['messages']['warning'][] = $message;
                }
                
                if(empty($data['messages'])) {
                    $media = $plugin->getMediaTable()->getMedia($media_id);
                    $data['values']['comments'] = $media->comments_count;
                    //$data['messages']['success'][] = 'Comment added successfully!';
                }
                
	    	$result = new JsonModel($data);
	    	
        } else {
                // Fetch media.
                $media = $plugin->getMediaTable()->getMedia($media_id);
                $mediaComments = $plugin->getMediaCommentTable()->getMediaComments($media_id);
                
                
                $ViewModel = new ViewModel(array(
                                'media' => $media,
                                'plugin' => $plugin,
                                'user' => $user,
                                'form' => $form,
                                'mediaComments' => $mediaComments,
                ));
                $ViewModel->setTerminal(true)
                        ->setTemplate('application/media/popupcomment');
                        
                        
                $htmlOutput = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($ViewModel);

                $result = new JsonModel(array(
                            'html' => $htmlOutput
                ));
        }
	    
        return $result;
    }
    
    public function likeAction(){
        $media_id = (int) $this->params()->fromRoute('mediaid', 0);
        $type = $this->params()->fromRoute('type', 'like');
        
        $plugin = $this->HelperPlugin();
        $request = $this->getRequest();
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

            $data = array('status' => true, 'messages'=>array(), 'values'=>array());

            switch($type){
                case "unlike":
                    if($id = $plugin->getMediaLikeTable()->checkMediaLike($media_id, $user_session->id)) {
                        // Delete row from  media_like table matched by user and media.
                        $plugin->getMediaLikeTable()->deleteMediaLike($id);
                        $data['values']['ajax-src'] = $this->url()->fromRoute('media/like', array('mediaid'=>$media_id));
                    } else {
                        throw new \Exception("You must like this media in order to do this action.");
                    }
                    break;
                case "like":
                default:
                    if(!$plugin->getMediaLikeTable()->checkMediaLike($media_id, $user_session->id)) {
                        // Add new row in media_like table.
                        $mediaLike = new \Application\Model\MediaLike();
                        $mediaLike->media_id = $media_id;
                        $mediaLike->user_id = $user_session->id;
                        $plugin->getMediaLikeTable()->addMediaLike($mediaLike);
                        $data['values']['ajax-src'] = $this->url()->fromRoute('media/like', array('mediaid'=>$media_id, 'type'=>'unlike'));
                    } else {
                        throw new \Exception("You already like this media.");
                    }
                    break;
            }

            $dbAdapter->getDriver()->getConnection()->commit();

        } catch(\Exception $e){
            $dbAdapter->getDriver()->getConnection()->rollback();

            $message = $e->getMessage();
            $data['messages']['warning'][] = $message;
        }
        
        $media = $plugin->getMediaTable()->getMedia($media_id);
        
        $data['values']['html'] = $media->likes;

        $result = new JsonModel($data);
                
        
        return $result;
    }
    
    public function popupmediaAction() {
        $media_id = (int) $this->params()->fromRoute('mediaid', 0);
        
        $data = array();
        $plugin = $this->HelperPlugin();
        $request = $this->getRequest();
        $user_session = new Container('user');
        
        if (!$plugin->isLoggedIn()) {
        	
    		$result = new JsonModel(array(
                            'actions'=>array('redirectModal' => $this->url()->fromRoute('auth/default', array('controller'=>'auth', 'action'=>'small-login-popup'))),
                            'status'=>'error'
	    	));
            
    		return $result;
             
        	//$this->redirect()->toRoute('auth/default', array('controller'=>'auth', 'action'=>'small-login-popup'));
    	}
                
        if ($request->isPost()){
                $post = $request->getPost();
               
                try {
                    $dbAdapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
                    $dbAdapter->getDriver()->getConnection()->beginTransaction();
                    
                    if(isset($post->stylebook_pos) && $post->stylebook_pos == 'existing') {
                        $stylebook_id = $post->stylebook_id;
                    } else {
                        $stylebook_id = $this->addStylebook($plugin, $post, $user_session->id);
                    }

                    $data = array('status' => true, 'messages'=>array());

                    // check errors during creation of new stylebook
                    if(isset($this->messages->warning['stylebook']) && !empty($this->messages->warning['stylebook'])) {
                        $data['status'] = 'error';
                        $data['messages']['warning'] = $this->messages->warning['stylebook'];
                        throw new \Exception('stylebook_warning');
                    } else {
                        // check if media already is connected with stylebook
                        if($plugin->getMediaStylebookTable()->checkStylebookMedia($media_id, $stylebook_id)) {
                            // Add new row in media_stylebook table.
                            $mediaStylebook = new MediaStylebook();
                            $mediaStylebook->media_id = $media_id;
                            $mediaStylebook->stylebook_id = $stylebook_id;
                            $plugin->getMediaStylebookTable()->addMediaStylebook($mediaStylebook);
                        }
                        
                    }
                    
                    $dbAdapter->getDriver()->getConnection()->commit();
                    
                } catch(\Exception $e){
                    $dbAdapter->getDriver()->getConnection()->rollback();
                    
                    $message = $e->getMessage();
                    switch($message) {
                        case "stylebook_warning":
                            break;
                        default:
                            $data['messages']['warning'][] = $message;
                            break;
                    }
                }
                
                if(empty($data['messages'])) {
                    $data['messages']['success'][] = 'Media connected with this stylebook successfully!';
                }
                
	    	$result = new JsonModel($data);
	    	
        } else {
                // Fetch stylebooks and tags for dropdown.
                $stylebooks = $plugin->getStylebookTable()->getSelectOptions($user_session->id);
                $tags = $plugin->getTagTable()->getSelectOptions(1);
                
                $ViewModel = new ViewModel(array(
                                'tags' => $tags,
                                'stylebooks' => $stylebooks,
                                'media_id' => $media_id
                ));
                $ViewModel->setTerminal(true)
                        ->setTemplate('application/media/popupmedia');
                        
                        
                $htmlOutput = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($ViewModel);

                $result = new JsonModel(array(
                            'html' => $htmlOutput
                ));
        }
	    
        return $result;
    }
    
    public function addAction() 
    {
        $renderer = $this->serviceLocator->get('Zend\View\Renderer\RendererInterface');
        $plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
        $formData = $this->sessionContainer->formData;
        
        $user_session = new Container('user');
        
        // check user is logged in
        if (!$plugin->isLoggedIn()) {
    		$this->redirect()->toRoute('login');
    	}
        
        $user = $user_session->user;
        $user->is_logged_in = $plugin->isLoggedIn();
        
        // check if SessionContainer contains stylebook_id and file
        if( !isset($formData['stylebook_id']) || !is_numeric($formData['stylebook_id']) || !isset($formData['file'])) {
            return $this->redirect()->toRoute('stylebook/add');
        }
        
        $seasonid = (int) $this->params()->fromRoute('seasonid');
        if (!$seasonid) {
        	$current_season = $plugin->getCurrentSeasonId();
        	$seasonid=$current_season['id'];
        }
        
        // Fetch stylebooks and tags for dropdown.
        $stylebooks = $plugin->getStylebookTable()->getSelectOptions($user_session->id);
        $tags = $plugin->getTagTable()->getSelectOptions(1);
        
        
        if ($this->getRequest()->isPost()) {
                $post = $this->getRequest()->getPost();
                
                if(isset($post->stylebook_pos) && $post->stylebook_pos == 'existing') {
                    $stylebook_id = $post->stylebook_id;
                } else {
                    $stylebook_id = $this->addStylebook($plugin, $post, $user_session->id);
                }
                
                if(!empty($this->messages->warning)) {
                    goto view;
                }
                
                if(isset($post->Media) && !empty($post->Media)) {
                    $formData['file'] = array();
                    $medias = $post->Media;
                    
                    foreach($medias as $media) {
                        $mediaForm = new MediaForm();
                        $mediaModel = new Media();
                        $mediaStylebook = new MediaStylebook();
                        $mediaForm->setInputFilter($mediaModel->getInputFilter());
                        $mediaForm->setData($media);

                        try {
                            $dbAdapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
                            $dbAdapter->getDriver()->getConnection()->beginTransaction();

                            if ($mediaForm->isValid()) {
                                $mediaModel->exchangeArray($mediaForm->getData());
                                $mediaModel->user_id = $user_session->id;
                                $media_id = $plugin->getMediaTable()->addMedia($mediaModel);

                                // Add tags.
                                if(isset($this->sessionContainer->formData['file']) && !empty($this->sessionContainer->formData['file'])) {
                                    foreach($this->sessionContainer->formData['file'] as $file) {
                                        if($file['tmp_name'] == 'public'.$media['tmp_name'] && isset($file['tags'])) {
                                            foreach($file['tags'] as $tag_id) {
                                                if(is_numeric($tag_id) && $plugin->getTagTable()->getTag($tag_id) && !$plugin->getMediaTagTable()->checkMediaTag($media_id, $tag_id)) {
                                                     $mediaTag = new MediaTag();
                                                     $mediaTag->media_id = $media_id;
                                                     $mediaTag->tag_id = $tag_id;
                                                     $mediaTag_id = $plugin->getMediaTagTable()->addMediaTag($mediaTag);
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }
//                                if(isset($media['tag_ids']) && strlen($media['tag_ids']) > 1) {
//                                   $tag_ids = explode(',', $media['tag_ids']);
//                                   array_pop($tag_ids);
//                                   foreach($tag_ids as $tag_id) {
//                                       if($plugin->getTagTable()->getTag($tag_id) && !$plugin->getMediaTagTable()->checkMediaTag($media_id, $tag_id)) {
//                                            $mediaTag = new MediaTag();
//                                            $mediaTag->media_id = $media_id;
//                                            $mediaTag->tag_id = $tag_id;
//                                            $plugin->getMediaTagTable()->addMediaTag($mediaTag);
//                                       }
//                                   }
//                                }

                                // Add new row in media_stylebook table.
                                $mediaStylebook->media_id = $media_id;
                                $mediaStylebook->stylebook_id = $stylebook_id;
                                $plugin->getMediaStylebookTable()->addMediaStylebook($mediaStylebook);

                                // Generate media file and copy from tmp to original folder.
                                $mediaItem = $plugin->getMediaTable()->getMedia($media_id);
                                $added_on = $mediaItem->added_on;
                                $plugin->saveMediaFile($media_id, $added_on, $media);

                            } else {
                                $formData['file'][] = $media;
                                $form_messages = $mediaForm->getMessages();
                                if(!isset($this->messages->warning['medias'])) {
                                    $this->messages->warning['medias'] = array();
                                    foreach ($form_messages as $message) {
                                        foreach ($message as $key=>$msg) {
                                            $this->messages->warning['medias'][] = $msg;
                                        }
                                    }
                                }
                            }
                            $dbAdapter->getDriver()->getConnection()->commit();

                        } catch(\Exception $e){
                            $dbAdapter->getDriver()->getConnection()->rollback();
                            $this->messages->warning['medias'][] = $e->getMessage();
                        }
                    }
                        
                    $this->sessionContainer->formData = $formData;
                }
                
                if(empty($this->messages->warning)) {
                    return $this->redirect()->toRoute('stylebook/view', array('stylebookid'=>$stylebook_id));
                }
        }
        
        view:
        if(isset($this->sessionContainer->formData['error'])) {
            $this->messages->warning['upload_error'] = $this->sessionContainer->formData['error'];
            unset($this->sessionContainer->formData['error']);
        }
    	return array(
           'uploaded_medias'   => $formData['file'],
           'stylebook_id' => $formData['stylebook_id'],
           'stylebooks' => $stylebooks,
           'tags' => $tags,
    	   'seasonid' => $seasonid,
           'plugin' => $plugin,
           'errors' => $this->messages->warning,
           'user' => $user
        );
        
    }

    public function editAction() 
    {
        $formData = $this->sessionContainer->formData;
        $stylebook_id = (int) $this->params()->fromRoute('stylebookid', 0);
        $renderer = $this->serviceLocator->get('Zend\View\Renderer\RendererInterface');
        $plugin = $this->HelperPlugin();
        $errors = array();
        
        $user_session = new Container('user');
        
        // check user is logged in
        if (!$plugin->isLoggedIn()) {
    		$this->redirect()->toRoute('login');
    	}
        
        //check stylebook id exists
        if(!$stylebook_id) {
                $errors[] = 'Stylebook not found';
                goto view;
        }
        
        $user = $user_session->user;
        
        if ($this->getRequest()->isPost()) {
                $post = $this->getRequest()->getPost();
                
                if(isset($post->Media) && !empty($post->Media)) {
                    $medias = $post->Media;
                    
                    foreach($medias as $media) {
                        $mediaForm = new MediaForm();
                        $mediaModel = new Media();
                        $mediaStylebook = new MediaStylebook();
                        $mediaForm->setInputFilter($mediaModel->getInputFilter());
                        $mediaForm->setData($media);

                        try {
                            $dbAdapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
                            $dbAdapter->getDriver()->getConnection()->beginTransaction();

                            if ($mediaForm->isValid()) {
                                $mediaModel->exchangeArray($mediaForm->getData());
                                $mediaModel->user_id = $user_session->id;
                                
                                if(isset($media['id']) && is_numeric($media['id'])) {
                                    $plugin->getMediaTable()->editMedia($mediaModel);
                                    $media_id = $media['id'];
                                } else {
                                    $media_id = $plugin->getMediaTable()->addMedia($mediaModel);
                                    
                                    // Add new row in media_stylebook table.
                                    $mediaStylebook->media_id = $media_id;
                                    $mediaStylebook->stylebook_id = $stylebook_id;
                                    $plugin->getMediaStylebookTable()->addMediaStylebook($mediaStylebook);
                                    
                                    if(isset($media['tmp_name'])) {
                                        // Generate media file and copy from tmp to original folder.
                                        $mediaItem = $plugin->getMediaTable()->getMedia($media_id);
                                        $added_on = $mediaItem->added_on;
                                        $plugin->saveMediaFile($media_id, $added_on, $media);
                                        
                                        //Add tags
                                        if(isset($formData['file']) && !empty($formData['file'])) {
                                            foreach($formData['file'] as $file) {
                                                if($file['tmp_name'] == 'public'.$media['tmp_name'] && isset($file['tags'])) {
                                                    foreach($file['tags'] as $tag_id) {
                                                        if(is_numeric($tag_id) && $plugin->getTagTable()->getTag($tag_id) && !$plugin->getMediaTagTable()->checkMediaTag($media_id, $tag_id)) {
                                                             $mediaTag = new MediaTag();
                                                             $mediaTag->media_id = $media_id;
                                                             $mediaTag->tag_id = $tag_id;
                                                             $mediaTag_id = $plugin->getMediaTagTable()->addMediaTag($mediaTag);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                            } else {
                                $form_messages = $mediaForm->getMessages();
                                if(!isset($errors['medias'])) {
                                    $errors['medias'] = array();
                                    foreach ($form_messages as $message) {
                                        foreach ($message as $key=>$msg) {
                                            $errors[] = $msg;
                                        }
                                    }
                                }
                            }
                            $dbAdapter->getDriver()->getConnection()->commit();

                        } catch(\Exception $e){
                            $dbAdapter->getDriver()->getConnection()->rollback();
                            $errors[] = $e->getMessage();
                        }
                    }
                        
                }
                
                view:
                if(!empty($errors)) {
                    $user_session->media_error = $errors;
                }
                
                $this->sessionContainer->formData['file'] = array();
                return $this->redirect()->toRoute('stylebook/view', array('stylebookid'=>$stylebook_id));
        }
        
    }
    
    /*
    public function deleteAction()
    {
        $plugin = $this->HelperPlugin();
    	$plugin->setLastUrl();
        
        $user_session = new Container('user');
        $params = array('status' => true, 'messages'=>array(), 'actions'=>array());
    	
    	$user=$user_session->user;
        
        if (!$user->is_logged_in) {
                if($plugin->isAjaxRequest()) {
                    $params['actions']['redirect'] = $this->url()->fromRoute('login');
                    goto view;
                } else {
                    return $this->redirect()->toRoute('login');
                }
    	}
        
        $media_id = (int) $this->params()->fromRoute('mediaid', 0);
        
        if (!$media_id) {
            if($plugin->isAjaxRequest()) {
                    $params['actions']['redirect'] = $this->url()->fromRoute('media/add');
                    goto view;
            } else {
                    return $this->redirect()->toRoute('media/add');
            }
        }
        
        // Fetch media.
        $media = $plugin->getMediaTable()->getMedia($media_id);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'Cancel');
            $params['media'] = $media;

            if ($del == 'Ok') {
                
                    if($plugin->canEditMedia($media)) {
                            // Remove Media with images.
                            $plugin->deleteMediaImage($media_id, $media->added_on);
                            $plugin->getMediaTable()->deleteMedia($media_id);
                    }
                    
            }
            
            // Redirect to home page
            return $this->redirect()->toRoute('mystyles');
            
        } elseif($plugin->isAjaxRequest()) {
            
                $ViewModel = new ViewModel(array(
                                'media' => $media,
                ));
                $ViewModel->setTerminal(true)
                        ->setTemplate('application/media/delete');
                $htmlOutput = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($ViewModel);
                $params['html'] = $htmlOutput;
                
        }
        
        view:
        if($plugin->isAjaxRequest()) {
                $result = new JsonModel($params);
        } else {
                $result = new ViewModel($params);
        }
        
        return $result;

    }
    */
    
    public function uploadAction()
    {
        $form = new \Application\Form\MultiUploadForm('file-form');
        $this->sessionContainer->upload_error = array();
		
        if ($this->getRequest()->isPost()) {
            // Postback
            $data = array_merge_recursive(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );

            $form->setData($data);
            
            if (isset($data['stylebook_id']) && is_numeric($data['stylebook_id'])) {
				if($form->isValid()) {
					$this->sessionContainer->formData = $form->getData();
					$this->sessionContainer->formData['stylebook_id'] = $data['stylebook_id'];
					$this->redirect()->toRoute('media/add');
				} else {
					
					$form_messages = $form->getMessages();
					foreach ($form_messages as $message) {
						foreach ($message as $key=>$msg) {
							$this->sessionContainer->upload_error[] = $msg;
						}
					}
					
					// Redirect to stylebook
					return $this->redirect()->toRoute('stylebook/view', array('stylebookid'=>$data['stylebook_id']));
				}
                
            }
        }
        
        return $this->redirect()->toRoute('media/add');
    }
    
    public function ajaxuploadAction()
    {
        $form        = new \Application\Form\MultiUploadForm('file-form');
        $inputFilter = $form->getInputFilter();
        $container   = new Container('partialExample');
        $tempFile    = $container->partialTempFile;
        $params = array('status' => true, 'messages'=>array());
        
        if ($this->getRequest()->isPost()) {
            // POST Request: Process form
            $postData = array_merge_recursive(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );

            $form->setData($postData);
            if ($form->isValid()) {
                
                // If we did not get a new file upload this time around, use the temp file
                $data = $form->getData();
                if (empty($data['file']) ||
                    (isset($data['file']['error']) && $data['file']['error'] !== UPLOAD_ERR_OK)
                ) {
                    $data['file'] = $tempFile;
                }

                // Send back success information via JSON
                $this->sessionContainer->formData['file'] = array_merge($this->sessionContainer->formData['file'], $data['file']);
                $params['formData'] = $data;
                return new JsonModel($params);
                
            } else {
                // Extend the session
                $container->setExpirationHops(1, 'partialTempFile');

                // Form was not valid, but the file input might be...
                // Save file to a temporary file if valid.
                $data = $form->getData();
                $form_messages = $form->getMessages();
                if (empty($form_messages) && isset($data['file']['error'])
                    && $data['file']['error'] === UPLOAD_ERR_OK
                ) {
                    // NOTE: $data['file'] contains the filtered file path.
                    // 'FileRenameUpload' Filter has been run, and moved the file.
                    $container->partialTempFile = $tempFile = $data['file'];;
                }
                
                foreach($form_messages as $message) {
                    foreach($message as $key=>$msg) {
                        $params['messages']['warning'][] = $msg;
                        break;
                    }
                }

                $params['status'] = false;
                $params['formData'] = $data;
                $params['tempFile'] = $tempFile;
                // Send back failure information via JSON
                return new JsonModel($params);
            }
        } else {
            // GET Request: Clear previous temp file from session
            unset($container->partialTempFile);
            $tempFile = null;
        }

    }
    
    public function gettagsAction(){
        $plugin = $this->HelperPlugin();
        $term = $this->params()->fromQuery('q', null);
        $type = $this->params()->fromQuery('type', null);
        
        $data = array();
        $tag_types = array('brand','style','category');
        if($type != null && in_array($type, $plugin->tagTypes())) {
            $tag_types = array($type);
        }
        $tags = $plugin->getTagTable()->getTopMenuTags($tag_types, $term)->toArray();
        $i = 0;
        foreach ($tags as $tag) {
                $data[$i] = array(
                        'name' => strtolower($tag['tag_name']), 
                        'id' => $tag['id'],
                    );
                $i++;
        }
//        $data = array_slice($data, 0, 10);
        
        return new JsonModel($data);
    }
    
    public function edittagsAction(){
        $media_id = (int) $this->params()->fromQuery('mid', 0);
        $index = (int) $this->params()->fromQuery('index', -1);
        
        $formData = $this->sessionContainer->formData;
        $editMedia = false;
        $addMedia = false;
        $temp_tags = array();
        
        $data = array();
        $plugin = $this->HelperPlugin();
        $request = $this->getRequest();
        $user_session = new Container('user');
        
        if (!$plugin->isLoggedIn()) {
    		$result = new JsonModel(array(
                        'actions'=>array('redirect' => $this->url()->fromRoute('login')),
                        'status'=>'error'
	    	));
                return $result;
    	}
        
        if($media_id) {
            $editMedia = true;
        } elseif($index >= 0 && $formData['file'][$index]) {
            $addMedia = true;
            $tmpMedia = $formData['file'][$index];
            if(isset($tmpMedia['tags']) && is_array($tmpMedia['tags'])) {
                $temp_tags = $plugin->getMediaTmpTags($tmpMedia['tags']);
            } else {
                $tmpMedia['tags'] = array();
            }
        }
        
        $user = $user_session->user;
        $form  = new \Application\Form\MediaTagsForm();
        
        
        if ($request->isPost()){
                $post = $request->getPost();
                $mediaTag = new \Application\Model\MediaTag();
               
                try {
                    $dbAdapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
                    $dbAdapter->getDriver()->getConnection()->beginTransaction();
                    
                    $data = array('status' => 'success', 'messages'=>array());

                    $post->brand .= ($post->popular_brand != null)?','.$post->popular_brand:'';
                    $post->category .= ($post->popular_category != null)?','.$post->popular_category:'';
                    $post->style .= ($post->popular_style != null)?','.$post->popular_style:'';
                    $post->season .= ($post->popular_season != null)?','.$post->popular_season:'';
                    $post->color .= ($post->popular_color != null)?','.$post->popular_color:'';
                    
                    if($editMedia) {
                        $media_tags = $plugin->getMediaTags($media_id);
                        if(count($media_tags) > 0) {
                            foreach($media_tags as $media_tag) {
                                if($post->$media_tag['type'] == null) {
                                    $post->$media_tag['type'] = 'tagged';
                                }
                            }
                        }
                    } elseif($addMedia) {
                        if(count($temp_tags) > 0) {
                            foreach($temp_tags as $temp_tag) {
                                if($post->$temp_tag['type'] == null) {
                                    $post->$temp_tag['type'] = 'tagged';
                                }
                            }
                        }
                    }
                    
                    $form->setData($post);
                    
                    if ($form->isValid()) {
                        
                        $tag_ids = explode(',', $post->brand.','.$post->category.','.$post->style.','.$post->season.','.$post->color);
                        $tag_ids = array_unique($tag_ids);
                        
                        if($editMedia) {
                            // Add tags.
                            foreach($tag_ids as $tag_id) {
                                if(is_numeric($tag_id) && $plugin->getTagTable()->getTag($tag_id) && !$plugin->getMediaTagTable()->checkMediaTag($media_id, $tag_id)) {
                                     $mediaTag = new MediaTag();
                                     $mediaTag->media_id = $media_id;
                                     $mediaTag->tag_id = $tag_id;
                                     $mediaTag_id = $plugin->getMediaTagTable()->addMediaTag($mediaTag);
                                }
                            }
                            
                        } elseif($addMedia) {
                             // Add temporary tags into session container.
                                foreach($tag_ids as $key=>$tag_id) {
                                    if(!is_numeric($tag_id)) {
                                        unset($tag_ids[$key]);
                                    }
                                }
                                $tmpMedia['tags'] = array_merge($tmpMedia['tags'], $tag_ids);
                                $tmpMedia['tags'] = array_unique($tmpMedia['tags']);
                                $this->sessionContainer->formData['file'][$index]['tags'] = $tmpMedia['tags'];
                        }
                        
                    } else {
                        $data['status'] = 'warning';
                        $data['messages']['warning'][] = "*Choose at least one tag per category";
                    }
                    
                    $dbAdapter->getDriver()->getConnection()->commit();
                    
                } catch(\Exception $e){
                    $dbAdapter->getDriver()->getConnection()->rollback();
                    
                    $message = $e->getMessage();
                    $data['messages']['warning'][] = $message;
                }
                
                if(empty($data['messages'])) {
                    $data['messages']['success'][] = 'The tags are successfully inserted';
                }
                
	    	$result = new JsonModel($data);
	    	
        } else {
                // Fetch media.
                $popular_tags = $plugin->getPopularTags();
                $params = array(
                            'popular_tags' => $popular_tags,
                            'form' => $form,
                            'plugin' => $plugin,
                            'user' => $user,
                            'temp_tags' => $temp_tags,
                        );
                
                if($editMedia) {
                    $params['media'] = $plugin->getMediaTable()->getMedia($media_id);
                    $params['media_tags'] = $plugin->getMediaTags($media_id);
                    $form->setAttribute('action', '/media/edittags?mid='.$media_id);
                } elseif($addMedia) {
                    $params['file'] = $formData['file'][$index];
                    $params['index'] = $index;
                    $form->setAttribute('action', '/media/edittags?index='.$index);
                } else {
                    $result = new JsonModel(array(
                            'html' => ''
                    ));
                    return $result;
                }
                
                $ViewModel = new ViewModel($params);
                $ViewModel->setTerminal(true)
                        ->setTemplate('application/media/edittags');


                $htmlOutput = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($ViewModel);

                $result = new JsonModel(array(
                            'html' => $htmlOutput
                ));
        }
        
        return $result;
    }
    
    public function deleteMediaTagAction(){
       $media_id = (int) $this->params()->fromQuery('mid', 0);
       $tag_id = (int) $this->params()->fromQuery('tid', 0);
       $index = (int) $this->params()->fromQuery('index', 0);
       $formData = $this->sessionContainer->formData;
       $params = array('status' => false, 'messages'=>array());
       
       if(!$tag_id) {
           goto view;
       }
       
       $user_session = new Container('user');
       $user=$user_session->user;
        
       if (!$user->is_logged_in) {
           goto view;
       }
       
       if($media_id) {
            $plugin = $this->HelperPlugin();
            $media = $plugin->getMediaTable()->getMedia($media_id);
            if($media && $plugin->canEditMedia($media)) {
                $mediaTagId = $plugin->getMediaTagTable()->checkMediaTag($media_id, $tag_id);
                if($mediaTagId) {
                    $plugin->getMediaTagTable()->deleteMediaTag($mediaTagId);
                    $params['status'] = true;
                }
            }
       } elseif($index >= 0 && $formData['file'][$index]) {
            $tags = $formData['file'][$index]['tags'];
            if(($key = array_search($tag_id, $tags)) !== false) {
                unset($tags[$key]);
                $params['status'] = true;
            }
            $this->sessionContainer->formData['file'][$index]['tags'] = $tags;
       } 
       
       view:
       return new JsonModel($params);
       
    }
    
    private function addStylebook($plugin = null, $post = null, $user_id)
    {
        $params['title'] = $post->title;
        $params['description'] = $post->description;
        $params['tag_id'] = 0;
        
        $form = new \Application\Form\StylebookForm();
        $stylebook = $this->getServiceLocator()->get('Application/Model/Stylebook');
        $stylebookTag = new \Application\Model\StylebookTag();
        $form->setInputFilter($stylebook->getInputFilter());
        $form->setData($params);

        if ($form->isValid()) {
            $stylebook->exchangeArray($form->getData());
            $stylebook->user_id = $user_id;
            $stylebook_id = $plugin->getStylebookTable()->addStylebook($stylebook);

            // Save Stylebook Tag
            $stylebookTag->stylebook_id = $stylebook_id;
            $stylebookTag->tag_id = $post->tag_id;
            $plugin->getStylebookTagTable()->addStylebookTag($stylebookTag);

            return $stylebook_id;
        } else {
            $form_messages = $form->getMessages();
            $this->messages->warning['stylebook'] = array();
            foreach ($form_messages as $message) {
    		foreach ($message as $key=>$msg) {
                    $this->messages->warning['stylebook'][]=$msg;
    		}
            }
        }
        
        return false;
    }
    
}