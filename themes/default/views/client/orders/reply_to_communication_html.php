<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/reply_to_communication_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 
 	$t_transaction = $this->getVar('transaction');
 	$pn_communication_id = $this->getVar('communication_id');
 ?>
 	<div id="caClientCommunicationsReply">
 		<a href="#" class="button" style="float: right;" onclick="jQuery('#caClientCommunicationsMessageDisplay').load('<?php print caNavUrl($this->request, 'client/orders', 'Communications', 'ViewMessage', array('communication_id' => $pn_communication_id)); ?>');"><?php print _t('Cancel'); ?> &rsaquo;</a>
<?php
	print caFormTag($this->request, 'SendReply', 'caClientCommunicationsReplyForm');
	$t_comm = new ca_commerce_communications($pn_communication_id);
	$vn_to_user_id = $t_comm->get('from_user_id');
	if (!preg_match('!'._t("Re:").'!i', $vs_subject = $t_comm->get('subject'))) {
		$vs_subject = _t("Re:").' '.$vs_subject;
	}
	$t_comm->clear();
	$t_comm->set('subject', $vs_subject);
	
	print "<div class='replyMessageHeader'><span class='replyMessageHeaderHeading'>"._t('Date').':</span> '.caGetLocalizedDateRange($t=time(), $t)."</div>";
	print "<div class='replyMessageHeader'><span class='replyMessageHeaderHeading'>"._t('To').':</span> '.caClientServicesGetSenderName(array('from_user_id' => $vn_to_user_id))."</div>";
	
	foreach($t_comm->getFormFields() as $vs_f => $va_info) {
		switch($vs_f) {
			case 'subject':
			case 'message':
			case 'transaction_id':
				print $t_comm->htmlFormElement($vs_f)."<br/>\n";
				break;
		}
	}
	
	print caHTMLHiddenInput('transaction_id', array('value' => $t_transaction->getPrimaryKey()));
	print caFormSubmitButton($this->request, __CA_NAV_BUTTON_SAVE__, _t("Send"), 'caClientCommunicationsReplyForm');
?>
		</form>
	</div>