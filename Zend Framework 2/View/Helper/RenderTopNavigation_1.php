<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;  
use Zend\ServiceManager\ServiceLocatorAwareInterface;  
use Zend\ServiceManager\ServiceLocatorInterface;  

class RenderTopNavigation_1 extends AbstractHelper implements ServiceLocatorAwareInterface  
{
	protected $sm;
	protected $serviceLocator;
	
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
		return $this;
	}
	
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}
	
	public function __construct() {
		// first one gives access to other view helpers  
		//$helperPluginManager = $this->getServiceLocator();  
		// the second one gives access to... other things.  
		//$this->sm = $helperPluginManager->getServiceLocator(); 
		$this->sm = $this->getServiceLocator(); 
		//var_dump($this->sm);
		
		//$plugin = $this->sm->get('Application\Controller\Plugin\HelperPlugin');
	}
}