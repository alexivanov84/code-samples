<?php

namespace Application\Model;

// import statements for filtering form
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class StylebookLike {
	public $id;
        public $user_id;
        public $stylebook_id;
	protected $inputFilter; // for validation of forms
	private $_dbAdapter;
	
	public function exchangeArray($data) {
		$this->id = (isset ( $data ['id'] )) ? $data ['id'] : null;
		$this->user_id = (isset ( $data ['user_id'] )) ? $data ['user_id'] : null;
                $this->stylebook_id = (isset ( $data ['stylebook_id'] )) ? $data ['stylebook_id'] : null;
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
					'name' => 'user_id',
					'required' => false,
					'filters' => array (
                                                array('name' => 'Int'),
					),
					'validators' => array (
								
							array (
                                                                    'name' => 'NotEmpty',
                                                                    'break_chain_on_failure' => true,
                                                                    'options' => array (
                                                                                    'messages' => array (
                                                                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'Please choose user!'
                                                                                    )
                                                                    )
							)
								
					)
			) ) );
                        
                        $inputFilter->add ( $factory->createInput ( array (
					'name' => 'stylebook_id',
					'required' => false,
					'filters' => array (
                                                array('name' => 'Int'),
					),
					'validators' => array (
								
							array (
                                                                    'name' => 'NotEmpty',
                                                                    'break_chain_on_failure' => true,
                                                                    'options' => array (
                                                                                    'messages' => array (
                                                                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'Please choose stylebook!'
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