<?php
namespace Application\Form;

use Zend\Form\Form;

class EditNotificationsForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('editusername');
        //$this->setAttribute('method', 'post');
        //$this->setAttribute('action', 'signup');

        $this->add(array(
           		'name' => 'ntf_comments',
            	'type' => 'Checkbox',
        ));
        
        $this->add(array(
        		'name' => 'ntf_follows',
        		'type' => 'Checkbox',
        ));
        
        $this->add(array(
        		'name' => 'ntf_likes',
        		'type' => 'Checkbox',
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Sign up',
                'id' => 'submitbutton',
            ),
        ));
    }
}