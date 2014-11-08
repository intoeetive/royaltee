
<?php 
$this->table->set_template($cp_pad_table_template);

$this->table->set_heading(
    array('data' => lang('gross_sales'), 'style' => 'width:40%;'),
    array('data' => lang('royalty_rate'), 'style' => 'width:60%;')
);

foreach ($cols as $key => $val)
{
	$this->table->add_row($val['sales'], $val['rate']);
}

echo $this->table->generate();

$this->table->clear();
?>
