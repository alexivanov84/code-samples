<?php
namespace Application\Form;

use Zend\Form\Form;

class EditSocialForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('editsocial');
        //$this->setAttribute('method', 'post');
        //$this->setAttribute('action', 'signup');

        $this->add(array(
	            'name' => 'website_url',
	            'type' => 'Url',
        ));

        $this->add(array(
        		'name' => 'facebook_url',
        		'type' => 'Url',
        ));
        
        $this->add(array(
        		'name' => 'twitter_url',
        		'type' => 'Url',
        ));
        
        $this->add(array(
        		'name' => 'google_url',
        		'type' => 'Url',
        ));
        
        $this->add(array(
        		'name' => 'blog_url',
        		'type' => 'Url',
        ));
        
        $this->add(array(
        		'name' => 'other_url_01',
        		'type' => 'Url'
        ));
        
        $this->add(array(
        		'name' => 'other_url_02',
        		'type' => 'Url'
        ));
        
        $this->add(array(
        		'name' => 'instagram_url',
        		'type' => 'Url'
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