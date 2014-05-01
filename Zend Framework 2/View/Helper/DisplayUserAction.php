<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class DisplayUserAction extends AbstractHelper
{
	public function __invoke()
	{
		return $this;
	}
	
	public function follow($follower, $followed) {
		if (isset($follower->id)) {
          if($follower->id != $followed->id){ 
            if(in_array($followed->id, $follower->followed_ids)){ 
              ?> <a href="#" class="follow-link switch ajax-load-follow" 
                                     ajax-src="<?php echo $this->view->url('user/follow', array('userid'=>$followed->id, 'type'=>'unfollow')); ?>">
                                      <i class="icon2-forward" title="Follow this user">&nbsp;Following</i></a> 
            <?php } else { ?>
                 <a href="#" class="follow-link switch ajax-load-follow" 
                                     ajax-src="<?php echo $this->view->url('user/follow', array('userid'=>$followed->id)); ?>">
                                      <i class="icon2-forward" title="Follow this user">&nbsp;Follow</i></a>
            <?php } ?>
          <?php } ?>
        <?php } else { ?>
              <a href="#" class="follow-link switch ajax-load-small-login-popup" 
                    				gumby-trigger="#modal4"
                                     ajax-src="<?php echo $this->view->url('auth/default', array('controller'=>'auth', 'action'=>'small-login-popup')); ?>">
                                      <i class="icon2-forward" title="Follow this user">&nbsp;Follow</i></a>
        <?php }
	}
	
	public function stylebookComment($user, $stylebook) {	
		if (isset($user->id)) {?>
        	<a class="switch ajax-load-comment inactive" href="#" style="text-decoration:none;" gumby-trigger="#modal3" ajax-src="<?php echo $this->view->url('stylebook/popupcomment', array('stylebookid'=>$stylebook['sid'])); ?>">
        		<i class="icon2-comment"  title="Comment"> <?php echo $stylebook['comments_count']; ?> </i>
        	</a>
       <?php } else { ?>
       		<a href="/login" class="follow-link switch ajax-load-small-login-popup" gumby-trigger="#modal4" ajax-src="<?php echo $this->view->url('auth/default', array('controller'=>'auth', 'action'=>'small-login-popup')); ?>">
				<i class="icon2-comment"  title="Comment"> <?php echo $stylebook['comments_count']; ?> </i>
			</a>
       <?php } 
	}
	
	public function stylebookLike($stylebook) {	
		if($stylebook['is_liked']) { ?>
        	<a class="switch ajax-load-like active" href="javascript:" style="text-decoration:none;" ajax-src="<?php echo $this->view->url('stylebook/like', array('stylebookid'=>$stylebook['sid'], 'type'=>'unlike')); ?>">
            	<i class="icon2-heart" style=""  title="Like this"> <?php echo $stylebook['likes']; ?> </i>
            </a>
        <?php } else { ?>
        	<a class="switch ajax-load-like inactive" href="#" style="text-decoration:none;" ajax-src="<?php echo $this->view->url('stylebook/like', array('stylebookid'=>$stylebook['sid'])); ?>">
            	<i class="icon2-heart"  title="Like this"> <?php echo $stylebook['likes']; ?> </i>
            </a>
       	<?php }
	}
}

