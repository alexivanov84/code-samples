<?php
/* @var $this ArticleController */
/* @var $model Article */

$this->breadcrumbs=array(
	'Articles'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List Article', 'url'=>array('index')),
	array('label'=>'Manage Article', 'url'=>array('admin'), 'visible' => Yii::app()->user->checkAccess('admin')),
);
?>

<h1>Create Article</h1>

<?php echo $this->renderPartial('_form', array('model'=>$model, 'categories'=>$categories)); ?>