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
 File: Royaltee_lib.php
-----------------------------------------------------
 Purpose: Referrals system that works well
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}


class Royaltee_lib {

    var $return_data	= ''; 	
    
    var $settings = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 

		$this->EE->lang->loadfile('royaltee');  
    }
    /* END */
    
    
    function _get_ext_settings()
    {
    	$query = $this->EE->db->select('settings')->from('extensions')->where('class', 'Royaltee_ext')->limit(1)->get();
        $settings = unserialize($query->row('settings')); 
        return $settings;
    }

	
	
	function update_hit_record($member_id)
	{
		//do we have cookies?
		if (!$this->EE->input->cookie('royaltee_referrer_id') || !$this->EE->input->cookie('royaltee_hit_id'))
		{
			return false;
		}
		
		//no record, or already used - who are you trying to fool, he?
		$q = $this->EE->db->select('hit_id, member_id')
    				->from('royaltee_hits')
    				->where('hit_id', $this->EE->input->cookie('royaltee_hit_id'))
					->where('referrer_id', $this->EE->input->cookie('royaltee_referrer_id'))
					->get();
		if ($q->num_rows()==0 || $q->row('member_id')!=0)
		{
			return false;
		}
		
		$update = array(
			'member_id' => $member_id,
			'register_date'=> $this->EE->localize->now
		);

		$this->EE->db->where('hit_id', $q->row('hit_id'));
		$this->EE->db->update('royaltee_hits', $update);
		
		//$this->EE->functions->set_cookie('royaltee_referrer_id', '', false);	
		//$this->EE->functions->set_cookie('royaltee_hit_id', '', false);	
		
		return $q->row('hit_id');
    	
    }
	
	
	
	
	function get_referrer_data()
	{
		$referrer_data = false;
		
		if ($this->EE->session->userdata('member_id')!=0)
    	{
    		$q = $this->EE->db->select('hit_id, hit_date, referrer_id')
	    				->from('royaltee_hits')
	    				->where('member_id', $this->EE->session->userdata('member_id'))
						->order_by('hit_id', 'asc')
						->limit(1)
						->get();
			if ($q->num_rows()>0)
			{
				if ($q->row('referrer_id')!=0)
				{
					//using record from database
					return $q->row_array();
				}
			}
			//is there a cookie and member logged in? if so, update hit record
			//but only if he joined AFTER link was clicked
	    	if ($this->EE->input->cookie('royaltee_hit_id')!='' && $this->EE->input->cookie('royaltee_referrer_id')!='')
	    	{
	    		
				$q = $this->EE->db->select('hit_id, hit_date, member_id, referrer_id')
		    				->from('royaltee_hits')
		    				->where('hit_id', $this->EE->input->cookie('royaltee_hit_id'))
							->where('referrer_id', $this->EE->input->cookie('royaltee_referrer_id'))
							->limit(1)
							->get();
				if ($q->num_rows()==0)
				{
					return false;
				}
				if ($q->row('hit_date') <= $this->EE->session->userdata('join_date'))
				{
					return false;
				}
				
				if ($q->row('member_id')==0)
				{
					$hit_id = $this->EE->royaltee_lib->update_hit_record($this->EE->session->userdata('member_id'));
					if ($hit_id==false)
					{
						return false;
					}
					else
					{
						return $q->row_array();
					}
				}
    		}
			
    	}	
    	else
		//if we have cookies but this is guest
    	if ($this->EE->input->cookie('royaltee_hit_id')!='' && $this->EE->input->cookie('royaltee_referrer_id')!='')
    	{
			//check that we have a record that cookie points to
			$q = $this->EE->db->select('hit_id, hit_date, referrer_id')
	    				->from('royaltee_hits')
	    				->where('hit_id', $this->EE->input->cookie('royaltee_hit_id'))
						->where('referrer_id', $this->EE->input->cookie('royaltee_referrer_id'))
						->where('member_id != 0')
						->get();
			if ($q->num_rows()==0 )
			//registered once, can NOT generate commission as guest
			{
				return false;
			}
			else
			{
				return $q->row_array();
			}
    	}
    	else
    	{
    		return false;
    	}
		
	}
		
		
		
		
		
		
		
		
		
		
		function boo(){
		
		//add credits
		if ($q->row('credits_author')!='0' || $q->row('credits_user')!='0')
		{
			$this->EE->db->select('module_id'); 
	        $credits_installed_q = $this->EE->db->get_where('modules', array('module_name' => 'Credits')); 
	        if ($credits_installed_q->num_rows()>0)
	        {
            	
				if ($q->row('credits_author')!='0')
	        	{
					$this->EE->db->select('action_id, enabled')
								->where('action_name', 'invitations_redeemed_author');
					$credits_action_q = $this->EE->db->get('exp_credits_actions');
					if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
		        	{
						if ($q->row('credits_author')<0)
						{
							$query = $this->EE->db->select('SUM(credits) as credits_total')->from('exp_credits')->where('member_id', $q->row('author_id'))->get();
							if ($query->num_rows()==0 || ($query->row('credits_total') < abs($q->row('credits_author'))))
							{
								$this->EE->output->show_user_error('general', lang('not_enough_credits_author'));
							}
						}
						
						$pData = array(
							'action_id'	=> $credits_action_q->row('action_id'),
							'site_id'	=> $this->EE->config->item('site_id'),
							'credits'	=> $q->row('credits_author'),
							'receiver'	=> $q->row('author_id'),
							'item_id'	=> $this->EE->session->userdata('member_id'),
							'item_parent_id' => 0
						);
		
						$this->_save_credits($pData);
		    		}
    			}
    			
    			if ($q->row('credits_user')!='0')
	        	{
					$this->EE->db->select('action_id, enabled')
								->where('action_name', 'invitations_redeemed_user');
					$credits_action_q = $this->EE->db->get('exp_credits_actions');
					if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
		        	{
						if ($q->row('credits_author')<0)
						{
							$query = $this->EE->db->select('SUM(credits) as credits_total')->from('exp_credits')->where('member_id', $this->EE->session->userdata('member_id'))->get();
							if ($query->num_rows()==0 || ($query->row('credits_total') < abs($q->row('credits_user'))))
							{
								$this->EE->output->show_user_error('general', lang('not_enough_credits_user'));
							}
						}
						
						$pData = array(
							'action_id'	=> $credits_action_q->row('action_id'),
							'site_id'	=> $this->EE->config->item('site_id'),
							'credits'	=> $q->row('credits_author'),
							'receiver'	=> $this->EE->session->userdata('member_id'),
							'item_id'	=> $this->EE->session->userdata('member_id'),
							'item_parent_id' => 0
						);
		
						$this->_save_credits($pData);
		    		}
    			}
	        }
       }
		
		
		//apply code

		$this->EE->db->query("UPDATE exp_members SET group_id = '".$q->row('destination_group_id')."' WHERE member_id = '".$this->EE->session->userdata('member_id')."'");
		$this->EE->stats->update_member_stats();
		
		
		//update our tables
		$this->EE->db->query("UPDATE exp_invitations_codes SET times_used=times_used+1 WHERE code_id='".$q->row('code_id')."'");
		$insert = array(
			'member_id' => $this->EE->session->userdata('member_id'),
			'code_id'	=> $q->row('code_id'),
			'ip_address'=> $this->EE->input->ip_address(),
			'used_date '=> $this->EE->localize->now
		);
		
		$this->EE->db->insert('invitations_uses', $insert);
		
		$this->EE->functions->redirect($_POST['RET']);
		
		
		
    }
    
    
    		    
    
    
    
    
    function install_credits_action()
    {
    	$this->EE->load->dbforge(); 
    	if ($this->EE->db->table_exists('credits_actions') != FALSE)
    	{
			$q = $this->EE->db->get_where('credits_actions', array('action_name' => 'royaltee_reward')); 
			if ($q->num_rows()==0)
			{
				$data = array(
						'action_name' => 'royaltee_reward',
						'action_title' => $this->EE->lang->line('royaltee_commission'),
						'action_credits' => 0,
						'enabled' => 1
					);
				$this->EE->db->insert('exp_credits_actions', $data);
			}
			$q = $this->EE->db->get_where('credits_actions', array('action_name' => 'royaltee_withdraw')); 
			if ($q->num_rows()==0)
			{
				$data = array(
						'action_name' => 'royaltee_withdraw',
						'action_title' => $this->EE->lang->line('royaltee_commission_withdraw'),
						'action_credits' => 0,
						'enabled' => 1
					);
				$this->EE->db->insert('exp_credits_actions', $data);
			}
			return true;
		}
		return false;
    }






    function _save_credits($pData=FALSE)
	{

		// Does the action stat already exist?
		$query = $this->EE->db->select('credit_id, credits')->from('exp_credits')->where('action_id', $pData['action_id'])->where('member_id',  $pData['receiver'])->where('site_id', $this->site_id)->limit(1)->get();

		// Do we need to update?
		$update = ( $query->num_rows() > 0 ) ? TRUE : FALSE;
		$credit_id = $query->row('credit_id');

		// Resources are not free
		$query->free_result();

		if ($update)
		{
			if ($pData['credits']>0)
			{
				$this->EE->db->set('credits', "( credits + {$pData['credits']} )", FALSE);
			}
			else
			{
				$this->EE->db->set('credits', "( credits ".$pData['credits'].")", FALSE);
			}
			$this->EE->db->where('credit_id', $credit_id);
			$this->EE->db->update('exp_credits');
		}
		else
		{
			$this->EE->db->set('action_id',	$pData['action_id']);
			$this->EE->db->set('site_id',	$this->site_id);
			$this->EE->db->set('member_id', $pData['receiver']);
			$this->EE->db->set('credits',	$pData['credits']);
			$this->EE->db->insert('exp_credits');
		}

		// Log Credits!
		if (isset($pData['rule_id']) != FALSE && $pData['rule_id'] > 0)
		{
			$pData['date'] = $this->EE->localize->now + 10;
		}

		$this->EE->db->set('site_id',		$this->site_id);
		$this->EE->db->set('sender',		(isset($pData['sender']) ? $pData['sender'] : 0) );
		$this->EE->db->set('receiver',		(isset($pData['receiver']) ?  $pData['receiver'] : 0) );
		$this->EE->db->set('action_id',		(isset($pData['action_id']) ?  $pData['action_id'] : 0) );
		$this->EE->db->set('rule_id',		(isset($pData['rule_id']) ?  $pData['rule_id'] : 0) );
		$this->EE->db->set('date',			(isset($pData['date']) ?  $pData['date'] : $this->EE->localize->now) );
		$this->EE->db->set('credits',		(isset($pData['credits']) ?  $pData['credits'] : 0) );
		$this->EE->db->set('item_type',		(isset($pData['item_type']) ?  $pData['item_type'] : 0) );
		$this->EE->db->set('item_id',		(isset($pData['item_id']) ?  $pData['item_id'] : 0) );
		$this->EE->db->set('item_parent_id',(isset($pData['item_parent_id']) ?  $pData['item_parent_id'] : 0) );
		$this->EE->db->set('comments',		(isset($pData['comments']) ?  $pData['comments'] : '') );
		$this->EE->db->insert('exp_credits_log');

		return;
	}
    

}
/* END */
?>