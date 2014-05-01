<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class StyleOrStylebook extends AbstractHelper
{
	public function __invoke($user_type)
	{
		if ($user_type=='user') {
			return 'Style';
		} else {
			return 'Stylebook';
		}
	}
}