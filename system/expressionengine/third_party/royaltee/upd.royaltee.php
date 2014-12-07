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
 File: upd.royaltee.php
-----------------------------------------------------
 Purpose: Author's revenue/commission system
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'royaltee/config.php';

class Royaltee_upd {

    var $version = ROYALTEE_ADDON_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
    } 
    
    function install() { 
  
        $this->EE->lang->loadfile('royaltee');  
		
		$this->EE->load->dbforge(); 

        $data = array( 'module_name' => 'Royaltee' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'has_publish_fields' => 'n'); 
        $this->EE->db->insert('modules', $data); 
        
        $data = array( 'class' => 'Royaltee' , 'method' => 'register_hit' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Royaltee' , 'method' => 'find_members' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Royaltee' , 'method' => 'find_products' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Royaltee' , 'method' => 'process_withdraw_request' ); 
        $this->EE->db->insert('actions', $data); 
        

		//exp_royaltee_commissions
		$fields = array(
			'commission_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'order_id'			=> array('type' => 'INT',		'default' => 0),
			'method'			=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => ''),//carttrob, store, withdraw
			'member_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'credits'			=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0),
			'credits_pending'	=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0), //for withdraw requests
			'record_date'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('commission_id', TRUE);
		$this->EE->dbforge->add_key('order_id');
		$this->EE->dbforge->add_key('method');
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->create_table('royaltee_commissions', TRUE);
		
		
		//exp_royaltee_payouts
		$fields = array(
			'payout_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
			'method'			=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => 'other'),//paypal,masspay,bank,other
			'member_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'amount'			=> array('type' => 'DECIMAL',	'constraint' => '7,2', 'default' => 0),
			'transaction_id'	=> array('type' => 'VARCHAR',	'constraint'=> 50,	'default' => 'other'),
			'comment'			=> array('type' => 'TEXT',		'default' => ''),
			'payout_date'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0)
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('payout_id', TRUE);
		$this->EE->dbforge->add_key('member_id');
		$this->EE->dbforge->create_table('royaltee_payouts', TRUE);
		
        //notification templates
        $data = array( 
			'site_id' => $this->EE->config->item('site_id'), 
			'enable_template' => 'y', 
			'template_name' => 'royaltee_withdraw_request_admin_notification', 
			'data_title'=> $this->EE->lang->line('withdraw_request_admin_notification_subject'), 
			'template_data'=> $this->EE->lang->line('withdraw_request_admin_notification_message') 
		); 
        $this->EE->db->insert('specialty_templates', $data); 
        
        
        return TRUE; 
        
    } 
    
    
    function uninstall() { 

        $this->EE->load->dbforge(); 
		
		$this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Royaltee')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Royaltee'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Royaltee'); 
        $this->EE->db->delete('actions'); 
        
        $this->EE->dbforge->drop_table('royaltee_commissions');
        $this->EE->dbforge->drop_table('royaltee_payouts');

        return TRUE; 
    } 
    
    function update($current='') 
	{ 
		
		return TRUE; 
    } 
	

}
/* END */
?>