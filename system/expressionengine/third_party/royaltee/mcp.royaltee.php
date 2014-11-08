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
 File: mcp.royaltee.php
-----------------------------------------------------
 Purpose: Author's revenue/commission system
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'royaltee/config.php';

class Royaltee_mcp {

    var $version = ROYALTEE_ADDON_VERSION;
    
    var $settings = array();
    
    var $perpage = 25;
    
    var $multiselect_fetch_limit = 50;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
        
        $this->EE->load->library('royaltee_lib');
        
        if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('royaltee_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('royaltee_module_name'));
        }
    } 
    
    
    //global settings: e-commerce solution
    //groups
    //member-to-group assignment tool
    //per-product settings (rate and requirement to purchase)
    //stats
    
    function index()
    {
        $ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=settings');
			return;
        }
        else
        {
            $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=stats');
			return;
        }
	
    }   
    

    
	
	
	
	function payouts()
	{
        $ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=settings');
			return;
        }
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');

    	$vars = array();
    	$js = '';
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;

        $this->EE->db->select('royaltee_commissions.*, screen_name');
        $this->EE->db->from('royaltee_commissions');
        
		//$this->EE->db->start_cache();
        $this->EE->db->where('method', 'withdraw');
        //$this->EE->db->stop_cache();
        
        $this->EE->db->join('members', 'royaltee_commissions.member_id=members.member_id', 'left');
        $this->EE->db->order_by('commission_id', 'desc');

        $query = $this->EE->db->get();
        $vars['total_count'] = $query->num_rows();
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
        
        $vars['table_headings'] = array(
                        lang('date_requested'),
                        lang('member'),
                        lang('amount'),
                        lang('status'),
                        lang('')
        			);		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           	$vars['data'][$i]['date'] = ($row['record_date']!='')?$this->EE->localize->format_date($date_format, $row['record_date']):'';
           	$vars['data'][$i]['member'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a> (".lang('balance')." ".$this->_balance($row['member_id'])." - <a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=stats'.AMP.'member_id='.$row['member_id']."\">".lang('view_stats')."</a>)";
           	$vars['data'][$i]['amount'] = ($row['credits']!=0)?(-$row['credits']):(-$row['credits_pending']);
           	switch ($row['order_id'])
           	{
  				case '0':
				   	$row['status'] = 'requested';
				   	break;
	   			case '-1':
			   		$row['status'] = 'cancelled';
			   		break;
		   		default:
			   		$row['status'] = 'processed';
			   		break;
  			}
           	$vars['data'][$i]['status'] = '<span class="'.$row['status'].'">'.lang($row['status']).'</span>';    
           	if ($row['order_id']==0)
           	{
           		//pending
           		$vars['data'][$i]['link'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=process_payout_form'.AMP.'id='.$row['commission_id']."\" class=\"process_payout\">".lang('process_payout')."</a> | <a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=cancel_payout'.AMP.'id='.$row['commission_id']."\" class=\"cancel_payout\">".lang('cancel_payout')."</a>";
           	}
           	elseif ($row['order_id']>0)
           	{
           		$vars['data'][$i]['link'] =  "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=view_payout'.AMP.'id='.$row['order_id']."\">".lang('view_transaction')."</a>";
           	}
           	else
           	{
           		$vars['data'][$i]['link'] = '';
           	}
           	$i++;
 			
        }
        
        $js .= '
				var draft_target = "";

			$("<div id=\"cancel_payout_warning\">'.$this->EE->lang->line('confirm_cancel_payout').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('cancel_payout').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					"'.lang('no').'": function() {
					$(this).dialog("close");
					},
					"'.lang('yes').'": function() {
					location=draft_target;
				}
				}});

			$(".cancel_payout").click( function (){
				$("#cancel_payout_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';
		/*
		$js .= '
				var draft_target = "";

			$("<div id=\"process_payout_warning\">'.$this->EE->lang->line('confirm_process_payout').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('process_payout').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					"'.lang('no').'": function() {
					$(this).dialog("close");
					},
					"'.lang('yes').'": function() {
					location=draft_target;
				}
				}});

			$(".process_payout").click( function (){
				$("#process_payout_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';
        */
        
        $q = $this->EE->db->select('COUNT(commission_id) AS qty')
    		->from('royaltee_commissions')
    		->where('order_id', 0)
    		->get();
    	
    	$vars['masspay_button'] = false;
    	
    	if ($q->row('qty') > 2)
    	{
    		$masspay_text = lang('masspay_text');
			$vars['masspay_button'] = true;
			
			if ($q->row('qty') > 250)
    		{

    			$masspay_text .= BR.lang('masspay_quantity_high');
    		
    		}
		
			$js .= '
				var draft_target = "";

			$("<div id=\"masspay_warning\">'.$masspay_text.'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('pay_with_masspay').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					"'.lang('no').'": function() {
					$(this).dialog("close");
					},
					"'.lang('yes').'": function() {
					location=draft_target;
				}
				}});

			$(".masspay").click( function (){
				$("#masspay_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';

		
		}
        
        $this->EE->javascript->output($js);
        
        $this->EE->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 7: {sorter: false}},
			widgets: ["zebra"]
		}');

		if ($vars['total_count'] > $this->perpage)
		{
        	$this->EE->db->select('COUNT(commission_id) AS cnt');
        	$this->EE->db->from('royaltee_commissions');
        	$this->EE->db->where('method', 'withdraw');
        	$query = $this->EE->db->get();
        	$vars['total_count'] = $query->row('cnt');
 		}
 		
 		//$this->EE->db->flush_cache();

        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=payouts';

        $p_config = $this->_p_config($base_url, $this->perpage, $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
    	return $this->EE->load->view('payouts', $vars, TRUE);
	
    }
    
    
    
    function process_payout()
    {

		if ($this->EE->input->get_post('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}

		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  

       	$commission_q = $this->EE->db->select()
       			->from('royaltee_commissions')
       			->where('commission_id', $this->EE->input->get_post('id'))
       			->where('order_id', 0)
       			->where('method', 'withdraw')
       			->get();

		if ($commission_q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
		
		$credits = $this->_correct_withdraw_amount($commission_q->row('member_id'), $commission_q->row('credits_pending'));
		
		if ($credits <= 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('payout_failed_unsufficient_balance'));
		}
		else
		{
	       	$this->_record_payout($commission_q->row('commission_id'), $commission_q->row('member_id'), $credits, $this->EE->input->post('method'), $this->EE->input->post('transaction_id'), $this->EE->input->post('comment'));
	       	
	    	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('request_processed'));
 		}
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=payouts');
	
    }
    
    
    function _balance($member_id)
    {
    	$ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
        
        //correct the amount, if needed
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('royaltee_commissions')
					->where('member_id', $member_id)
					->get();
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $member_id)
					->get();
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		return $amount_avail;
    }
    
    
    function _correct_withdraw_amount($member_id, $requested_amount)
    {
    	$ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
        
        //correct the amount, if needed
		$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('royaltee_commissions')
					->where('member_id', $member_id)
					->get();
		$amount_avail = 0;
		if ($q->num_rows()>0)
		{
			$amount_avail = $q->row('credits_total');
		}

		if (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')
		{
			$q = $this->EE->db->select('SUM(credits) as credits_total')
					->from('credits')
					->where('member_id', $member_id)
					->get();
			if ($q->num_rows()>0)
			{
				if ($q->row('credits_total') < $amount_avail)
				{
					$amount_avail = $q->row('credits_total');
				}
			}
		}
		
		$credits = abs($requested_amount);
		
		if ($credits > $amount_avail)
		{
			$credits = $amount_avail;
		}
		
		return $credits;
    }
    
    
    function _record_payout($commission_id, $member_id, $credits, $method, $transaction_id, $comment='')
    {
    	$ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
		
		$insert = array(
			'method'			=> $method,
			'member_id'			=> $member_id,
			'amount'			=> $credits,
			'transaction_id'	=> $transaction_id,
			'comment'			=> $comment,
			'payout_date'		=> $this->EE->localize->now
		);
		$this->EE->db->insert('royaltee_payouts', $insert);
		$payout_id = $this->EE->db->insert_id();
		
       	$data = array(
		   	'order_id'	=> $payout_id,
		   	'credits'	=> -$credits
		   );
  		$this->EE->db->where('commission_id', $commission_id);
  		$this->EE->db->update('royaltee_commissions', $data);
  		
  		
  		if (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')
		{
			$credits_action_q = $this->EE->db->select('action_id, enabled')
									->from('exp_credits_actions')
									->where('action_name', 'royaltee_withdraw')
									->get();
			if ($credits_action_q->num_rows()>0 && $credits_action_q->row('enabled')==1)
	    	{
				$pData = array(
					'action_id'			=> $credits_action_q->row('action_id'),
					'site_id'			=> $this->EE->config->item('site_id'),
					'credits'			=> -$credits,
					'receiver'			=> $member_id,
					'item_id'			=> $payout_id,
					'item_parent_id' 	=> $commission_id
				);
				
				$this->EE->royaltee_lib->_save_credits($pData);
			}
		}
    }


    
    function process_masspay_action()
	{
		$q = $this->EE->db->select('royaltee_commissions.*, email')
    		->from('royaltee_commissions')
    		->join('members', 'royaltee_commissions.member_id=members.member_id', 'left')
    		->where('order_id', 0)
    		->where('method', 'withdraw')
    		->order_by('order_id', 'asc')
    		->limit(250)
    		->get();
		
		// Set request-specific fields.
		$emailSubject =urlencode($this->EE->config->item('site_name').' '.lang('royaltee_payout'));
		$receiverType = urlencode('EmailAddress');
		$currency = urlencode('USD');							// or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')
		
		// Add request-specific fields to the request string.
		$nvpStr="&EMAILSUBJECT=$emailSubject&RECEIVERTYPE=$receiverType&CURRENCYCODE=$currency";
		
		$recipients = array();
		//var_dump($q->result_array());
		if ($q->num_rows()>2)
		{
			foreach ($q->result_array() as $i=>$row)
			{
				if (!isset($recipients[$row['member_id']]))
				{
					$recipients[$row['member_id']]['commission_id'] = $row['commission_id'];
					$recipients[$row['member_id']]['member_id'] = $row['member_id'];
					$credits = $this->_correct_withdraw_amount($row['member_id'], $row['credits_pending']);
					$recipients[$row['member_id']]['credits'] = $credits;
					$receiverEmail = urlencode($row['email']);
					$amount = urlencode($credits);
					$uniqueID = urlencode($row['commission_id']);
					$note = '';//urlencode($receiverData['note']);
					$nvpStr .= "&L_EMAIL$i=$receiverEmail&L_Amt$i=$amount&L_UNIQUEID$i=$uniqueID&L_NOTE$i=$note";
				}
			}
			//echo $nvpStr;
			
			// Execute the API operation; see the PPHttpPost function above.
			$httpParsedResponseAr = $this->_PPHttpPost('MassPay', $nvpStr);
			//var_dump($httpParsedResponseAr);
			//exit();
			
			if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
			{
				foreach($recipients as $member_id => $recipient_data) 
				{
					$this->_record_payout($recipient_data['commission_id'], $member_id, $recipient_data['credits'], 'masspay', '');//$httpParsedResponseAr["CORRELATIONID"]);
				}
				//exit('MassPay Completed Successfully: '.print_r($httpParsedResponseAr, true));
				$this->EE->session->set_flashdata('message_success', str_replace("%x", count($recipients), lang('masspay_processed')));
			} else  {
				$error_message = ($httpParsedResponseAr['L_LONGMESSAGE0']) ? urldecode($httpParsedResponseAr['L_LONGMESSAGE0']) : lang('masspay_failed');
				$this->EE->session->set_flashdata('message_failure', $error_message);
			}
		}
		
		
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=payouts');
        
        //TODO: implement IPN listener to get actual transaction ID
		

	}
    
    
    
    
    function cancel_payout()
    {

		if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  

       	$q = $this->EE->db->select('commission_id')
       			->from('royaltee_commissions')
       			->where('commission_id', $this->EE->input->get('id'))
       			->where('order_id', 0)
       			->where('method', 'withdraw')
       			->get();
		
		if ($q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$data = array(
		   	'order_id'	=> -1
		   );
  		$this->EE->db->where('commission_id', $q->row('commission_id'));
  		$this->EE->db->update('royaltee_commissions', $data);
    	
    	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('request_cancelled'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=payouts');
	
    }
    
    
    
    function view_payout()
    {
    	if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
    	
    	$date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
       	
       	$q = $this->EE->db->select('royaltee_payouts.*, screen_name')
       			->from('royaltee_payouts')
       			->join('members', 'royaltee_payouts.member_id=members.member_id', 'left')
       			->where('payout_id', $this->EE->input->get('id'))
       			->get();
		
		if ($q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$row = $q->row_array();
    	
    	$vars['data'] = array(
			'date_processed'	=> 	$this->EE->localize->format_date($date_format, $row['payout_date']),
			'member'			=>	"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a> (<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=stats'.AMP.'stats_type=member'.AMP.'id='.$row['member_id']."\">".lang('view_stats')."</a>)",
			'amount'			=> $row['amount'],
			'method'			=> lang($row['method']),
			'transaction_id'	=> $row['transaction_id'],
			'comment'			=> $row['comment'],
			
		);
        
    	return $this->EE->load->view('view_payout', $vars, TRUE);
	
    }
    
    
    
    function process_payout_form()
    {
    	if ($this->EE->input->get('id')=='')
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
    	
    	$q = $this->EE->db->select('royaltee_commissions.*, screen_name')
       			->from('royaltee_commissions')
       			->join('members', 'royaltee_commissions.member_id=members.member_id', 'left')
       			->where('commission_id', $this->EE->input->get('id'))
       			->where('order_id', 0)
       			->where('method', 'withdraw')
       			->get();
		
		if ($q->num_rows()==0)
		{
			show_error(lang('unauthorized_access'));
		}
       	
       	$row = $q->row_array();
    	
    	$vars['data'] = array(
			'member'		=>	"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a>".form_hidden('id', $row['commission_id']),
			'amount'			=> -$row['credits_pending'],
			'method'			=> form_dropdown('method', array('paypal'=>lang('paypal'), 'bank'=>lang('bank'), 'other'=>lang('other')), 'other'),
			'transaction_id'	=> form_input('transaction_id', ''),
			'comment'			=> form_textarea('comment', '')
			
		);
        
    	return $this->EE->load->view('process_payout', $vars, TRUE);
	
    }
    
    

    
    function _string_to_timestamp($human_string, $localized = TRUE)
    {
        if ($this->EE->config->item('app_version')<260)
        {
            return $this->EE->localize->convert_human_date_to_gmt($human_string, $localized);
        }
        else
        {
            return $this->EE->localize->string_to_timestamp($human_string, $localized);
        }
    }
	 

    function stats()
    {
        $ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
        if (empty($ext_settings))
        {
        	$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=settings');
			return;
        }
		
		$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
       	$date_format = ($date_fmt == 'us')?'%m/%d/%y %h:%i %a':'%Y-%m-%d %H:%i';
       	$date_format_picker = ($date_fmt == 'us')?'mm/dd/y':'yy-mm-dd';

    	$vars = array();
        
        if ($this->EE->input->get_post('perpage')!==false)
        {
        	$this->perpage = $this->EE->input->get_post('perpage');	
        }
        $vars['selected']['perpage'] = $this->perpage;
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;
        
        $vars['selected']['member_id']=$this->EE->input->get_post('member_id');
        
        $vars['selected']['date_from']=($this->EE->input->get_post('date_from')!='')?$this->EE->input->get_post('date_from'):'';
        
        $vars['selected']['date_to']=($this->EE->input->get_post('date_to')!='')?$this->EE->input->get_post('date_to'):'';

        $this->EE->cp->add_js_script('ui', 'datepicker'); 
        $this->EE->javascript->output(' $("#date_from").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        $this->EE->javascript->output(' $("#date_to").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        
        $q = $this->EE->db->select('royaltee_commissions.member_id, screen_name')
        		->distinct()
        		->from('royaltee_commissions')
        		->join('members', 'royaltee_commissions.member_id=members.member_id', 'left')
        		->order_by('screen_name', 'asc')
        		->get();
   		$members_list = array('' => '');
   		foreach ($q->result_array() as $row)
   		{
   			$members_list[$row['member_id']] = $row['screen_name'];
   		}
   		$vars['member_select'] = form_dropdown('member_id', $members_list, $vars['selected']['member_id']);
        
        switch ($ext_settings['ecommerce_solution'])
        {
        	case 'store':
        		$this->EE->db->select('royaltee_commissions.*, screen_name, store_order_items.entry_id, store_order_items.title')
        			->from('royaltee_commissions')
					->join('members', 'royaltee_commissions.member_id=members.member_id', 'left')
                    ->join('store_order_items', 'royaltee_commissions.order_id=store_order_items.order_id', 'left');
        		break;

			case 'cartthrob':
        	default:
        		$this->EE->db->select('royaltee_commissions.*, screen_name, cartthrob_order_items.entry_id, cartthrob_order_items.title')
        			->from('royaltee_commissions')
					->join('members', 'royaltee_commissions.member_id=members.member_id', 'left')
                    ->join('cartthrob_order_items', 'royaltee_commissions.order_id=cartthrob_order_items.order_id', 'left');
        		break;
        }
        
        $this->EE->db->where('royaltee_commissions.order_id > ', 0);
		
		if ($vars['selected']['member_id']!='' || $vars['selected']['date_from']!='' || $vars['selected']['date_to']!='')
		{
			//$this->EE->db->start_cache();
			if ($vars['selected']['member_id']!='')
			{
				$this->EE->db->where('royaltee_commissions.member_id', $vars['selected']['member_id']);
			}
			if ($vars['selected']['date_from']!='')
			{
				$this->EE->db->where('record_date >= ', $this->_string_to_timestamp($vars['selected']['date_from']));
			}
			if ($vars['selected']['date_to']!='')
			{
				$this->EE->db->where('record_date <= ', $this->_string_to_timestamp($vars['selected']['date_to']));
			}
			//$this->EE->db->stop_cache();
		}
		
		if ($this->perpage!=0)
		{
        	$this->EE->db->limit($this->perpage, $vars['selected']['rownum']);
 		}
 		
 		$this->EE->db->order_by('record_date', 'desc');
 		
 		//echo $this->EE->db->_compile_select();
 		

        $query = $this->EE->db->get();
        //$this->EE->db->_reset_select();
        
        $vars['table_headings'] = array(
                        lang('date'),
                        lang('author'),
                        lang('order'),
                        lang('product'),
                        lang('customer'),
                        lang('commission'),
        			);		
		   
		
		   
		$i = 0;
        foreach ($query->result_array() as $row)
        {
           $vars['data'][$i]['date'] = $this->EE->localize->format_date($date_format, $row['record_date']);
           $vars['data'][$i]['author'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a>";    
           switch ($ext_settings['ecommerce_solution'])
	       {
	        	case 'store':
	        		$vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=store'.AMP.'method=orders'.AMP.'order_id='.$row['order_id']."\">".lang('order').NBS.$row['order_id']."</a>";   
                    $vars['data'][$i]['product'] = "<a href=\"".BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$row['entry_id']."\">".$row['title']."</a>"; 
	        		break;
        		case 'cartthrob':
        		default:
					$vars['data'][$i]['order'] = "<a href=\"".BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$row['order_id']."\">".lang('order').NBS.$row['order_id']."</a>";   
                    $vars['data'][$i]['product'] = "<a href=\"".BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$row['entry_id']."\">".$row['title']."</a>".BR;   
					break;
            } 
            $vars['data'][$i]['customer'] = ($row['member_id']!=0)?"<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$row['member_id']."\">".$row['screen_name']."</a>":lang('guest');  
           $vars['data'][$i]['commission'] = $row['credits'];    
           $i++;
 			
        }
        
        

		if (($vars['selected']['rownum']==0 && $this->perpage > $query->num_rows()) || $this->perpage==0)
		{
        	$vars['total_count'] = $query->num_rows();
 		}
 		else
 		{
 			
  			$this->EE->db->select("COUNT('*') AS count")
  				->from('royaltee_commissions');
			$this->EE->db->where('royaltee_commissions.order_id > ', 0);
  				
			if ($vars['selected']['member_id']!='' || $vars['selected']['date_from']!='' || $vars['selected']['date_to']!='')
			{
				if ($vars['selected']['member_id']!='')
				{
					$this->EE->db->where('royaltee_commissions.member_id', $vars['selected']['member_id']);
				}
				if ($vars['selected']['date_from']!='')
				{
					$this->EE->db->where('record_date >= ', $this->_string_to_timestamp($vars['selected']['date_from']));
				}
				if ($vars['selected']['date_to']!='')
				{
					$this->EE->db->where('record_date <= ', $this->_string_to_timestamp($vars['selected']['date_to']));
				}
			}
	        
	        $q = $this->EE->db->get();
	        
	        $vars['total_count'] = $q->row('count');
 		}
 		
 		//$this->EE->db->flush_cache();
 		
 		$this->EE->jquery->tablesorter('.mainTable', '{
			headers: {0: {sorter: false}, 7: {sorter: false}},
			widgets: ["zebra"]
		}');

        $this->EE->load->library('pagination');

        $base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=stats';
        $base_url .= AMP.'perpage='.$vars['selected']['perpage'];
        if ($vars['selected']['member_id']!='')
		{
        	$base_url .= AMP.'member_id='.$vars['selected']['member_id'];
 		}

        $p_config = $this->_p_config($base_url, $vars['selected']['perpage'], $vars['total_count']);

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
    	return $this->EE->load->view('stats', $vars, TRUE);
	
    }
    
    
    function notification_templates()
    {

        $this->EE->load->helper('form');
    	$this->EE->load->library('table');
 
        $query = $this->EE->db->where('template_name', 'royaltee_withdraw_request_admin_notification')->get('specialty_templates');
        foreach ($query->result_array() as $row)
        {
            $vars['data'][$row['template_name']] = array(	
                'data_title'	=> form_input("{$row['template_name']}"."[data_title]", $row['data_title'], 'style="width: 100%"'),
                'template_data'	=> form_textarea("{$row['template_name']}"."[template_data]", $row['template_data'])
        		);
    	}

    	return $this->EE->load->view('notification_templates', $vars, TRUE);
	
    }    
    
    function save_notification_templates()
    {
        
        $templates = array('royaltee_withdraw_request_admin_notification');

        foreach ($templates as $template)
        {
            $data_title = (isset($_POST[$template]['data_title']))?$this->EE->security->xss_clean($_POST[$template]['data_title']):$this->EE->lang->line(str_replace('reeservation', 'subject', $template));
            $template_data = (isset($_POST[$template]['template_data']))?$this->EE->security->xss_clean($_POST[$template]['template_data']):$this->EE->lang->line(str_replace('reeservation', 'message', $template));
            
            $this->EE->db->where('template_name', $template);
            $this->EE->db->update('specialty_templates', array('data_title' => $data_title, 'template_data' => $template_data));
        }       

        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=notification_templates');
    }
    
    
    
    
    
    
    
    
    
    function settings()
    {
		$ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
		
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');
    	
    	$ecommerce_solutions = array(
			'cartthrob'			=>	lang('cartthrob'),
			//'brilliantretail'	=>	lang('brilliantretail'),
			//'simplecommerce'	=>	lang('simplecommerce'),
			'store'				=>	lang('store')
		);
    	
 
        $vars['settings'] = array(	
            'ecommerce_solution'			=> form_dropdown('ecommerce_solution', $ecommerce_solutions, $ext_settings['ecommerce_solution']),
            'withdraw_minimum'				=> form_input('withdraw_minimum', $ext_settings['withdraw_minimum']),
            'integrate_devdemon_credits'	=> form_checkbox('devdemon_credits', 'y', (isset($ext_settings['devdemon_credits']) && $ext_settings['devdemon_credits']=='y')?true:false),
            'masspay_mode'					=> form_dropdown('masspay_mode', array('sandbox'=>lang('sandbox'), 'live'=>lang('live')), $ext_settings['masspay_mode']),
            'masspay_api_username'			=> form_input('masspay_api_username', $ext_settings['masspay_api_username']),
            'masspay_api_password'			=> form_input('masspay_api_password', $ext_settings['masspay_api_password']),
            'masspay_api_signature'			=> form_input('masspay_api_signature', $ext_settings['masspay_api_signature']),
            
    		);
		if ($this->EE->db->table_exists('credits_actions') == FALSE)
    	{
    		$vars['settings']['integrate_devdemon_credits'] = form_hidden('devdemon_credits', '').lang('not_available');
   		}
        
    	return $this->EE->load->view('settings', $vars, TRUE);
	
    }    
    
    function save_settings()
    {
		
		if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}

        unset($_POST['submit']);
        
        if ($this->EE->input->post('devdemon_credits')=='y')
        {
        	$enable = $this->EE->royaltee_lib->install_credits_action();
        	if ($enable==false)
        	{
        		$_POST['devdemon_credits'] = '';
        	}
		}
        
        $this->EE->db->where('class', 'Royaltee_ext');
    	$this->EE->db->update('extensions', array('settings' => serialize($_POST)));
    	
    	$this->EE->session->set_flashdata(
    		'message_success',
    	 	$this->EE->lang->line('preferences_updated')
    	);
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=royaltee'.AMP.'method=index');
    }
    
    function _p_config($base_url, $per_page, $total_rows)
    {
        $p_config = array();
        $p_config['base_url'] = $base_url;
        $p_config['total_rows'] = $total_rows;
		$p_config['per_page'] = $per_page;
		$p_config['page_query_string'] = TRUE;
		$p_config['query_string_segment'] = 'rownum';
		$p_config['full_tag_open'] = '<p id="paginationLinks">';
		$p_config['full_tag_close'] = '</p>';
		$p_config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$p_config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$p_config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$p_config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';
        return $p_config;
    }
    
    
    
    function _PPHttpPost($methodName_, $nvpStr_) {

		$ext_settings = $this->EE->royaltee_lib->_get_ext_settings();
	
		// Set up your API credentials, PayPal end point, and API version.
		$API_UserName = urlencode($ext_settings['masspay_api_username']);
		$API_Password = urlencode($ext_settings['masspay_api_password']);
		$API_Signature = urlencode($ext_settings['masspay_api_signature']);
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		if($ext_settings['masspay_mode']=="sandbox") {
			$API_Endpoint = "https://api-3t.".$ext_settings['masspay_mode'].".paypal.com/nvp";
		}

		$version = urlencode('51.0');
	
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
	
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
	
		// Set the API operation, version, and API signature in the request.
		$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
	
		// Set the request as a POST FIELD for curl.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	
		// Get response from the server.
		$httpResponse = curl_exec($ch);
	
		if(!$httpResponse) {
			exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
		}
	
		// Extract the response details.
		$httpResponseAr = explode("&", $httpResponse);
	
		$httpParsedResponseAr = array();
		foreach ($httpResponseAr as $i => $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1) {
				$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
			}
		}
	
		if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
			exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
		}
	
		return $httpParsedResponseAr;
	}
    
    
  
  

}
/* END */
?>