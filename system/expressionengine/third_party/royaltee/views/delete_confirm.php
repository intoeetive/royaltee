
<h3><?=lang('confirm_deleting')?></h3>

<p><?=lang('invitation_delete_warning')?></p>

	<?php
	
	echo form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=delete', array('id'=>'invitations_action_form'));
	echo $data;
	?>

<div class="tableSubmit">

	<?=form_submit('submit', lang('delete'), 'class="submit"'); ?>
</div>
<?=form_close()?>