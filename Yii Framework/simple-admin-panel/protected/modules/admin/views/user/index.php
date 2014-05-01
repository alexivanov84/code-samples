<?php
/* @var $this UserController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Users',
);

$this->menu=array(
	array('label'=>'Create User', 'url'=>array('create'), 'visible' => Yii::app()->user->checkAccess('admin')),
	array('label'=>'Manage User', 'url'=>array('admin'), 'visible' => Yii::app()->user->checkAccess('admin')),
);
?>

<h1>Users</h1>

<?php $this->widget('bootstrap.widgets.TbGridView', array(
	'type'=>'striped bordered condensed',
	'dataProvider'=>$dataProvider,
	'template'=>"{items}",
	'columns'=>array(
		array('name'=>'id', 'header'=>'#'),
		array('name'=>'firstname', 'header'=>'Firstname'),
		array('name'=>'lastname', 'header'=>'Lastname'),
		array('name'=>'email', 'header'=>'Email'),
                array('name'=>'role', 'header'=>'Role'),
		array(
			'class'=>'bootstrap.widgets.TbButtonColumn',
			'viewButtonUrl'=>'Yii::app()->controller->createUrl("view",array("id"=>$data["id"]))',
			'updateButtonUrl'=>'Yii::app()->controller->createUrl("update",array("id"=>$data["id"]))',
			'deleteButtonUrl'=>'Yii::app()->controller->createUrl("delete",array("id"=>$data["id"]))',
			'htmlOptions'=>array('style'=>'width: 70px; text-align: center; vertical-align: middle;'),
                        'template'=>'{view}{update}{delete}{password}',
                        'buttons'=>array( 
                                'password' => array(
                                  'icon'=>'lock',
                                  'url'=>'Yii::app()->controller->createUrl("password",array("id"=>$data["id"]))',                                       
                                ),                               
                        ), 
		),
	),
)); ?>
