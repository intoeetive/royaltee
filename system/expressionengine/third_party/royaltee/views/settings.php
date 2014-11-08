<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=save_settings', array('id'=>'royaltee_settings_form'));?>
 
<?php $this->load->view('tabs'); ?>  


<?php 
$this->table->set_template($cp_pad_table_template); 
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);


foreach ($settings as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();

?>
<?php $this->table->clear()?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?php
form_close();

