<?php
namespace Application\Form;

use Zend\Form\Form;

class EditEmailForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('editusername');
        //$this->setAttribute('method', 'post');
        //$this->setAttribute('action', 'signup');

        $this->add(array(
            'name' => 'email',
            'type' => 'Text',
            'attributes' => array (
        		'class' => 'email input',
        		'placeholder' => 'Email',
        	),
        ));
        $this->add(array(
        		'name' => 'email2',
        		'type' => 'Text',
        		'attributes' => array (
        				'class' => 'email input',
        		),
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