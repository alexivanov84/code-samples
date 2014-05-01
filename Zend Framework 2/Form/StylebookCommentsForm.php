<?php
namespace Application\Form;

use Zend\Form\Form;

class StylebookCommentsForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('stylebook_comment');
        $this->setAttributes(array('method'=>'post', 'class'=>'popup-comment'));
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'user_id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'stylebook_id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'comment',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input textarea',
                'rows'  => '5',
                'id' => 'comment',
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Post Comment',
                'id' => 'submitbutton',
                'class' => 'login',
            ),
        ));
        $this->add(array(
            'name' => 'reset',
            'attributes' => array(
                'type'  => 'reset',
                'value' => 'Cancel',
                'id' => 'cancel_comment',
            ),
        ));
    }
}