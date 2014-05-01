<?php
namespace Application\Form;

use Zend\Form\Form;

class MediaForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('media');
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type'  => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'title',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'set-val',
                'placeholder' => 'Bohemian Fall Wear',
            ),
        ));
        $this->add(array(
            'name' => 'comment',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'set-val',
                'value' => '',
            ),
        ));
        
        $this->add(array(
            'name' => 'media_type_id',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'set-val',
            ),
        ));
    }
}