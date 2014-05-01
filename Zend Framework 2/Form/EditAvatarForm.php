<?php
namespace Application\Form;

use Zend\Form\Form;

class EditAvatarForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('editavatar');
        //$this->setAttribute('method', 'post');
        //$this->setAttribute('action', 'signup');

        
        $this->add(array(
        		'name' => 'avatar',
        		'type' => 'file',
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