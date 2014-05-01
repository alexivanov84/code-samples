<?php
/* @var $this ArticleController */
/* @var $model Article */
/* @var $form CActiveForm */
?>

<div class="form">

<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'=>'article-form',
	'enableAjaxValidation'=>false,
	'enableClientValidation'=>true,
	'clientOptions'=>array(
        'validateOnSubmit'=>true,
    ),
	'htmlOptions'=>array('class'=>'well'),
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

        <?php echo $form->textFieldRow($model,'title',array('size'=>60,'maxlength'=>255,'class'=>'span3')); ?>

        <?php echo $form->textAreaRow($model,'excerpt',array('rows'=>6, 'cols'=>50,'class'=>'span3')); ?>

        <?php echo $form->textAreaRow($model,'description',array('rows'=>6, 'cols'=>50,'class'=>'span3')); ?>

		<?php echo $form->dropDownListRow($model,'category',CHtml::listData($categories,'id','name'),array('class'=>'span3')); ?>

        <div class="form-actions">
                <?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit','type'=>'primary','label'=>$model->isNewRecord ? 'Create' : 'Save', 'icon'=>'ok'));?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->