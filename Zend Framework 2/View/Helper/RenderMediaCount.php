<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class RenderMediaCount extends AbstractHelper
{
	public function __invoke($type, $count)
	{
		if ($type == 'image' && $count!=1) {
			return $count.' Photos';
		} elseif($type == 'image' && $count==1) {
			return $count.' Photo';
		} elseif($type == 'video' && $count!=1) {
			return $count.' Videos';
		} elseif($type == 'video' && $count==1) {
			return $count.' Video';
		}
	}
}