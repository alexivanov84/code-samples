<?php
namespace Application\Form;

use Zend\Form\Form;

class EditProfileForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('editprofile');
        //$this->setAttribute('method', 'post');
        //$this->setAttribute('action', 'signup');

        $this->add(array(
            'name' => 'first_name',
            'type' => 'Text',
        ));

        $this->add(array(
        		'name' => 'last_name',
        		'type' => 'Text',
        ));
        
        $this->add(array(
        		'name' => 'about',
        		'type' => 'Text',
        ));
        
        $this->add(array(
        		'name' => 'city',
        		'type' => 'Text',
        ));
        
        $this->add(array(
        		'name' => 'zip',
        		'type' => 'Text',
        ));
        
        $this->add(array(
        		'name' => 'state_id',
        		'type' => 'Select'
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