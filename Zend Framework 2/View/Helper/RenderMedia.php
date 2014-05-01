<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class RenderMedia extends AbstractHelper
{
	public function __invoke($media, $stylebook=null, $owner=null, $viewer=null, $searchparam=null, $filterparam=null, $gen_photo=null, $page_number=null)
	{
		if ($gen_photo) {
			$image_size = 'm';
		} else {
			$image_size = 's';
		}
		?>
		<div class="photo <?php echo (($gen_photo)? 'gen-photo':'');?>" <?php echo (isset($media['msid'])? 'id ="media-stylebook'.$media['msid'].'"' : '') ; ?>>
		<!-- <h2 class="photo-title">
		<div class="media-title pull_left"><?php echo $media['title']; ?></div>
		-->
			<h2 class="photo-title">
				<div class="media-title pull_left"><?php echo $media['title']; ?></div>
				<?php if(isset($owner->id) && isset($viewer->id) && isset($media['msid'])):?>
					<?php if ($owner->id==$viewer->id):?>
						<a class="photo-title-side pull_right switch ajax-load-delete" 
													href="<?php echo $this->view->url('stylebook/deleteStylebookMedia', array('msid'=>$media['msid'])); ?>"
                                                    gumby-trigger="#modal3"
                                                    ajax-src="<?php echo $this->view->url('stylebook/deleteStylebookMedia', array('msid'=>$media['msid'])); ?>">Delete</a>
                    <?php endif;?>
                <?php endif; ?>
			</h2>
			<?php  if(isset($stylebook)): ?>
				<a class="photo-img" href="<?php echo $this->view->url('stylebook/preview', array('stylebookid'=>$stylebook['id'],'mediaid'=>$media['id'])); ?>" > <img src="<?php echo $this->view->basePath($this->view->plugin->getMediaImage($media['id'], $media['added_on'], $image_size)); ?>" alt="" title="" /> </a>
			<?php  elseif(isset($searchparam) || isset($filterparam)):?>
				<?php 
                                $preview_array = array('mediaid'=>$media['id']);
                                $preview_array = (!empty($searchparam))?array_merge($preview_array, array('searchparam'=>$searchparam)):$preview_array;
                                $preview_array = (!empty($filterparam))?array_merge($preview_array, array('filterparam'=>$filterparam)):$preview_array;
                                $preview_array = (!empty($page_number))?array_merge($preview_array, array('pagenumber'=>$page_number)):$preview_array;
								$href = $this->view->url('preview/profile', $preview_array);
                                //$href = '/preview'.((!empty($searchparam))? '/search/'.$searchparam : '').((!empty($filterparam))? '/filter/'.$filterparam : '').'/';
				?>
				<a class="photo-img" href="<?php echo $href?>" > <img src="<?php echo $this->view->basePath($this->view->plugin->getMediaImage($media['id'], $media['added_on'], $image_size)); ?>" alt="" title="" /> </a>
			<?php  else:?>
				<a class="photo-img" href="#" > <img src="<?php echo $this->view->basePath($this->view->plugin->getMediaImage($media['id'], $media['added_on'], $image_size)); ?>" alt="" title="" /> </a>
			<?php endif;?>
			<div class="fig-activity"> 
				<?php if(isset($viewer->id)) { ?>	
					<a class="switch ajax-load-add inactive" href="#" style="text-decoration:none;" title="Add this to your <?php echo $this->view->StyleOrStylebook($viewer->user_type)?>"
		                                                    gumby-trigger="#modal2"
		                                                    ajax-src="<?php echo $this->view->url('media/popupmedia', array('mediaid'=>$media['id'])); ?>"> <i class="icon2-plus"> <?php echo $media['adds_count']?> </i></a> 
		            <a class="switch ajax-load-comment inactive" href="#" style="text-decoration:none;" title="View comments"
		                                                    gumby-trigger="#modal3"
		                                                    ajax-src="<?php echo $this->view->url('media/popupcomment', array('mediaid'=>$media['id'])); ?>"> <i class="icon2-comment"> <?php echo $media['comments_count']; ?> </i></a>
		        <?php } else { ?>
		        	<a class="switch ajax-load-small-login-popup inactive" href="#" style="text-decoration:none;" title="Add this to your <?php echo $this->view->StyleOrStylebook($viewer->user_type)?>"
		                                                    gumby-trigger="#modal4"
		                                                    ajax-src="<?php echo $this->view->url('auth/default', array('controller'=>'auth', 'action'=>'small-login-popup')); ?>"> <i class="icon2-plus"> <?php echo $media['adds_count']?> </i></a> 
		            <a class="switch ajax-load-small-login-popup inactive" href="#" style="text-decoration:none;" title="View comments"
		                                                    gumby-trigger="#modal4"
		                                                    ajax-src="<?php echo $this->view->url('auth/default', array('controller'=>'auth', 'action'=>'small-login-popup')); ?>"> <i class="icon2-comment"> <?php echo $media['comments_count']; ?> </i></a>
		        <?php } //end if(isset($viewer->id))?>         
		                                        
				<?php if(in_array($media['id'], $viewer->media_likes_ids)) { ?>
				<a class="switch ajax-load-like active" href="javascript:" style="text-decoration:none;" title="Click to Unlike"
		                                                   ajax-src="<?php echo $this->view->url('media/like', array('mediaid'=>$media['id'], 'type'=>'unlike')); ?>"> <i class="icon2-heart" style=""> <?php echo $media['likes']; ?> </i></a>
				<?php } else { ?>
				<a class="switch ajax-load-like inactive" href="#" style="text-decoration:none;" title="Click to Like"
		                                                    ajax-src="<?php echo $this->view->url('media/like', array('mediaid'=>$media['id'])); ?>"> <i class="icon2-heart"> <?php echo $media['likes']; ?> </i></a>
				<?php } ?>
			</div>
		</div>
		<?php 
	}
}