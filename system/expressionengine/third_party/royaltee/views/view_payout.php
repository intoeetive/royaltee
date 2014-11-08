<?php 

$this->load->view('tabs'); 


$this->table->set_template($cp_pad_table_template);
foreach ($data as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}
echo $this->table->generate();

$this->table->clear();

?>
