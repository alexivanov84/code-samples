<?php
/* @var $this UserController */
/* @var $model User */

$this->breadcrumbs=array(
	'Users'=>array('index'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List User', 'url'=>array('index')),
	array('label'=>'Create User', 'url'=>array('create')),
);

?>

<h1>Manage Users</h1>

<?php $this->widget('bootstrap.widgets.TbExtendedGridView', array(
	'id'=>'user-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'username',
		'firstname',
		'lastname',
		'email',
		'role',
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'viewButtonUrl'=>'Yii::app()->controller->createUrl("view",array("id"=>$data["id"]))',
			'updateButtonUrl'=>'Yii::app()->controller->createUrl("update",array("id"=>$data["id"]))',
			'deleteButtonUrl'=>'Yii::app()->controller->createUrl("delete",array("id"=>$data["id"]))',
			'htmlOptions'=>array('style'=>'width: 50px'),
		),
	),
)); ?>
