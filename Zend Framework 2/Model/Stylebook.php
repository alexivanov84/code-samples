<?php

namespace Application\Model;

// import statements for filtering form
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Stylebook {
	// public $id;
	public $id;
	public $user_id;
	public $title;
	public $description;
        private $clause = array();
        private $errorTitle = '';
	protected $inputFilter; // for validation of forms
	private $_dbAdapter;
	
	public function exchangeArray($data) {
		$this->id = (isset ( $data ['id'] )) ? $data ['id'] : null;
		$this->user_id = (isset ( $data ['user_id'] )) ? $data ['user_id'] : null;
		$this->title = (isset ( $data ['title'] )) ? $data ['title'] : null;
		$this->description = (isset ( $data ['description'] )) ? $data ['description'] : null;
		//$this->tag_id = (isset ( $data ['tag_id'] )) ? $data ['tag_id'] : null;
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
        
        public function setClause($clause) {
                $this->errorTitle = $clause['title'];
                unset($clause['title']);
                $this->clause = $clause;
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
					'required' => true,
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
							),
                                                        array (
									'name' => 'Db\NoRecordExists',
									'break_chain_on_failure' => true,
									'options' => array_merge(array (
											'table' => 'stylebook',
											'field' => 'title',
//											'exclude' => '(user_id = '. $this->user_id.')',
											'adapter' => $this->getDbAdapter(),
											'messages' => array (
                                                                                            \Zend\Validator\Db\NoRecordExists::ERROR_RECORD_FOUND => "One of your stylebook is already named '".$this->errorTitle."'. Choose another name."
											)
									), $this->clause)
							)
                                                        
								
					)
			) ) );
			
			$inputFilter->add ( $factory->createInput ( array (
					'name' => 'description',
					'required' => false,
					'filters' => array (
							array (
									'name' => 'StripTags' 
							),
							array (
									'name' => 'StringTrim' 
							) 
					)

			) ) );
			
			$this->inputFilter = $inputFilter;
		}
		
		return $this->inputFilter;
	}
	// End method for form validation:
}