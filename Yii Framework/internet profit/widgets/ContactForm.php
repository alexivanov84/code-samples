<?php

/**
 * ContactForm class file.
 *
 * @author Alex Ivanov <alexivanov660@gmail.com>
 */

/**
 * ContactForm displays form which allows to add your view file and set your html code.
 *
 *

 * The following example shows how to use ContactForm:

 * <pre>
 * $this->widget('application.widgets.ContactForm', array(
 *     'itemView' => 'test', // the name of view file, e.g. widgets/views/contactform/test.php
 *     'loadCaptcha' => true // true/false, allows to enable or disable captcha code validation.
 * ));
 * </pre>
 *
 */

class ContactForm extends CWidget
{
        public $itemViewPrefix = 'contactform';
        
        public $itemView = 'index';
        
        public $baseScriptUrl = null;
        
        public $cssFile = null; 
        
        public $loadCaptcha = false;
        
	/**
	 * Initializes the ContactForm widget.
	 */
	public function init()
	{
                if($this->baseScriptUrl===null)
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.widgets.assets')).'/contactform';

		if($this->cssFile!==null)
		{
                        $this->cssFile=$this->baseScriptUrl.'/'.$this->cssFile.'.css';
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}
	}

	/**
	 * Calls {@link renderMenu} to render the form.
	 */
	public function run()
        {
		$this->renderContent();
	}

	/**
	 * Renders the contact form.
	 */
	protected function renderContent()
	{
                $owner=$this->getOwner();
                $render=$owner instanceof CController ? 'renderPartial' : 'render';

                if($this->loadCaptcha)
                    $model = new ContactMessages('frontend');
                else
                    $model = new ContactMessages();

                if (isset($_POST['ContactMessages'])) {
                    $model->attributes = $_POST['ContactMessages'];

                    if ($model->validate()) {
                        $model->message = CHtml::encode($model->message);
                        $model->getMoreFields($_POST['ContactForm']);
                        $model->getFiles('ContactForm');
                        $model->insert();

                        $returnUrl = $owner->createUrl('thankyou');
                        if (!empty($_POST['returnUrl']))
                            $returnUrl = $_POST['returnUrl'];

                        $owner->redirect($returnUrl);
                    }
                }

                $data = array('model' => $model);
                $this->render($this->itemViewPrefix . '/' . $this->itemView, $data);
			
	}

}

