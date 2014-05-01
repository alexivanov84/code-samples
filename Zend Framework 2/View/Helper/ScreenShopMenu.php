<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ScreenShopMenu extends AbstractHelper
{
    protected $menu_short;

    public function __invoke()
    {
    ?>
       		<div id="sub-nav1" class="sub-nav ">
				<div class="row">
					<div class="ten centered columns sub-menu-groups-wrap">
						<div class="six columns clearfix sub-menu-group">
							<h2>brands</h2>
							<?php  
								$k=0;
								if (isset($this->screen_shop_menu)){
									foreach ($this->screen_shop_menu['brand'] as $brand_tag_id=>$brand_tag) {
										if (!( $k % 6)) {
											if ($k==0) {
												echo '<ul class="sub-menu">';
											} else {
												echo '</ul><ul class="sub-menu">';
											}
										}
										echo '<li><a href="/'.urlencode($brand_tag['tag_name']).'/" '.(($brand_tag['tag_usage']==0)? 'title="coming soon..." class="inactive"' : '').'>'.$brand_tag['tag_name'].'</a></li>';
										$k++;
									}
									echo '<li><a href="/navigation/getsubmenu/brand/" ajax-src="/navigation/getsubmenu/brand" class="sub-menu-more switch active ajax-load-submenu">and more...</a></li>';
									echo '</ul>';
								}
							?>
						</div>
						<div class="three columns clearfix sub-menu-group">
							<h2>styles</h2>
							<?php 
								$k=0;
								if (isset($this->screen_shop_menu)){
									foreach ($this->screen_shop_menu['style'] as $style_tag_id=>$style_tag) {
										if (!( $k % 6)) {
											if ($k==0) {
												echo '<ul class="sub-menu">';
											} else {
												echo '</ul><ul class="sub-menu">';
											}
										}
										echo '<li><a href="/'.urlencode($style_tag['tag_name']).'/" '.(($style_tag['tag_usage']==0)? 'title="coming soon..." class="inactive"' : '').'>'.$style_tag['tag_name'].'</a></li>';
										$k++;
									}
									echo '<li><a href="/navigation/getsubmenu/style" ajax-src="/navigation/getsubmenu/style" class="sub-menu-more switch active ajax-load-submenu">and more...</a></li>';
									echo '</ul>';
								}
							?>
						</div>
						<div class="three columns clearfix sub-menu-group">
							<h2>categories</h2>
							<?php 
								$k=0;
								if (isset($this->screen_shop_menu)){
									foreach ($this->screen_shop_menu['category'] as $category_tag_id=>$category_tag) {
										if (!( $k % 6)) {
											if ($k==0) {
												echo '<ul class="sub-menu">';
											} else {
												echo '</ul><ul class="sub-menu">';
											}
										}
										echo '<li><a href="/'.urlencode($category_tag['tag_name']).'/" '.(($category_tag['tag_usage']==0)? 'title="coming soon..." class="inactive"' : '').'>'.$category_tag['tag_name'].'</a></li>';
										$k++;
									}
									echo '<li><a href="/navigation/getsubmenu/category" ajax-src="/navigation/getsubmenu/category" class="sub-menu-more switch active ajax-load-submenu">and more...</a></li>';
									echo '</ul>';
								}
							?>
						</div>
					</div>
					<div class="row">
						<div class="twelve centered columns sub-close-wrap">
							<a gumby-trigger="|#subNav1" class="switch sub-nav-close"
							href="#"></a>
						</div>
					</div>					
				</div>
			</div>
       <?php 
    }
    
    public function prepareScreenShopMenu($sm) {
    	$menu_tag_types=array('brand','style','category');
    	$menu_tags = $this->getTagTable($sm)->getTopMenuTags($menu_tag_types)->toArray();
    	foreach ($menu_tags as $tag) {
    		$menu_all[$tag['tag_type']][$tag['id']]['tag_name']=$tag['tag_name'];
    		$menu_all[$tag['tag_type']][$tag['id']]['tag_score']=$tag['tag_score'];
    		$menu_all[$tag['tag_type']][$tag['id']]['tag_usage']=$tag['tag_usage'];
    	}
    
    	$menu_short['brand']=array_slice($menu_all['brand'], 0, 18, true);
    	$menu_short['style']=array_slice($menu_all['style'], 0, 11, true);
    	$menu_short['category']=array_slice($menu_all['category'], 0, 11, true);
    
    	//var_dump($menu_short);
    	//TODO finish screen shop full menu
    	//TODO enable caching for the menu
    	$this->screen_shop_menu= $menu_short;
    }
    
    private function getTagTable($sm) {
    	return $sm->getServiceLocator()->get('Application\Model\TagTable');
    }
}