<?php 
/**
 * @var $form CActiveForm
 */
?>

<h1>Contact Us</h1>

<div class="form">
<?php echo CHtml::beginForm('', 'post', array('id'=>'contact-messages-form', 'enctype'=>'multipart/form-data'))?>

<p class="note">Fields with <span class="required">*</span> are

required.</p>

<table>
<?php echo CHtml::errorSummary($model); ?>
	<tr>
		<td class="rowTd"><?php echo 'Your name *';//$form->labelEx($model, 'client_name'); ?>
		</td>
		<td><?php echo CHtml::activeTextField($model, 'client_name', array('size' => 60, 'maxlength' => 100)); ?>
		<?php echo CHtml::error($model, 'client_name'); ?></td>
	</tr>
	<tr>
		<td class="rowTd"><?php echo CHtml::activeLabelEx($model, 'email'); ?></td>
		<td><?php echo CHtml::activeTextField($model, 'email', array('size' => 50, 'maxlength' => 50)); ?>
		<?php echo CHtml::error($model, 'email'); ?></td>
	</tr>
	<tr>
		<td class="rowTd"><?php echo CHtml::activeLabelEx($model, 'subject'); ?></td>
		<td><?php echo CHtml::activeTextField($model, 'subject', array('size' => 50, 'maxlength' => 50)); ?>
		<?php echo CHtml::error($model, 'subject'); ?></td>
	</tr>
	<tr>
		<td class="rowTd"><?php echo CHtml::activeLabelEx($model, 'message'); ?></td>
		<td><?php echo CHtml::activeTextArea($model, 'message', array('rows' => 6, 'cols' => 50)); ?>
		<?php echo CHtml::error($model, 'message'); ?></td>
	</tr>
        
        <?php if (extension_loaded('gd')): ?>
	<tr>
		<td class="rowTd"><?php echo CHtml::activeLabel($model, 'verifyCode'); ?>
		</td>
		<td>
		<?php $this->widget('CCaptcha'); ?> <?php echo CHtml::activeTextField($model, 'verifyCode'); ?>

		<p class="hint">Please enter the letters as they are shown in the
		image above. <br />
		Letters are not case-sensitive.</p>
		</td>
	</tr>
	<?php endif; ?>
        
	<tr>
            <td class="rowTd">
                <div class="row buttons"><?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
                </div>
            </td>
	</tr>
</table>

	<?php echo CHtml::endForm()?></div>
<!-- form -->

