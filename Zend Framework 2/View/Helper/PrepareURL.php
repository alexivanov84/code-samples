<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PrepareURL extends AbstractHelper
{
	public function __invoke($url)
	{
		if (strpos($url, 'http') !== false) {
			return $url;	
		} else {
			return 'http://'.$url;
		}
		
	}
}