<?php
namespace Application\Form;

use Zend\InputFilter;
use Zend\Form\Form;
use Zend\Form\Element;

class MediaTagsForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('media_tags');
        $this->setAttributes(array('method'=>'post', 'class'=>'media-tags'));
        $this->setInputFilter($this->createInputFilter());
        
        $this->add(array(
            'name' => 'popular',
            'attributes' => array(
                'type'  => 'hidden',
                'id' => 'popular',
            ),
            'filters' => array (
                array (
                    'name' => 'StripTags'
                ),
                array (
                    'name' => 'StringTrim'
                )
            ),
        ));
        $this->add(array(
            'name' => 'brand',
            'attributes' => array(
                'type'  => 'text',
                'id' => 'brand',
            ),
            'filters' => array (
                array (
                    'name' => 'StripTags'
                ),
                array (
                    'name' => 'StringTrim'
                )
            ),
        ));
        $this->add(array(
            'name' => 'category',
            'attributes' => array(
                'type'  => 'text',
                'id' => 'category',
            ),
            'filters' => array (
                array (
                    'name' => 'StripTags'
                ),
                array (
                    'name' => 'StringTrim'
                )
            ),
        ));
        $this->add(array(
            'name' => 'style',
            'attributes' => array(
                'type'  => 'text',
                'id' => 'style',
            ),
            'filters' => array (
                array (
                    'name' => 'StripTags'
                ),
                array (
                    'name' => 'StringTrim'
                )
            ),
        ));
        $this->add(array(
            'name' => 'season',
            'attributes' => array(
                'type'  => 'text',
                'id' => 'season',
            ),
            'filters' => array (
                array (
                    'name' => 'StripTags'
                ),
                array (
                    'name' => 'StringTrim'
                )
            ),
        ));
        $this->add(array(
            'name' => 'color',
            'attributes' => array(
                'type'  => 'text',
                'id' => 'color',
            ),
            'filters' => array (
                array (
                    'name' => 'StripTags'
                ),
                array (
                    'name' => 'StringTrim'
                )
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Done Tagging',
                'id' => 'submitbutton',
                'class' => 'login',
            ),
        ));
    }
    
    public function createInputFilter()
    {
        $inputFilter = new InputFilter\InputFilter();

        $brand = new InputFilter\Input('brand');
        $category = new InputFilter\Input('category');
        $style = new InputFilter\Input('style');
        $season = new InputFilter\Input('season');
        $color = new InputFilter\Input('color');
        
        $brand->setRequired(true);
        $category->setRequired(true);
        $style->setRequired(true);
        $season->setRequired(true);
        $color->setRequired(true);
        
        $validator = new \Zend\Validator\NotEmpty();
        $brand->getValidatorChain()
            ->addValidator($validator);
        $category->getValidatorChain()
            ->addValidator($validator);
        $style->getValidatorChain()
            ->addValidator($validator);
        $season->getValidatorChain()
            ->addValidator($validator);
        $color->getValidatorChain()
            ->addValidator($validator);
        
        $inputFilter->add($brand)
                    ->add($category)
                    ->add($style)
                    ->add($season)
                    ->add($color);
        
        return $inputFilter;
    }
}