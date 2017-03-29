<?php

/*
=====================================================
 RoyaltEE
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ft.royaltee.php
-----------------------------------------------------
 Purpose: Author's revenue/commission system
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'royaltee/config.php';


class Royaltee_ft extends EE_Fieldtype {
	
	var $info = array(
		'name'		=> ROYALTEE_ADDON_NAME,
		'version'	=> ROYALTEE_ADDON_VERSION
	);
    
    var $modifiers_nr = 5;
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Field on Publish
	 *
	 * @access	public
	 * @param	existing data
	 * @return	field html
	 *
	 */
	function display_field($data)
	{
        $this->EE->lang->loadfile('royaltee');
        $this->EE->load->library('table');  
        
        if (!is_array($data))
        {
            $data = unserialize(base64_decode($data));  
        } 
        $view_data = array(); 
        for ($i=0; $i<$this->modifiers_nr; $i++)
        {
            $view_data['cols'][] = array(
                'sales' => form_input($this->field_name."[$i][sales]", (isset($data[$i]["sales"])?$data[$i]["sales"]:'')),
                'rate'  => form_input($this->field_name."[$i][rate]", (isset($data[$i]["rate"])?$data[$i]["rate"]:'')),
            );
        }
        
        $out = $this->EE->load->view('field', $view_data, TRUE);
		
		return $out;
        
	}

	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
        
        return $tagdata;
	}
 
    
    function save($data)
	{
		return base64_encode(serialize($data));
	}
    
    function save_settings($data) {
        return array();    
    }
    

	function install()
	{
		return array();
	}
	

}

?>