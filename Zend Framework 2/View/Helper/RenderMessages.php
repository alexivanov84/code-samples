<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Application\Model\Message;

class RenderMessages extends AbstractHelper
{
	public function __invoke(Message $messages)
	{
		if ($messages) {
			
			echo '<ul class="messages">';
			
			if($messages->success) {	
				foreach ($messages->success as $msgscs) {
					echo '<li class="success label">' . $msgscs . '</li>';
				}
			}
			
			if($messages->warning) {
				foreach ($messages->warning as $msgscs) {
					echo '<li class="warning label">' . $msgscs . '</li>';
				}
			}
			
			if($messages->error) {
				foreach ($messages->error as $msgscs) {
					echo '<li class="danger label">' . $msgscs . '</li>';
				}
			}
			
			echo '</ul>';
			
		}
	}
}