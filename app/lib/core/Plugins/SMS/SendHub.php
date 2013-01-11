<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SMS/WLPlugSMSSendHub.php : generates SMS messages via SendHub API
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage SMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugSMS.php");
include_once(__CA_LIB_DIR__."/core/Plugins/SMS/BaseSMSPlugin.php");
include_once(__CA_MODELS_DIR__."/ca_users.php");

class WLPlugSMSSendHub Extends BaseSMSPlugin Implements IWLPlugSMS {
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'SendHub';
		
		$this->description = _t('Sends SMS text messages using the SendHub API');
	}
	# ------------------------------------------------
	/**
	 * 
	 */
	static public function send($pn_user_id, $ps_message) {
		global $AUTH_CURRENT_USER_ID, $g_request;
		if (!function_exists("curl_init")) { return false; }
		
		if ($pn_user_id == $AUTH_CURRENT_USER_ID) {
			$t_user = $g_request->user;	// use request user object 
		} else {
			$t_user = new ca_users($pn_user_id);
		}
		if (!$t_user->getPrimaryKey()) { return null; }
		if (!$t_user->get('sms_number')) { return null; }
		
		if (
			!($vn_sendhub_contact_id = $t_user->getVar('sms_sendhub_contact_id'))
			||
			($t_user->getVar('sms_sendhub_phone_number') != $t_user->get('sms_number'))
		) { 
			if (!($vn_sendhub_contact_id = WLPlugSMSSendHub::addContact($t_user))) {
				// TODO: check and log errors here
				return null;
			}
		}
			
		$vs_user = $t_user->getAppConfig()->get('sms_user');
		$vs_api_key = $t_user->getAppConfig()->get('sms_api_key');
		$vs_url = "https://api.sendhub.com/v1/messages/?username={$vs_user}&api_key={$vs_api_key}";
		$o_ch = curl_init();
		$ps_message = stripslashes(rawurldecode($ps_message));
		$ps_message = trim(preg_replace("!\n+!","\\"."n", $ps_message));
		curl_setopt($o_ch, CURLOPT_URL, $vs_url);
		curl_setopt($o_ch, CURLOPT_HEADER, false);
		curl_setopt($o_ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($o_ch, CURLOPT_POSTFIELDS, '{"contacts":['.$vn_sendhub_contact_id.'],"text":"'.$ps_message.'"}');
		curl_setopt($o_ch, CURLOPT_RETURNTRANSFER, 1);
		$vs_return = curl_exec($o_ch);
		$va_return = json_decode($vs_return);
		
		// TODO: check and log errors here
		
		curl_close($o_ch); 
		
		return true;
	}
	# ------------------------------------------------
	/**
	 * 
	 */
	static public function addContact($pt_user){
		$vs_user = $pt_user->getAppConfig()->get('sms_user');
		$vs_api_key = $pt_user->getAppConfig()->get('sms_api_key');
		$vs_url = "https://api.sendhub.com/v1/contacts/?username={$vs_user}&api_key={$vs_api_key}";
		
		$o_ch = curl_init();
		
		$vs_name = $pt_user->get('fname').' '.$pt_user->get('lname');
		$vs_number = preg_replace('![^\d]+!', '', $pt_user->get('sms_number'));
		if (!$vs_number) { return null; }
	
		curl_setopt($o_ch, CURLOPT_URL, $vs_url);
		curl_setopt($o_ch, CURLOPT_HEADER, false);
		curl_setopt($o_ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($o_ch, CURLOPT_POSTFIELDS, '{"name":"'.strtoupper($vs_name).'","number":"'.$vs_number.'"}');
		curl_setopt($o_ch, CURLOPT_RETURNTRANSFER, 1);
		$vs_return = curl_exec($o_ch);
		$va_return = json_decode($vs_return);
		
		// TODO: check and log errors here
		
		$pt_user->setMode(ACCESS_WRITE);
		$pt_user->setVar('sms_sendhub_contact_id', $vn_sendhub_contact_id = (int)$va_return->{'id'});
		$pt_user->setVar('sms_sendhub_phone_number', $pt_user->get('sms_number'));
		$pt_user->update();
		if ($pt_user->numErrors()) {
			// TODO: check and log errors here
			return null;
		}
		curl_close($o_ch);
		
		return $vn_sendhub_contact_id; 
	}
	# ------------------------------------------------
}
?>