<?php
/* @var $this CategoryController */
/* @var $model Category */

$this->breadcrumbs=array(
	'Categories'=>array('index'),
	'Manage',
);

$this->menu=array(
	array('label'=>'List Category', 'url'=>array('index')),
	array('label'=>'Create Category', 'url'=>array('create')),
);

?>

<h1>Manage Categories</h1>

<?php $this->widget('bootstrap.widgets.TbExtendedGridView', array(
	'id'=>'category-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'template'=>"{items}",
	'columns'=>array(
		array('name'=>'id'),
		array('name'=>'name'),
		array('name'=>'author', 'value'=>'$data->user->username'),
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'viewButtonUrl'=>'Yii::app()->controller->createUrl("view",array("id"=>$data["id"]))',
			'updateButtonUrl'=>'Yii::app()->controller->createUrl("update",array("id"=>$data["id"]))',
			'deleteButtonUrl'=>'Yii::app()->controller->createUrl("delete",array("id"=>$data["id"]))',
			'htmlOptions'=>array('style'=>'width: 50px'),
		),
	),
	
)); ?>
