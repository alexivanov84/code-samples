<?php
namespace Application\Controller\Plugin;
     
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;
use Auth\Model\User;
use Auth\Model\Auth;
use Imagine;
use Contactology;

class HelperPlugin extends AbstractPlugin{

	public $authservice;
	
	public function setLastUrl(){
		$user_session = new Container('user');
		$user_session->last_url = $this->curPageURL();
	}
        
        public function getMediaTypes() {
                $types = array(
                    '1' => array('image/jpeg', 'image/jpg', 'image/pjpeg', 'image/x-png', 'image/png'),
                    '2' => array('video/mpeg', 'video/mpeg4', 'video/avi', 'video/mov', 'video/mpg', 'video/wmv', 'video/vid')
                );
                
                return $types;
        }
	

	public function curPageURL() {
		$pageURL = 'http';
		//if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) {
			$pageURL .= "s";
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}
	
	public function isLoggedIn() {
		if ($this->getAuthService()->hasIdentity()){
			return true;
		} else {
			return false;
		}
	}
        
        public function isAjaxRequest(){
                if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        return true;
                }
                
                return false;
        }
        
        public function getImageSizes(){
                return array('s', 'm', 'org');
        }
        
        public function tagTypes() {
                return array('green'=>'season', 'orange'=>'style', 'violet'=>'category', 'blue'=>'brand', 'gray'=>'color');
        }
	
	public function setLoginSession($user){
		$user_session = new Container('user');
		 
		$user_session->id = $user->id;
		$user_session->email = $user->email;
		$user_session->first_name=$user->first_name;
		$user_session->last_name=$user->last_name;
		$user_session->user_type=$user->user_type;
	
		$user_session->user = $user;
		$user_session->user->is_logged_in=true;
		$user_session->user->cover = $this->getUserCover($user->id);
		$user_session->user->avatar = $this->getUserAvatar($user->id, 'm');
		$user_session->user->url = $this->getUserProfileUrl($user->username);
		 
		 
		$this->getAuthService()->setStorage($this->getSessionStorage());
		$this->getAuthService()->getStorage()->write($user->id);
		 
	}
	
	public function prepareScreenShopMenu() {
		$menu_tag_types=array('brand','style','category');
		$menu_tags = $this->getTagTable()->getTopMenuTags($menu_tag_types)->toArray();
		foreach ($menu_tags as $tag) {
			$menu_all[$tag['tag_type']][$tag['id']]=$tag['tag_name'];
		}
		
		$menu_short['brand']=array_slice($menu_all['brand'], 0, 18, true);
		$menu_short['style']=array_slice($menu_all['style'], 0, 11, true);
		$menu_short['category']=array_slice($menu_all['category'], 0, 11, true);
		
		//var_dump($menu_short);
		//TODO finish screen shop full menu
		//TODO enable caching for the menu
		return $menu_short;
	}
	
	public function prepareFilterMenu() {
		$menu_tag_types=array('brand','style','category');
		$menu_tags = $this->getTagTable()->getTopMenuTags($menu_tag_types)->toArray();
		foreach ($menu_tags as $tag) {
			$menu_all[$tag['tag_type']][$tag['id']]=$tag['tag_name'];
		}
		
		$menu_short['brand']=array_slice($menu_all['brand'], 0, 32, true);
		$menu_short['style']=array_slice($menu_all['style'], 0, 32, true);
		$menu_short['category']=array_slice($menu_all['category'], 0, 32, true);
		
		//var_dump($menu_short);
		//TODO finish screen shop full menu
		//TODO enable caching for the menu
		return $menu_short;
	}
	
	public function getAuthService()
	{
		if (!isset($this->authservice)) {
			$this->authservice = $this->getController()->getServiceLocator()
			->get('AuthService');
		}
	
		return $this->authservice;
	}
	
	public function getSessionStorage()
	{
		if (!isset($this->storage)) {
			$this->storage = $this->getController()->getServiceLocator()
			->get('Auth\Model\MyAuthStorage');
		}
	
		return $this->storage;
	}
        
	/*
        public function getMediaImage($filename, $mediaId, $size) {
		$validator = new \Zend\Validator\File\Exists();
		
		if ($validator->isValid('public/images/content/media/'.$filename.md5($mediaId).'_media_'.$size.'.jpg')) {
			
			return ($this->basePath().'images/content/media/'.$filename.md5($mediaId).'_media_'.$size.'.jpg');
			
		} else {
			
			return ($this->basePath().'images/content/avatar/no_image_avatar_'.$size.'.jpg');
		}
	}
       */
        
        public function deleteMediaImage($mediaid = null, $timestamp = null) {
                if($mediaid == null)
                    return;
                
		$validator = new \Zend\Validator\File\Exists();
                $timestamp = explode(' ', $timestamp);
                $dateArray = $timestamp[0];
                $medias_dir ='public/images/content/media/'.str_replace('-', '/', $dateArray).'/';
		$sizes = $this->getImageSizes();
                
                foreach($sizes as $size) {
                    if ($validator->isValid($medias_dir.md5($mediaid).'_media_'.$size.'.jpg')) {
                            unlink($medias_dir.md5($mediaid).'_media_'.$size.'.jpg');
                    }
                }
                return;
	}
        
        public function getMediaImage($mediaid = null, $timestamp = null, $size = 's') {
                if($mediaid == null)
                    return ($this->basePath().'images/content/no_image_media_'.$size.'.jpg');
                
		$validator = new \Zend\Validator\File\Exists();
                $timestamp = explode(' ', $timestamp);
                $dateArray = $timestamp[0];
                $medias_dir ='public/images/content/media/'.str_replace('-', '/', $dateArray).'/';
		
		if ($validator->isValid($medias_dir.md5($mediaid).'_media_'.$size.'.jpg')) {
			return ($this->basePath().'images/content/media/'.str_replace('-', '/', $dateArray).'/'.md5($mediaid).'_media_'.$size.'.jpg');
		} else {
			return ($this->basePath().'images/content/no_image_media_'.$size.'.jpg');
		}
	}
	
	public function saveMediaFile($mediaid, $timestamp, $file) {
                $validator = new \Zend\Validator\File\Exists();
                $timestamp = explode(' ', $timestamp);
                $dateArray = explode('-', $timestamp[0]);
		$medias_dir ='public/images/content/media/';
                
                if(!$validator->isValid($medias_dir.$dateArray[0])){
                    mkdir($medias_dir.$dateArray[0], 0777);
                }
                if(!$validator->isValid($medias_dir.$dateArray[0].'/'.$dateArray[1])){
                    mkdir($medias_dir.$dateArray[0].'/'.$dateArray[1], 0777);
                }
                if(!$validator->isValid($medias_dir.$dateArray[0].'/'.$dateArray[1].'/'.$dateArray[2])){
                    mkdir($medias_dir.$dateArray[0].'/'.$dateArray[1].'/'.$dateArray[2], 0777);
                }
                
                $medias_dir = $medias_dir.$dateArray[0].'/'.$dateArray[1].'/'.$dateArray[2].'/';
		$this->createThumbnail(468, 468, 'public'.$file['tmp_name'], $medias_dir.md5($mediaid).'_media_m.jpg');
		$this->createThumbnail(227, 227, 'public'.$file['tmp_name'], $medias_dir.md5($mediaid).'_media_s.jpg');
		$this->maxsizeImage(1920, 1080, 'public'.$file['tmp_name'], $medias_dir.md5($mediaid).'_media_org.jpg');
		
		return $file['tmp_name'];
	}
	
	public function getUserAvatar($userid, $size) {
		$validator = new \Zend\Validator\File\Exists();
		
		if ($validator->isValid('public/images/content/avatar/'.md5($userid).'_avatar_'.$size.'.jpg')) {
			
			return ($this->basePath().'images/content/avatar/'.md5($userid).'_avatar_'.$size.'.jpg');
			
		} else {
			
			return ($this->basePath().'images/content/no_image_avatar_'.$size.'.jpg');
		}
		
	}
	
	public function saveAvatar($userid, $avatar) {
		
		$avatars_dir ='public/images/content/avatar/';
		
		$filter = new \Zend\Filter\File\Rename(array(
				'target'	=>	$avatars_dir.md5($userid).'_avatar_org.jpg',
				'overwrite'	=>	true,
		));
		
		$avatar = $filter->filter($avatar);
		
		$this->createThumbnail(76, 76, $avatar['tmp_name'], $avatars_dir.md5($userid).'_avatar_m.jpg');
		 
		$this->createThumbnail(50, 50, $avatar['tmp_name'], $avatars_dir.md5($userid).'_avatar_s.jpg');
		
		$this->maxsizeImage(1920, 1080, $avatar['tmp_name'], $avatars_dir.md5($userid).'_avatar_org.jpg');
		
		return $avatar['tmp_name'];
	
	}
	
	public function createThumbnail($width, $height, $src_img, $dest_img) {
		$imagine = new Imagine\Gd\Imagine(); 
		
		$size    = new Imagine\Image\Box($width, $height);
		
		$mode    = Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
		
		$imagine->open($src_img)
			->thumbnail($size, $mode)
			->save($dest_img);
	}
	
	public function maxsizeImage($width, $height, $src_img, $dest_img) {
		$imagine = new Imagine\Gd\Imagine(); 
		
		$size    = new Imagine\Image\Box($width, $height);
		
		$mode    = Imagine\Image\ImageInterface::THUMBNAIL_INSET;
		
		$image = $imagine->open($src_img);

		if (($width <= $image->getSize()->getWidth()) || ($height <= $image->getSize()->getHeight())) {
			
			$image->thumbnail($size, $mode)->save($dest_img);
		} else {
                        $image->save($dest_img);
                }
	}
	
	
	public function getUserCover($userid) {
		$validator = new \Zend\Validator\File\Exists();
		
		if ($validator->isValid('public/images/content/cover/'.md5($userid).'_cover.jpg')) {
				
			return ($this->basePath().'images/content/cover/'.md5($userid).'_cover.jpg');
			
		} else {
			
			return false;
		}
	}
	
	public function saveCover($userid, $cover) {
	
		$covers_dir ='public/images/content/cover/';
	
		$filter = new \Zend\Filter\File\Rename(array(
				'target'	=>	$covers_dir.md5($userid).'_cover_org.jpg',
				'overwrite'	=>	true,
		));
	
		$cover = $filter->filter($cover);
	
		$this->createThumbnail(943, 225, $cover['tmp_name'], $covers_dir.md5($userid).'_cover.jpg');
	
		$this->maxsizeImage(1920, 1080, $cover['tmp_name'], $covers_dir.md5($userid).'_cover_org.jpg');
	
		return $cover['tmp_name'];
	
	}
	
	public function getUserProfileUrl($username) {
		return $this->baseUri().'/user/'.$username;
	}
	
	public function basePath() {
		$renderer = $this->getController()->getServiceLocator()->get('Zend\View\Renderer\RendererInterface');
		return $renderer->basePath('');
	}
	
	public function baseUri() {
		$uri = $this->getController()->getRequest()->getUri();
    	return $base = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());
	}
	
	public function getUserTable()
	{
		if (!isset($this->userTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->userTable = $sm->get('Auth\Model\UserTable');
		}
		return $this->userTable;
	}
        
        public function getUserFollowTable()
	{
		if (!isset($this->userFollowTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->userFollowTable = $sm->get('Auth\Model\UserFollowTable');
		}
		return $this->userFollowTable;
	}
        
	public function generateUsername($user_info) {
		 
		if (empty($user_info['username'])) {
			$user_info['username'] = $user_info['first_name'].$user_info['last_name'];
		}
		if (empty($user_info['username'])) {
			$user_info['username'] = preg_replace('/([^@]*).*/', '$1', $user_info['email']);
		}
		$validatorChain = new \Zend\Validator\ValidatorChain();
		$validator1 = new \Zend\Validator\NotEmpty();
		$validator2 = new \Zend\Validator\Db\NoRecordExists(
				array(
						'table' => 'user',
						'field' => 'username',
						'adapter' => $this->getController()->getServiceLocator()->get('db')
				)
		);
		$validatorChain->attach($validator1)->attach($validator2);
		 
		while (!$validatorChain->isValid($user_info['username'])) {
			$user_info['username'] = $user_info['username'].rand(0,9);
		}
		 
		return $user_info['username'];
	}
        
	public function getStylebookTable()
	{
		if (!isset($this->stylebookTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->stylebookTable = $sm->get('Application\Model\StylebookTable');
		}
		return $this->stylebookTable;
	}
        
        public function getTagTable()
	{
		if (!isset($this->tagTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->tagTable = $sm->get('Application\Model\TagTable');
		}
		return $this->tagTable;
	}
        
        public function getStylebookTagTable()
	{               
		if (!isset($this->stylebookTagTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->stylebookTagTable = $sm->get('Application\Model\StylebookTagTable');
		}
		return $this->stylebookTagTable;
	}
        
        public function getMediaTable()
	{
		if (!isset($this->mediaTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->mediaTable = $sm->get('Application\Model\MediaTable');
		}
		return $this->mediaTable;
	}
        
        public function getMediaTagTable()
	{
		if (!isset($this->mediaTagTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->mediaTagTable = $sm->get('Application\Model\MediaTagTable');
		}
		return $this->mediaTagTable;
	}
        
        public function getMediaStylebookTable()
	{
		if (!isset($this->mediaStylebookTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->mediaStylebookTable = $sm->get('Application\Model\MediaStylebookTable');
		}
		return $this->mediaStylebookTable;
	}
        
        public function getMediaLikeTable()
	{
		if (!isset($this->mediaLikeTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->mediaLikeTable = $sm->get('Application\Model\MediaLikeTable');
		}
		return $this->mediaLikeTable;
	}
        
        public function getStylebookLikeTable()
	{
		if (!isset($this->stylebookLikeTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->stylebookLikeTable = $sm->get('Application\Model\StylebookLikeTable');
		}
		return $this->stylebookLikeTable;
	}
        
        public function getMediaCommentTable()
	{
		if (!isset($this->mediaCommentTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->mediaCommentTable = $sm->get('Application\Model\MediaCommentTable');
		}
		return $this->mediaCommentTable;
	}
        
        public function getStylebookCommentTable()
	{
		if (!isset($this->stylebookCommentTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->stylebookCommentTable = $sm->get('Application\Model\StylebookCommentTable');
		}
		return $this->stylebookCommentTable;
	}
	
	public function getStateTable()
	{
		if (!isset($this->stateTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->stateTable = $sm->get('Application\Model\StateTable');
		}
		return $this->stateTable;
	}
	
	public function getUserRestoreTable()
	{
		if (!isset($this->userRestoreTable)) {
			$sm = $this->getController()->getServiceLocator();
			$this->userRestoreTable = $sm->get('Auth\Model\UserRestoreTable');
		}
		return $this->userRestoreTable;
	}
        
    public function getStylebookMedia($stylebookid, $mediaid = null, $items_count = null, $page_number = 1) {
                $result = array();
                $current = array();
                $medias = $this->getMediaTable()->getStylebookMedia($stylebookid, $items_count, $page_number);
                foreach($medias as $key=>$media) {
                    if($mediaid != null && $mediaid == $media['id']) {
                        $current[0] = $media;
                        continue;
                    }
                    $result[$key] = $media;
                }
                
                $result = array_merge($current, $result);
                return $result;
    }
        
    public function getPopularTags() {
                $results = array();
                foreach($this->tagTypes() as $tagType) {
                    $results[$tagType] = $this->getTagTable()->getPopularTags($tagType);
                }

                return $results;
    }
        
    public function getMediaTags($media_id) {
                $media_id = (int) $media_id;
                $tags = array();
                $results = array();
                if($media_id) {
                    $results = $this->getMediaTagTable()->getMediaTags($media_id);
                    $i = 0;
                    foreach($results as $result) {
                        $tags[$i]['id'] = $result['tag_id'];
                        $tags[$i]['name'] = $result['tag_name'];
                        $tags[$i]['type'] = $result['tag_type'];
                        $i++;
                    }
                }
                return $tags;
    }
        
    public function getMediaTmpTags(array $ids) {
                $tags = array();
                $results = array();
                if(!empty($ids)) {
                    $results = $this->getTagTable()->getTagsByIds($ids);
                    $i = 0;
                    foreach($results as $result) {
                        $tags[$i]['id'] = $result['id'];
                        $tags[$i]['name'] = $result['name'];
                        $tags[$i]['type'] = $result['type'];
                        $i++;
                    }
                }
                return $tags;
    }
    
    public function getCurrentSeasonId() {
    	
        $day = date("z");
		$YY = date("y");
        $spring_starts = date("z", strtotime("March 21"));
        $spring_ends   = date("z", strtotime("June 20"));

        $summer_starts = date("z", strtotime("June 21"));
        $summer_ends   = date("z", strtotime("September 22"));

        $autumn_starts = date("z", strtotime("September 23"));
        $autumn_ends   = date("z", strtotime("December 20"));
        
        $winter_starts = date("z", strtotime("December 21"));
        $winter_ends   = date("z", strtotime("March 20"));

        if( $day >= $spring_starts && $day <= $spring_ends ) :
               $season = "SPRING";
        elseif( $day >= $summer_starts && $day <= $summer_ends ) :
               $season = "SUMMER";
        elseif( $day >= $autumn_starts && $day <= $autumn_ends ) :
               $season = "FALL";
        else :
               $season = "WINTER";
        	   if ( $day >= 0 && $day <= $winter_ends) {
        	   		$YY = ($YY-1).'-'.$YY;
        	   } else {
        	   		$YY = $YY.'-'.($YY+1);
        	   }
        endif;
        
    	$season_name = $season.' '.$YY;
		$season_array = $this->getTagTable()->getSeasonTagByName($season_name)->toArray();
		
		if (empty($season_array[0])) {
			$season_array=$this->getTagTable()->getLastSeasonTag()->toArray();
		}
		return $season_array[0];
    }
    
	public function getUserStylebooksBySeason($user_id, $seasons) {
	 	
        $user_session = new Container('user');
		$stylebooks_by_season = null;
		foreach($seasons as $season) {

			$stylebooks=$this->getStylebookTable()->getUserStylebooksBySeason($user_id, $season['tid'])->toArray();
			 
			unset($stylebook_ids);
			 
			foreach ($stylebooks as $stylebook) {
				$stylebook_ids[]=$stylebook['sid'];
			}
			$stylebook_media_thumbnails = $this->getStylebookTable()->getStylebooksThumbnails($stylebook_ids)->toArray();
                        
                        $stylebook_likes_ids = $this->getStylebookLikeTable()->getStylebookIdsByUser($user_session->id);
			 
			foreach ($stylebooks as $stylebook) {
				 
				if (isset($stylebook['sid'])&&isset($stylebook_media_thumbnails)) {
					$thumbnail_media = $this->getStylebookMediaThumbnailFromArray($stylebook['sid'], $stylebook_media_thumbnails);
					$stylebook['media_title']=$thumbnail_media['title'];
				} else {
					$thumbnail_media = false;
				}
				 
				if (isset($thumbnail_media['mid']) && isset($thumbnail_media['added_on'])) {
					$stylebook['thumbnail']=$this->getMediaImage($thumbnail_media['mid'], $thumbnail_media['added_on'], 'm');
				} else {
					$stylebook['thumbnail']=$this->getMediaImage(NULL, NULL, 'm');
				}
                                
                                if(in_array($stylebook['sid'], $stylebook_likes_ids)){
                                        $stylebook['is_liked'] = true;
                                } else {
                                        $stylebook['is_liked'] = false;
                                }
				 
				$stylebooks_by_season[$season['tid']][]=$stylebook;
			}
			 
		}
//		 echo "<pre>"; var_dump($stylebooks_by_season); exit;
		return $stylebooks_by_season;
			
	}
	
	public function getTopStylebooks($user, $limit) {
		$stylebooks=$this->getStylebookTable()->getTopStylebooks($limit)->toArray();
		$stylebook_ids=array();
		foreach ($stylebooks as $stylebook) {
			$stylebook_ids[]=$stylebook['sid'];
		}
		$stylebook_media_thumbnails = $this->getStylebookTable()->getStylebooksThumbnails($stylebook_ids)->toArray();
		$stylebook_likes_ids = $this->getStylebookLikeTable()->getStylebookIdsByUser($user->id);
		
		foreach ($stylebooks as $key=>$stylebook) {
				
			if (isset($stylebook['sid'])&&isset($stylebook_media_thumbnails)) {
				$thumbnail_media = $this->getStylebookMediaThumbnailFromArray($stylebook['sid'], $stylebook_media_thumbnails);
				$stylebooks[$key]['media_title']=$thumbnail_media['title'];
			} else {
				$thumbnail_media = false;
			}
				
			if (isset($thumbnail_media['mid']) && isset($thumbnail_media['added_on'])) {
				$stylebooks[$key]['thumbnail']=$this->getMediaImage($thumbnail_media['mid'], $thumbnail_media['added_on'], 'm');
			} else {
				$stylebooks[$key]['thumbnail']=$this->getMediaImage(NULL, NULL, 'm');
			}
		
			if(in_array($stylebook['sid'], $stylebook_likes_ids)){
				$stylebooks[$key]['is_liked'] = true;
			} else {
				$stylebooks[$key]['is_liked'] = false;
			}
		}
		
		return $stylebooks;
	}
	
	public function getTopBrands($user, $limit) {
		$brands=$this->getUserTable()->getTopBrands($limit)->toArray();
		
		$brand_ids=array();
		foreach ($brands as $brand) {
			$brand_ids[]=$brand['id'];
		}
		$brand_media_thumbnails = $this->getUserTable()->getUserThumbnails($brand_ids)->toArray();

		foreach ($brands as $key=>$brand) {
			$brand_object = new User();
			$brand_object->exchangeArray($brand);
			if (isset($brand['id'])&&isset($brand_media_thumbnails)) {
				$thumbnail_media = $this->getUserMediaThumbnailFromArray($brand['id'], $brand_media_thumbnails);
				$brand_object->media_title=$thumbnail_media['title'];
			} else {
				$thumbnail_media = false;
			}
	
			if (isset($thumbnail_media['mid']) && isset($thumbnail_media['added_on'])) {
				$brand_object->thumbnail=$this->getMediaImage($thumbnail_media['mid'], $thumbnail_media['added_on'], 'm');
			} else {
				$brand_object->thumbnail=$this->getMediaImage(NULL, NULL, 'm');
			}
			$brands[$key] = $brand_object;
		}
		
		return $brands;
	}

	public function getUserMediaThumbnailFromArray($user_id, $thumbnails) {
	
		foreach ($thumbnails as $key=>$thumbnail) {
			if ($thumbnail['user_id']==$user_id) {
				return $thumbnail;
				break;
			}
		}
	}
	
	public function getMostLikedMedia($limit) {
		$medias = $this->getMediaTable()->getMostLikedMedia($limit)->toArray();
		return $medias;
	}
	
	public function getLatestMedia($limit) {
		$medias = $this->getMediaTable()->getLatestMedia($limit)->toArray();
		return $medias;
	}
	
	public function getStylebookMediaThumbnailFromArray($stylebook_id, $thumbnails) {

		foreach ($thumbnails as $key=>$thumbnail) {
			if ($thumbnail['sid']==$stylebook_id) {
				return $thumbnail;
				break;
			}
		}
	}
	
	public function getStates() {
		$db_states=$this->getStateTable()->fetchAll();
                $states = array();
		foreach($db_states as $state) {
			$states[$state['id']]=$state['name'];
		}
		return $states;
	}
	
	public function prepareStylebookMediaCount($media_count) {
		$mcount['image']=0;
		$mcount['video']=0;
		if (is_array($media_count)) {
			foreach ($media_count as $mc) {
				if ($mc['type']=='image') {
					$mcount['image']=$mc['mcount'];
				} elseif ($mc['type']=='video') {
					$mcount['video']=$mc['mcount'];
				}
			}
		}
		return $mcount;
	}
	
	public function isUserViewable($user_subject, $viewer) {
		
		if (empty($user_subject)) {
			return false;
		} 
			
		if(isset($viewer->user_type)) {
			if($viewer->user_type=='admin') {
				return true;
			}
		}
		
		if(!isset($user_subject->active)) {
			return false;
		} elseif (($user_subject->user_type=="stylist") && !$user_subject->approved) {
			return false;
		} else {
			return true;
		}
	}
	
	public function isStylebookViewable($stylebook, $stylebookOwner, $stylebookViewer) {
		if (empty($stylebook)) {
			return false;
		} 
		
		if(isset($stylebookViewer->user_type)) {
			if($stylebookViewer->user_type=='admin') {
				return true;
			}
		}
		
		if (!isset($stylebookOwner->active)) {
			return false;
		}elseif (!empty($stylebookViewer) && ($stylebookOwner->id==$stylebookViewer->id)) {
			return true;
		} elseif (($stylebookOwner->user_type=="stylist") && !$stylebookOwner->approved) {
			return false;
		} else {
			return true;
		}
	}
	
	public function isMediaViewable($media, $mediaOwner) {
		if (empty($media)) {
			return false;
		} elseif (($mediaOwner->user_type=="stylist") && !$mediaOwner->approved) {
			return false;
		} else {
			return true;
		}
	}

        
        public function canEditStylebook($stylebook) {
                $user_session = new Container('user');
                $user = $user_session->user;
		if (empty($stylebook)) {
			return false;
		} elseif (($user->user_type=="stylist") && !$user->approved) {
			return false;
		} elseif($stylebook['user_id'] == $user->id && $user->user_type=="stylist") {
			return true;
		}
		
	}


    public function canEditMedia($media) {
                $user_session = new Container('user');
                $user = $user_session->user;
		if (empty($media)) {
			return false;
		} elseif (($user->user_type=="stylist") && !$user->approved) {
			return false;
		} elseif($media->user_id == $user->id && $user->user_type=="stylist") {
			return true;
		}
	}

	public function browseMediaByParameters($explore_parameters, $items_count, $page_number) {
		$medias = $this->getMediaTable()->getFilteredMediaByTag($explore_parameters, $items_count, $page_number);
		return $medias;
	} 
	
	public function searchMediaByParameters($searchparam, $filterparam, $items_count, $page_number) {
		$medias = $this->getMediaTable()->getSearchedMedia($searchparam, $filterparam, $items_count, $page_number);
		return $medias;
	}
	
	public function prepareFilterParameters($filterparam) {
		$filterparam=trim($filterparam, '/');
		$filterparam_string = $filterparam;
		$filterparam=explode('/', $filterparam);
		$filterparam_array=array();
		foreach ($filterparam as $param) {
			if(!empty($param)) {
				$filterparam_array[]=urldecode($param);
			}
		}
		return array('string'=>$filterparam_string, 'array'=>$filterparam_array);
	}
	
	public function prepareSearchParameters($searchparam) {
		$searchparam=trim(trim($searchparam, '/'), ' ');
		$searchpram_string = $searchparam;
		$searchparam=urldecode($searchparam);
		$searchparam=explode(' ', $searchparam);
		$searchparam_array=array();
		foreach ($searchparam as $param) {
			if(!empty($param)) {
				$searchparam_array[]=$param;
			}
		}
		
		return array('string'=>$searchpram_string, 'array'=>$searchparam_array);
	}
	
	public function sendWelcomeEmail($user) {
		$config = $this->getController()->getServiceLocator()->get('Config');
		$contactology= new Contactology($config['contactology']['api_key']);
		
		if(isset($user)){
			$contactData = array('email'=>$user->email, 
					'first_name'=>$user->first_name, 
					'last_name'=>$user->last_name,
					'username'=>$user->username,
					'user_type'=>$user->user_type,
					);
			$optionalParameters=array('updateCustomFields'=>true);
			if($user->user_type=="stylist") {
				$result = $contactology->List_Import_Contacts( $config['contactology']['stylists_list_id'], 'Stylists', array($contactData), $optionalParameters);
			} else {
				$result = $contactology->List_Import_Contacts( $config['contactology']['users_list_id'], 'Reg Users', array($contactData), $optionalParameters);
			}
			
			$result['email']=$user->email;
			$result = print_r($result, true);
			file_put_contents('./log/contactology.log',date("Y-m-d H:i:s", time())."\n".$result."\n",FILE_APPEND);			
			
			//send email to admin if stylist is created
			$mail = new \Zend\Mail\Message();
			$mail->setBody(	'New stylist account created: '.$user->username.' ('.$user->email.') ');
			$mail->setFrom($config['email']['noreply']);
			$mail->addTo($config['email']['admin']);
			$mail->setSubject('New stylist account created');
			$transport = new \Zend\Mail\Transport\Sendmail();
			$transport->send($mail);

		}
	}
	
	public function sendDeactivationEmail($user) {
		$config = $this->getController()->getServiceLocator()->get('Config');
		$contactology= new Contactology($config['contactology']['api_key']);
	
		if(isset($user)){
			$contactData = array('email'=>$user->email,
					'first_name'=>$user->first_name,
					'last_name'=>$user->last_name,
					'username'=>$user->username,
					'user_type'=>$user->user_type,
			);
			$optionalParameters=array('updateCustomFields'=>true);
			$result = $contactology->List_Import_Contacts( $config['contactology']['deactivation_list_id'], 'Deactivation', array($contactData), $optionalParameters);

				
			$result['email']=$user->email;
			$result = print_r($result, true);
			file_put_contents('./log/contactology_deactivation.log',date("Y-m-d H:i:s", time())."\n".$result."\n",FILE_APPEND);
		}
	}
	
	public function removeFromDeactivatedList($user) {
		$config = $this->getController()->getServiceLocator()->get('Config');
		$contactology= new Contactology($config['contactology']['api_key']);
	
		if(isset($user)){
			return $result = $contactology->List_Unsubscribe( $config['contactology']['deactivation_list_id'], $user->email);
		}
		
	}
	
}
