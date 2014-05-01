<?php

namespace Application\Model;

// import statements for filtering form
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Media {
	public $id;
        public $user_id;
	public $media_type_id;
        public $title;
        public $comment;
        public $comment_private;
        public $added_on;
        public $likes;
        public $comments_count;
	protected $inputFilter; // for validation of forms
	private $_dbAdapter;
	
	public function exchangeArray($data) {
		$this->id = (isset ( $data ['id'] )) ? $data ['id'] : null;
                $this->user_id = (isset ( $data ['user_id'] )) ? $data ['user_id'] : null;
		$this->media_type_id = (isset ( $data ['media_type_id'] )) ? $data ['media_type_id'] : null;
                $this->title = (isset ( $data ['title'] )) ? (string) $data ['title'] : null;
                $this->comment = (isset ( $data ['comment'] )) ? (string) $data ['comment'] : null;
                $this->comment_private = (isset ( $data ['comment_private'] )) ? (string) $data ['comment_private'] : null;
                $this->added_on = (isset ( $data ['added_on'] )) ? $data ['added_on'] : null;
                $this->likes = (isset ( $data ['likes'] )) ? $data ['likes'] : null;
                $this->comments_count = (isset ( $data ['comments_count'] )) ? $data ['comments_count'] : null;
	}
	
	public function setDbAdapter($dbAdapter) {
		$this->_dbAdapter = $dbAdapter;
	}
	
	public function getDbAdapter() {
		return $this->_dbAdapter;
	}
	
	public function getArrayCopy() {
		return get_object_vars ( $this );
	}
	
	// Method for form validation:
	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new \Exception ( "Not used" );
	}
	public function getInputFilter() {
		if (! $this->inputFilter) {
			$inputFilter = new InputFilter ();
			$factory = new InputFactory ();
			
			/*
			 * $inputFilter->add($factory->createInput(array( 'name' => 'id',
			 * 'required' => true, 'filters' => array( array('name' => 'Int'),
			 * ), )));
			 */

			$inputFilter->add ( $factory->createInput ( array (
					'name' => 'title',
					'required' => false,
					'filters' => array (
							array (
									'name' => 'StripTags'
							),
							array (
									'name' => 'StringTrim'
							)
					),
					'validators' => array (
								
							array (
									'name' => 'NotEmpty',
									'break_chain_on_failure' => true,
									'options' => array (
											'messages' => array (
													\Zend\Validator\NotEmpty::IS_EMPTY => 'Please enter title!'
											)
									)
							)
								
					)
			) ) );
                        
                        $inputFilter->add ( $factory->createInput ( array (
					'name' => 'comment',
					'required' => false,
					'filters' => array (
							array (
									'name' => 'StripTags'
							),
							array (
									'name' => 'StringTrim'
							)
					),
					'validators' => array (
								
							array (
									'name' => 'NotEmpty',
									'break_chain_on_failure' => true,
									'options' => array (
											'messages' => array (
													\Zend\Validator\NotEmpty::IS_EMPTY => 'Please enter comment!'
											)
									)
							)
								
					)
			) ) );
			
			$this->inputFilter = $inputFilter;
		}
		
		return $this->inputFilter;
	}
	// End method for form validation:
}