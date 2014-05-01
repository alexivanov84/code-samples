<?php
/* @var $this UserController */
/* @var $model User */

$this->breadcrumbs=array(
	'Users'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List User', 'url'=>array('index'), 'visible' => Yii::app()->user->checkAccess('admin')),
	array('label'=>'Create User', 'url'=>array('create'), 'visible' => Yii::app()->user->checkAccess('admin')),
	array('label'=>'View User', 'url'=>array('view', 'id'=>$model->id), 'visible' => Yii::app()->user->checkAccess('admin')),
	array('label'=>'Manage User', 'url'=>array('admin'), 'visible' => Yii::app()->user->checkAccess('admin')),
);
?>

<h1>Update User <?php echo $model->firstname."\t".$model->lastname; ?></h1>

<?php echo $this->renderPartial('_form', array('model'=>$model)); ?>