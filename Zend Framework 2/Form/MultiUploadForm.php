<?php

namespace Application\Form;

use Zend\InputFilter;
use Zend\Form\Form;
use Zend\Form\Element;

class MultiUploadForm extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);
        $this->addElements();
        $this->setInputFilter($this->createInputFilter());
    }

    public function addElements()
    {
        // File Input
        $file = new Element\File('file');
        $file
            ->setLabel('Multi-File Input')
            ->setAttributes(array('multiple' => true, 'class'=>'media_file'));
        $this->add($file);

        // Hidden Input
//        $hidden = new Element\Hidden('stylebook_id');
//        $hidden->setAttributes(array('value'=>0));
//        $this->add($hidden);
    }

    public function createInputFilter()
    {
        $inputFilter = new InputFilter\InputFilter();

        // File Input
        $file = new InputFilter\FileInput('file');
        $file->setRequired(true);
        $file->getFilterChain()->attachByName(
            'filerenameupload',
            array(
                'target'          => 'public/tmpuploads/',
                'overwrite'       => true,
                'use_upload_name' => true,
                'randomize'       => true,
            )
        );
        
        $validator = new \Zend\Validator\File\Extension('jpg, png, gif, jpeg'); //mp4, flv, mov, avi, mpeg, mpeg4 for videos
        $validator->setMessage('The file you are trying to upload is not a supported format. 
                                    Please check our 
                                    <a href="/pages/image-specs" target="_blank">image specifications page</a>.');
        $file->getValidatorChain()
            ->addValidator($validator);
        
        $validator = new \Zend\Validator\File\Size(array('max' => '10MB'));
        $validator->setMessage('The file you are trying to upload is not a supported format. 
                                    Please check our 
                                    <a href="/pages/image-specs" target="_blank">image specifications page</a>.');
                                    
        $file->getValidatorChain()
            ->addValidator($validator);
                
        $inputFilter->add($file);

        // Hidden Input
//        $hidden = new InputFilter\Input('stylebook_id');
//        $hidden->setRequired(true);
//        $inputFilter->add($hidden);

        return $inputFilter;
    }
}