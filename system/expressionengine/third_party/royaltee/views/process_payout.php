<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=process_payout');?>

<?php 

$this->load->view('tabs'); 

?> 

<h3><?=lang('process_payout')?></h3>

<?php 
$this->table->set_template($cp_pad_table_template);
foreach ($data as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}
echo $this->table->generate();
$this->table->clear();
?>



<p><?=form_submit('submit', lang('process_payout'), 'class="submit"')?></p>


<?php
form_close();