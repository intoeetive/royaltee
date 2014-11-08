<?php 

$this->load->view('tabs'); 

if ($total_count == 0) {
	
	?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_records')?></p>
	</div>
<?php 

}
else
{

?>

<div id="filterMenu">
	<fieldset>
		<legend><?=lang('refine_results')?></legend>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=stats');?>

		<div class="group">
            <?php
			
			$field = array(
              'name'        => 'date_from',
              'value'       => $selected['date_from'],
              'size'        => '25',
              'id'          => 'date_from',
              'style'       => 'width:120px'
            );
			
			echo lang('dates_from').NBS.NBS.form_input($field);
			
			$field = array(
              'name'        => 'date_to',
              'value'       => $selected['date_to'],
              'size'        => '25',
              'id'          => 'date_to',
              'style'       => 'width:120px'
            );
			
			echo lang('_to').NBS.NBS.form_input($field);
			
			echo NBS.NBS.NBS;
			
			echo lang('member').NBS.$member_select.NBS.NBS.NBS;
			
			$perpage = array(
				'25' => '25',
				'50' => '50',
				'100' => '100',
				'0' => lang('all')
			);
			
			echo BR.form_dropdown('perpage', $perpage, $selected['perpage']).NBS.lang('records').NBS.lang('per_page');
            
            echo NBS.NBS.form_submit('submit', lang('show'), 'class="submit" id="search_button"');
            
            ?>
		</div>

	<?=form_close()?>
	</fieldset>
</div>


<?php

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading($table_headings);


foreach ($data as $item)
{
	//var_dump($item);
	$this->table->add_row($item['date'], $item['author'] , $item['order'] , $item['product'], $item['customer'], $item['commission']);
}

echo $this->table->generate();


$this->table->clear();
?>
<span class="pagination"><?=$pagination?></span>
<?php
}
?>