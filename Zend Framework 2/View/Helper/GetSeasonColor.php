<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class GetSeasonColor extends AbstractHelper
{
	public function __invoke($season)
	{
		if (isset($season)) {
			
			if (strpos(trim(strtolower($season)), 'fall')!==false) {
				return 'color1';
			}
			
			if (strpos(trim(strtolower($season)), 'summer')!==false) {
				return 'color2';
			}
			
			if (strpos(trim(strtolower($season)), 'spring')!==false) {
				return 'color3';
			}
			
			if (strpos(trim(strtolower($season)), 'winter')!==false) {
				return 'color4';
			}
			
		}
	}
}