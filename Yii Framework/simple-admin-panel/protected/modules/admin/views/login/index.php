<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'=>'login-form-index-form',
	'enableAjaxValidation'=>false,
	'enableClientValidation'=>true,
	'clientOptions'=>array(
        'validateOnSubmit'=>true,
    ),
	'htmlOptions'=>array(
		'class'=>'form-signin',
	),
)); ?>

    <h2 class="form-signin-heading">Admin Panel</h2>
    
    <?php echo $form->errorSummary($model, null, null, array('class'=>'alert')); ?>
    <?php echo $form->textFieldRow($model,'username', array('class'=>'input-block-level', 'placeholder'=>'Username')); ?>
    <?php echo $form->passwordFieldRow($model,'password', array('class'=>'input-block-level', 'placeholder'=>'Password')); ?>
    <?php echo $form->label($model,'rememberMe'.$form->checkBox($model,'rememberMe'), array('class'=>'checkbox',"style"=>"text-align:left;")); ?>
    
    <?php echo CHtml::submitButton('Submit',array("class"=>"btn btn-inverse btn-large","style"=>"align:left;")); ?>
<?php $this->endWidget(); ?>
    
  