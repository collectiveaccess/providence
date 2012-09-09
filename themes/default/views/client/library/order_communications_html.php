<?php
/* ----------------------------------------------------------------------
 * app/views/client/order_communications_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source places management software
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
 * ----------------------------------------------------------------------
 */
 	
 	$t_order = $this->getVar('t_order');
 	$t_transaction = $this->getVar('t_transaction');
	$o_client_services_config = $this->getVar('client_services_config');

	$vn_order_id = (int)$t_order->getPrimaryKey();
	$vn_transaction_id = $this->getVar('transaction_id');
	$va_errors = $this->getVar('errors');
	
	$va_messages = $this->getVar('messages');
	
if(sizeof($va_messages)){
?>
	<a href="#" onclick="jQuery('#caClientOrderCommunicationForm').slideDown(); jQuery('#caClientOrdersComposeButton').hide(); return false;" id="caClientOrdersComposeButton"><?php print _t("Compose New Message"); ?></a>
<?php
}
?>
	<h1><?php print _t('Communications'); ?></h1>
<?php
	print "<div id='caClientOrderCommunicationForm' ".((sizeof($va_messages)) ? "style='display:none;'" : "").">";
	print caFormTag($this->request, 'SaveCommunications', 'caClientCommunicationsReplyForm');
	$t_comm = new ca_commerce_communications($va_messages[0]['communication_id']);
	$vn_to_user_id = $t_transaction->get('user_id');
	$vs_subject = $t_comm->get('subject');
	if ($vs_subject && !preg_match('!'._t("Re:").'!i', $vs_subject)) {
		$vs_subject = _t("Re:").' '.$vs_subject;
	}
	$t_comm->clear();
	$t_comm->set('subject', $vs_subject);
	
	print "<H2>"._t("New Message")."</H2>";
	print "<div class='caClientOrderCommunicationFormBg'><div class='formLabel'><b>"._t('To').':</b> '.caClientServicesGetSenderName(array('from_user_id' => $vn_to_user_id))."</div>";
	
	foreach($t_comm->getFormFields() as $vs_f => $va_info) {
		switch($vs_f) {
			case 'subject':
			case 'message':
			case 'transaction_id':
				print $t_comm->htmlFormElement($vs_f)."\n";
				break;
		}
	}
	
	print $t_order->htmlFormElement('order_id');
	print caHTMLHiddenInput('transaction_id', array('value' => $t_transaction->getPrimaryKey()));
	print "</div><!-- end caClientOrderCommunicationFormBg -->";
	print caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Send"), 'caClientCommunicationsReplyForm');
if(sizeof($va_messages)){
?>
		<a href="#" class="button" onclick="jQuery('#caClientOrderCommunicationForm').slideUp(); jQuery('#caClientOrdersComposeButton').show(); return false;" style="float:right;"><?php print _t("Cancel"); ?> &rsaquo;</a>
<?php
}
?>
		</form></div><!-- end caClientOrderCommunicationForm -->
	
<?php
	if(is_array($va_messages) && sizeof($va_messages)){
		print "<H2>".sizeof($va_messages)." ".((sizeof($va_messages) == 1) ? _t("Message") : _t("Messages"))."</H2>";
		// Print existing messages
		foreach(array_reverse($va_messages) as $vn_i => $va_message) {
			print "<div style='border-bottom:1px solid #333333; padding-bottom:10px; margin-bottom: 10px;'>".caClientServicesFormatMessage($this->request, $va_message)."</div>";
		}
	}
?>
	<div class="editorBottomPadding"><!-- empty --></div>
<?php
//	print $vs_control_box;
?>
<div class="editorBottomPadding"><!-- empty --></div>