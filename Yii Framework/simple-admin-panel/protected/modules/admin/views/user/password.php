<?php
/* @var $this UsersController */
/* @var $model Users */

$this->breadcrumbs=array(
	'Users'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List User', 'url'=>array('index')),
	array('label'=>'Create User', 'url'=>array('create')),
	array('label'=>'View User', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage User', 'url'=>array('admin')),
);
?>

<h1>Update User <?php echo $model->firstname."\t".$model->lastname; ?></h1>


<div class="form">
   
<?php $form=$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id'=>'users-form',
        'htmlOptions'=>array('class'=>'well'),
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

        <?php echo $form->errorSummary($model); ?>

	<?php echo $form->errorSummary($model); ?>

        <?php echo $form->passwordFieldRow($model,'password',array('size'=>60,'maxlength'=>64, 'class'=>'span3', 'value' =>'')); ?>

        <?php echo $form->passwordFieldRow($model,'confirm_password',array('size'=>60,'maxlength'=>64, 'class'=>'span3', 'value' =>'')); ?>

        <div class="form-actions">
                <?php $this->widget('bootstrap.widgets.TbButton', array('buttonType'=>'submit','type'=>'primary','label'=>'Save', 'icon'=>'ok'));?>
	</div>
        
<?php $this->endWidget(); ?>

</div>

