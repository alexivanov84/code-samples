<?php
/* @var $this UserController */
/* @var $model User */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'=>'user-form',
	'enableAjaxValidation'=>false,
	'enableClientValidation'=>true,
	'clientOptions'=>array(
        'validateOnSubmit'=>true,
    ),
	'htmlOptions'=>array('class'=>'well'),
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

        <?php echo $form->textFieldRow($model,'username',array('size'=>60,'maxlength'=>60,'class'=>'span3')); ?>
        
        <?php if ($model->isNewRecord) { ?>
            <?php echo $form->passwordFieldRow($model,'password',array('size'=>60,'maxlength'=>64, 'class'=>'span3', 'value' =>'')); ?>
        <?php } ?>

        <?php echo $form->textFieldRow($model,'firstname',array('size'=>60,'maxlength'=>128,'class'=>'span3')); ?>

        <?php echo $form->textFieldRow($model,'lastname',array('size'=>60,'maxlength'=>128,'class'=>'span3')); ?>

        <?php echo $form->textFieldRow($model,'email',array('size'=>60,'maxlength'=>255,'class'=>'span3')); ?>

		<?php if(Yii::app()->user->isAdmin()) { ?>
			<?php echo $form->dropDownListRow($model,'role',User::$roles,array('class'=>'span3')); ?>
		<?php } ?>
		
	<div class="form-actions">
                <?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit','type'=>'primary','label'=>$model->isNewRecord ? 'Create' : 'Save', 'icon'=>'ok'));?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->