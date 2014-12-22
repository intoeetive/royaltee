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
 File: ext.royaltee.php
-----------------------------------------------------
 Purpose: Author's revenue/commission system
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'royaltee/config.php';

class Royaltee_ext {

	var $name	     	= ROYALTEE_ADDON_NAME;
	var $version 		= ROYALTEE_ADDON_VERSION;
	var $description	= 'Author\'s revenue/commission system';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/royaltee.html';
    
    var $settings 		= array();
    var $site_id		= 1;
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;

		$this->EE->load->library('royaltee_lib');  
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(

    		//record commission
    		array(
    			'hook'		=> 'cartthrob_on_authorize',
    			'method'	=> 'cartthrob_purchase',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'store_order_complete_end',
    			'method'	=> 'store_purchase',
    			'priority'	=> 10
    		),
    		
    		
            
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
        
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    
    
    
    function settings()
    {
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=settings');
		return true;
    }
    

    
    
    
    
    function store_purchase($order)
    {
        
        require_once PATH_THIRD.'/store/src/Model/Order.php';
        require_once PATH_THIRD.'/store/src/Model/OrderItem.php';
        require_once PATH_THIRD.'/store/src/Model/Product.php';
 
        $paid = $order->getIsOrderPaidAttribute();
        if ($paid==false) return false;        
        
        //get the royaltee fields
        $royaltee_fields_q = $this->EE->db->select('field_id')
            ->from('channel_fields')
            ->where('field_type', 'royaltee')
            ->get();
        if ($royaltee_fields_q->num_rows()==0)
        {
            return false;
        }
        $royaltee_fields = array();
        foreach ($royaltee_fields_q->result_array() as $row)
        {
            $royaltee_fields[$row['field_id']] = $row['field_id'];
        }
        if (count($royaltee_fields)==0) return false;
        
		//per-product checks
        foreach ($order->items as $item_row_id=>$orderitem)
		{
            $item = $orderitem->product->toArray();

            //for each item, check whether there is commission rule/field
            $all_rules = array();
            
            foreach ($royaltee_fields as $royaltee_field)
            {
                $field = 'field_id_'.$royaltee_field;
				$q = $this->EE->db->select($field)
						->from('channel_data')
						->where_in('channel_data.entry_id', $item['entry_id'])
						->get();
                if ($q->row($field)!='')
                {
                    $rules = unserialize(base64_decode($q->row($field)));

                    foreach ($rules as $rule)
                    {
                        if ($rule['rate']!='')
                        {
                            $all_rules[$rule['sales']] = $rule['rate'];
                        }
                    }
                }
            }
            
            if (empty($all_rules))
            {
                continue;
            }
            
            //product author/owner
            $q = $this->EE->db->select('author_id')
    				->from('channel_titles')
    				->where_in('entry_id', $item['entry_id'])
    				->get();
            $member_id = $q->row('author_id');
            if ($q->num_rows()==0)
            {
                continue;
            }
            
            //gross sales of member's items to date
            $this->EE->db->select('SUM(price) AS gross')
    			->from('store_orders')
                ->join('store_order_items', 'store_order_items.order_id=store_orders.id', 'left')
                ->join('channel_titles', 'store_order_items.entry_id=channel_titles.entry_id', 'left')
                ->where('author_id', $member_id)
                ->where('order_paid != ', '')
                ->where('order_paid_date !=', '');
    		$q = $this->EE->db->get();                 
                            
            if ($q->num_rows()==0)
            {
                continue;
            }
            
            

            $gross = $q->row('gross');
            $rate = 0;
            
            //list the rules and get highest rate applicable
            foreach ($all_rules as $sales=>$rulerate)
            {
                if (floatval($gross) >= floatval($sales))
                {
                    if ($rulerate > $rate)
                    {
                        $rate = $rulerate;
                    }
                }
            }
            
            if ($rate == 0)
            {
                continue;
            }
            
            //get the amount
    		$paid_for_product = $item['price']*$order->countItemsById($item['entry_id']);
    		
    		//divide order discount_processing
    		if ($order->order_discount!=0)
    		{
    			$divided_discount = ($order->order_discount / $order->order_subtotal) * $paid_for_product;
    			$paid_for_product -= $divided_discount;
    		}					
    		
    		if ($paid_for_product<=0) return false;
			
			//echo 'looks like all is fine';
			
			$commission_amount = $paid_for_product*$rate/100;
		
    		if ($commission_amount==0) continue;
            
            //ok, time to add the commission record!
    		$insert = array(
    			'order_id'			=> $order->id,
    			'method'			=> 'store',
    			'member_id'			=> $member_id,
    			'credits'			=> $commission_amount,
    			'record_date'		=> $this->EE->localize->now
    		);
    		$this->EE->db->insert('royaltee_commissions', $insert);
    		
    		if (isset($this->settings['devdemon_credits']) && $this->settings['devdemon_credits']=='y')
    		{
    			$credits_action_q = $this->EE->db->select('action_id, enabled')
    									->from('exp_credits_actions')
    									->where('action_name', 'royaltee_reward')
    									->get();
    			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
    	    	{
    				$pData = array(
    					'action_id'			=> $credits_action_q->row('action_id'),
    					'site_id'			=> $this->EE->config->item('site_id'),
    					'credits'			=> $commission_amount,
    					'receiver'			=> $member_id,
    					'item_id'			=> $order->id,
    					'item_parent_id' 	=> $this->EE->session->userdata('member_id')
    				);
    				
    				$this->EE->royaltee_lib->_save_credits($pData);
    			}
    		}
        }
        
        
    }
    
    
    

    
    
    
    function cartthrob_purchase()
    {

		// the commission is calculated based on items cost only (shipping and taxed not included)
		// plus, some items have comission rate set specificly for them
		// so we need to parse what's in the order
		
		$order_data = $this->EE->cartthrob->cart->order(); 
		if ($order_data['auth']['authorized']!=true) return false;

		if ($order_data['subtotal']==0) return false;
		
		
		//calculate order discout
		$order_discount = 0;
		if (isset($order_data['discounts']) && !empty($order_data['discounts']))
		{
			//individual item discount
			foreach ($order_data['discounts'] as $discount)
			{
				$order_discount += $discount['amount'];
			}
		}

        
        //get the royaltee fields
        $royaltee_fields_q = $this->EE->db->select('field_id')
            ->from('channel_fields')
            ->where('field_type', 'royaltee')
            ->get();
        if ($royaltee_fields_q->num_rows()==0)
        {
            return false;
        }
        $royaltee_fields = array();
        foreach ($royaltee_fields_q->result_array() as $row)
        {
            $royaltee_fields[$row['field_id']] = $row['field_id'];
        }
        if (count($royaltee_fields)==0) return false;

		//per-product checks
		foreach ($order_data['items'] as $item_row_id=>$item)
		{
			//for each item, check whether there is commission rule/field
            $all_rules = array();
            $rate = 0;
            foreach ($royaltee_fields as $royaltee_field)
            {
                $field = 'field_id_'.$royaltee_field;
				$q = $this->EE->db->select($field)
						->from('channel_data')
						->where_in('channel_data.entry_id', $item['product_id'])
						->get();
                if ($q->row($field)!='')
                {
                    $rules = unserialize(base64_decode($q->row($field)));

                    foreach ($rules as $rule)
                    {
                        if ($rule['rate']!='')
                        {
                            $all_rules[$rule['sales']] = $rule['rate'];
                        }
                    }
                }
            }
            
            if (empty($all_rules))
            {
                continue;
            }
            
            //product author/owner
            $q = $this->EE->db->select('author_id')
    				->from('channel_titles')
    				->where_in('entry_id', $item['product_id'])
    				->get();
            $member_id = $q->row('author_id');
            if ($q->num_rows()==0)
            {
                continue;
            }
            

            //gross sales of member's items to date
            $q = $this->EE->db->select('SUM(price) AS gross')
    			->from('cartthrob_order_items')
                ->join('cartthrob_status', 'cartthrob_status.entry_id=cartthrob_order_items.order_id', 'left')
                ->join('channel_titles', 'cartthrob_order_items.entry_id=channel_titles.entry_id', 'left')
                ->where('cartthrob_status.status', 'authorized')
                ->where('channel_titles.author_id', $member_id)
    			->get();
            if ($q->num_rows()==0)
            {
                continue;
            }

            $gross = $q->row('gross');
            $rate = 0;
            
            //list the rules and get highest rate applicable
            foreach ($all_rules as $sales=>$rulerate)
            {
                if (floatval($gross) >= floatval($sales))
                {
                    if ($rulerate > $rate)
                    {
                        $rate = $rulerate;
                    }
                }
            }
            
            if ($rate == 0)
            {
                continue;
            }
            
			//get the amount
			$paid_for_product = $item['price']*$item['quantity'];
			
			if (isset($item['discounts']) && !empty($item['discounts']))
			{
				//individual item discount
				foreach ($item['discounts'] as $discount)
				{
					$paid_for_product -= $discount['amount'];
				}
			}
			
			//divide order discount_processing
			if ($order_discount!=0)
			{
				$divided_discount = ($order_discount / $order_data['subtotal']) * $paid_for_product;
				$paid_for_product -= $divided_discount;
			}					
			
			if ($paid_for_product<=0) return false;
			
			//echo 'looks like all is fine';
			
			$commission_amount = $paid_for_product*$rate/100;
		
    		if ($commission_amount==0) continue;
            
            //ok, time to add the commission record!
    		$insert = array(
    			'order_id'			=> $order_data['order_id'],
    			'method'			=> 'carttrob',
    			'member_id'			=> $member_id,
    			'credits'			=> $commission_amount,
    			'record_date'		=> $this->EE->localize->now
    		);
    		$this->EE->db->insert('royaltee_commissions', $insert);
    		
    		if (isset($this->settings['devdemon_credits']) && $this->settings['devdemon_credits']=='y')
    		{
    			$credits_action_q = $this->EE->db->select('action_id, enabled')
    									->from('exp_credits_actions')
    									->where('action_name', 'royaltee_reward')
    									->get();
    			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
    	    	{
    				$pData = array(
    					'action_id'			=> $credits_action_q->row('action_id'),
    					'site_id'			=> $this->EE->config->item('site_id'),
    					'credits'			=> $commission_amount,
    					'receiver'			=> $member_id,
    					'item_id'			=> $order_data['order_id'],
    					'item_parent_id' 	=> $this->EE->session->userdata('member_id')
    				);
    				
    				$this->EE->royaltee_lib->_save_credits($pData);
    			}
    		}
        }
    
    }
   
    
  

}
// END CLASS
