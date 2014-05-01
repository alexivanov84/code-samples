<?php
namespace Application\Form;

use Zend\Form\Form;

class StylebookForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('stylebook');
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
                'placeholder' => '',
            ),
        ));
        $this->add(array(
            'name' => 'description',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'set-val',
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'tag_id',
            'options' => array(
                'value_options' => array('0'=>'Select tag'),
            ),
            'attributes' => array(
                'class' => 'styled',
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'create style',
                'id' => 'submitbutton',
                'class' => 'btn2',
            ),
        ));
        /*Added by Isabelle: 06/26
        Because the previous submit btn was not working on the view.
        The call $this->formSubmit($form->get('submit')) was showing the value "edit style" and not "create style"
        btw: we should take out the word "style" to make it more generic. Because it is going to be "style" for reg. users but "stylebooks" for Brands and Stylists
        */
        $this->add(array(
            'name' => 'saveStyle',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'save style',
                'id' => 'submitbutton',
                'class' => 'btn2',
            ),
        ));         
        $this->add(array(
            'name' => 'reset',
            'attributes' => array(
                'type'  => 'reset',
                'value' => 'Cancel',
                'class' => 'btn2',
            ),
        ));
        /*Added by Isabelle: 06/26
        Added this but the deleteAction is missing I think.
        Could you guys tie this button to a delete style action if it doesn't exist yet? Thanks.
        They want this action to open a popup window: "Are you sure you want to delete this style/book? This action cannot be undone. Yes / No Buttons"
        */
        $this->add(array(
            'name' => 'delete',
            'attributes' => array(
                'type'  => 'delete',
                'value' => 'Delete',
                'class' => 'btn2',
            ),
        ));        
    }
}