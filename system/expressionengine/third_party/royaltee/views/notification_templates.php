<?php

echo form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=save_notification_templates');

$this->load->view('tabs'); 

foreach ($data as $name=>$rows)
{
    echo "<h3 class=\"accordion\">".lang($name)."</h3>";
    
    $this->table->set_template($cp_pad_table_template);    
    
    foreach ($rows as $key => $val)
    {
    	$this->table->add_row(lang($key, $key), $val);
    }
    
    echo $this->table->generate();
    $this->table->clear();
}
?>


<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?php
form_close();

