<?php /* @var $this Controller */ ?>
<?php $this->beginContent('/layouts/main'); ?>

<div class="container">
	<div id="sidebar">
	<?php
            $this->widget('bootstrap.widgets.TbMenu', array(
                'type'=>'tabs', // '', 'tabs', 'pills' (or 'list')
                'stacked'=>false, // whether this is a stacked menu
                'items'=>$this->menu,
            )); 
	?>
	</div><!-- sidebar -->
</div>
<div class="container">
	<div id="content">
		<?php echo $content; ?>
	</div><!-- content -->
</div>

<?php $this->endContent(); ?>

